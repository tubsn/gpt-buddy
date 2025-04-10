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
		$this->view->title = APP_NAME . ' | Text to Speech';
		$this->models('OpenAIWhisper,OpenAiAudio');
	}

	public function index() {
		$this->view->input = $_POST['input'] ?? null;
		$this->view->render('tts/index');
	}

	public function generate() {
		$text = $_POST['text'] ?? null;
		$voice = $_POST['voice'] ?? null;
		$model = $_POST['model'] ?? null;
		$instructions = $_POST['instructions'] ?? null;
		$file = $this->OpenAIWhisper->tts($text, $voice, $model, $instructions);
		$this->view->json(['status' => 'ok', 'audio' => $file]); 
	}

}
