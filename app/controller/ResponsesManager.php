<?php

namespace app\controller;
use flundr\mvc\Controller;

class ResponsesManager extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->title = 'ChatGPT Assistent';
		$this->view->aimodels = AIMODELS ?? []; 
		$this->models('ChatGPT,Conversations,Prompts,OpenAIImage');
	}

	public function purge_logs() {

		$files = $this->get_files();
		//dd($files);

		foreach ($files as $id) {
			$result = $this->delete_response($id);
			print_r($result);
		}

	}

	public function get_files() {

		$dir = ROOT . 'cache/conversations/*';
		$files = [];

		$paths = glob($dir);

		foreach ($paths as $path) {
			if (str_contains($path, 'resp_')) {
				$files[] = basename($path); // nur Dateiname
			}
		}

		return $files;
	}


	public function delete_response($responseId) {

		$apiKey = CHATGPTKEY;
		$baseUrl = 'https://api.openai.com';
		$url = rtrim($baseUrl, '/') . '/v1/responses/' . rawurlencode($responseId);

		$curlHandle = curl_init($url);
		if ($curlHandle === false) {
			return ['ok' => false, 'status' => 0, 'error' => 'curl_init failed', 'body' => null];
		}

		curl_setopt_array($curlHandle, [
			CURLOPT_CUSTOMREQUEST => 'DELETE',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $apiKey,
			],
			CURLOPT_TIMEOUT => 30,
		]);

		$responseBody = curl_exec($curlHandle);
		$curlError = curl_error($curlHandle);
		$httpStatus = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

		curl_close($curlHandle);

		if ($responseBody === false) {
			return ['ok' => false, 'status' => $httpStatus, 'error' => $curlError ?: 'curl_exec failed', 'body' => null];
		}

		$decodedBody = json_decode($responseBody, true);
		$body = (json_last_error() === JSON_ERROR_NONE) ? $decodedBody : $responseBody;

		return [
			'ok' => ($httpStatus >= 200 && $httpStatus < 300),
			'status' => $httpStatus,
			'error' => ($httpStatus >= 200 && $httpStatus < 300) ? null : ($decodedBody['error']['message'] ?? null),
			'body' => $body,
		];
	}






}

