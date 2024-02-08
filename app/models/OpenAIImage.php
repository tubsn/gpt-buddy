<?php

namespace app\models;
use Orhanerday\OpenAi\OpenAi;
use flundr\utility\Session;

class OpenAIImage 
{

	public function __construct() {}

	public function fetch($prompt, $options = null) {

		$resolution = '1792x1024';
		$quality = 'standard';
		$style = 'vivid';

		if ($options) {
			if (isset($options['resolution'])) {$resolution = $options['resolution'] ?? $resolution;}
			if (isset($options['quality'])) {$quality = $options['quality'] ?? $quality;}
			if (isset($options['style'])) {$style = $options['style'] ?? $style;}
		}

		$open_ai = new OpenAi(CHATGPTKEY);

		$complete = $open_ai->image([
			'model' => 'dall-e-3',
			'prompt' => $prompt,
			'n' => 1, // Number of Images
			'quality' => $quality,
			'style' => $style,
			'size' => $resolution,
			'response_format' => 'b64_json',
		]);

		$out = json_decode($complete,1);

		if (isset($out['error'])) {
			throw new \Exception($out['error']['message'], 400);
		}

		$path = $this->save_file($out['data'][0]['b64_json'], $prompt);
		return $path;

	}


	private function save_file($base64SJson, $prompt) {
		$imagedata = base64_decode($base64SJson);
		$image = imagecreatefromstring($imagedata);

		$filename = uniqid() . '.jpg';
		$path = PUBLICFOLDER . 'generated/';

		if (!file_exists($path)) {mkdir($path, 0777, true);}
		$file = $path . $filename;

		imagejpeg($image, $file, 80);
		//file_put_contents($file,$imagedata);

		$this->add_prompt_to_file($file, $prompt);
		
		return '/generated/' . $filename;
	}


	public function add_prompt_to_file($file, $prompt) {

		$prompt = strip_tags($prompt);
		$prompt = htmlentities($prompt);

		$comment = '--PROMPT--' . strip_tags($prompt);
		$imgWithPrompt = iptcembed($comment, $file);
		file_put_contents($file, $imgWithPrompt);		
	}

}
