<?php

namespace app\models\ai;
use flundr\utility\Log;

final class ResponseEventCollector
{
	private $emit;
	private ?string $lastResponseId = null;
	private array $toolCalls = [];
	private array $functionItemIdToCallId = [];
	private array $completeResponses = [];

	public function __construct(callable $emitter) {
		$this->emit = $emitter;
	}

	public function last_response_id(): ?string {
		return $this->lastResponseId;
	}

	public function tool_calls(): array	{
		return $this->toolCalls;
	}

	public function complete_response(): array	{
		//Log::write(json_encode($this->completeResponses));
		return $this->completeResponses[0] ?? [];
	}

	public function handle(array $event): void {
		$eventType = (string) ($event['type'] ?? '');

		// Use this for debugging
		//($this->emit)(['type' => 'debug', 'content' => $event]);
		//return;

		switch ($eventType) {
			case 'response.created': // required for progress events		
			case 'response.completed': // completed could mean a completed part eg. tool call
				if (isset($event['response']['id'])) {
					$this->lastResponseId = (string) $event['response']['id'];
				}

				if ($event['response']['status'] == 'in_progress') {
					//($this->emit)(['type' => 'progress', 'content' => $event['response']]);
				}

				if ($event['response']['status'] == 'completed') {

					if (!empty($event['response']['output'])) {
						// Remove long MCP Tool Lists						
						$filteredOutputEvents = array_values(array_filter($event['response']['output'], fn ($entry) => (
							$entry['type'] ?? null) !== 'mcp_list_tools')
						); 
						$event['response']['output'] = $filteredOutputEvents;

						$messageOutputEvents = array_values(array_filter(
							$event['response']['output'] ?? [],
							function (array $entry): bool {
								return ($entry['type'] ?? null) === 'message'
									&& ($entry['status'] ?? null) === 'completed';
							}
						));

						$this->completeResponses = $messageOutputEvents;

					}

					($this->emit)(['type' => 'completed', 'content' => $event['response']]);

				}
				break;

			case 'response.output_text.delta':
				$deltaText = (string) ($event['delta'] ?? '');
				if ($deltaText !== '') {
					($this->emit)(['type' => 'delta', 'content' => $deltaText]);
				}
				break;

			case 'response.output_item.added': {
				$item = $event['item'] ?? [];
				$type = $item['type'] ?? '';
				if ($type === 'function_call') {
					$itemId = (string) ($item['id'] ?? '');
					$callId = (string) ($item['call_id'] ?? '');
					$toolName = (string) ($item['name'] ?? '');
					if ($itemId !== '' && $callId !== '') {
						$this->functionItemIdToCallId[$itemId] = $callId;
						if (!isset($this->toolCalls[$callId])) {
							$this->toolCalls[$callId] = [
								'call_id' => $callId,
								'name' => $toolName,
								'arguments' => '',
							];
						} elseif ($toolName !== '') {
							$this->toolCalls[$callId]['name'] = $toolName;
						}

						($this->emit)(['type' => 'tool_call', 'tool_name' => $toolName, 'content' => 'start']);
					}
				}

				if ($type === 'reasoning') {
					($this->emit)(['type' => 'reasoning', 'content' => 'start']);
				}
				if ($type === 'mcp_call') {
					$toolName = (string) ($item['name'] ?? '');
					($this->emit)(['type' => 'tool_call', 'tool_name' => $toolName, 'content' => 'start']);
				}

				break;
			}

			case 'response.function_call_arguments.delta': {
				$itemId = (string) ($event['item_id'] ?? '');
				$deltaChunk = (string) ($event['delta'] ?? '');
				if ($itemId !== '' && $deltaChunk !== '') {
					$callId = $this->functionItemIdToCallId[$itemId] ?? '';
					if ($callId !== '') {
						if (!isset($this->toolCalls[$callId])) {
							$this->toolCalls[$callId] = [
								'call_id' => $callId,
								'name' => '',
								'arguments' => '',
							];
						}
						$this->toolCalls[$callId]['arguments'] .= $deltaChunk;
					}
				}
				break;
			}

			case 'response.function_call_arguments.done': {
				$itemId = (string) ($event['item_id'] ?? '');
				$finalArgs = (string) ($event['arguments'] ?? '');
				if ($itemId !== '') {
					$callId = $this->functionItemIdToCallId[$itemId] ?? '';
					if ($callId !== '') {
						if (!isset($this->toolCalls[$callId])) {
							$this->toolCalls[$callId] = [
								'call_id' => $callId,
								'name' => '',
								'arguments' => '',
							];
						}
						if ($finalArgs !== '') {
							$this->toolCalls[$callId]['arguments'] = $finalArgs;
							($this->emit)(['type' => 'tool_call', 'arguments' => $finalArgs]);							
						}
					}
				}
				break;
			}

			case 'response.output_item.done': {
				$item = $event['item'] ?? [];
				$type = $item['type'] ?? '';

				// This calls the requested Server Function
				if ($type === 'function_call') {
					$itemId = (string) ($item['id'] ?? '');
					$callId = (string) ($item['call_id'] ?? '');
					$toolName = (string) ($item['name'] ?? '');
					$argsText = isset($item['arguments'])
						? (is_string($item['arguments']) ? $item['arguments'] :
							json_encode($item['arguments'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
						: '';
					if ($itemId !== '' && $callId !== '') {
						$this->functionItemIdToCallId[$itemId] = $callId;
						$this->toolCalls[$callId] = [
							'call_id' => $callId,
							'name' => $toolName,
							'arguments' => $argsText !== '' ? $argsText : ($this->toolCalls[$callId]['arguments'] ?? ''),
						];
					}
				}

				// Output a progress event for tool calls
				if ($type === 'mcp_call' || $type === 'function_call') {
					($this->emit)(['type' => 'progress', 'content' => $event['item'] ?? $event]);
				}

				if ($type === 'reasoning') {
					($this->emit)(['type' => 'reasoning', 'content' => 'done']);
				}

				break;
			}


			case 'response.mcp_call_arguments.done':
				($this->emit)(['type' => 'progress', 'content' => $event['item'] ?? $event]);
				break;

			case 'response.web_search_call.searching':
				($this->emit)(['type' => 'progress', 'content' => $event['item'] ?? $event]);
				($this->emit)(['type' => 'tool_call', 'tool_name' => 'Websearch', 'content' => 'start']);
				break;

			case 'response.error':
				$errorMessage = $event['error']['message'] ?? 'unknown';
				($this->emit)(['type' => 'error', 'message' => $errorMessage]);
				break;

			default:
				break;
		}
	}
}