<?php

namespace app\models\mcp;
use flundr\utility\Log;
use flundr\utility\Session;
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
		$connection = new ConnectionHandler(CHATGPTKEY, 'https://api.openai.com', '/v1/responses');
		$ai = new OpenAI($connection);
		$ai->model = 'gpt-5.2';
		$ai->reasoning = 'none';

		$query = $args['query'] ?? '';
		$ai->add_message($query);
		return $ai->resolve();
	}

}
