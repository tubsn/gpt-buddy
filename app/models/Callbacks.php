<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;
use \app\models\Knowledge;
use \app\models\Scrape;

class Callbacks
{

	public function __construct() {}

	public function run($callback, $prompt = null) {

		if ($callback == 'current-date') {return $this->current_date($prompt);}
		if ($callback == 'current-time') {return $this->current_time($prompt);}

		return $prompt;
	}

	private function current_date($prompt) {
		$date = date('d.m.Y', time());
		$prompt['content'] = $prompt['content'] . "\n" . 'Wir haben heute den: ' . $date;
		return $prompt;
	}

	private function current_time($prompt) {
		$date = date('H:i', time());
		$prompt['content'] = $prompt['content'] . "\n" . 'Es ist ' . $date . ' Uhr.';
		return $prompt;
	}

	private function in_knowledgebase($callback) {
		$knowledge = new Knowledge();
		$knowledgenames = $knowledge->distinct();
		$knowledgenames = array_map('strtolower', $knowledgenames);
		if (in_array(strtolower($callback), $knowledgenames)) {return true;}
		return false;
	}

	private function curl($url) {

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

		$recievedData = curl_exec($ch);
		if ($recievedData === false) {
			dd(curl_error($ch));
		}

		curl_close ($ch);

		return json_decode($recievedData, true);

	}

}
