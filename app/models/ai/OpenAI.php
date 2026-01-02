<?php

namespace app\models\ai;

use \Closure;
use flundr\utility\Session;

class OpenAI
{
	public bool $jsonMode = false;
	public string $model = 'gpt-5.1';
	public ?string $reasoning = null;
	public array $messages = [];
	public ?array $jsonSchema = null;
	public $tools = null; // Register your own Tool Class

	public ?string $debugEventFile = null;
	//public ?string $debugEventFile = LOGS . 'response-events.json';

	private ?Closure $onDelta = null;
	private ?string $lastResponseId = null;
	private array $lastResponse = [];
	private array $toolCalls = [];
	private array $pendingToolOutputs = [];
	private array $toolRegistry = [];
	private array $functionItemIdToCallId = [];
	private ?ResponseEventCollector $eventCollector = null;

	public function __construct(private ConnectionHandler $connection) {
		$this->tools = $this->dummy_tools();
	}

	public function register_tool(string $remoteName, array $schema, ?callable $callable = null): void {
		$isBuiltin = isset($schema['type']) && $schema['type'] !== 'function' && !isset($schema['function']);
		$this->toolRegistry[$remoteName] = [
			'mode' => $isBuiltin ? 'builtin' : 'function',
			'schema' => $schema,
			'callable' => $callable,
		];
	}

	private function dummy_tools() {
		return new class {
			public function use(): void {}
		};
	}

	public function add_toolhandler($handler) {
		$handler->connect($this);
		$this->tools = $handler;
	}

	public function add_message($text, string $role = 'user', $index = null): void {

		$allowedRoles = ['system', 'user', 'assistant', 'developer'];
		if (!in_array($role, $allowedRoles, true)) {$role = 'user';}

		$message = ['role' => $role, 'content' => $text,];

		if ($index === null || $index === 'last') {
			$this->messages[] = $message;
			return;
		}

		if ($index === 'first') {
			array_unshift($this->messages, $message);
			return;
		}

		if (is_numeric($index)) {
			$position = max(0, (int) $index);
			$position = min($position, count($this->messages));
			array_splice($this->messages, $position, 0, [$message]);
			return;
		}

		$this->messages[] = $message;
	}


	public function last_conversation() {
		$conversation = $this->messages;
		$lastMessage = $this->last_response();
		if (!empty($lastMessage)) {
			array_push($conversation, $lastMessage);
		}
		return $conversation;
	}

	public function last_response() {
		$response = $this->lastResponse;
		if (empty($response)) {return [];}
		$out['role'] = $response['role'];
		$out['content'] = $response['content'][0]['text'];
		return $out;
	}

	public function last_response_id() {
		return $this->lastResponseId ?? null;
	}

	public function resolve(): string {
		$finalText = '';

		while (true) {

			$requestOptions = $this->build_options(false);
			$responseData = $this->connection->request($requestOptions, null);

			$this->lastResponseId =
				$responseData['id'] ??
				($responseData['response']['id'] ?? $this->lastResponseId);

			$textChunk = $this->extract_output_text($responseData);
			if ($textChunk !== '') {
				$finalText = $textChunk;
			}

			$this->toolCalls = $this->parse_function_tool_calls($responseData);
			$this->pendingToolOutputs = [];

			if (empty($this->toolCalls)) {break;}
			$this->execute_tools();
		}

		return $finalText;
	}

