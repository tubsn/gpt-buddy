<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\JWTAuth;
use flundr\auth\Auth;

class API extends Controller {

	public function __construct() {
		$this->view('DefaultLayout');
		$this->models('ChatGPT,Conversations,Prompts,OpenAIImage');
		header('Access-Control-Allow-Origin: *');		
	}

	public function stream($id, $force4 = false) {
		if ($force4) {$this->ChatGPT->forceGPT4 = true;}
		header('Content-type: text/event-stream');
		header('Cache-Control: no-cache');
		$response = $this->ChatGPT->stream($id);
	}

	public function generate_image() {
		$prompt = $_POST['question'];
		$options['resolution'] = $_POST['resolution'] ?? null;
		$options['quality'] = $_POST['quality'] ?? null;
		$options['style'] = $_POST['style'] ?? null;
		
		try {
			$output = $this->OpenAIImage->fetch($prompt, $options);
			$this->view->json($output);
		} catch (\Exception $e) {
			$this->view->json(['error' => 'GPT-Error: ' . $e->getMessage()]);
		}

	}


	public function stream_force_gpt4($id) {
		$this->stream($id, true);
	}

	public function ping() {echo 'pong';}


	public function prompt($id) {
		$jwt = new JWTAuth;
		$jwt->authenticate_via_header();

		$prompt = $this->Prompts->get($id);
		if (empty($prompt)) {throw new \Exception("Prompt not Found", 404);}
		echo $this->view->json($prompt);
	}

	public function prompts() {
		$jwt = new JWTAuth;
		$jwt->authenticate_via_header();
		
		$prompts = $this->Prompts->all();
		if (empty($prompts)) {throw new \Exception("No Prompts Found", 404);}
		echo $this->view->json($prompts);
	}

	public function create_bearer_token() {

		if (!Auth::logged_in()) { Auth::loginpage(); }
		if (Auth::get('level') != 'Admin') {
			throw new \Exception("Sie haben keine Berechtigung diese Seite aufzurufen", 403);
		}

		$jwt = new JWTAuth;
		$token = $jwt->create_token(null, null, '+1year');
		echo ($token);
	}

}
