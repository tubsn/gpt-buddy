<?php

namespace app\models;

class BNNTickerAdapter
{

	private $apiURL = 'https://edit.storytile.net/api/public/v2/';

	function __construct() {}

	public function get_by_id($tickerID) {

		$auth = '?client=' . BNNTICKERCLIENTID . '&token=' . BNNTICKERTOKEN;
		$event = '&event=' . $tickerID;

		$url = $this->apiURL . 'event' . $auth . $event;

		$curlData = $this->curl_with_redirect($url);

		$url = $curlData['url'];
		$tickerData = json_decode($curlData['data'],1);

		if ($tickerData['state'] != 200) {
			return 'Ticker konnte nicht importiert werden';
		}

		$tickertext = $this->extract_content($tickerData['items']);
		return $tickertext;
	}


	public function extract_content($ticker) {
		$ticker = array_map(function($entry) {
			$out = $entry['text'] ?? '';

			if (isset($entry['gametime']) && !empty($entry['gametime'])) {
				$out = 'Minute ' . $entry['gametime'] . ' ' . $out;
			}

			if (isset($entry['player_in'])) {
				$out = $out . ' eingewechselt: ' . $entry['player_in'];
			}

			if (isset($entry['player_out'])) {
				$out = $out . ' ausgewechselt: ' . $entry['player_out'];
			}

			return $out . "\n";
		}, $ticker);
		return array_reverse($ticker);
	}



	private function curl_with_redirect($url) {

		//curl_setopt ($ch, CURLOPT_POST, 1);
		//curl_setopt ($ch, CURLOPT_POSTFIELDS, $jsonData);

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
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
