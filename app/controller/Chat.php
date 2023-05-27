<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\auth\JWTAuth;
use flundr\utility\Session;

class Chat extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->interface = 'default';
		$this->view->title = 'ChatGPT Assistent';
		$this->models('ChatGPTApi,Prompts,OpenAIImage');
	}


	public function index($category = null) {

		if ($category == 'translate') {
			$this->view->title = 'Übersetzer';
			$this->view->interface = 'translate';
		}

		if ($category == 'shorten') {
			$this->view->title = 'Textlängen Anpassesn';
			$this->view->interface = 'shorten';
		}

		if ($category == 'spelling') {
			$this->view->title = 'Rechtschreibung Korrigieren';
			$this->view->interface = 'spelling';
		}

		$jwt = new JWTAuth(); // Create a JWT Token for current User
		$this->view->JWTtoken = $token = $jwt->create_token(Auth::get('id'), 'lr-digital.de', '+1 hour');

		$this->view->prompts = $this->Prompts->list(1);
		$this->view->render('chat');
	}

	public function image() {

		$this->view->response = $this->OpenAIImage->fetch($_POST['question']);
		$this->view->question = $_POST['question'];		
		$this->view->render('image',);

	}


}
