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

	public function tts($input, $voice = 'nova', $model = 'tts-1', $instructions = null) {
		
		$voice = strtolower($voice);
		$availableVoices = ['alloy', 'ash', 'coral', 'echo',
		 'fable', 'onyx', 'nova', 'sage', 'shimmer'];
		if (!in_array($voice, $availableVoices)) {
			throw new \Exception("$voice is not available as Voice");
		}

		$availableModels = ['tts-1', 'tts-1-hd', 'gpt-4o-mini-tts', 'gpt-audio-mini', 'gpt-audio'];
		if (!in_array($model, $availableModels)) {
			throw new \Exception("$model is not available as Model");
		}

		$options = [
			'model' => $model,
			'input' => $input,
			'voice' => $voice,
		];

		if (str_contains($model, 'gpt-audio')) {
			$options = [
				'model' => $model,
				'messages' => [['role' => 'user', 'content' => $input]],
				'audio' => ['voice' => $voice, 'format' => 'mp3'],
				'modalities' => ['text','audio'],
			];
		}

		if (!empty($instructions)) {
			$instructions = strip_tags($instructions);
			$options['instructions'] = $instructions;
		}

		$open_ai = new OpenAi(CHATGPTKEY);
		if (!str_contains($model, 'gpt-audio')) {
			$result = $open_ai->tts($options);
			$decoded = json_decode($result, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$error = $decoded['error']['message'] ?? 'Processing Error';
				throw new \Exception("Audio Processing: " . $error, 400);
			}
		}
		else {
			$result = $open_ai->chat($options);
			$result = json_decode($result,1);

			if (isset($result['error'])) {
				throw new \Exception("Audio Processing: " . $result['error']['message'], 400);
			}

			$result = $result['choices'][0]['message']['audio']['data'];
			$result = base64_decode($result);
		}

		$filename = date('Y-m-d-H-i') . '-' . bin2hex(random_bytes(4)) . '.mp3';
		$folder = 'audio'. DIRECTORY_SEPARATOR . 'tts' . DIRECTORY_SEPARATOR;

		$path = PUBLICFOLDER . $folder;
		if (!is_dir($path)) {mkdir($path, 0777, true);}

		$file = $path . $filename;
		file_put_contents($file, $result);

		return '/' . str_replace('\\', '/', $folder . $filename);

	}

}
