<?php

namespace app\models;
use Orhanerday\OpenAi\OpenAi;
use flundr\utility\Session;
use app\models\Prompts;

use Gioni06\Gpt3Tokenizer\Gpt3TokenizerConfig;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;

class ChatGPTApi 
{

	private $api;
	private $prompts;

	public $action;
	public $response;
	public $tokens = 0;
	public $messages = [];

	public function __construct() {
		$this->prompts = new Prompts;
		$this->load_history();
	}

	public function ask($question, $action = null) {

		if (empty($this->messages)) {
			$this->loadPreset($action); // Use presets only if history is empty
		}

		$this->add($question);
		$this->fetch();

		return $this->response();

	}

	private function loadPreset($action = null) {

		if (!$action) {return;}
		$this->action = $action;

		$userConfiguredPrompt = $this->prompts->get_and_track(urlencode($action));
		if ($userConfiguredPrompt) {
			Session::set('chataction', $action);
			$this->add($userConfiguredPrompt['content'], 'system');
		}

		// Default Actions
		if ($action == 'general') {$this->add("Du bist ein KI-Assistent names AI-Buddy, Du arbeitest bei der Lausitzer Rundschau in Cottbus, einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern", 'system');}

		if ($action == 'spelling-only') {$this->add("Korrigiere ausschließlich die Rechtschreibung nach deutschem Duden. Gramatik beibehalten! Verändere keine Eigennamen!\n2. Gib mit eine Liste der Änderungen", 'system');}
		if ($action == 'spelling-grammar') {$this->add("Korrigiere Rechtschreibung, Gramatik und Lesbarkeit nach deutschem Duden. Verändere keine Eigennamen!\nGib mit eine Liste der Änderungen", 'system');}
		if ($action == 'spelling-comma') {$this->add("Du bist ein Rechtschreibexperte und mit der deutschen Rechtschreibung sehr gut vertraut. Als nächstes werde ich dir einen Text schicken, den du auf Kommasetzung und Rechtschreibung kontrollierst und mir das korrigierte Ergebnis als Antwort zurück gibst.", 'system');}		
		
		if ($action == 'translate-de') {$this->add('Translate into german', 'system');}
		if ($action == 'translate-en') {$this->add('Translate into english', 'system');}
		if ($action == 'translate-spain') {$this->add('Translate into spanish', 'system');}
		if ($action == 'translate-pl') {$this->add('Translate into polish', 'system');}
		if ($action == 'translate-sorb') {$this->add('Übersetze nach Sorbish', 'system');}
		if ($action == 'translate-fr') {$this->add('Translate into french', 'system');}
		if ($action == 'translate-cz') {$this->add('Translate into czech', 'system');}
		if ($action == 'translate-ukr') {$this->add('Translate into ukrainian', 'system');}
		if ($action == 'translate-klingon') {$this->add('Translate into klingon', 'system');}

		if ($action == 'shorten-s') {$this->add('Shorten the text to 60 Words, do not change the context!', 'system');}
		if ($action == 'shorten-m') {$this->add('Shorten the text to 120 Words, do not change the context!', 'system');}
		if ($action == 'shorten-l') {$this->add('Shorten the text to 300 Words, do not change the context!', 'system');}
		if ($action == 'shorten-xl') {$this->add('Shorten the text to 500 Words, do not change the context!', 'system');}

	}

	private function add($message, $role = 'user') {
		$allowedRoles = ['user', 'system', 'assistant'];
		if (!in_array($role, $allowedRoles)) {throw new \Exception("Role not allowed", 404);}
		array_push($this->messages, ['role' => $role, 'content' => $message]);
	}

	private function fetch() {

		$maxTokens = 4096 - $this->count_tokens() - 50;

		$open_ai = new OpenAi(CHATGPTKEY);
		$chat = $open_ai->chat([
			'model' => 'gpt-3.5-turbo',
			'messages' => $this->messages,
			'temperature' => 1.0,
			'max_tokens' => $maxTokens,
			'frequency_penalty' => 0,
			'presence_penalty' => 0,
		]);

		$out = json_decode($chat); // response is in Json

		if (isset($out->error)) {
			$this->api_error_as_json($out->error);
		}

		if (isset($out->usage->total_tokens)) {
			$this->tokens = $out->usage->total_tokens ?? $this->tokens;
		}
		
		$this->response = $out->choices[0]->message->content;

	}

	private function api_error_as_json($error) {
		$short = $error->code;
		$message = $error->message;

		header("Content-type: application/json; charset=utf-8");
		echo json_encode([
			'error' => true,
			'errormessage' => $short,
			'answer' => $message,
		]);

		die;
	}


	private function count_tokens_by_words() {

		$content = array_column($this->messages, 'content');
		$content = implode(" ", $content);
		$content = strtolower(trim(preg_replace('/[^A-Za-z0-9\- ]/', ' ', $content)));
		$wordList = explode(" ", $content);
		return count($wordList);		

	}

	private function count_tokens() {

		$content = array_column($this->messages, 'content');
		$content = implode(" ", $content);

		$config = new Gpt3TokenizerConfig();
		$tokenizer = new Gpt3Tokenizer($config);
		$numberOfTokens = $tokenizer->count($content);

		$this->tokens = $numberOfTokens;
		return $numberOfTokens;		

	}

	private function load_history() {
		$this->messages = Session::get('chathistory') ?? [];
	}

	public function wipe_history() {
		Session::unset('chathistory');
		Session::unset('chataction');
	}

	public function response() {
		$this->add($this->response,'assistant');
		Session::set('chathistory', $this->messages);

		return [
			'answer' => $this->response,
			'history' => $this->messages,
			'tokens' => $this->tokens,
		];
	}

}
