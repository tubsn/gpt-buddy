<?php

namespace app\models;

use Orhanerday\OpenAi\OpenAi;

class OpenAIImage
{
	public function __construct() {}

	public function fetch($prompt, $options = null)	{
		$model = 'gpt-image-2';
		$resolution = '1536x1024';
		$quality = 'medium';
		$background = 'auto';
		$image = '';

		if (is_array($options)) {
			if (isset($options['resolution'])) { $resolution = $options['resolution'] ?? $resolution; }
			if (isset($options['quality'])) { $quality = $options['quality'] ?? $quality; }
			if (isset($options['background'])) { $background = $options['background'] ?? $background; }
			if (isset($options['image'])) { $image = $options['image'] ?? $image; }
		}

		$openAiClient = new OpenAi(CHATGPTKEY);

		$generatorOptions = [
			'model' => $model,
			'prompt' => $prompt,
			'n' => 1,
			'quality' => $quality,
			'moderation' => 'low',
			'background' => $background,
			'size' => $resolution,
		];

		$temporaryFilePath = null;

		try {
			if (!empty($image)) {
				$sanitizedImage = $this->sanitize_image_url($image);
				$curlFile = $this->create_curl_file($sanitizedImage, $temporaryFilePath);

				$generatorOptions['image'] = $curlFile;
				$completeResponse = $openAiClient->imageEdit($generatorOptions);
			} else {
				$completeResponse = $openAiClient->image($generatorOptions);
			}
		} finally {
			if (!empty($temporaryFilePath) && file_exists($temporaryFilePath)) {
				@unlink($temporaryFilePath);
			}
		}

		$decodedResponse = json_decode($completeResponse, true);

		if (isset($decodedResponse['error'])) {
			throw new \Exception($decodedResponse['error']['message'], 400);
		}

		$path = $this->save_file($decodedResponse['data'][0]['b64_json'], $prompt);
		return $path;
	}


	private function create_curl_file(string $imagePathOrUrl, ?string &$temporaryFilePath = null): \CURLFile {
		$localImagePath = $imagePathOrUrl;

		if ($this->is_remote_url($imagePathOrUrl)) {
			$localImagePath = $this->download_url_to_tempFile($imagePathOrUrl);
			$temporaryFilePath = $localImagePath;
		}

		if (!file_exists($localImagePath) || !is_readable($localImagePath)) {
			throw new \Exception('Image file not found or not readable: ' . $localImagePath, 400);
		}

		$mimeType = $this->detect_mime_type($localImagePath);
		$fileName = basename($localImagePath);

		return new \CURLFile($localImagePath, $mimeType, $fileName);
	}

	private function is_remote_url(string $value): bool {
		return (bool)preg_match('~^https?://~i', trim($value));
	}

	private function download_url_to_tempFile(string $url): string {
		$temporaryFilePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR
			. 'openai_image_'
			. uniqid('', true);

		$curlHandle = curl_init($url);
		if ($curlHandle === false) {
			throw new \Exception('Failed to init curl for image download', 500);
		}

		curl_setopt_array($curlHandle, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_SSL_VERIFYPEER => true,
		]);

		$fileContent = curl_exec($curlHandle);
		$httpCode = (int)curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

		if ($fileContent === false || $httpCode >= 400) {
			$curlError = curl_error($curlHandle);
			curl_close($curlHandle);
			throw new \Exception('Failed to download image. HTTP: ' . $httpCode . ' Error: ' . $curlError, 400);
		}

		curl_close($curlHandle);

		file_put_contents($temporaryFilePath, $fileContent);
		return $temporaryFilePath;
	}

	private function detect_mime_type(string $filePath): string {
		if (function_exists('finfo_open')) {
			$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($fileInfo) {
				$mimeType = finfo_file($fileInfo, $filePath);
				finfo_close($fileInfo);
				if (!empty($mimeType)) {
					return $mimeType;
				}
			}
		}

		return 'application/octet-stream';
	}

	private function sanitize_image_url($url) {
		$url = trim($url);

		if (!preg_match('~^https?://~', $url)) {
			return PUBLICFOLDER . ltrim($url, '/');
		}

		if (strpos($url, '.localhost') !== false) {
			$parsedPath = parse_url($url, PHP_URL_PATH);
			return PUBLICFOLDER . ltrim((string)$parsedPath, '/');
		}

		return $url;
	}

	private function save_file($base64SJson, $prompt) {
		$imageData = base64_decode($base64SJson);
		$gdImage = imagecreatefromstring($imageData);

		$filename = uniqid() . '.jpg';
		$path = PUBLICFOLDER . 'generated/';

		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}

		$file = $path . $filename;

		imagejpeg($gdImage, $file, 80);

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