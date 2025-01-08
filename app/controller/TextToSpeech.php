<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;

class TextToSpeech extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->js = '/styles/js/texttospeech.js';
		$this->view->title = 'Text to Speech';
		$this->models('OpenAIWhisper');
	}

	public function index() {
		$this->view->input = $_POST['input'] ?? null;
		$this->view->render('tts/index');
	}

	public function generate() {

		//$this->view->json(['status' => 'ok', 'audio' => '/audio/tts/2025-01-08-12-00-1e7d1e7a.mp3']); 
		//return;

		$text = $_POST['text'] ?? null;
		$voice = $_POST['voice'] ?? null;
		$file = $this->OpenAIWhisper->tts($text, $voice);
		$this->view->json(['status' => 'ok', 'audio' => $file]); 
	}

}
