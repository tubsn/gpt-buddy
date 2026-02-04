<?php

namespace app\models;

class Json_Adapter
{

	// This is an Example Class for a base article Importer

	public function get_by_url($url) {

		$articleID = $url; // You might need to extract the article ID here

		$url = 'www.my-api.de/api/' . $articleID;
		$curlData = $this->curl_with_redirect($url);

		$url = $curlData['url'];
		$data = $curlData['data'];

		/* $data contains the API response.
		This method must return an array that includes a 'content' key containing the raw article text, e.g.:
		$data['content'] = 'Raw article text ...';

		If the API returns a different structure, map/transform the response here so that 'content'
		always contains the raw text. */

		return $data;

	}


	private function curl_with_redirect($url) {

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