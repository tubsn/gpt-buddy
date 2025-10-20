<?php

namespace app\models;
use Orhanerday\OpenAi\OpenAi;
use app\models\Prompts;
use app\models\Conversations;
use app\models\OpenAIVision;

use flundr\utility\Log;
use League\CommonMark\CommonMarkConverter;
use Gioni06\Gpt3Tokenizer\Gpt3TokenizerConfig;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;

class ChatGPT 
{

	private $api;
	private $prompts;
	private $conversations;
	private $models = AIMODELS;

	public $model = AIMODELS[0] ?? 'gpt-4.1';
	private $modelMeta = [];
	private $isReasoningModel = false;

	public $forceGPT4 = false;
	public $jsonMode = false;
	public $azureMode = false;	
	public $reasoning = 'low';
	public $verbosity = 'medium';

	public $conversationID;
	public $promptID;
	public $directPromptID;
	public $maxtimeout = 10;
	public $error;
	
	public $conversation = []; // Message History
	public $payload = null;
	public $afterthought = null;
	public $temperature = 0.7;
	public $stream = true;
	public $tokens = 0;
	public $lastResponse;

	private $sseBuffer = ''; // Used for streaming

	public function __construct() {
		$this->prompts = new Prompts;
		$this->conversations = new Conversations;

	}

	public function ask($question, $options = null) {
		$this->config($options);
		$this->fetch($question);
		return $this->response();
	}

	private function response() {
		if ($this->error) {return ['error' => $this->error];}

		return [
			'conversationID' => $this->conversationID,
			'temperature' => $this->temperature,
			'tokens' => $this->tokens,
		];
	}

	private function config($options = null) {
		if (!$options) {return;}
		if ($options['directPromptID']) {$this->directPromptID = $options['directPromptID']; return;}
		if ($options['conversationID'] && !empty($options['conversationID'])) {$this->conversationID = $options['conversationID'];}
		if ($options['promptID']) {$this->promptID = $options['promptID'];}
		if ($options['payload']) {$this->payload = $options['payload'];}
	}

	private function resolve_model() {
		
		if (!is_array($this->model)) {return;}
		$this->modelMeta = $this->model;

		$this->model = $this->modelMeta['apiname'] ?? 'gpt-4.1';

		if (str_contains($this->model, 'gpt-5') || str_contains($this->model, 'o3-') || str_contains($this->model, 'o1-') || str_contains($this->model, 'o4-') || $this->model == 'o3') {
			$this->isReasoningModel = true;
		}

		$this->reasoning = $this->modelMeta['reasoning'] ?? 'low';
		$this->verbosity = $this->modelMeta['verbosity'] ?? 'medium';
		if (strtolower($this->modelMeta['provider'] ?? '') == 'azure') {$this->azureMode = true;}

	}

	private function fetch($question) {

		if ($this->directPromptID) {
			$prompt = $this->prompts->get_and_track($this->directPromptID);
			if ($prompt) {$question = $prompt['content'];}
			if ($prompt['knowledges'] ?? null) {
				foreach ($prompt['knowledges'] as $knowledge) {
					$this->add($knowledge, 'system');
				}
			}			
		}

		if (empty($question)) {$this->error = 'Keine Frage übermittelt'; return;}

		if ($this->conversationID) {
			$conversationData = $this->conversations->get($this->conversationID) ?? [];
			$this->conversation = $conversationData['conversation'] ?? [];
			
			if (isset($conversationData['temperature'])) {
				$this->temperature = $conversationData['temperature'];
			}

			if (empty($this->conversation)) {$this->conversationID = null;}
		}

		if (!$this->conversationID && $this->promptID) {
			// Note: it´s possible that the Question is altered here
			$question = $this->prompt_processing($question);
		}		

		// Replace {{{ Tripple Mustache }}} logic in Input
		$question = $this->prompts->replace_random_tokens($question);
		$question = $this->prompts->replace_ignore_tokens($question);
		$question = $this->prompts->replace_tokens($question);

		if (!empty($this->payload)) {
			$question = [
				['type' => 'text', 'text' => $question],
				['type' => 'image_url', 'image_url' => ['url' => $this->prepare_image_for_vision($this->payload)]],
			];
		};

		$this->add($question, 'user');

		if ($this->afterthought) {
			$this->add($this->afterthought, 'system');
		}

		$this->count_tokens();
		$this->save_conversation();

	}


