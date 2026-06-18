<?php

namespace app\models;

class Transcriber
{
	public $apiKey = CHATGPTKEY;
	public $modelName = 'whisper-1';
	public $apiURL = 'https://api.openai.com/v1/audio/transcriptions';
	public $tempDir = PUBLICFOLDER . 'audio' . DIRECTORY_SEPARATOR . 'chunkwhisper';
	public $language = 'de';
	public $mimeType = 'audio/mpeg';
	public $transferFilename = 'transcribe.mp3';
	public $splittChunkSizeMB = 15;
	public $timeStamps = false;
	private $ffmpegPath;

	public function __construct() {
		$this->ffmpegPath = defined('FFMPEGPATH') ? FFMPEGPATH : 'ffmpeg';
		$this->resolve_external_model();
	}

	public function transcribe($inputFile) {

		if (!$this->ffmpeg_available()) {
			return $this->run_api_request($inputFile);
		}

		$compressedFile = $this->tempDir . DIRECTORY_SEPARATOR . 'compressed.ogg';
		$this->transferFilename = basename($compressedFile);
		$this->mimeType = 'audio/ogg';

		if (!$this->compress_audio($inputFile, $compressedFile)) {
			return 'Audio-Komprimierung fehlgeschlagen';
		}

		$chunks = $this->split_audio($compressedFile);

		if (!$chunks) {
			return 'Audio-Splitting fehlgeschlagen';
		}

		$result = '';

		foreach ($chunks as $chunkFile) {
			$chunkText = $this->run_api_request($chunkFile);

			if ($chunkText !== '') {
				$result .= $chunkText . "\n";
			}
		}

		return trim($result);
	}

	private function ffmpeg_available() {
		$outputLines = [];
		$returnCode = 1;

		$ffmpeglocation = escapeshellcmd($this->ffmpegPath);
		exec($ffmpeglocation . ' -version 2>&1', $outputLines, $returnCode);

		if ($returnCode === 0) {return true;} 
		return false;
	}

	public function compress_audio($inputFile, $outputFile) {
		$outputDir = dirname($outputFile);

		if (!is_dir($outputDir)) {
			mkdir($outputDir, 0777, true);
		}

		$command = sprintf(
			'%s -y -i %s -c:a libopus -b:a 12k -application voip %s',
			escapeshellcmd($this->ffmpegPath),
			escapeshellarg($inputFile),
			escapeshellarg($outputFile)
		);

		exec($command, $commandOutput, $returnCode);

		return $returnCode === 0;
	}

	public function split_audio($inputFile) {

		$chunkDir = $this->tempDir . DIRECTORY_SEPARATOR . 'chunks';
		if (!is_dir($chunkDir)) {mkdir($chunkDir, 0777, true);}

		$fileSize = filesize($inputFile);
		$chunkSize = $this->splittChunkSizeMB * 1024 * 1024;

		if ($fileSize <= ($chunkSize)) {
			return [$inputFile];
		}

		$duration = $this->get_audio_duration($inputFile);

		if ($duration === false) {
			return false;
		}

		$numberOfChunks = (int) ceil($fileSize / ($chunkSize));
		$chunkDuration = (int) ceil($duration / $numberOfChunks);

		$chunkFiles = [];

		for ($chunkIndex = 0; $chunkIndex < $numberOfChunks; $chunkIndex++) {
			$startSecond = $chunkIndex * $chunkDuration;
			$chunkFile = $chunkDir . DIRECTORY_SEPARATOR . 'chunk_' . $chunkIndex . '.ogg';

			$command = sprintf(
				'%s -y -i %s -ss %d -t %d -c copy %s',
				escapeshellcmd($this->ffmpegPath),
				escapeshellarg($inputFile),
				$startSecond,
				$chunkDuration,
				escapeshellarg($chunkFile)
			);

			exec($command, $commandOutput, $returnCode);

			if ($returnCode !== 0) {
				return false;
			}

			$chunkFiles[] = $chunkFile;
		}

		return $chunkFiles;
	}

	private function get_audio_duration($file) {
		$command = sprintf(
			'%s -i %s 2>&1',
			escapeshellcmd($this->ffmpegPath),
			escapeshellarg($file)
		);

		$output = shell_exec($command);

		if (preg_match('/Duration: (\d+):(\d+):(\d+\.\d+)/', $output, $matches)) {
			$seconds = ($matches[1] * 3600) + ($matches[2] * 60) + (float) $matches[3];
			return $seconds;
		}

		return false;
	}

	public function run_api_request($file) {

		$curlHandle = curl_init();

		$postFields = [
			'file' => new \CURLFile($file, $this->mimeType, $this->transferFilename),
			'model' => $this->modelName,
			'language' => $this->language,
			'response_format' => 'verbose_json',
			'timestamp_granularities[]' => ['segment', 'word'],
		];

		curl_setopt($curlHandle, CURLOPT_URL, $this->apiURL);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_POST, true);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer ' . $this->apiKey
		]);

		$response = curl_exec($curlHandle);

		if ($response === false) {
			$errorMessage = curl_error($curlHandle);
			curl_close($curlHandle);
			return 'Transcription cURL Fehler: ' . $errorMessage;
		}

		$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		curl_close($curlHandle);

		if ($httpCode !== 200) {
			return 'Transcription API Fehler: ' . $response;
		}

		$data = json_decode($response, true);

		if (!is_array($data)) {
			return 'Ungültige Transcription API Antwort';
		}

		return $this->resolve_paragraphs($data);
	}

	private function resolve_paragraphs($data) {
		if (empty($data['segments'] ?? [])) {
			return trim($data['text'] ?? 'Kein Audio erkannt');
		}

		$output = '';

		foreach ($data['segments'] as $segment) {
			$part = trim($segment['text'] ?? '');
			if ($part === '') {continue;}

			if ($this->timeStamps) {
				$startTime = $this->format_timestamp($segment['start'] ?? 0);
				$output .= '[' . $startTime . '] ';
			}

			$output .= $part;

			if ($this->ends_sentence($part)) {$output .= "\n";}
			else {$output .= ' ';}
		}

		return trim($output);
	}

	private function ends_sentence($text) {
		$trimmedText = trim($text);

		return str_ends_with($trimmedText, '.')
			|| str_ends_with($trimmedText, '!')
			|| str_ends_with($trimmedText, '?')
			|| str_ends_with($trimmedText, ':');
	}

	private function format_timestamp($seconds) {
		$totalSeconds = (int) floor((float) $seconds);
		$hours = (int) floor($totalSeconds / 3600);
		$minutes = (int) floor(($totalSeconds % 3600) / 60);
		$remainingSeconds = $totalSeconds % 60;

		if ($hours > 0) {
			return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
		}

		return sprintf('%02d:%02d', $minutes, $remainingSeconds);
	}

	private function resolve_external_model() {
		if (defined('EXTERNAL_MODELS') && !empty(EXTERNAL_MODELS['Transcription'])) {
			$model = EXTERNAL_MODELS['Transcription'];
			$this->apiURL = $model['url'];
			if ($model['provider'] == 'azure') {
				$this->apiKey = AZUREKEY;
			}
		}
	}	
}