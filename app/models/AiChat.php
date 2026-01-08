<?php

namespace app\models;
use \app\models\AiToolingHandler;
use \app\models\Tracking;
use \app\models\Prompts;
use \app\models\OpenAiVision;
use \app\models\ai\OpenAI;
use \app\models\ai\ConnectionHandler;
use flundr\auth\Auth;
use flundr\utility\Session;
use flundr\utility\Log;

class AiChat
{

	public $ai;
	private $tools;
	private $stats;
	private $prompts;
	private $connection;
	private $isNewConversation = true;

	public function __construct() {
		$this->connection = new ConnectionHandler(CHATGPTKEY, 'https://api.openai.com', '/v1/responses');
		$this->ai = new OpenAI($this->connection);
		$this->ai->add_toolhandler(new AiToolingHandler());
		$this->resolve_model();
		$this->stats = new Tracking();
		$this->prompts = new Prompts();
		$this->clear_toolcalling_results();
		//$this->clear_logs(); // activate when Response Debug Logging is enabled		
	}

	public function init_stream() {

		$this->build_conversation();
		$this->resolve_tools();
		$this->track_usage();

		$this->init_streaming_header();

		// Note the str_pad is important for some webservers to stream SSE
		$this->ai->stream(function (array $event) {
			echo 'data: ' . json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
			echo str_pad('', 256)."\n";
			flush();
		});

		$this->merge_conversation_and_tool_data();
	}

	public function build_conversation() {

		$userInput = Session::get('input') ?? '';
		$prompt = $this->prompts->get_and_track(Session::get('promptID')); 

		if (empty($userInput) && !($prompt['direct'] ?? false)) {
			$this->sse_error('Achtung keine Eingabe erkannt');
		}

		// If Content is combined with image
		$payload = Session::get('payload');
		if (!empty($payload)) {
			$userInput = [
				['type' => 'input_text', 'text' => $userInput],
				['type' => 'input_image', 'image_url' => $this->prepare_image_for_vision($payload)],
			];
		};

		// If this is an existing Conversation we can return early
		$conversation = Session::get('conversation');
		if (!empty($conversation)) {

			$this->isNewConversation = false;

			if (Session::get('regenerate')) {
				$conversation = $this->remove_last_interaction($conversation);
			}

			array_push($conversation, ['role' => 'user', 'content' => $userInput]);
			$this->ai->messages = $conversation;
			Session::unset('conversation'); // we need to remove the conversation here else it wont be deleted if there is in error while streaming
			return;
		}

		// Start a new Conversation as User or System / Direct Prompt
		if (!empty($prompt['content'])) {
			$role = empty($userInput) ? 'user' : 'system';
			$this->ai->add_message($prompt['content'], $role);
		}

		// Add Knowledgebases
		if (!empty($prompt['knowledges'])) {
			foreach ($prompt['knowledges'] as $knowledge) {$this->ai->add_message($knowledge, 'system');}
		}

		// Userinput might be omitted in direct Prompts
		if (!empty($userInput)) {
			$this->ai->add_message($userInput, 'user');
		}

		// Add Afterprompt and optional Refining System Parameters
		if (!empty($prompt['afterthought'])) {$this->ai->add_message($prompt['afterthought'], 'system');}
		$this->handle_rag_parameters();

	}

	public function resolve_tools() {
		$categoryName = Session::get('category');
		$category = CATEGORIES[$categoryName] ?? null;

		$tools = $category['tools'] ?? null;
		if (!is_array($tools)) {$tools = [$tools];}

		foreach ($tools as $tool) {
			if (!empty($tool)) {$this->ai->tools->use($tool);}
		}

		$searchtool = Session::get('search');
		if ($searchtool) {$this->ai->tools->use('search');}

		$this->ai->tools->use('date');
		//$this->ai->tools->use('weekday');
		//$this->ai->tools->use('Aibuddy_Github');
		//$this->ai->tools->use('BNN_MCP');

	}

	public function track_usage($type = 'chat') {

		try {
			$data = [];
			$trackingID = Session::get('trackingID');
			$responseID = Session::get('responseID');

			if ($responseID) {
				$trackingIDs = Session::get('trackingIDs');
				$trackingID = $trackingIDs[$responseID] ?? $trackingID;
			}

			$messages = $this->ai->messages;
			$userMessages = count(array_filter($messages, static fn(array $message): bool => ($message['role'] ?? null) === 'user'));
			$data['length'] = $userMessages;

			if ($trackingID) {
 				// we only need to update on new userinteractions				
				if ($data['length'] > 1) {
					$this->stats->update(['length' => $data['length']], $trackingID);
				}
				// we return early so the "old" TrackingID has to be saved
				Session::set('trackingID', $trackingID);
				return; 
			}

			$promptID = Session::get('promptID');
			$promptID = intval($promptID); // removes promptIDs like 'default'
			$userID = Auth::get('id');

			if ($userID) {$data['user_id'] = $userID;}
			if ($promptID) {$data['prompt_id'] = $promptID;}

			$data['type'] = $type; // change e.g. for api usage
			$data['model'] = $this->ai->model ?? null;
			$data['reasoning'] = $this->ai->reasoning ?? null;

			$id = $this->stats->create($data);
			Session::set('trackingID', $id);
	
		} catch (\PDOException $e) {
			Log::error('Tracking Error while writing to DB: ' . $e->getMessage());
		}

	}

