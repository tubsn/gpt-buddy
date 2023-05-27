<?php

namespace app\controller;
use flundr\mvc\Controller;
//use flundr\auth\Auth;
use flundr\auth\JWTAuth;

class API extends Controller {

	public function __construct() {
		$this->view('DefaultLayout');
		$this->models('ChatGPTApi,Prompts,OpenAIImage');
	}

	public function ask() {

		header('Access-Control-Allow-Origin: *');
		$jwt = new JWTAuth(); // Create a JWT Token for current User
		$user = $jwt->authenticate($_POST['token'],'lr-digital.de');

		$question = $_POST['question'] ?? null;
		$action = $_POST['action'] ?? null;

		$markdown = $_POST['markdown'] ?? false;
		$this->ChatGPTApi->set_markdown($markdown);

		$history = $_POST['history'] ?? null;
		$this->ChatGPTApi->set_history($history);

		$response = $this->ChatGPTApi->ask($question, $action);
		$this->view->json($response);

	}

	public function ping() {
		header('Access-Control-Allow-Origin: *');
		echo 'pong';
	}

}
