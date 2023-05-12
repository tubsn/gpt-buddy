<?php

namespace app\models;
use Orhanerday\OpenAi\OpenAi;
use flundr\utility\Session;

class OpenAIImage 
{

	private $api;
	private $prompts;

	public $action;
	public $response;
	public $messages = [];
	public $fileCounter = 0;

	public function __construct() {}

	public function fetch($prompt) {

		$open_ai = new OpenAi(CHATGPTKEY);
		Session::set('imagehistory', $prompt);

		$complete = $open_ai->image([
			"prompt" => "$prompt",
			"n" => 3,
			"size" => "512x512",
			"response_format" => "b64_json",
		]);

		$out = json_decode($complete,1);

		if (isset($out['error'])) {
			throw new \Exception($out['error']['message'], 400);
		}

		//return $out['data'][0]['url'];

		foreach ($out['data'] as $set) {
			$this->save_file($set['b64_json']);
		}

	}


	private function save_file($base64SJson) {
		$imagedata = base64_decode($base64SJson);
		$file = PUBLICFOLDER . 'generated-image-' . $this->fileCounter . '.jpg';
		file_put_contents($file,$imagedata);
		$this->fileCounter++;

	}

	public function history() {
		return Session::get('imagehistory') ?? null;
	}

	public function wipe_history() {
		Session::unset('imagehistory');
	}

}
