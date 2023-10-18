<?php

namespace app\models;
use Orhanerday\OpenAi\OpenAi;
use flundr\utility\Session;

class OpenAIWhisper 
{

	private $api;
	private $prompts;

	public $action;
	public $response;
	public $messages = [];
	public $fileCounter = 0;

	public function __construct() {}

	public function transcribe($file) {

		$open_ai = new OpenAi(CHATGPTKEY);
		
		$result = $open_ai->transcribe([
			"model" => "whisper-1",
			"file" => $file,
		]);

		$out = json_decode($result,1);

		if (isset($out['error'])) {
			throw new \Exception($out['error']['message'], 400);
		}

		if ($out['text']) {
			return $out['text'];
		}

	}

}
