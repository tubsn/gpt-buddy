<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\cache\RequestCache;
use League\CommonMark\CommonMarkConverter;
use app\models\Prompts;

class DriveRAG extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}
		$this->view('DefaultLayout');
		$this->models('DriveRAGApi,ChatGPT,Prompts');
		$this->view->result = null;
		$this->view->query = null;		
		$this->view->errorMessage = null;		
		$this->view->userneed = 'Update Me';	
		$this->view->userneedOptions =['Update Me', 'Divert Me', 'Give me Perspective', 'Educate Me', 'Help Me', 'Inspire Me'];	
		$this->view->length = 'Medium';	
		$this->view->lengthOptions = ['Kurz','Medium','Lang'];
		$this->view->promptOptions = $this->Prompts->category('rag');

		$this->view->articleContentPromptID = 247;
		$this->view->phrasePromptID = 246;

	}

	public function index() {
		$this->view->render('driverag/search');
	}

	public function ragvolo() {
		$Prompts = new Prompts();
		$this->view->prompt = $this->view->promptOptions[0];
		$this->view->render('driverag/index');
	}

	public function generate_search_query() {

		$Prompts = new Prompts();

		$phrasePromptID = $this->view->phrasePromptID;
		$articleContentPromptID = $this->view->articleContentPromptID;

		$query = $_POST['query'] ?? null;
		$cache = new RequestCache($query, 60*60);

		$this->view->promptOptions = $Prompts->category('rag');
		
		$prompt = $Prompts->get_for_api($articleContentPromptID);
		if ($_POST['prompt']) {
			$prompt = $Prompts->get_for_api($_POST['prompt']);
		}

		$userneed = $_POST['userneed'] ?? null;
		if (!in_array($userneed, $this->view->userneedOptions)) {$userneed = $this->view->userneed;}

		$length = $_POST['length'] ?? null;
		if (!in_array($length, $this->view->lengthOptions)) {$length = $this->view->length;}

		$phrasePrompt = $Prompts->get($phrasePromptID);

		$this->ChatGPT->jsonMode = true;
		$this->ChatGPT->model = 'gpt-4.1';
		$this->ChatGPT->reasoning = 'high';

		$keywords = $this->ChatGPT->direct($query, $phrasePrompt['content']);

		$json = json_decode($keywords,true);
		$phrase = $json['phrase'];

		$ragResult = $cache->get();

		if (!$ragResult) {

			try {
				$ragResult = $this->DriveRAGApi->search($phrase);
				$cache->save($ragResult);
			} catch (\Exception $e) {
				$this->view->errorMessage = $e->getMessage();
				$ragResult = [];
			}

		}

		$ragResult = array_slice($ragResult, 0, 10);
		$articlesJsonString = json_encode($ragResult);

		$prompt['content'] = str_replace(['{{phrase}}', '{{userneed}}', '{{length}}'], [$phrase, $userneed, $length], $prompt['content']);

		$this->ChatGPT->jsonMode = false;
		$this->ChatGPT->conversation = [];

		$this->ChatGPT->model = 'gpt-4.1';

		$this->ChatGPT->add($prompt['content'], 'system');
		foreach ($prompt['knowledges'] as $knowledge) {$this->ChatGPT->add($knowledge, 'system');}
		$this->ChatGPT->add($articlesJsonString, 'user');
		$this->ChatGPT->add($prompt['afterthought'], 'system');

		$article = $this->ChatGPT->direct();

		$converter = new CommonMarkConverter();
		$article = $converter->convert($article);

		$this->view->prompt = $prompt;
		$this->view->query = $query;
		$this->view->userneed = $userneed;
		$this->view->phrase = $phrase;
		$this->view->rag = $ragResult;
		$this->view->article = $article;
		$this->view->keywords = $keywords;
		$this->view->render('driverag/index');

	}

	public function search() {
		$query = $_POST['query'] ?? null;
		$result = $this->DriveRAGApi->search($query);
		$this->view->query = $query;
		$this->view->result = $result ?? null;
		$this->view->render('driverag/search');
	}


	public function addParagraphs($text, $maxLength = 400) {
		$sentences = preg_split('/([.!?]\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$out = '';
		$current = '';

		foreach ($sentences as $part) {
			$current .= $part;
			if (mb_strlen($current) >= $maxLength) {
				$out .= '<p>' . trim($current) . '</p>';
				$current = '';
			}
		}
		if (trim($current) != '') {
			$out .= '<p>' . trim($current) . '</p>';
		}
		return $out;
	}

}
