<?php

namespace app\models;
use GuzzleHttp\Client;
use \DOMDocument;
use \DOMXPath;
use \Exception;

class Scrape
{

	public function __construct() {
		libxml_use_internal_errors(true); //Suppress libXML Warnings
	}

	public function by_class($url, $selector = null) {

		$htmlData = $this->retrieve_url_data($url);

		$doc = new DOMDocument();
		$doc->loadHTML($htmlData);
		$xpath = new DOMXPath($doc);

		$xmlNodes = $xpath->query("//*[contains(@class, '$selector')]");
		return $doc->saveHTML($xmlNodes->item(0));

	}


	private function retrieve_url_data($url) {

		if (!$this->validate($url)) {throw new Exception("URL to Scrape has Invalid format", );}

		$httpClient = new Client();
		$response = $httpClient->get($url);
		$htmlString = (string) $response->getBody();

		return $htmlString;

	}

	private function validate($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}


}
