<?php

namespace app\controller;
use flundr\mvc\Controller;
//use flundr\auth\Auth;

class API extends Controller {

	public function __construct() {
		$this->view('DefaultLayout');
		$this->models('ChatGPT,Conversations,Prompts,OpenAIImage');
		header('Access-Control-Allow-Origin: *');		
	}

	public function stream($id, $force4 = false) {

		if ($force4) {$this->ChatGPT->forceGPT4 = true;}

		$maxElapsedSeconds = 10;
		$conversationMeta = $this->Conversations->get_meta($id);
		if (!$conversationMeta) {throw new \Exception("Conversation not Found", 400);}
		if (time() - $conversationMeta['edited'] > $maxElapsedSeconds) {throw new \Exception("Conversation Timeout Error", 400);}

		header('Content-type: text/event-stream');
		header('Cache-Control: no-cache');
		$response = $this->ChatGPT->stream($id);
	}

	public function generate_image() {
		$prompt = $_POST['question'];
		$options['resolution'] = $_POST['resolution'] ?? null;
		$options['quality'] = $_POST['quality'] ?? null;
		$options['style'] = $_POST['style'] ?? null;
		
		$output = $this->OpenAIImage->fetch($prompt, $options);
		$this->view->json($output);
	}


	public function stream_force_gpt4($id) {
		$this->stream($id, true);
	}

	public function ping() {echo 'pong';}

}
