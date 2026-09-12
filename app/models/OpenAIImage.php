<?php

namespace app\models;

class OpenAIImage {

	public function __construct() {}

	public function get_options() {
		return [
			'model' => [
				'label' => 'Modell',
				'options' => [
					'gpt-image-2.5-flare' => 'GPT-Flare',
					'gpt-image-2.5-sunburst' => 'GPT-Sunburst',
				],
			],
			'quality' => [
				'label' => 'Qualität',
				'options' => [
					'low' => 'Niedrig',
					'medium' => 'Normal',
					'high' => 'Hoch',
					'max' => 'Max',
				],
			],
			
			'resolution' => [
				'label' => 'Format/Auflösung',
				'options' => [
					'1536x1024' => 'Quer (1536×1024)',
					'2560x1440' => 'Quer (2560×1440)',
					'1024x1536' => 'Hoch (1024×1536)',
					'1440x2560' => 'Hoch (1440×2560)',
					'1024x1024' => 'Quadrat (1024×1024)',
					'1920x1920' => 'Quadrat (1920×1920)',
				],
			],
			'background' => [
				'label' => 'Transparenz',
				'options' => [
					'opaque' => 'Ausgefüllt',
					'transparent' => 'Transparent',
				],
			],
		];
	}

	public function resolve_options($options = null) {
		$options = is_array($options) ? $options : [];
		$resolvedOptions = [];

		foreach ($this->get_options() as $fieldName => $field) {
			$selectedValue = $options[$fieldName] ?? null;

			$resolvedOptions[$fieldName] = (
				is_string($selectedValue) &&
				array_key_exists($selectedValue, $field['options'])
			)
				? $selectedValue
				: array_key_first($field['options']);
		}

		return $resolvedOptions;
	}

	public function fetch($prompt, $options = null) {
		$options = is_array($options) ? $options : [];
		$resolvedOptions = $this->resolve_options($options);

		$images = $options['images'] ?? [];

		// Kompatibilität mit bisherigen Aufrufen.
		if (!$images && !empty($options['image'])) {
			$images = [$options['image']];
		}

		if (!is_array($images) || count($images) > 10) {
			throw new \Exception('Maximal 10 Referenzbilder erlaubt.', 400);
		}

		$imagePaths = [];

		foreach ($images as $image) {
			if (!is_string($image) || trim($image) === '') {
				throw new \Exception('Ungültiger Bildpfad.', 400);
			}

			$imagePaths[] = $this->get_allowed_image_path($image);
		}

		$imagePaths = array_values(array_unique($imagePaths));

		$outputFormat = $resolvedOptions['background'] === 'transparent'
			? 'png'
			: 'jpeg';

		$generatorOptions = [
			'model' => $resolvedOptions['model'],
			'prompt' => $prompt,
			'n' => 1,
			'quality' => $resolvedOptions['quality'],
			'background' => $resolvedOptions['background'],
			'size' => $resolvedOptions['resolution'],
			'output_format' => $outputFormat,
		];

		if ($imagePaths) {
			$completeResponse = $this->request_image_edit($generatorOptions, $imagePaths);
		} else {
			$generatorOptions['moderation'] = 'low';
			$completeResponse = $this->request_image_generation($generatorOptions);
		}

		$decodedResponse = json_decode($completeResponse, true);

		if (!is_array($decodedResponse)) {
			throw new \Exception('Ungültige JSON-Antwort von OpenAI.', 502);
		}

		if (!empty($decodedResponse['error']['message'])) {
			throw new \Exception($decodedResponse['error']['message'], 400);
		}

		if (empty($decodedResponse['data'][0]['b64_json'])) {
			throw new \Exception('OpenAI hat keine Bilddaten geliefert.', 502);
		}

		return $this->save_file(
			$decodedResponse['data'][0]['b64_json'],
			$prompt,
			$outputFormat
		);
	}

	private function request_image_generation($generatorOptions) {
		$requestBody = json_encode($generatorOptions);

		if ($requestBody === false) {
			throw new \Exception('OpenAI-Anfrage konnte nicht kodiert werden.', 500);
		}

		return $this->send_request(
			'https://api.openai.com/v1/images/generations',
			['Content-Type: application/json'],
			$requestBody
		);
	}

	private function request_image_edit($generatorOptions, $imagePaths) {
		$formFields = $generatorOptions;

		foreach ($imagePaths as $imageIndex => $imagePath) {
			$formFields['image[' . $imageIndex . ']'] = new \CURLFile(
				$imagePath,
				$this->detect_mime_type($imagePath),
				basename($imagePath)
			);
		}

		return $this->send_request(
			'https://api.openai.com/v1/images/edits',
			[],
			$formFields
		);
	}

	private function send_request($requestUrl, $headers, $requestBody) {
		$curlHandle = curl_init($requestUrl);

		if ($curlHandle === false) {
			throw new \Exception('OpenAI-Anfrage konnte nicht gestartet werden.', 500);
		}

		curl_setopt_array($curlHandle, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $requestBody,
			CURLOPT_HTTPHEADER => array_merge([
				'Authorization: Bearer ' . CHATGPTKEY,
				'Accept: application/json',
			], $headers),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 20,
			CURLOPT_TIMEOUT => 180,
		]);

