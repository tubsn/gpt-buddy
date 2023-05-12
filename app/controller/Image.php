<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;

class Image extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->interface = 'default';
		$this->view->title = 'ChatGPT Assistent';
		$this->models('ChatGPTApi,Prompts,OpenAIImage');
	}

	public function index($category = null) {

		if (isset($_POST['question'])) {
			$this->view->response = $this->OpenAIImage->fetch($_POST['question']);
		}

		$this->view->question = $this->OpenAIImage->history();
		$this->view->title = 'Image Assistent';
		$this->view->render('image');

	}

	public function ask() {

		$question = $_POST['question'] ?? null;
		$action = $_POST['action'] ?? null;

		$response = $this->ChatGPTApi->ask($question, $action);
		$this->view->json($response);

	}

}
