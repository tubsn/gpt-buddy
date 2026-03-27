<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\JWTAuth;
use flundr\auth\Auth;

class API extends Controller {

	public function __construct() {
		$this->view('DefaultLayout');
		$this->models('ChatGPT,Conversations,Prompts,OpenAIImage,DirectResponse');
		header('Access-Control-Allow-Origin: *');		
	}

	public function restrict_access_with_jwt() {
		$jwt = new JWTAuth;
		$remoteAccessURL = null;
		if (defined('DIRECT_ACCESS_URL')) {$remoteAccessURL = DIRECT_ACCESS_URL;}
		
		try {$jwt->authenticate_via_header($remoteAccessURL);}
		catch (\Exception $e) {
			$errorMessage = $e->getMessage();
			$errorCode = $e->getCode();
			http_response_code($errorCode);
			$this->view->json($errorMessage);
			exit;
		}
	}

	public function general_response() {
		$this->restrict_access_with_jwt();
		$request = $this->validate_request(); 

		$prompt = $request['prompt'] ?? null;
		$text = $this->DirectResponse->resolve($prompt);
		$responseMeta = $this->DirectResponse->responseData;
				
		$output = [
			'status' => 200,
			'response' => $text,
			'details' => $responseMeta,
		];

		$this->view->json($output);
	}

	public function validate_request() {
		$request = $this->get_header_input();

		if (empty($request)) {
			$this->raise_error_as_json('Request did not contain any data', 401);
		}

		$required = ['prompt'];
		$missingFields = array_diff($required, array_keys($request));

		if (!empty($missingFields)) {
			$missingFields = implode(', ', $missingFields);
			$this->raise_error_as_json('Request malformed - required fields missing: ' . $missingFields, 401);
		}

		return $request;
	}

	public function raise_error_as_json($message, $code) {
		$output = ['status' => $code, 'response' => $message,];
		http_response_code($code);
		$this->view->json($output);
		exit;
	}

	public function get_header_input() {
		$rawBody = file_get_contents('php://input');
		$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
		$trimmedBody = trim($rawBody);

		$isJsonHeader = stripos($contentType, 'application/json') !== false;
		$looksLikeJson = str_starts_with($trimmedBody, '{') || str_starts_with($trimmedBody, '[');

		if ($isJsonHeader || $looksLikeJson) {
			return json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
		}

		if (!empty($_POST)) {return $_POST;}
		return [];
	}

	public function hub_response() {
		$this->restrict_access_with_jwt();
		$prompt = $_POST['prompt'] ?? null;
		$data = $_POST['data'] ?? null;
		$response = $this->DirectResponse->resolve($prompt, $data);
		echo $response;
	}


	public function generate_image() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
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

	public function create_bearer_token($urlsafe = null) {

		if (!Auth::logged_in()) { Auth::loginpage(); }
		if (Auth::get('level') != 'Admin') {
			throw new \Exception("Sie haben keine Berechtigung diese Seite aufzurufen", 403);
		}

		$remoteAccessURL = null;
		if (defined('DIRECT_ACCESS_URL') && !empty($urlsafe)) {$remoteAccessURL = DIRECT_ACCESS_URL;}

		$jwt = new JWTAuth;
		$token = $jwt->create_token(null, $remoteAccessURL, '+5years');
		echo ($token);
	}
}