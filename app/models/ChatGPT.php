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

	public $conversationID;
	public $promptID;
	public $error;
	
	public $conversation = []; // Message History
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
			'tokens' => $this->tokens,
		];
	}

	private function config($options = null) {
		if (!$options) {return;}
		if ($options['conversationID'] && !empty($options['conversationID'])) {$this->conversationID = $options['conversationID'];}
		if ($options['promptID']) {$this->promptID = $options['promptID'];}
	}


	private function fetch($question) {

		if (empty($question)) {$this->error = 'Keine Frage übermittelt'; return;}
		if ($this->conversationID) {
			$this->conversation = $this->conversations->get($this->conversationID) ?? [];
			if (empty($this->conversation)) {$this->conversationID = null;}
		}

		if (!$this->conversationID && $this->promptID) {
			$prompt = $this->prompts->get_and_track(urlencode($this->promptID)); // URL Encode noch ins Model!
			if ($prompt) {$this->add($prompt['content'], 'system');}
			if (isset($prompt['markdown']) && $prompt['markdown']) {
				$this->add('Nutze ab jetzt Markdown für Formatierungen vorallem Überschriften und Fettungen!', 'system');
			}
		}

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
		if ($this->conversationID) {$this->conversations->update($this->conversation, $this->conversationID); return;}
		$this->conversationID = $this->conversations->save($this->conversation);
	}

	public function stream($id) {

		$this->conversationID = $id;

		$conversation = $this->conversations->get($id);
		$this->conversation = $conversation;

		$maxTokens = 4096 - $this->count_tokens($conversation) - 50;
		
		if ($maxTokens < 1) {
			$this->error_to_stream('Anfrage beinhaltet zu viele Tokens! Maximal 4096');
		}

		$open_ai = new OpenAi(CHATGPTKEY);
		$options = [
			'model' => 'gpt-3.5-turbo',
			'messages' => $conversation,
			'temperature' => 1.0,
			'max_tokens' => $maxTokens,
			'frequency_penalty' => 0,
			'presence_penalty' => 0,
			'stream' => true,
		];

		$open_ai->chat($options, function ($curl_info, $data) {
			//Log::write($data);
			if ($this->handle_GPT_Api_errors($data)) {return 0;}
			$this->handle_stream_set($data);
			return strlen($data);
		});

		// Update the Conversation with the last response
		$this->add($this->lastResponse, 'assistant');
		$this->conversations->update($this->conversation, $id);

	}

	private function handle_GPT_Api_errors($raw) {
		$json = json_decode($raw);
		if (!isset($json->error->message)) {return false;}

		$message = $json->error->type . ': ' . $json->error->message;
		$this->error_to_stream($message);
	}

	private function error_to_stream($message) {
		$message = json_encode($message);
		echo 'data: ' . $message . "\n\n";
		echo 'event: stop' . "\n";
		echo 'data: stopped' . "\n\n";
		echo str_pad('',4096)."\n";
		ob_flush();
		flush();

		// Last Entry has to be removed, cause the User Prompt is added to the Conversation anyways
		$this->conversations->remove_last_entry($this->conversationID);

		exit;
	}

	private function handle_stream_set($raw) {

		if (mb_substr($raw, 0, 4) == 'data') {$raw = substr($raw,6);} // Cut of "data:" overhead

		if (str_contains($raw, 'data:')) {
			$arr = explode('data:', $raw);
			
			foreach ($arr as $set) {
				if (str_contains($set, '{"role":"assistant"}')) {continue;}
				if (mb_substr($set, 0, 4) == 'data') {$set = substr($set,6);}
				$this->flush_content($set);
			}

			return;
		}

		$this->flush_content($raw);
		
	}

	private function flush_content($raw) {
		ignore_user_abort(false);
		$raw = trim($raw);
		if ($raw == '[DONE]') {
			echo "event: stop\n";
			echo "data: stopped\n\n";
			echo str_pad('',4096)."\n";
			ob_flush();
			flush();
			return;
		}

		$response = json_decode($raw, 1);
		if (isset($response['choices'][0]['delta']['content'])) {
			$content = $response['choices'][0]['delta']['content'];
		}

		if (!isset($content)) {return;}
		$this->lastResponse .= $content;

		$content = json_encode($content);

		echo 'data: ' . $content . "\n\n";
		echo str_pad('',4096)."\n";
		ob_flush();
		flush();

	}

	private function count_tokens($messages = null) {

		if (!$messages) {$messages = $this->conversation;}

		$content = array_column($messages, 'content');
		$content = implode("\n", $content);

		$config = new Gpt3TokenizerConfig();
		$tokenizer = new Gpt3Tokenizer($config);
		$numberOfTokens = $tokenizer->count($content);

		$this->tokens = $numberOfTokens;
		return $numberOfTokens;		

	}


}
