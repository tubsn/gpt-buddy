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

	public function stream($model, $id) {
		$this->ChatGPT->model = AIMODELS[$model] ?? 'gpt-4.1';
		header('Content-type: text/event-stream');
		header('Cache-Control: no-cache');
		$response = $this->ChatGPT->stream($id);
	}

	public function generate_image() {
		$prompt = $_POST['question'];
		$options['resolution'] = $_POST['resolution'] ?? null;
		$options['quality'] = $_POST['quality'] ?? null;
		$options['background'] = $_POST['background'] ?? null;
		$options['image'] = $_POST['image'] ?? null;
		
		try {
			$output = $this->OpenAIImage->fetch($prompt, $options);
			$this->view->json($output);
		} catch (\Exception $e) {
			$this->view->json(['error' => 'GPT-Error: ' . $e->getMessage()]);
		}

	}

	public function ping() {echo 'pong';}


	public function prompt($id) {
		if (!Auth::logged_in() && !Auth::valid_ip()) {
			$jwt = new JWTAuth;
			$jwt->authenticate_via_header();
		}
		$prompt = $this->Prompts->get_for_api($id);
		if (empty($prompt)) {throw new \Exception("Prompt not Found", 404);}
		echo $this->view->json($prompt);
	}

	public function prompts() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {
			$jwt = new JWTAuth;
			$jwt->authenticate_via_header();
		}
		$prompts = $this->Prompts->all();
		if (empty($prompts)) {throw new \Exception("No Prompts Found", 404);}
		echo $this->view->json($prompts);
	}

	public function direct_access() {
		$jwt = new JWTAuth;
		$remoteAccessURL = null;
		if (defined('DIRECT_ACCESS_URL')) {$remoteAccessURL = DIRECT_ACCESS_URL;}
		
		try {$jwt->authenticate_via_header($remoteAccessURL);}
		catch (\Exception $e) {
			$errorMessage = $e->getMessage();
			$errorCode = $e->getCode();
			http_response_code($errorCode);
			$this->view->json($errorMessage);
			die;
		}

		$data = $_POST['data'] ?? null;

		$systemPrompt = $_POST['prompt'] ?? null;
		if (is_numeric($_POST['prompt'] ?? null)) {
			// When Prompt is an ID -> gather all Prompt Infos in a String
			$systemPrompt = $this->Prompts->get_flat_content($_POST['prompt']);
		}

		if (empty($data)) {echo $this->ChatGPT->direct($systemPrompt ?? null); die;}
		echo $this->ChatGPT->direct($data, $systemPrompt ?? null);
	}

	public function create_bearer_token() {

		if (!Auth::logged_in()) { Auth::loginpage(); }
		if (Auth::get('level') != 'Admin') {
			throw new \Exception("Sie haben keine Berechtigung diese Seite aufzurufen", 403);
		}

		$remoteAccessURL = null;
		if (defined('DIRECT_ACCESS_URL')) {$remoteAccessURL = DIRECT_ACCESS_URL;}

		$jwt = new JWTAuth;
		$token = $jwt->create_token(null, $remoteAccessURL, '+5years');
		echo ($token);
	}

}
