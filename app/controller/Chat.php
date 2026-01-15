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
		$this->view->aimodels = AIMODELS ?? []; 
		$this->models('ChatGPT,Conversations,Prompts,OpenAIImage');
	}

	public function index() {

		$this->view->referer('/');

		$categorySettings = CATEGORIES[strtolower(PORTAL)] ?? [];
		$this->view->category = $categorySettings;

		$generalPrompts = $this->Prompts->category('alle');		
		$portalPrompts = $this->Prompts->category(strtolower(PORTAL));

		$this->view->prompts = array_merge($generalPrompts, $portalPrompts);

		$this->view->title = 'ChatGPT Assistent';
		$this->view->render('ui/chat-interface');
	}

	public function category($category) {
		
		$this->view->referer('/' . $category);

		$category = strtolower($category);
		if (!in_array($category, array_keys(CATEGORIES))) {throw new \Exception("Page not Found", 404);}

		$categorySettings = CATEGORIES[$category];
		$categorySettings['name'] = $category;

		$this->view->category = $categorySettings;
		$this->view->title = CATEGORIES[$category]['title'] ?? 'ChatGPT Assistent';

		if ($category == 'rag') {
			$this->view->taglist = $this->read_tags();
			$this->view->rag = true;
		}

		$this->view->prompts = $this->Prompts->category($category);
		$this->view->render('ui/chat-interface');

	}

	// Should be Refactored into Model
	public function read_tags() {
		$filepath = ROOT . 'cache/tags/taglist.txt';
		if (!file_exists($filepath)) {return null;}
		$tagString = file_get_contents($filepath);
		$tags = unserialize($tagString);
		unset($tags['Storys'],$tags['S-Nummer']);
		return array_merge(...array_values($tags));
	}


	public function faq() {
		$this->view->title = 'Hinweise zum Umgang mit KI';
		$this->view->render('faq');
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
			$question = 'Mach einen lustigen Witz zum heutigen Tag ('.$date.'). Maximal 30 Wörter. Orientiere dich am Humor von Bully Herbig (nicht erwähnen). Themenbereich Naturwissenschaft';
			$this->ChatGPT->model = 'gpt-4.1-mini';
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
		$options['payload'] = $_POST['payload'] ?? null;
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

	public function show_sessions() {
		$conversations = Session::get('conversations');
		dd($conversations);
	}

	public function show_conversation($id) {
		$conversation = $this->Conversations->get_json_with_markdown($id);
		$conversationJson = $this->Conversations->get_json($id);
		if (empty($conversation)) {throw new \Exception("Conversation not Found", 404);}

		$conversations = Session::get('conversations');
		$conversations[$id] = $conversationJson;
		Session::set('conversations', $conversations);
		Session::set('conversation', $conversationJson);

		$this->view->title = 'Chat vom ' . $id;
		$this->view->conversation = $conversation;
		$this->view->id = $id;
		$this->view->render('conversation');
	}

	public function edit_conversation($id) {
		$conversationData = $this->Conversations->get($id);
		if (empty($conversationData)) {throw new \Exception("Conversation Unavailable", 404);}

		$conversation = $conversationData['conversation'];
		
		$entryID = $_POST['entryID'] ?? null;
		if (is_null($entryID)) {throw new \Exception("Conversation EntryID missing", 404);}
		$content = $_POST['content'] ?? null;

		// Be aware that this removes possible Arrays e.g. when images were attached
		$conversation[$entryID]['content'] = $content;
		$conversationData['conversation'] = $conversation;

		$success = $this->Conversations->update($conversationData, $id);
		$this->view->json($success);
	}

	public function get_conversation_json($id) {
		$conversation = $this->Conversations->get_json($id);
		if (empty($conversation)) {throw new \Exception("Conversation Unavailable", 404);}
		$this->view->json($conversation);
	}

	public function add_conversation_entry($id) {
		$conversation = $this->Conversations->add_entry('Neuer Eintrag (Rechtsklick zum editieren)', $id);
		$this->view->json($conversation['conversation']);
	}

	public function remove_last_conversation_entry($id) {
		$this->Conversations->remove_last_entry($id);
		$this->view->json($this->Conversations->get($id)['conversation']);
	}

	public function remove_last_conversation_generation($id) {
		$this->Conversations->remove_last_generation($id);
		$this->view->json($this->Conversations->get($id)['conversation']);
	}

	public function remove_conversation_entry($id, $index) {
		$this->Conversations->remove_entry($id, $index);
		$this->view->json($this->Conversations->get($id)['conversation']);
	}

}
