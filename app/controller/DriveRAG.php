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
		$this->view->timeframe = 'diese Woche';	
		$this->view->timeframeOptions = ['diese Woche', 'dieser Monat', '3 Monate', 'alles'];
		$this->view->taglist = $this->read_tags();
		$this->view->promptOptions = $this->Prompts->category('rag');
		$this->view->articleContentPromptID = 247;
		$this->view->phrasePromptID = 246;
	}

	public function rag_index() {
		$Prompts = new Prompts();
		$this->view->timeframe = null;
		$this->view->prompt = $this->view->promptOptions[0];
		$this->view->render('driverag/index');
	}

	public function generate_search_query() {

		$Prompts = new Prompts();

		$phrasePromptID = $this->view->phrasePromptID;
		$articleContentPromptID = $this->view->articleContentPromptID;

		$query = $_POST['query'] ?? null;

		$this->view->promptOptions = $Prompts->category('rag');
		
		$prompt = $Prompts->get_for_api($articleContentPromptID);
		if ($_POST['prompt']) {
			$prompt = $Prompts->get_for_api($_POST['prompt']);
		}

		$userneed = $_POST['userneed'] ?? null;
		if (!in_array($userneed, $this->view->userneedOptions)) {$userneed = $this->view->userneed;}
		$this->view->length = $userneed;

		$length = $_POST['length'] ?? null;
		if (!in_array($length, $this->view->lengthOptions)) {$length = $this->view->length;}
		$this->view->length = $length;

		$timeframe = $_POST['timeframe'] ?? null;
		if (!in_array($timeframe, $this->view->timeframeOptions)) {$timeframe = $this->view->timeframe;}

		$phrasePrompt = $Prompts->get($phrasePromptID);
		$this->ChatGPT->jsonMode = true;
		$this->ChatGPT->model = AIMODELS[$prompt['model']] ?? 'gpt-4.1';
		$this->ChatGPT->reasoning = 'high';

		$keywords = $this->ChatGPT->direct($query, $phrasePrompt['content']);

		$json = json_decode($keywords,true);
		$phrase = $json['phrase'];


		$parameters = [];
		$ressorts = $_POST['ressorts'] ?? null;
		if ($ressorts) {$parameters['ressorts'] = $ressorts;}
		
		$tags = $_POST['tags'] ?? null;
		if ($tags) {
			$tags = explode_and_trim(',', $tags);
			$parameters['tags'] = $tags;
		}

		$exact = $_POST['exact'] ?? null;
		if ($exact) {$parameters['exact'] = $exact;}

		$this->view->ressorts = $ressorts;
		$this->view->tags = $tags;
		$this->view->exact = $exact ?? false;

		$from = $_POST['from'] ?? null;
		$to = $_POST['to'] ?? null;

		$this->view->from = $from;
		$this->view->to = $to;

		if (empty($from)) {$from = '-365 days';}
		if (empty($to)) {$to = 'today';}	
		
		try {
			$ragResult = $this->DriveRAGApi->search($phrase, $from, $to, $parameters);
		} catch (\Exception $e) {
			$this->view->errorMessage = $e->getMessage();
			$ragResult = [];
		}

		$ragResult = array_slice($ragResult, 0, 10);
		$articlesJsonString = json_encode($ragResult);

		$prompt['content'] = str_replace(['{{query}}', '{{phrase}}', '{{userneed}}', '{{length}}'], [$query, $phrase, $userneed, $length], $prompt['content']);

		$this->ChatGPT->jsonMode = false;
		$this->ChatGPT->conversation = [];

		$this->ChatGPT->model = AIMODELS[$prompt['model']] ?? 'gpt-4.1';

		$this->ChatGPT->add($prompt['content'], 'system');

		if (!empty($prompt['knowledges'])) {
			foreach ($prompt['knowledges'] as $knowledge) {$this->ChatGPT->add($knowledge, 'system');}
		}

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


	public function search_index() {
		$this->view->timeframe = null;	
		$this->view->render('driverag/search');
	}

	public function search() {
		$query = $_POST['query'] ?? null;

		$from = $_POST['from'] ?? null;
		$to = $_POST['to'] ?? null;

		$parameters = [];
		$ressorts = $_POST['ressorts'] ?? null;
		if ($ressorts) {$parameters['ressorts'] = $ressorts;}
		
		$tags = $_POST['tags'] ?? null;
		if ($tags) {
			$tags = explode_and_trim(',', $tags);
			$parameters['tags'] = $tags;
		}

		$exact = $_POST['exact'] ?? null;
		if ($exact) {$parameters['exact'] = $exact;}

		$this->view->from = $from;
		$this->view->to = $to;
		$this->view->ressorts = $ressorts;
		$this->view->tags = $tags;
		$this->view->exact = $exact ?? false;

		if (empty($from)) {$from = '-365 days';}
		if (empty($to)) {$to = 'today';}

		$result = $this->DriveRAGApi->search($query, $from, $to, $parameters);
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

	public function read_tags() {
		$filepath = ROOT . 'cache/tags/taglist.txt';
		if (!file_exists($filepath)) {return null;}
		$tagString = file_get_contents($filepath);
		$tags = unserialize($tagString);
		unset($tags['Storys'],$tags['S-Nummer']);
		return array_merge(...array_values($tags));
	}

}
