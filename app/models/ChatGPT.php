<?php

namespace app\models;
use Orhanerday\OpenAi\OpenAi;
use app\models\Prompts;
use app\models\Conversations;

use flundr\utility\Log;
use League\CommonMark\CommonMarkConverter;
use Gioni06\Gpt3Tokenizer\Gpt3TokenizerConfig;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;

class ChatGPT 
{

	private $api;
	private $prompts;
	private $conversations;

	public $models = [
		'default' => 'gpt-3.5-turbo',
		'gpt4' => 'gpt-4o',
	];

	public $forceGPT4 = false;
	public $jsonMode = false;

	public $conversationID;
	public $promptID;
	public $directPromptID;
	public $maxtimeout = 10;
	public $error;
	
	public $conversation = []; // Message History
	public $payload = null;
	public $temperature = 0.7;
	public $stream = true;
	public $tokens = 0;
	public $lastResponse;

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


	private function fetch($question) {

		if ($this->directPromptID) {
			$prompt = $this->prompts->get_and_track($this->directPromptID);
			if ($prompt) {$question = $prompt['content'];}
		}

		if (empty($question)) {$this->error = 'Keine Frage übermittelt'; return;}

		if ($this->conversationID) {
			$conversationData = $this->conversations->get($this->conversationID) ?? [];
			$this->conversation = $conversationData['conversation'];
			
			if (isset($conversationData['temperature'])) {
				$this->temperature = $conversationData['temperature'];
			}

			if (empty($this->conversation)) {$this->conversationID = null;}
		}

		if (!$this->conversationID && $this->promptID) {
			$prompt = $this->prompts->get_and_track($this->promptID);

			if ($prompt) {$this->add($prompt['content'], 'system');}
			if (isset($prompt['format']) && $prompt['format']) {
				$this->add('Nutze Markdown für Formatierungen', 'system');
			}
			if (isset($prompt['temperature'])) {
				$this->temperature = $prompt['temperature'];
			}
		}

		if (!empty($this->payload)) {
			$question = [
				['type' => 'text', 'text' => $question],
				['type' => 'image_url', 'image_url' => ['url' => PAGEURL . $this->payload]],
			];
		};

		$this->add($question, 'user');
		$this->count_tokens();
		$this->save_conversation();

	}

	private function add($message, $role = 'user') {
		$allowedRoles = ['user', 'system', 'assistant'];
		if (!in_array($role, $allowedRoles)) {throw new \Exception("Role not allowed", 404);}
		array_push($this->conversation, ['role' => $role, 'content' => $message]);
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
	public function direct($question, $systemPrompt = null) {

		if ($systemPrompt) {$this->add($systemPrompt, 'system');}

		$this->add($question);
		$model = $this->models['default'];

		$this->count_tokens($this->conversation);

		if ($this->forceGPT4) {
			$model = $this->models['gpt4'];
		}

		$responseFormat = ['type' => 'text'];
		if ($this->jsonMode) {$responseFormat = ['type' => 'json_object'];}

		$open_ai = new OpenAi(CHATGPTKEY);
		if (defined('CHATGPTBASEURL')) {$open_ai->setBaseURL(CHATGPTBASEURL);}

		$chat = $open_ai->chat([
			'model' => $model,
			'messages' => $this->conversation,
			'temperature' => $this->float_temperature(), // has to be valid floatvalue
			'response_format' => $responseFormat,
			// 'max_tokens' => 4096, 
		]);

		$chat = json_decode($chat); // response is in Json
		if (isset($chat->error->message)) {throw new \Exception("Direct GPT-API Error: " . $chat->error->message, 400);}
		return $chat->choices[0]->message->content;

	}


	// Streamed GPT Response
	public function stream($id) {

		$this->load_conversation($id);
		$this->count_tokens();

		$model = $this->models['default'];

		if ($this->forceGPT4) {
			$model = $this->models['gpt4'];
		}

		$open_ai = new OpenAi(CHATGPTKEY);
		if (defined('CHATGPTBASEURL')) {$open_ai->setBaseURL(CHATGPTBASEURL);}

		$options = [
			'model' => $model,
			'messages' => $this->conversation,
			'temperature' => $this->float_temperature(), // has to be valid floatvalue
			// 'max_tokens' => 4096, 
			'frequency_penalty' => 0,
			'presence_penalty' => 0,
			'stream' => true,
		];

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

	private function float_temperature() {
		$temp = $this->temperature;
		$temp = str_replace(",",".",$temp);
		$temp = preg_replace('/\.(?=.*\.)/', '', $temp);
		$temp = floatval($temp);
		if ($temp > 1) {$temp = 1;}
		return $temp;
	}

	private function handle_GPT_Stream_Api_errors($raw) {
		//$this->error_to_stream($raw);

		$json = json_decode($raw);
		if (isset($json->error->message)) {
			$error = $json->error->type . ': ' . $json->error->message;
			$this->error_to_stream($error);
			return;
		}

		// If json is corrupted try with regex
		if (!preg_match('/"error":\s*{/', $raw)) {return;}

		$pattern = '/"message": "(.*?)",\s+"type": "(.*?)"/';
		preg_match($pattern, $raw, $matches);
		$message = $matches[1];
		$type = $matches[2];

		$error = $type . ' | ' . $message;
		$this->error_to_stream($error);
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

		// Stop when Stream sends DONE
		ignore_user_abort(false);

		// extract only the Content in the Stream 
		// which sadly isn't always a perfect json :/
		$content = $this->extract_content_as_json_string($raw);
		
		foreach ($content as $str) {

			$array = json_decode($str,1);
			$content = $array['content'] ?? '';

			$this->lastResponse .= $content;
			$content = json_encode($content);
		
			echo 'data: ' . $content . "\n\n";
			echo str_pad('',4096)."\n";

			ob_flush();
			flush();

		}

		if (str_contains($raw, 'data: [DONE]')) {
			echo "event: stop\n";
			echo "data: stopped\n\n";
			echo str_pad('',4096)."\n";
			ob_flush();
			flush();
			return;
		}

	}

	private function extract_content_as_json_string($string) {
		$pattern = '/\{"content":".*?"\}/'; // Extracts the Part which is Valid JSON
		if (preg_match_all($pattern, $string, $matches)) {return $matches[0];}
		return [];
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
