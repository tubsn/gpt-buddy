<?php

namespace app\models;

class RSS_Adapter
{

	public $portalURL = 'https://www.lr-online.de';

	function __construct() {}

	public function get_by_id($articleID = null) {

		if (PORTAL == 'MOZ') {$this->portalURL = 'https://www.moz.de';}
		if (PORTAL == 'SWP') {$this->portalURL = 'https://www.swp.de';}

		$url = $this->portalURL . '/' . $articleID . '?_XML=RSS';
		$curlData = $this->curl_with_redirect($url);

		$url = $curlData['url'];
		$rssData = $curlData['data'];

		return $this->convert_news_markup($rssData, $url);

	}

	public function indexpage() {

		$curlData = $this->curl_with_redirect($this->portalURL . '?_XML=rss')['data'] ;
		$xml = simplexml_load_string($curlData);

		$articleFeed = $xml->channel->item;

		$articles = [];

		foreach ($articleFeed as $article) {
			$item['kicker'] = $article->kicker->__toString();
			$item['date'] = $article->pubDate->__toString();
			$item['title'] = $article->title->__toString();
			$item['author'] = $article->author->__toString();
			$item['description'] = $article->description->__toString();
			$item['url'] = $article->link->__toString();
			$item['freemium'] = $article->freemium->__toString();
			$item['thumb'] = $article->enclosure['url']->__toString();
			$item['id'] = $this->extract_id($item['url']);
			array_push($articles,$item);
		}

		return $articles;

	}

	public function convert_news_markup($data, $url) {

		$xml = simplexml_load_string($data);
		if (!$xml) {return null;}

		$components = $xml->NewsItem->NewsComponent;

		$article['id'] = $this->extract_id($url);
		$article['ressort'] = $this->extract_ressort($url);
		//$newsMLRessort = $components[0]->DescriptiveMetadata->xpath('Property[@FormalName="Department"]')[0]['Value']->__toString();

		$article['title'] = $components[0]->NewsLines->HeadLine->__toString();
		$article['kicker'] = null; // Not available in Detail RSS :/
		$article['description'] = $components[0]->NewsLines->SubHeadLine->__toString();
		$article['author'] = $components[0]->AdministrativeMetadata->xpath('Property[@FormalName="Author"]')[0]['Value']->__toString();
		$article['plus'] = $xml->NewsItem->NewsManagement->accessRights->__toString()  == 'available to subscribers only' ? true : false;


		$bodycontent = 'body.content';
		$article['content'] = $xml->NewsItem->NewsComponent->ContentItem->DataContent->nitf->body->$bodycontent->__toString();

		$article['content'] = str_replace('data-src=', 'src=', $article['content']);

		// This is the last Edit timestamp - Pubdate is not available
		//$timestamp = $xml->NewsItem->NewsManagement->ThisRevisionCreated->__toString();
		$timestamp = $xml->NewsItem->Identification->NewsIdentifier->DateId->__toString();
		$article['pubdate'] = date('Y-m-d H:i:s', strtotime($timestamp));

		if (isset($xml->NewsItem->xpath('NewsComponent[@Duid="leadImage"]//NewsComponent')[0]->ContentItem['Href'])) {
			$article['image'] = $xml->NewsItem->xpath('NewsComponent[@Duid="leadImage"]//NewsComponent')[0]->ContentItem['Href']->__toString();
		}
		else {$article['image'] = null;}

		$article['link'] = substr($url,0,-9);;

		return $article;

	}


	private function extract_id($url) {
		// Regex search for the ID = -8Digits.html
		$searchPattern = "/-(\d{8}).html/";
		preg_match($searchPattern, $url, $matches);
		return $matches[1]; // First Match should be the ID
	}

	private function extract_ressort($url) {

		$path = parse_url($url, PHP_URL_PATH);
		$path = trim ($path, '/');
		$paths = explode('/',$path);

		$paths = array_filter($paths, function($path) {
			return strpos($path,'.html') == false ;
		});

		if ($paths[0] == 'lausitz') {
			return $paths[1];
		}

		if (isset($paths[1]) && $paths[1] == 'sport') {
			return $paths[1];
		}

		return $paths[0];

	}

	private function get_image($enclosureURL) {
		// toString throws Fatal Error if cast on null
		if ($enclosureURL) {
			return $enclosureURL->__toString();
		}
		return null;
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