	public function log_events($eventData) {
		file_put_contents(
			$this->debugEventFile, json_encode($eventData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND
		);
	}

	public function stream(callable $onDelta): void {
		$this->onDelta = Closure::fromCallable($onDelta);

		try {
			
			while (true) {
				$responseCollector = new ResponseEventCollector(function (array $payload): void {
					$this->emit($payload);
				});

				$requestOptions = $this->build_options(true);

				// Calling the Connection for Streaming Events
				$this->connection->request($requestOptions, function (array $eventData) use ($responseCollector) {
					if ($this->debugEventFile) {$this->log_events($eventData);}
					$responseCollector->handle($eventData);
					return true;
				});

				$this->lastResponseId = $responseCollector->last_response_id();
				$this->lastResponse = $responseCollector->complete_response();
				$this->toolCalls = $responseCollector->tool_calls();

				if (!empty($this->toolCalls)) {
					$this->execute_tools();
					continue;
				}

				// this is the absolute streaming ending
				$this->emit(['type' => 'final']); 
				break;
			}

		} 

		catch (\RuntimeException $e) {
			$this->emit(['type' => 'error', 'text' => $e->getMessage()]);
		}

	}

	private function build_options(bool $useStream): array {
		$isFollowUp = $this->lastResponseId !== null && !empty($this->pendingToolOutputs);

		$options['model'] = $this->model;
		$options['stream'] = $useStream;
		$options['tool_choice'] = 'auto';
		$options['tools'] = $this->tools_schema();
		$options['parallel_tool_calls'] = true;
		// $options['max_tool_calls'] = 5;

		if ($this->reasoning) {
			$options['reasoning']['effort'] = $this->reasoning;
		}

		if ($isFollowUp) {
			$options['previous_response_id'] = $this->lastResponseId;
			$options['input'] = array_map(
				fn(array $toolOutput) => [
					'type' => 'function_call_output',
					'call_id' => (string) $toolOutput['tool_call_id'],
					'output' => (string) $toolOutput['output'],
				],
				array_values($this->pendingToolOutputs)
			);
			return $options;
		}

		$options['input'] = $this->messages;

		$outputOptions = $this->response_format_options();
		if (!empty($outputOptions)) {
			$options = array_merge($options, $outputOptions);
		}

		return $options;
	}

	private function response_format_options(): array {
		$options = [];
		if (!$this->jsonMode) {
			return $options;
		}

		if ($this->jsonSchema === null) {
			$options['text'] = ['format' => ['type' => 'json_object']];
			return $options;
		}

		$options['text'] = [
			'format' => [
				'name' => 'ForcedSchema',
				'type' => 'json_schema',
				'strict' => true,
				'schema' => $this->jsonSchema,
			],
		];

		return $options;
	}

	private function tools_schema(): array {
		$tools = [];
		foreach ($this->toolRegistry as $toolName => $entry) {
			$mode = $entry['mode'] ?? 'function';
			if ($mode === 'builtin') {
				$tools[] = $entry['schema'];
				continue;
			}

			$schema = $entry['schema'] ?? [];
			$tools[] = [
				'type' => 'function',
				'name' => $schema['name'] ?? $toolName,
				'description' => $schema['description'] ?? '',
				'parameters' => $schema['parameters'] ?? new \stdClass(),
			];
		}
		return $tools;
	}

	private function execute_tools(): void {
		$this->pendingToolOutputs = [];

		foreach ($this->toolCalls as $callId => $callData) {
			$toolName = (string) ($callData['name'] ?? '');
			$argumentsJson = (string) ($callData['arguments'] ?? '');
			$argumentsArray = json_decode($argumentsJson, true) ?: [];

			$registryEntry = $this->toolRegistry[$toolName] ?? null;
			if ($registryEntry && ($registryEntry['mode'] ?? 'function') === 'builtin') {
				continue;
			}

			if (!$registryEntry || !isset($registryEntry['callable'])) {
				$errorText = json_encode(
					[
						'error' => $registryEntry ? 'No callable for tool: ' . $toolName
							: 'Unknown tool: ' . $toolName,
					],
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				);
				$this->pendingToolOutputs[] = [
					'tool_call_id' => (string) $callId,
					'output' => $errorText,
				];
				continue;
			}

			$result = $this->dispatch_tool($toolName, $argumentsArray);
			$outputText = is_string($result) ? 
			$result : json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			$this->pendingToolOutputs[] = [
				'tool_call_id' => (string) $callId,
				'output' => $outputText,
			];
		}
	}

	private function dispatch_tool(string $remoteName, array $args): mixed {
		try {
			$callable = $this->toolRegistry[$remoteName]['callable'] ?? null;
			if (!$callable) {
				return ['error' => 'No callable for tool: ' . $remoteName];
			}
			return call_user_func($callable, $args);
		} catch (\Throwable $throwable) {
			return ['error' => $throwable->getMessage()];
		}
	}

	private function extract_output_text(array $response): string {

		if (isset($response['output_text']) && is_string($response['output_text'])) {
			return $response['output_text'];
		}
		if (isset($response['response']['output_text']) && is_string($response['response']['output_text'])) {
			return $response['response']['output_text'];
		}

		$buffer = '';
		if (isset($response['output']) && is_array($response['output'])) {
			foreach ($response['output'] as $outputItem) {
				$outputType = $outputItem['type'] ?? '';
				if ($outputType === 'output_text' && is_string($outputItem['text'] ?? null)) {
					$buffer .= $outputItem['text'];
				}
				if ($outputType === 'message' && isset($outputItem['content']) && is_array($outputItem['content'])) {
					foreach ($outputItem['content'] as $contentItem) {
						if (($contentItem['type'] ?? '') === 'output_text' && is_string($contentItem['text'] ?? null)) {
							$buffer .= $contentItem['text'];
						}
					}
				}
			}
		}
		return $buffer;
	}

	private function parse_function_tool_calls(array $response): array {
		$calls = [];

		$pushCall = function (array $src) use (&$calls): void {
			$callId = $src['call_id'] ?? ($src['id'] ?? null);
			$name = $src['name'] ?? ($src['function']['name'] ?? null);
			$arguments = $src['arguments'] ?? ($src['function']['arguments'] ?? '');

			if (!$callId || !$name) {return;}
			if (!is_string($arguments)) {
				$arguments = json_encode($arguments ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}

			$calls[$callId] = [
				'call_id' => (string) $callId,
				'name' => (string) $name,
				'arguments' => $arguments,
			];

		};

		if (isset($response['output']) && is_array($response['output'])) {
			foreach ($response['output'] as $outputItem) {

				$type = $outputItem['type'] ?? '';
				if ($type === 'function_call' || $type === 'tool_call') {
					$pushCall($outputItem);
				} elseif ($type === 'message' && isset($outputItem['content']) && is_array($outputItem['content'])) {
					foreach ($outputItem['content'] as $contentItem) {
						$contentType = $contentItem['type'] ?? '';
						if ($contentType === 'function_call' || $contentType === 'tool_call') {
							$pushCall($contentItem);
						}
					}
				}
			}
		}

		if (isset($response['tool_calls']) && is_array($response['tool_calls'])) {
			foreach ($response['tool_calls'] as $item) {
				$pushCall($item);
			}
		}
		if (isset($response['response']['tool_calls']) && is_array($response['response']['tool_calls'])) {
			foreach ($response['response']['tool_calls'] as $item) {
				$pushCall($item);
			}
		}
		if (isset($response['content']) && is_array($response['content'])) {
			foreach ($response['content'] as $contentItem) {
				$contentType = $contentItem['type'] ?? '';
				if ($contentType === 'function_call' || $contentType === 'tool_call') {
					$pushCall($contentItem);
				}
			}
		}

		return $calls;
	}

	private function emit(array $payload): void {
		if ($this->onDelta instanceof \Closure) {
			($this->onDelta)($payload);
		}
	}
}
