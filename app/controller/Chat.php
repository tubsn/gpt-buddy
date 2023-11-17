<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\auth\JWTAuth;
use flundr\cache\RequestCache;
use flundr\utility\Session;

class Chat extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->title = 'ChatGPT Assistent';
		$this->models('ChatGPT,Conversations,Prompts,OpenAIImage');
	}

	public function index() {

		$this->view->referer('/');

		$categorySettings = CATEGORIES[strtolower(PORTAL)];
		$categorySettings['directActions'] = ['53|Einfach Plaudern...'];
		$this->view->category = $categorySettings;

		$generalPrompts = $this->Prompts->category('alle');		
		$portalPrompts = $this->Prompts->category(strtolower(PORTAL));

		$this->view->prompts = array_merge($generalPrompts, $portalPrompts);

		$this->view->title = 'ChatGPT Assistent';
		$this->view->render('chat');
	}

	public function category($category) {
		
		$this->view->referer('/' . $category);

		$category = strtolower($category);
		if (!in_array($category, array_keys(CATEGORIES))) {throw new \Exception("Page not Found", 404);}

		$this->view->category = CATEGORIES[$category];
		$this->view->title = CATEGORIES[$category]['title'] ?? 'ChatGPT Assistent';
		
		$this->view->prompts = $this->Prompts->category($category);
		$this->view->render('chat');

	}

	public function faq() {
		$this->view->title = 'Hinweise zum Umgang mit KI';
		$this->view->render('faq');
	}

	public function faq_swp() {
		$this->view->title = 'Hinweise zum Umgang mit KI';
		$this->view->render('faq-swp');
	}

	public function changelog() {
		$this->view->funfact = $this->fun_fact();		
		$this->view->title = 'Changelog';
		$this->view->render('changelog');
	}

	public function engines() {
		$engines = $this->ChatGPT->list_engines();
		dd($engines);
	}

	public function fun_fact() {
		$cache = new RequestCache('funfact', 60*60);
		$funfact = $cache->get();
		if (empty($funfact)) {
			$date = date('d.F');
			$question = 'Mach lustigen Witz zum heutigen Tag ('.$date.'). Maximal 30 Wörter. Orientiere dich am Humor von Dave Chappelle. Themenbereich Naturwissenschaft';
			$funfact = $this->ChatGPT->direct($question);
			$cache->save($funfact);
		}

		return $funfact;
	}

	public function ask() {
		$question = $_POST['question'] ?? null;
		$options['promptID'] = $_POST['action'] ?? null;
		$options['directPromptID'] = $_POST['directPromptID'] ?? null;
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
		$conversation = $this->Conversations->get_with_markdown($id);
		if (empty($conversation)) {throw new \Exception("Conversation not Found", 404);}

		$meta = $this->Conversations->get_meta($id);

		$this->view->templates['header'] = null;

		$this->view->title = 'Chat vom ' . date('d.m.Y H:i', $meta['edited']) . '&thinsp;Uhr';
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
