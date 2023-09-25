<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;

class Callbacks
{

	public function __construct() {}

	public function run($callback, $prompt = null) {

		if ($callback == 'lr-news') {
			return $this->json_feed_lr($prompt);
		}

		if ($callback == 'wyld-db') {
			return $this->wyld_marketingplan($prompt);
		}

		if ($callback == 'article-scores') {
			return $this->compare_article_scores($prompt);
		}

		if ($callback == 'current-date') {return $this->current_date($prompt);}
		if ($callback == 'current-time') {return $this->current_time($prompt);}

		return $prompt;
	}

	private function wyld_marketingplan($prompt) {

		$rssData = $this->curl('https://wyld.lr-digital.de/api/booked');
		$prompt['content'] = $prompt['content'] . "\n" . json_encode($rssData);

		return $prompt;

	}

	private function compare_article_scores($prompt) {

		if (PORTAL == 'MOZ') {$rssData = $this->curl('https://reports-moz.lr-digital.de/export/articles/score');}
		if (PORTAL == 'SWP') {$rssData = $this->curl('https://reports-swp.lr-digital.de/export/articles/score');}
		else {$rssData = $this->curl('https://reports.lr-digital.de/export/articles/score');}
		$prompt['content'] = $prompt['content'] . "\n" . json_encode($rssData);

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

	private function json_feed_lr($prompt) {

		$rssData = $this->curl('https://epreader.lr-digital.de/excerpt');
		
		$date = 'Das aktuelle Datum ist: ' . date('d.m.Y H:i');
		$prompt['content'] = $prompt['content'] . "\n" . $date;
		$prompt['content'] = $prompt['content'] . "\n" . json_encode($rssData);

		return $prompt;

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
