<?php

namespace app\models;
use flundr\utility\Log;
use flundr\utility\Session;
use \app\models\Prompts;
use \app\models\ai\OpenAI;
use \app\models\ai\ConnectionHandler;
use \app\models\AiToolingHandler;

class DirectResponse {

	public function __construct() {}


	public function resolve($prompt, $query = null) {

		if (is_numeric($prompt)) {
			return $this->with_prompt_ID($prompt, $query);
		}

		else {
			return $this->text_prompt($prompt, $query);
		}
	}

	public function text_prompt($prompt, $query = null) {

		$connection = new ConnectionHandler(CHATGPTKEY, 'https://api.openai.com/v1/responses');
		$ai = new OpenAI($connection);
		$ai->add_toolhandler(new AiToolingHandler());

		$ai->model = 'gpt-5.4';
		$ai->reasoning = 'none';

		if (empty($query)) {
			$ai->add_message($prompt);
		}

		else {
			$ai->add_message($prompt, 'system');
			$ai->add_message($query);
		}

		return $ai->resolve();
	}

	public function with_prompt_ID($promptID, $query = null) {

		$connection = new ConnectionHandler(CHATGPTKEY, 'https://api.openai.com/v1/responses');
		$ai = new OpenAI($connection);
		$ai->add_toolhandler(new AiToolingHandler());		

		$ai->model = 'gpt-5.4';
		$ai->reasoning = 'none';
		//$ai->tools->use('date');

		$prompts = new Prompts();
		$prompt = $prompts->get_for_api($promptID);
		$ai->add_message($prompt['content'],'system');

		$tools = json_decode($prompt['tools'] ?? '',1);
		foreach ($tools as $tool) {
			if (!empty($tool)) {$ai->tools->use($tool);}
		}

		if (!empty($prompt['knowledges'])) {
			foreach ($prompt['knowledges'] as $knowledge) {$ai->add_message($knowledge, 'system');}
		}
	
		if (isset($prompt['withdate']) && $prompt['withdate']) {
			$ai->add_message('Aktuelles Datum: ' . date('Y-m-d H:i'), 'system');
		}

		if (!empty($query)) {
			$ai->add_message($query);
		}

		if (!empty($prompt['afterthought'])) {$ai->add_message($prompt['afterthought'], 'system');}

		return $ai->resolve();
	}

}