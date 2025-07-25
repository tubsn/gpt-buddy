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

		try {
			$htmlData = $this->retrieve_url_data($url);	
		} catch (Exception $e) {
			return $e->getMessage();
		}
		
		if (empty($htmlData)) {return 'Kein Inhalt erkannt';}
		if (!$this->is_html($htmlData)) {return $htmlData;}

		$htmlData = $this->force_utf8($htmlData);

		$doc = new DOMDocument();
		$doc->loadHTML($htmlData);
		$xpath = new DOMXPath($doc);

		// Removes all Script Containers
		$scriptTags = $doc->getElementsByTagName('script');
		while ($scriptTags->length > 0) {
			$scriptTags->item(0)->parentNode->removeChild($scriptTags->item(0));
		}

		$xmlNodes = $xpath->query("//*[contains(@class, '$selector')]");

		if (count($xmlNodes) > 1) {

			$out = '';
			foreach ($xmlNodes as $node) {
				$out .= $node->textContent;
				$out .= "\n\n";
			}

			return trim($out);
		}

		return $doc->saveHTML($xmlNodes->item(0));

	}

	public function by_class_plain($url, $selector = null, int $maxLength = 0) {
		$raw = $this->by_class($url, $selector);
		$plain = $this->make_plain($raw);
		if ($maxLength > 0 && (strlen($plain) > $maxLength)) {
			$plain = substr($plain, 0, $maxLength) . ' ...';
		}
		return $plain;
	}

	private function make_plain($html) {
		$html = strip_tags($html);
		$html = preg_replace('/\s+/', ' ', trim($html)); // Remove Spaces
		return $html;
	}

	private function force_utf8($html) {
		if (stripos($html, '<meta charset=') === false) {
			if (stripos($html, '<head>') !== false) {
				$html = preg_replace(
					'/<head>/i',
					'<head><meta charset="UTF-8">',
					$html,
					1
				);
			}
			else {$html = '<meta charset="UTF-8">' . $html;}
		}
		return $html;
	}

	private function retrieve_url_data($url) {

		if (!preg_match('/^https?:\/\//', $url)) {
			$url = 'https://' . $url;
		}

		if (!$this->validate($url)) {throw new Exception("URL to Scrape has Invalid format", );}

		$options = [
			'headers' => ['Accept-Charset' => 'utf-8']
		];

		$httpClient = new Client();
		$response = $httpClient->get($url, $options);
		$htmlString = $response->getBody();

		return $htmlString;

	}

	private function is_html($string) {
		return preg_match('/<\s*html.*?>/i', $string) === 1;
	}

	private function is_json($string) {
		json_decode($string);
		return (json_last_error() === JSON_ERROR_NONE);
	}

	private function validate($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}


}
