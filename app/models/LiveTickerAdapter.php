<?php

namespace app\models;

class LiveTickerAdapter
{

	function __construct() {
		$this->portalURL = 'https://live.lr-digital.de/ticker/';
		$this->extension = '.json';
	}

	public function get_by_id($tickerID) {

		$url = $this->portalURL . $tickerID . $this->extension;
		$curlData = $this->curl_with_redirect($url);

		$url = $curlData['url'];
		$tickerData = $curlData['data'];

		return $this->summarize($tickerData);

	}

	public function summarize($tickerData) {

		$ticker = json_decode($tickerData,1);

		$infotext = strip_tags($ticker['ticker']['info']);


		$ticker['posts'] = array_filter($ticker['posts'], function($post) {
			if (!str_contains($post['time'], ':')) {
				return $post;
			}
		});

		$content = array_column($ticker['posts'],'content');


		$content = array_map(function($item) {
			if (empty($item)) {return $item;}
			return strip_tags($item);
		}, $content);

		$content = array_slice($content, 0, 50);

		$content = array_reverse($content);
		array_unshift($content, $infotext);

		return $content;

	}



	private function curl_with_redirect($url) {

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

		$recievedData = curl_exec($ch);
		if ($recievedData === false) {
			dd(curl_error($ch));
		}

		$lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

		curl_close ($ch);

		if ($responseCode == 404) {
			throw new \Exception("Artikel nicht gefunden oder kann nicht importiert werden", 404);
		}

		return ['data' => $recievedData, 'url' => $lastUrl];

	}



}
