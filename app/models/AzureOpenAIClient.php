<?php

namespace app\models;

class AzureOpenAIClient 
{

	private $apiKey;
	private $apiURL;

	public function __construct($modelMeta = null) {

		if (empty($modelMeta['url'])) {throw new \Exception("Azure Model URL Missing in Config ", 400);}
		if (!defined('AZUREKEY')) {throw new \Exception("AZUREKEY is not defined in .env", 400);}

		$this->apiKey = AZUREKEY;
		$this->apiURL = $modelMeta['url'];
	}

	public function chat($options, callable $callback) {

		$ch = curl_init($this->apiURL);
		$payload = json_encode($options);

		curl_setopt_array($ch, [
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer {$this->apiKey}",
				"Content-Type: application/json",
			],
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $payload,
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_WRITEFUNCTION => function($ch, $data) use ($callback) {
				$info = curl_getinfo($ch);
				return $callback($info, $data);
			}
		]);

		curl_exec($ch);
		curl_close($ch);
	}


}