	private function prompt_processing($question) {

		if ($this->promptID == 'unbiased') {return $question;}
		$prompt = $this->prompts->get_and_track($this->promptID);

		if (empty($prompt['content'])) {echo 'Achtung: Prompt hat keinen Inhalt'; die;}

		if ($prompt) {$this->add($prompt['content'], 'system');}

		if (isset($prompt['format']) && $prompt['format']) {
			$this->add('Nutze Markdown für Formatierungen ohne den ```markdown hinweis', 'system');
		}

		if (isset($prompt['withdate']) && $prompt['withdate']) {
			$now = date('Y-m-d H:i');
			$this->add('Heute ist der ' . $now, 'system');
		}

		if (isset($prompt['afterthought']) && $prompt['afterthought']) {
			$this->afterthought = $prompt['afterthought'];
		}

		if (isset($prompt['temperature'])) {
			$this->temperature = $prompt['temperature'];
		}

		if (isset($prompt['postprocess']) && !empty($prompt['postprocess'])) {

			$systemMessage = null;				
			if ($prompt['knowledges']) {$systemMessage = implode("\n", $prompt['knowledges']);}

			$firstResponse = $this->direct($question, $systemMessage);
			$this->add($firstResponse, 'assistant');

			$postProcessPrompt = $this->prompts->get_and_track($prompt['postprocess']);
			if ($postProcessPrompt) {

				$forcedModel = $postProcessPrompt['model'] ?? null;
				if ($this->models[$forcedModel]) {
					$this->model = $this->models[$forcedModel]['apiname'] ?? $this->model;
				}
				$question = $postProcessPrompt['content'];
			}
			// Knowledgebases should not be processed twice with postprocess Prompts
			unset($prompt['knowledges']);

		}

		if ($prompt['knowledges'] ?? null) {
			foreach ($prompt['knowledges'] as $knowledge) {
				$this->add($knowledge, 'system');
			}
		}

		return $question;

	}


	public function prepare_image_for_vision($imagePath) {
		$visionData = PAGEURL . $imagePath;
		if (defined('USEBASE64VISION') && USEBASE64VISION) {
			$vision = new OpenAiVision();
			$visionData = $vision->file_to_base64(PUBLICFOLDER . $this->payload);
		}
		return $visionData;
	}

	public function add($message, $role = 'user', $prepend = false) {
		$allowedRoles = ['user', 'system', 'assistant', 'developer'];
		if (!in_array($role, $allowedRoles)) {throw new \Exception("Role not allowed", 404);}
		if ($prepend) {
			array_unshift($this->conversation, ['role' => $role, 'content' => $message]);
			return;
		}
		array_push($this->conversation, ['role' => $role, 'content' => $message]);
	}

	private function prepend($message, $role = 'user') {
		$this->add($message, $role, true);
	}

	private function save_conversation() {
		$data['edited'] = time();
		$data['prompt'] = $this->promptID;
		$data['temperature'] = $this->temperature;
		$data['conversation'] = $this->conversation;

		if ($this->conversationID) {$this->conversations->update($data, $this->conversationID);}
		else {$this->conversationID = $this->conversations->save($data);}
	}

