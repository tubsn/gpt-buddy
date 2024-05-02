<?php

namespace app\models;

class Json_Adapter
{

	public $portalURL = '';

	public function get_by_id($articleID = null) {

		$url = $this->portalURL . '/' . $articleID;
		$curlData = $this->curl_with_redirect($url);

		$url = $curlData['url'];
		$data = $curlData['data'];

		return $this->convert_input($data);

	}

	private function convert_input($data) {
		$out['content'] = $data['editor_text'];
		return $out;
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
