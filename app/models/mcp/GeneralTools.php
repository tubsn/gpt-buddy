<?php

namespace app\models\mcp;
use flundr\utility\Log;
use flundr\utility\Session;

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


}