	public function resolve_model() {

		$userModel = Session::get('model');
		$modelData = AIMODELS[$userModel] ?? null;
		
		if (!$modelData) {$modelData = ['apiname' => 'gpt-5.1', 'reasoning' => 'none'];}

		$this->ai->model = $modelData['apiname'];
		$this->ai->reasoning = $modelData['reasoning'] ?? null;

	}

	public function remove_last_interaction($conversation) {

		$lastUser = $lastAssistant = null;
		foreach (array_reverse($conversation, true) as $k => $v) {
			if ($lastUser === null && $v['role'] === 'user') $lastUser = $k;
			if ($lastAssistant === null && $v['role'] === 'assistant') $lastAssistant = $k;
			if ($lastUser !== null && $lastAssistant !== null) break;
		}
		if ($lastUser !== null) unset($conversation[$lastUser]);
		if ($lastAssistant !== null) unset($conversation[$lastAssistant]);

		return array_values($conversation); // Open Ai requires iterating indices
	}

	public function prepare_image_for_vision($imagePath) {
		$visionData = PAGEURL . $imagePath;
		if (defined('USEBASE64VISION') && USEBASE64VISION) {
			$vision = new OpenAiVision();
			$visionData = $vision->file_to_base64(PUBLICFOLDER . Session::get('payload'));
		}
		return $visionData;
	}

	public function merge_conversation_and_tool_data() {

		$oldID = Session::get('responseID');
		$responseID = $this->ai->last_response_id();
		$conversation = $this->ai->last_conversation();

		$tooldata = Session::get('tooldata');
		if ($tooldata) {
			$tooldata = json_encode($tooldata);
			$tooldata = ['role' => 'system', 'content' => $tooldata];

			$conversation = $this->ai->last_conversation();
			$insertPosition = count($conversation) - 1;
			array_splice($conversation, $insertPosition, 0, [$tooldata]);
		}

		// This part is important to save Conversation and Tracking Data between Sessions
		$conversations = Session::get('conversations');
		$conversations[$responseID] = $conversation;
		unset($conversations[$oldID]);
		Session::set('conversations', $conversations);

		$trackingIDs = Session::get('trackingIDs');
		$trackingIDs[$responseID] = Session::get('trackingID');
		unset($trackingIDs[$oldID]);
		Session::set('trackingIDs', $trackingIDs);

		$this->log_conversation();

		// Reset state in current Session
		Session::unset('trackingID');
		Session::unset('responseID');
		Session::unset('conversation');
		Session::unset('regenerate');

	}

	public function log_conversation() {

		if (!defined('LOG_CONVERSATIONS')) {return;}
		if (!LOG_CONVERSATIONS) {return;}

		$folder = ROOT . 'cache' . DIRECTORY_SEPARATOR . 'conversations' . DIRECTORY_SEPARATOR;
		$oldID = Session::get('responseID');
		$responseID = $this->ai->last_response_id();

		if (!$this->isNewConversation) {
			$oldFile = $folder . $oldID;
			if (is_file($oldFile)) {unlink($oldFile);}
		}

		$file = $folder . $responseID;
		$content = $this->ai->last_conversation();
		$content = json_encode($content);
		file_put_contents($file, $content);
	}

	public function handle_rag_parameters() {

		$params = Session::get('parameters');
		if (!$params) {return;}

		$prefix = '(Nur beachten wenn DriveRag verwendet wird) ';

		if ($params['from']) {$this->ai->add_message($prefix . 'Nutze als Startdatum den ' . $params['from'], 'system');}
		if ($params['to']) {$this->ai->add_message($prefix . 'Nutze als Enddatum den ' . $params['to'], 'system');}
		
		if ($params['section']) {$this->ai->add_message($prefix . 'Filtere die Section auf: ' . $params['section'], 'system');}
		if ($params['tags']) {$this->ai->add_message($prefix . 'Filtere nach folgenden Tags: ' . $params['tags'], 'system');}
		
		if ($params['userneed']) {$this->ai->add_message('Schreibe mit dem Userneed: ' . $params['userneed'], 'system');}

		if ($params['length']) {
			$length = '300-400 Wörter';
			switch ($params['length']) {
				case 'kurz': $length = '300-400 Wörtern'; break;
				case 'medium': $length = '600-800 Wörtern'; break;
				case 'lang': $length = '1200-1400 Wörtern'; break;
			}
			$this->ai->add_message('Schreibe in deiner Ausgabe zwingend mit einer genauen Länge von: ' . $length, 'system');
		}

	}

	public function init_streaming_header($debug = false) {

		// These Settings help disabling buffers in Streaming environments
		if (function_exists('apache_setenv')) {
			@apache_setenv('no-gzip', '1');
		}

		@ini_set('zlib.output_compression', '0');
		@ini_set('output_buffering', '0');
		@ini_set('implicit_flush', '1');

		while (ob_get_level() > 0) {ob_end_clean();}

		if ($debug) {return;}
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('X-Accel-Buffering: no');

	}

	public function sse_error($message) {
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');			
		echo "event: error\n";
		echo "data: " . addslashes($message) . "\n\n";
		@ob_flush();
		flush();
		exit;
	}

	public function clear_toolcalling_results() {
		// removes Tool Calling results from last chat
		// Used to visualize external Data in Conversation History
		Session::unset('tooldata'); 
	}

	public function clear_logs() {
		$files = glob(rtrim(LOGS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*');
		foreach ($files as $file) {
			if (is_file($file)) {@unlink($file);}
		}
	}

}