		$responseBody = curl_exec($curlHandle);
		$curlError = curl_error($curlHandle);
		$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

		if ($responseBody === false) {
			throw new \Exception('OpenAI-Anfrage fehlgeschlagen: ' . $curlError, 502);
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			$decodedResponse = json_decode($responseBody, true);
			$errorMessage = $decodedResponse['error']['message'] ?? 'Unbekannter API-Fehler';

			throw new \Exception(
				'OpenAI API (' . $httpCode . '): ' . $errorMessage,
				502
			);
		}

		return $responseBody;
	}

	private function get_allowed_image_path($imagePath) {
		$parsedUrlPath = parse_url($imagePath, PHP_URL_PATH);

		if (!is_string($parsedUrlPath) || $parsedUrlPath === '') {
			throw new \Exception('Ungültiger Bildpfad.', 400);
		}

		$publicDirectory = realpath(PUBLICFOLDER);

		if ($publicDirectory === false) {
			throw new \Exception('Public-Verzeichnis nicht gefunden.', 500);
		}

		$requestedPath = realpath(
			$publicDirectory . DIRECTORY_SEPARATOR . ltrim($parsedUrlPath, '/')
		);

		if ($requestedPath === false) {
			throw new \Exception('Bilddatei nicht gefunden.', 400);
		}

		$allowedDirectories = [
			realpath($publicDirectory . '/uploads'),
			realpath($publicDirectory . '/generated'),
		];

		$isAllowedPath = false;

		foreach ($allowedDirectories as $allowedDirectory) {
			if ($allowedDirectory === false) {
				continue;
			}

			$directoryPrefix = rtrim($allowedDirectory, DIRECTORY_SEPARATOR)
				. DIRECTORY_SEPARATOR;

			if (strpos($requestedPath, $directoryPrefix) === 0) {
				$isAllowedPath = true;
				break;
			}
		}

		if (!$isAllowedPath || !is_file($requestedPath) || !is_readable($requestedPath)) {
			throw new \Exception('Bildpfad nicht erlaubt oder Datei nicht lesbar.', 400);
		}

		if (filesize($requestedPath) > 25 * 1024 * 1024) {
			throw new \Exception('Ein Referenzbild darf maximal 25 MB groß sein.', 400);
		}

		$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

		if (!in_array($this->detect_mime_type($requestedPath), $allowedMimeTypes, true)) {
			throw new \Exception('Nicht unterstütztes Bildformat.', 400);
		}

		return $requestedPath;
	}

	private function detect_mime_type($filePath) {
		if (!class_exists('\finfo')) {
			return 'application/octet-stream';
		}

		$fileInfo = new \finfo(FILEINFO_MIME_TYPE);

		return $fileInfo->file($filePath) ?: 'application/octet-stream';
	}

	private function save_file($base64Json, $prompt, $outputFormat) {
		$imageData = base64_decode($base64Json, true);

		if ($imageData === false) {
			throw new \Exception('Ungültige Bilddaten empfangen.', 502);
		}

		$imageInformation = getimagesizefromstring($imageData);
		$expectedMimeType = $outputFormat === 'png' ? 'image/png' : 'image/jpeg';

		if (
			$imageInformation === false ||
			($imageInformation['mime'] ?? '') !== $expectedMimeType
		) {
			throw new \Exception('Ungültiges Bildformat empfangen.', 502);
		}

		$fileExtension = $outputFormat === 'png' ? 'png' : 'jpg';
		$timestamp = date('ymdHis') . sprintf('%06d', (microtime(true) * 1000000) % 1000000);
		$randomPart = bin2hex(random_bytes(2));
		$filename = 'ai_' . $timestamp . '_' . $randomPart . '.' . $fileExtension;

		$directoryPath = rtrim(PUBLICFOLDER, '/\\') . '/generated/';

		if (!is_dir($directoryPath)) {
			if (!mkdir($directoryPath, 0775, true) && !is_dir($directoryPath)) {
				throw new \Exception('Bildverzeichnis konnte nicht erstellt werden.', 500);
			}
		}

		$filePath = $directoryPath . $filename;

		if (file_put_contents($filePath, $imageData, LOCK_EX) === false) {
			throw new \Exception('Bild konnte nicht gespeichert werden.', 500);
		}

		if ($outputFormat === 'jpeg') {
			$this->add_prompt_to_file($filePath, $prompt);
		}

		return '/generated/' . $filename;
	}

	public function add_prompt_to_file($filePath, $prompt) {
		if (!function_exists('iptcembed')) {
			return;
		}

		// Bestehendes Metadatenformat beibehalten.
		$cleanPrompt = htmlentities(strip_tags($prompt), ENT_QUOTES, 'UTF-8');
		$imageWithPrompt = iptcembed('--PROMPT--' . $cleanPrompt, $filePath);

		if ($imageWithPrompt !== false) {
			file_put_contents($filePath, $imageWithPrompt, LOCK_EX);
		}
	}
}