<?php

namespace app\models\mcp;
use flundr\utility\Log;
use flundr\utility\Session;
use \app\models\Prompts;
use \app\models\ai\OpenAI;
use \app\models\ai\ConnectionHandler;

class GeneralTools
{

	public function __construct() {}

	public function get_weekday(array $args): string {
		$dateString = $args['date'] ?? '';
		$timestamp = strtotime($dateString);
		if ($timestamp === false) {
			return 'Invalid date';
		}
		return date('l', $timestamp);
	}

	public function current_datetime(): string {
		return date('Y-m-d H:i:s');
	}

	public function count_chars(array $args) {
		$string = $args['text'] ?? '';
		$string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
		return strlen($string);
	}

	public function call_gpt(array $args) {
		$connection = new ConnectionHandler(CHATGPTKEY, 'https://api.openai.com/v1/responses');
		$ai = new OpenAI($connection);
		$ai->model = 'gpt-5.2';
		$ai->reasoning = 'none';

		$promptID = $args['promptID'] ?? '';

		if (!empty($promptID)) {
			$prompts = new Prompts();
			$prompt = $prompts->get_for_api($promptID);
			$ai->add_message($prompt['content'],'system');

			if (!empty($prompt['knowledges'])) {
				foreach ($prompt['knowledges'] as $knowledge) {$ai->add_message($knowledge, 'system');}
			}
		
			if (isset($prompt['withdate']) && $prompt['withdate']) {
				$ai->add_message('Aktuelles Datum: ' . date('Y-m-d H:i'), 'system');
			}

			$query = $args['query'] ?? '';
			$ai->add_message($query);

			if (!empty($prompt['afterthought'])) {$ai->add_message($prompt['afterthought'], 'system');}
		}

		else {
			$query = $args['query'] ?? '';
			$ai->add_message($query);
		}

		return $ai->resolve();
	}

	public function dom_parser($url, $selector = 'body') {

		$htmlString = $this->curl($url);

		$dom = @\Dom\HTMLDocument::createFromString($htmlString);
		
		if ($selector) {
			$selectedNodes = $dom->querySelectorAll($selector);
			$dom = $selectedNodes;
		}

		if (!is_iterable($dom)) {$dom = [$dom];}

		$content = '';
		foreach ($dom as $node) {
			$content .= $node->innerHTML;
		}

		$content = strip_tags($content);
		$content = str_replace(["\r\n", "\r"], "\n", $content);
		$content = str_replace("\xC2\xA0", " ", $content); // &nbsp;

		// Einrückungen entfernen + Mehrfach-Spaces in Zeilen auf 1 reduzieren
		$content = preg_replace('/^[^\S\n]+/m', '', $content);   // führende Spaces/Tabs je Zeile
		$content = preg_replace('/[^\S\n]+$/m', '', $content);   // trailing Spaces/Tabs je Zeile
		$content = preg_replace('/[^\S\n]{2,}/u', ' ', $content); // mehrere Spaces/Tabs -> 1 Space

		// Mehrere Leerzeilen reduzieren
		$content = preg_replace("/\n{2,}/", "\n", $content);

		return $content;

	}

	private function curl($url) {

		$headers = ["Content-Type: application/json"];
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);

		$recievedData = curl_exec($ch);
		if ($recievedData === false) {
			return (curl_error($ch));
		}

		curl_close($ch);

		return $recievedData;
	}

}
