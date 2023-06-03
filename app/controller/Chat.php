<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\auth\JWTAuth;
use flundr\utility\Session;

class Chat extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->interface = 'default';
		$this->view->title = 'ChatGPT Assistent';
		$this->models('ChatGPT,Conversations,Prompts,OpenAIImage');
	}

	public function index($category = null) {

		$possibleCateogries = ['translate', 'shorten', 'spelling'];
		if ($category && !in_array($category, $possibleCateogries)) {throw new \Exception("Page not Found", 404);}

		if ($category == 'translate') {
			$this->view->title = 'Übersetzer';
			$this->view->interface = 'translate';
		}

		if ($category == 'shorten') {
			$this->view->title = 'Textlängen Anpassesn';
			$this->view->interface = 'shorten';
		}

		if ($category == 'spelling') {
			$this->view->title = 'Rechtschreibung Korrigieren';
			$this->view->interface = 'spelling';
		}

		$this->view->prompts = $this->Prompts->list(1); // true hides Inactive Prompts
		$this->view->render('chat');
	}

	public function faq() {
		$this->view->title = 'Hinweise zum Umgang mit KI';
		$this->view->render('faq');
	}

	public function ask() {
		$question = $_POST['question'] ?? null;
		$options['promptID'] = $_POST['action'] ?? null;
		$options['conversationID'] = $_POST['conversationID'] ?? null;

		$response = $this->ChatGPT->ask($question, $options);
		$this->view->json($response); // contains ConversionID
	}

	public function image() {
		$this->view->response = $this->OpenAIImage->fetch($_POST['question']);
		$this->view->question = $_POST['question'];		
		$this->view->render('image');
	}

	public function conversation_list() {

		$conversations = $this->Conversations->list();
		$conversationsByDay = array_count_values(array_column($conversations,'day'));
		$conversationsAmount = count($conversations);

		$this->view->conversations = $conversations;
		$this->view->conversationsByDay = $conversationsByDay;

		$this->view->title = 'Gesamt Conversations: ' . $conversationsAmount;
		$this->view->render('conversation-list');

	}

	public function show_conversation($id) {
		$conversation = $this->Conversations->get($id);
		if (empty($conversation)) {throw new \Exception("Conversation not Found", 404);}

		$meta = $this->Conversations->get_meta($id);

		$this->view->templates['header'] = null;

		$this->view->title = 'Chat vom ' . date('d.m.Y H:i', $meta['edited']) . 'Uhr';
		$this->view->conversation = $conversation;
		$this->view->render('conversation');
	}


	public function get_conversation_json($id) {
		$conversation = $this->Conversations->get($id);
		if (empty($conversation)) {throw new \Exception("Conversation Unavailable", 404);}
		$this->view->json($conversation);
	}

	public function remove_last_conversation_entry($id) {
		$this->Conversations->remove_last_entry($id);
		$this->view->json($this->Conversations->get($id));
	}

}
