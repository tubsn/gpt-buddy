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

	public function stream($id) {
		$maxElapsedSeconds = 10;
		$conversationMeta = $this->Conversations->get_meta($id);
		if (!$conversationMeta) {throw new \Exception("Conversation not Found", 400);}
		if (time() - $conversationMeta['edited'] > $maxElapsedSeconds) {throw new \Exception("Conversation Timeout Error", 400);}

		header('Content-type: text/event-stream');
		header('Cache-Control: no-cache');
		$response = $this->ChatGPT->stream($id);
	}

	public function ping() {echo 'pong';}

}