	private function load_conversation($id) {
		$conversationData = $this->conversations->get($id);

		if (empty($conversationData)) {
			$this->error_to_stream('Conversation File not Found - Check the Cache Folder for read and write access!'); return;
		}

		if (time() - $conversationData['edited'] > $this->maxtimeout) {
			$this->error_to_stream('Conversation File timeout'); return;
		}

		if (empty($conversationData['conversation'])) {
			$this->error_to_stream('Conversation File corrupted'); return;
		}
		
		$this->conversationID = $id;
		$this->conversation = $conversationData['conversation'];
		$this->promptID = $conversationData['prompt'] ?? null;
		$this->temperature = $conversationData['temperature'] ?? $this->temperature;
	}

	public function list_engines() {
		$open_ai = new OpenAi(CHATGPTKEY);
		return $open_ai->listModels();
	}

	public function detect_moderation_flags($text) {
		$open_ai = new OpenAi(CHATGPTKEY);
		$flags = $open_ai->moderation(['input' => $text]);
		return $flags;
	}

	// Direct GPT Question with Static Response as Json
	public function direct($question = null, $systemPrompt = null) {

		$this->resolve_model();

		if ($systemPrompt) {$this->prepend($systemPrompt, 'system');}
		if ($question) {$this->add($question);}

		$this->count_tokens($this->conversation);

		$responseFormat = ['type' => 'text'];
		if ($this->jsonMode) {$responseFormat = ['type' => 'json_object'];}

		$open_ai = new OpenAi(CHATGPTKEY);
		if ($this->azureMode == true) {
			$open_ai = new AzureOpenAIClient($this->modelMeta);
			$this->model = 'AZURE';
		}

		$options = [
			'model' => $this->model,
			'messages' => $this->conversation,
			'response_format' => $responseFormat,
		];

		if ($this->isReasoningModel) {
			$options['reasoning_effort'] = $this->reasoning;
			$options['verbosity'] = $this->verbosity;
		} else {
			$options['temperature'] = $this->float_temperature();
		}

		$chat = $open_ai->chat($options);

		$chat = json_decode($chat); // response is in Json
		if (isset($chat->error->message)) {throw new \Exception("Direct GPT-API Error: " . $chat->error->message, 400);}
		return $chat->choices[0]->message->content;

	}


	// Streamed GPT Response
	public function stream($id) {

		$this->resolve_model();
		$this->load_conversation($id);
		$this->count_tokens();

		$open_ai = new OpenAi(CHATGPTKEY);

		$options = [
			'model' => $this->model,
			'messages' => $this->conversation,
			'stream' => true,
		];

		if ($this->isReasoningModel) {
			$options['reasoning_effort'] = $this->reasoning;
			$options['verbosity'] = $this->verbosity;
		} else {
			$options['temperature'] = $this->float_temperature();
		}

		// Streaming not Supported for some Models
		if (str_contains($this->model, '-search')) {
			$options['stream'] = false;
			unset($options['temperature']);
			$chat = $open_ai->chat($options);			
			$this->stream_to_direct($chat);
			exit;	
		}

		if ($this->azureMode == true) {
			$open_ai = new AzureOpenAIClient($this->modelMeta);
			$this->model = 'AZURE';
		}

		$open_ai->chat($options, function ($curl_info, $data) {
			//Log::write($data);
			if ($this->handle_GPT_Stream_Api_errors($data)) {return 0;}
			$this->handle_stream_set($data);
			return strlen($data);
		});

		// Update the Conversation with the last response
		$this->add($this->lastResponse, 'assistant');
		$this->save_conversation();

	}

	private function stream_to_direct($chat) {

		$chat = json_decode($chat);

		if (isset($chat->error->message)) {
			$this->error_to_stream($chat->error->message);
		}

		$message = $chat->choices[0]->message->content;
		$this->add($message, 'assistant');
		$message = json_encode($message);

		echo 'data: ' . $message . "\n\n";
		echo str_pad('',4096)."\n";
		echo "event: stop\n";
		echo "data: stopped\n\n";
		echo str_pad('',4096)."\n";

		$this->save_conversation();
	}


