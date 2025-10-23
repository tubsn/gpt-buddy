<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;
use League\CommonMark\CommonMarkConverter;

class ResearchAgent extends Controller {

	public function __construct() {
		$this->view('DefaultLayout');
		$this->view->title = 'Research Mode';
		$this->models('ChatGPTApi,Prompts');
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}
	}

	public function index() {
		$this->view->render('agentmode/index');
	}


	public function ask() {

		$baseurl = 'http://localhost:8000';
		$path = '/research';
		$url = $baseurl . $path;

		$data = [
			"query" => "Welche Energieversorger Aktien haben zur Zeit (September 2025) in Bezug auf die Ki Datacenter Entwicklung großes Potential.",
			//"mode" => "basic", // ohne triage
			"mode" => "pipeline",
			"language" => "de",
			"mock_answers" => [
				"Gibt es eine bevorzugte Region oder Sprache?" => "Fokus auf Baden-Württemberg, deutschsprachige Quellen bevorzugt."
			],
			"verbose" => false
		];

		$response = $this->curl($url, $data);
		$response = json_decode($response,1);

		dd($response);
	}


	public function job($id) {

		$baseurl = 'http://localhost:8000';
		$path = '/jobs/' . $id . '/result';
		$url = $baseurl . $path;
		$response = $this->curl($url);

		$data = json_decode($response,1);

		$this->view->response = $data;

		$output = $data;

		if (isset($data['final_output'])) {
			$jsonDecoded = json_decode($data['final_output'], true);
			$output = (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded))
				? $jsonDecoded
				: $data['final_output'];
		}
		else {
			throw new \Exception($data['detail'], 400);
			
		}

		$converter = new CommonMarkConverter();
		$this->view->response = $converter->convert($output);

		$this->view->render('agentmode/index');
	}


	public function stream($id) {

		$baseurl = 'http://localhost:8000';
		$path = '/jobs/' . $id . '/stream';
		$url = $baseurl . $path;
		$response = $this->curl($url);

		dd($response);
	}

	function curl($url, $options = null) {

		$ch = curl_init($url);
		$header = ["Content-Type: application/json"];

		if ($options) {
			$jsonData = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

}
