<?php

namespace app\models\mcp;
use flundr\utility\Log;
use flundr\utility\Session;

class DriveMixer
{

	private $apiURL = DRIVE_API_URL;
	private $apiKey = DRIVE_API_KEY;

	public function __construct() {}


	public function analytics($args) {

		$from = $args['from'];
		$to = $args['to'];

		$from = date('Y-m-d', strtotime($from)) ;
		$to = date('Y-m-d', strtotime($to));

		$data = [
			'limit' => 5,
			'start_date' => $from,
			'end_date' => $to,
			'article_locality' => 'All',
			'sort_by' => 'performance_score',
		];

		$response = $this->curl($this->apiURL . '/bigquery', $data);
		$json = json_decode($response, true);
		$json = $json['articles'];

		$json = array_map(
		function (array $entry): array {
			unset($entry['article_body']);
			return $entry;
		}, $json);

		/*
		$output = [];

		foreach ($json as $article) {
			$output[] = implode(PHP_EOL, [
				'Titel: ' . ($article['title'] ?? ''),
				'Datum: ' . ($article['date'] ?? ''),
				'Teaser: ' . ($article['article_teaser'] ?? ''),
				'URL: ' . ($article['article_url'] ?? ''),
				str_repeat('-', 80),
			]);
		}

		$output = implode(PHP_EOL . PHP_EOL, $output);
		*/

		return $json;

	}



	public function search($query, $from = '2000-01-01', $to = 'today', $limit = 10, $filters = null, $teasersOnly = false) {

		$minimalScore = 0;

		$from = date('Y-m-d', strtotime($from)) . ' 00:00:00';
		$to = date('Y-m-d', strtotime($to)) . ' 23:59:59';
		$limit = intval($limit);

		$filters = $this->format_filters($filters);

		$query = $this->sanitize($query);

		$fields = ['article_title', 'article_teaser'];
		if (!$teasersOnly) {array_push($fields, 'article_text');}

		$settings = [
			'query' => $query,
			'algorithm' => 'hybrid',
			'start_date' => $from,
			'end_date' => $to,
			'limit' => $limit,			
			'fields' => $fields,
		];

		$settings = array_merge($settings,$filters);

		$response = $this->curl($this->apiURL . '/search', $settings);

		$json = json_decode($response, true);

		if (isset($json['status_code'])) {

			$error = $json['detail'] ?? 'Unknown';

			$details = json_decode($json['details'],1);
			$errors = json_decode($details,1);
			$errormessage = $errors[0]['msg'] ?? null;

			throw new \Exception('Drive-API-Error: ' . $error . ' - ' .$errormessage, 500);
		}

		//if (empty($json['results'])) {throw new \Exception('Drive-API-Error: ' . $response, 500);}

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


		// Api limit might not work all the time
		$data = array_slice($data, 0, $limit);

		Session::set('tooldata', $data);
		//Log::write(json_encode($settings));
		//Log::write(json_encode($data));

		return $data;
	}

	private function format_filters($parameters = null) {
		if (empty($parameters)) {return [];}

		$filters = [];

		if (isset($parameters['tags'])) {
			$tags = $parameters['tags'];
			$tags = explode_and_trim(',', $tags);
			$filters['tags'] = $tags;
		}

		if (isset($parameters['ressorts']) && !is_array($parameters['ressorts'])) {
			$parameters['ressorts'] = [$parameters['ressorts']];
		}

		if (isset($parameters['ressorts'])) {
			foreach ($parameters['ressorts'] as $key => $ressort) {
				$parameters['ressorts'][$key] = '"section": "' . $ressort . '"';
			}

			if (isset($filters['tags'])) {
				$filters['tags'] = array_merge($filters['tags'],$parameters['ressorts']);
			}
			else {
				$filters['tags'] = $parameters['ressorts'];
			}
		}

		if (isset($parameters['exact'])) {
			$filters['exact_search'] = $parameters['exact'];
		}	

		return ['filters' => $filters];

	}


	private function sanitize($query) {
		return htmlspecialchars(strip_tags(trim($query)), ENT_QUOTES, 'UTF-8');
	}

	private function curl($url, $data) {

		$apikey = $this->apiKey;
		if (auth_groups('Aachen')) {$apikey = DRIVE_API_KEY2;}

		$headers = [
			'accept: application/json',
			'apikey: ' . $apikey,
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
