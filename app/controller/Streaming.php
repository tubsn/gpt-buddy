<?php

namespace app\controller;
use flundr\auth\Auth;
use flundr\mvc\Controller;
use flundr\utility\Session;

class Streaming extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}
		$this->view('DefaultLayout');
		$this->models('AiChat');
		$this->init_sse_error_handling();
	}

	public function sse() {
		// Datatransfer is Handled via Session (see Postrequest)
		// We need a Subsession for each Browser Tab
		$this->access_subsession();

		try {
			$this->AiChat->init_stream();
		} catch (\Exception $e) {
			$this->AiChat->sse_error('Error while Streaming: ' . $e->getMessage());
		}
	}

	public function dump_session() {
		$session = Session::get_data();
		dd($session);
	}

	public function kill_session() {
		Session::delete();
	}


	public function access_subsession() {
		$responseID = Session::get('responseID');

		if ($responseID) {
			$conversations = Session::get('conversations');

			if (empty($conversations)) {return;}
			$conversation = $conversations[$responseID] ?? null;
			Session::set('conversation', $conversation);
		}
	}


	// Conversation Handling should be refactored into an own controller!
	public function delete_conversation($responseID = null) {
		if ($responseID) {
			$conversations = Session::get('conversations');	
			unset($conversations[$responseID]);
			Session::set('conversations', $conversations);

			$trackingIDs = Session::get('trackingIDs');	
			unset($trackingIDs[$responseID]);
			Session::set('trackingIDs', $trackingIDs);
		}
		Session::unset('conversation');
		Session::unset('trackingID');
	}

	public function get_conversation($responseID = null) {

		if ($responseID) {
			$conversations = Session::get('conversations');
			$this->view->json($conversations[$responseID] ?? null);
			return;
		}

		$this->view->json(Session::get('conversation'));
	}

	public function add_conversation_entry() {
		$conversations = Session::get('conversations');
		$responseID = $_POST['responseID'] ?? null;
		$conversation = $conversations[$responseID];

		$content = 'Neuer Eintrag (Rechtsklick zum editieren)';
		$entry = ['role' => 'system', 'content' => $content];
		array_push($conversation, $entry);

		$conversations[$responseID] = $conversation;
		Session::set('conversations', $conversations);

		$this->view->json($conversation);
	}

	public function edit_conversation() {
		$entryID = $_POST['entryID'] ?? null;
		if (is_null($entryID)) {throw new \Exception("Conversation EntryID missing", 404);}
		$content = $_POST['content'] ?? null;

		$conversations = Session::get('conversations');
		$responseID = $_POST['responseID'] ?? null;
		$conversation = $conversations[$responseID];

		$conversation[$entryID]['content'] = $content;

		$conversations[$responseID] = $conversation;

		Session::set('conversations', $conversations);

		$this->view->json(true);
	}

	public function remove_conversation_entry($index) {
		$conversations = Session::get('conversations');
		$responseID = $_POST['responseID'] ?? null;
		$conversation = $conversations[$responseID];

		unset($conversation[$index]);
		$conversation = array_values($conversation); // OpenAI Requires sequential indexes 

		$conversations[$responseID] = $conversation;
		Session::set('conversations', $conversations);		

		$this->view->json($conversation);
	}

	public function post_request() {

		$data = $this->get_header_input();
		Session::set('responseID', $data['responseID'] ?? null);
		Session::set('input', $data['input'] ?? null);
		Session::set('category', $data['category'] ?? null);
		Session::set('payload', $data['payload'] ?? null);
		Session::set('promptID', $data['promptID'] ?? null);
		Session::set('model', $data['model'] ?? null);
		Session::set('parameters', $data['parameters'] ?? null);
		Session::set('regenerate', $data['regenerate'] ?? null);

		$url = '/stream/sse';
		$status = 'success';

		$this->view->json([
			'status' => $status,
			'url' => $url,
		]);

	}

	public function get_header_input() {
		$rawBody = file_get_contents('php://input');
		return json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
	}

	public function init_sse_error_handling() {

		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			header('Content-Type: text/event-stream');
			header('Cache-Control: no-cache');
			$error = $errstr . ' | ' . $errfile . ' | ' . $errline;
			echo "event: error\n";
			echo "data: PHP Error: " . addslashes($error) . "\n\n";
			@ob_flush();
			flush();
			exit;
		});

		set_exception_handler(function($exception) {
			header('Content-Type: text/event-stream');
			header('Cache-Control: no-cache');
			echo "event: error\n";
			echo "data: Fatal Error: " . addslashes($exception->getMessage() . ' | ' . $exception->getFile() . ' | ' . $exception->getLine()) . "\n\n";
			@ob_flush();
			flush();
			exit;
		});

	}

}
