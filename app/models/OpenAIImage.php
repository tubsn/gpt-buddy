<?php

namespace app\models;

class OpenAIImage
{
	public function __construct() {}

	public function fetch($prompt, $options = null) {
		$model = 'gpt-image-2';
		$resolution = '1536x1024';
		$quality = 'medium';
		$background = 'auto';
		$image = '';

		if (is_array($options)) {
			if (!empty($options['resolution'])) {
				$resolution = $options['resolution'];
			}

			if (!empty($options['quality'])) {
				$quality = $options['quality'];
			}

			if (!empty($options['background'])) {
				$background = $options['background'];
			}

			if (!empty($options['image'])) {
				$image = $options['image'];
			}
		}

		$generatorOptions = [
			'model' => $model,
			'prompt' => $prompt,
			'n' => 1,
			'quality' => $quality,
			'moderation' => 'low',
			'background' => $background,
			'size' => $resolution,
			'output_format' => 'jpeg',
		];

		if (!empty($image)) {
			$allowedImagePath = $this->get_allowed_image_path($image);
			$completeResponse = $this->request_image_edit($generatorOptions, $allowedImagePath);
		} else {
			$completeResponse = $this->request_image_generation($generatorOptions);
		}

		$decodedResponse = json_decode($completeResponse, true);

		if (!is_array($decodedResponse)) {
			throw new \Exception(
				'Invalid response from OpenAI: ' . json_last_error_msg(),
				500
			);
		}

		if (!empty($decodedResponse['error']['message'])) {
			throw new \Exception($decodedResponse['error']['message'], 400);
		}

		if (empty($decodedResponse['data'][0]['b64_json'])) {
			throw new \Exception('OpenAI did not return image data', 500);
		}

		return $this->save_file($decodedResponse['data'][0]['b64_json'], $prompt);
	}

	private function request_image_generation($generatorOptions) {
		$requestUrl = 'https://api.openai.com/v1/images/generations';

		$requestBody = json_encode($generatorOptions);

		if ($requestBody === false) {
			throw new \Exception('Could not encode OpenAI request', 500);
		}

		return $this->send_request(
			$requestUrl,
			[
				'Content-Type: application/json',
			],
			$requestBody
		);
	}

	private function request_image_edit($generatorOptions, $imagePath) {
		$requestUrl = 'https://api.openai.com/v1/images/edits';
		$mimeType = $this->detect_mime_type($imagePath);

		$formFields = [
			'model' => $generatorOptions['model'],
			'prompt' => $generatorOptions['prompt'],
			'n' => $generatorOptions['n'],
			'quality' => $generatorOptions['quality'],
			'background' => $generatorOptions['background'],
			'size' => $generatorOptions['size'],
			'output_format' => $generatorOptions['output_format'],
			'image' => new \CURLFile(
				$imagePath,
				$mimeType,
				basename($imagePath)
			),
		];

		return $this->send_request(
			$requestUrl,
			[],
			$formFields
		);
	}

	private function send_request($requestUrl, $headers, $requestBody) {
		$curlHandle = curl_init($requestUrl);

		if ($curlHandle === false) {
			throw new \Exception('Could not initialize OpenAI request', 500);
		}

		$requestHeaders = array_merge(
			[
				'Authorization: Bearer ' . CHATGPTKEY,
				'Accept: application/json',
			],
			$headers
		);

		curl_setopt($curlHandle, CURLOPT_POST, true);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $requestBody);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $requestHeaders);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 180);

		$responseBody = curl_exec($curlHandle);
		$curlError = curl_error($curlHandle);
		$httpCode = (int)curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

		curl_close($curlHandle);

		if ($responseBody === false) {
			throw new \Exception('OpenAI request failed: ' . $curlError, 500);
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			$decodedResponse = json_decode($responseBody, true);
			$errorMessage = $decodedResponse['error']['message'] ?? $responseBody;

			throw new \Exception(
				'OpenAI API error (' . $httpCode . '): ' . $errorMessage,
				400
			);
		}

		return $responseBody;
	}

	private function get_allowed_image_path($imagePath) {
		$parsedUrlPath = parse_url((string)$imagePath, PHP_URL_PATH);

		if (empty($parsedUrlPath)) {
			throw new \Exception('Invalid image path', 400);
		}

		$publicDirectory = realpath(PUBLICFOLDER);

		if ($publicDirectory === false) {
			throw new \Exception('Public directory not found', 500);
		}

		$requestedPath = realpath(
			$publicDirectory . DIRECTORY_SEPARATOR . ltrim($parsedUrlPath, '/')
		);

		if ($requestedPath === false) {
			throw new \Exception('Image file not found', 400);
		}

		$allowedDirectories = [
			realpath(PUBLICFOLDER . 'uploads'),
			realpath(PUBLICFOLDER . 'generated'),
		];

		$isAllowedPath = false;

		foreach ($allowedDirectories as $allowedDirectory) {
			if ($allowedDirectory === false) {
				continue;
			}

			$allowedDirectoryPrefix = rtrim(
				$allowedDirectory,
				DIRECTORY_SEPARATOR
			) . DIRECTORY_SEPARATOR;

			if (strpos($requestedPath, $allowedDirectoryPrefix) === 0) {
				$isAllowedPath = true;
				break;
			}
		}

		if (!$isAllowedPath) {
			throw new \Exception('Image path is not allowed', 400);
		}

		if (!is_file($requestedPath) || !is_readable($requestedPath)) {
			throw new \Exception('Image file not found or not readable', 400);
		}

		$mimeType = $this->detect_mime_type($requestedPath);
		$allowedMimeTypes = [
			'image/jpeg',
			'image/png',
			'image/webp',
		];

		if (!in_array($mimeType, $allowedMimeTypes, true)) {
			throw new \Exception('Unsupported image type', 400);
		}

		return $requestedPath;
	}

	private function detect_mime_type($filePath) {
		if (!function_exists('finfo_open')) {
			return 'application/octet-stream';
		}

		$fileInfo = finfo_open(FILEINFO_MIME_TYPE);

		if ($fileInfo === false) {
			return 'application/octet-stream';
		}

		$mimeType = finfo_file($fileInfo, $filePath);

		if (empty($mimeType)) {
			return 'application/octet-stream';
		}

		return $mimeType;
	}

	private function save_file($base64Json, $prompt) {
		$imageData = base64_decode($base64Json, true);

		if ($imageData === false) {
			throw new \Exception('Invalid image data received', 500);
		}

		$gdImage = imagecreatefromstring($imageData);

		if ($gdImage === false) {
			throw new \Exception('Could not create image from response data', 500);
		}

		$filename = uniqid('generated_', true) . '.jpg';
		$directoryPath = PUBLICFOLDER . 'generated/';

		if (!file_exists($directoryPath)) {
			mkdir($directoryPath, 0775, true);
		}

		$filePath = $directoryPath . $filename;

		if (!imagejpeg($gdImage, $filePath, 80)) {
			imagedestroy($gdImage);
			throw new \Exception('Could not save generated image', 500);
		}

		imagedestroy($gdImage);

		$this->add_prompt_to_file($filePath, $prompt);

		return '/generated/' . $filename;
	}

	public function add_prompt_to_file($filePath, $prompt) {
		$cleanPrompt = strip_tags($prompt);
		$cleanPrompt = htmlentities($cleanPrompt, ENT_QUOTES, 'UTF-8');

		$comment = '--PROMPT--' . $cleanPrompt;
		$imageWithPrompt = iptcembed($comment, $filePath);

		if ($imageWithPrompt !== false) {
			file_put_contents($filePath, $imageWithPrompt);
		}
	}
}