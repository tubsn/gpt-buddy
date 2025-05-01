<?php

namespace app\models;
use Orhanerday\OpenAi\OpenAi;
use flundr\utility\Session;

class OpenAIImage 
{

	public function __construct() {}

	public function fetch($prompt, $options = null) {

		$resolution = '1536x1024';
		$quality = 'medium';
		$background = 'auto';
		$image = '';

		if ($options) {
			if (isset($options['resolution'])) {$resolution = $options['resolution'] ?? $resolution;}
			if (isset($options['quality'])) {$quality = $options['quality'] ?? $quality;}
			if (isset($options['background'])) {$background = $options['background'] ?? $background;}
			if (isset($options['image'])) {$image = $options['image'] ?? $image;}
		}

		$open_ai = new OpenAi(CHATGPTKEY);

		$generatorOptions = [
			'model' => 'gpt-image-1',
			'prompt' => $prompt,
			'n' => 1, // Number of Images
			'quality' => $quality,
			'moderation' => 'low',
			'background' => $background,
			'size' => $resolution,
		];

		if ($image) {

			$image = $this->sanitize_image_url($image);
			//dd($image);

			$generatorOptions['image'] = curl_file_create($image, 'image/jpeg');

			$complete = $open_ai->imageEdit($generatorOptions);
		} else {
			$complete = $open_ai->image($generatorOptions);
		}

		$out = json_decode($complete,1);

		if (isset($out['error'])) {
			throw new \Exception($out['error']['message'], 400);
		}

		$path = $this->save_file($out['data'][0]['b64_json'], $prompt);
		return $path;

	}

	private function sanitize_image_url($url) {
		if (!preg_match('~^https?://~', $url)) {
			return PUBLICFOLDER . ltrim($url, '/');
		}

		if (strpos($url, '.localhost') !== false) {
			$parsed = parse_url($url, PHP_URL_PATH);
			return PUBLICFOLDER . ltrim($parsed, '/');
		}
		return $url;
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
