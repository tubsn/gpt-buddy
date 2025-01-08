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

	public function tts($input, $voice = 'nova') {
		
		$voice = strtolower($voice);
		$availableVoices = ['echo', 'fable', 'onyx', 'nova', 'shimmer'];
		if (!in_array($voice, $availableVoices)) {
			throw new \Exception("$voice is not Available as Voice");
		}

		$open_ai = new OpenAi(CHATGPTKEY);
		$result = $open_ai->tts([
			'model' => 'tts-1', // tts-1-hd
			'input' => $input,
			'voice' => $voice,
		]);

		$filename = date('Y-m-d-H-i') . '-' . bin2hex(random_bytes(4)) . '.mp3';
		$folder = 'audio'. DIRECTORY_SEPARATOR . 'tts' . DIRECTORY_SEPARATOR;

		$path = PUBLICFOLDER . $folder;
		if (!is_dir($path)) {mkdir($path, 0777, true);}

		$file = $path . $filename;
		file_put_contents($file, $result);

		return '/' . str_replace('\\', '/', $folder . $filename);

	}

}