	private function float_temperature() {
		$temp = $this->temperature;
		$temp = str_replace(",",".",$temp);
		$temp = preg_replace('/\.(?=.*\.)/', '', $temp);
		$temp = floatval($temp);
		if ($temp > 1) {$temp = 1;}
		return $temp;
	}

	private function handle_GPT_Stream_Api_errors($raw) {
		$json = json_decode($raw);
		if (isset($json->error->message)) {
			$error = $json->error->type . ': ' . $json->error->message;
			$this->error_to_stream($error);
			return true;
		}

		// Falls JSON kaputt ist, per Regex prüfen
		if (!preg_match('/"error":\s*{/', $raw)) {
			return false;
		}

		$pattern = '/"message": "(.*?)",\s+"type": "(.*?)"/';
		preg_match($pattern, $raw, $matches);

		if (!isset($matches[1])) {
			$pattern = '/"message": "([^"]*)/';
			preg_match($pattern, $raw, $matches);
		}

		$message = $matches[1] ?? 'Unknown error';
		$type = $matches[2] ?? null;

		$error = ($type ? $type . ' | ' : '') . $message;
		$this->error_to_stream($error);
		return true;
	}

	private function error_to_stream($message) {
		$message = json_encode($message);
		echo 'event: error' . "\n";
		echo 'data: ' . $message . "\n\n";
		echo str_pad('',4096)."\n";
		ob_flush();
		flush();

		// Last Entry has to be removed, cause the User Prompt is added to the Conversation anyways
		$this->conversations->remove_last_entry($this->conversationID);
		exit;
	}


	private function handle_stream_set($raw) {
		$this->sseBuffer .= $raw;

		while (($pos = strpos($this->sseBuffer, "\n\n")) !== false) {
			$event = substr($this->sseBuffer, 0, $pos);
			$this->sseBuffer = substr($this->sseBuffer, $pos + 2);

			$lines = preg_split("/\r?\n/", $event);
			$dataLines = [];
			foreach ($lines as $line) {
				if (stripos($line, 'data:') === 0) {
					$dataLines[] = ltrim(substr($line, 5));
				}
			}
			if (!$dataLines) { continue; }

			$payload = implode("\n", $dataLines);

			if ($payload === '[DONE]') {
				echo "event: stop\n";
				echo "data: stopped\n\n";
				echo str_pad('',4096)."\n";
				ob_flush(); flush();
				return;
			}

			$json = json_decode($payload, true);
			if ($json === null) {
				$this->sseBuffer = $payload . "\n\n" . $this->sseBuffer;
				continue;
			}

			$delta = $json['choices'][0]['delta']['content'] ?? '';
			
			if ($delta === '' && isset($json['choices'][0]['text'])) {
				$delta = $json['choices'][0]['text'];
			}

			if ($delta === '' && isset($json['delta'])) {
				$delta = $json['delta'];
			}

			if ($delta !== '') {
				$this->lastResponse .= $delta;
				echo 'data: ' . json_encode($delta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
				echo str_pad('',4096)."\n";
				ob_flush(); flush();
			}

			if (($json['choices'][0]['finish_reason'] ?? null) !== null) {
				echo "event: stop\n";
				echo "data: stopped\n\n";
				echo str_pad('',4096)."\n";
				ob_flush(); flush();
				return;
			}
		}
	}

	private function count_tokens($messages = null) {

		if (!$messages) {$messages = $this->conversation;}

		$content = array_column($messages, 'content');

		// Fix Token Count for Vision Model
		$content = array_map(function($set) {
			if (is_array($set)) {return $set[0]['text'];}
			else return $set;
		}, $content);

		$content = implode("\n", $content);

		$config = new Gpt3TokenizerConfig();
		$tokenizer = new Gpt3Tokenizer($config);
		$numberOfTokens = $tokenizer->count($content);

		$this->tokens = $numberOfTokens;
		return $numberOfTokens;		
	}


}
