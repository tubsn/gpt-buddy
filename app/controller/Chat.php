<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;

use Gioni06\Gpt3Tokenizer\Gpt3TokenizerConfig;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;

class Chat extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->interface = 'default';
		$this->view->title = 'ChatGPT Assistent';
		$this->models('ChatGPTApi,Prompts,OpenAIImage');
	}

	public function test() {


		$text = Session::get('chathistory');

		$text = array_column($text, 'content');
		$text = implode(" ", $text);

		$config = new Gpt3TokenizerConfig();
		$tokenizer = new Gpt3Tokenizer($config);
		$numberOfTokens = $tokenizer->count($text);

		echo $numberOfTokens;

	}


	public function index($category = null) {

		if ($category == 'translate') {
			$this->ChatGPTApi->wipe_history();
			$this->view->title = 'Übersetzer';
			$this->view->interface = 'translate';
		}

		if ($category == 'shorten') {
			$this->ChatGPTApi->wipe_history();
			$this->view->title = 'Textlängen Anpassesn';
			$this->view->interface = 'shorten';
		}

		if ($category == 'spelling') {
			$this->ChatGPTApi->wipe_history();
			$this->view->title = 'Rechtschreibung Korrigieren';
			$this->view->interface = 'spelling';
		}

		$this->view->prompts = $this->Prompts->list(1);
		$this->view->render('chat');
	}

	public function ask() {

		$question = $_POST['question'] ?? null;
		$action = $_POST['action'] ?? null;

		$response = $this->ChatGPTApi->ask($question, $action);
		$this->view->json($response);

	}

	public function wipe() {
		$this->ChatGPTApi->wipe_history();
	}

	public function history() {

		$data['action'] = Session::get('chataction') ?? null;
		$data['history'] = Session::get('chathistory') ?? null;
		$this->view->json($data);
	}

	public function image() {

		$this->view->response = $this->OpenAIImage->fetch($_POST['question']);
		$this->view->question = $_POST['question'];		
		$this->view->render('image',);

	}



}
