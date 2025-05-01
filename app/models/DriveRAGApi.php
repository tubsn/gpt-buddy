<?php

namespace app\models;

class DriveRAGApi
{

	private $apiURL = DRIVE_API_URL;
	private $apiKey = DRIVE_API_KEY;

	public function __construct() {}

	public function search($query, $from = '-90 days', $to = 'today') {

		$minimalScore = 0;

		$from = date('Y-m-d', strtotime($from));
		$to = date('Y-m-d', strtotime($to));

		$query = $this->sanitize($query);

		$data = [
			"query" => $query,
			"algorithm" => "hybrid",
			"start_date" => $from,
			"end_date" => $to,
			"fields" => ['article_title', 'article_text'],
		];

		$response = $this->curl($this->apiURL . '/search', $data);
		$json = json_decode($response, true);

		if (empty($json['results'])) {throw new \Exception('Drive-API-Error: ' . $response, 500);}

		$data = $json['results'];

		if ($minimalScore > 0) {
			$data = array_filter($data, function($item) use ($minimalScore){
				return $item['score'] >= $minimalScore;
			});
		}

		$data = array_map(function($item) {
			$item['url'] = $item['urls'][0];
			unset($item['urls']);
			return $item;
		}, $data);

		return $data;
	}

	private function sanitize($query) {
		return htmlspecialchars(strip_tags(trim($query)), ENT_QUOTES, 'UTF-8');
	}

	private function curl($url, $data) {

		$headers = [
			'accept: application/json',
			'apikey: ' . $this->apiKey,
			'Content-Type: application/json'
		];

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($data));		

		$recievedData = curl_exec($ch);
		if ($recievedData === false) {
			dd(curl_error($ch));
		}

		$lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

		curl_close ($ch);

		if ($responseCode == 404) {
			throw new \Exception("Fehler beim Abrufen der URL", 404);
		}

		return $recievedData;

	}

}
