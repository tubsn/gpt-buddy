<?php

namespace app\models;

class ChunkWhisper
{
	private $openAiApiKey;
	private $ffmpegPath;

	public function __construct() {
		$this->openAiApiKey = CHATGPTKEY;
		$this->ffmpegPath = FFMPEGPATH;
	}

	public function transcribe($inputFile, $tmpDir, $language = 'de', $withTimestamps = false) {
		$compressedFile = $tmpDir . DIRECTORY_SEPARATOR . 'compressed.ogg';

		if (!$this->compressAudio($inputFile, $compressedFile)) {
			return 'Audio-Komprimierung fehlgeschlagen';
		}

		$chunks = $this->splitAudio($compressedFile, $tmpDir . DIRECTORY_SEPARATOR . 'chunks');

		if (!$chunks) {
			return 'Audio-Splitting fehlgeschlagen';
		}

		$result = '';

		foreach ($chunks as $chunkFile) {
			$chunkText = $this->transcribeChunk($chunkFile, $language, $withTimestamps);

			if ($chunkText !== '') {
				$result .= $chunkText . "\n";
			}
		}

		return trim($result);
	}

	public function compressAudio($inputFile, $outputFile) {
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

	public function splitAudio($inputFile, $chunkDir, $chunkSizeMB = 15) {
		if (!is_dir($chunkDir)) {
			mkdir($chunkDir, 0777, true);
		}

		$fileSize = filesize($inputFile);

		if ($fileSize <= ($chunkSizeMB * 1024 * 1024)) {
			return [$inputFile];
		}

		$duration = $this->getAudioDuration($inputFile);

		if ($duration === false) {
			return false;
		}

		$numberOfChunks = (int) ceil($fileSize / ($chunkSizeMB * 1024 * 1024));
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

	private function getAudioDuration($file) {
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

	public function transcribeChunk($chunkFile, $language = 'de', $withTimestamps = false) {
		$curlHandle = curl_init();

		$postFields = [
			'file' => new \CURLFile($chunkFile, 'audio/ogg', basename($chunkFile)),
			'model' => 'whisper-1',
			'language' => $language,
			'response_format' => 'verbose_json',
			'timestamp_granularities[]' => ['segment', 'word'],
		];

		curl_setopt($curlHandle, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_POST, true);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer ' . $this->openAiApiKey
		]);

		$response = curl_exec($curlHandle);

		if ($response === false) {
			$errorMessage = curl_error($curlHandle);
			curl_close($curlHandle);
			return 'cURL Fehler: ' . $errorMessage;
		}

		$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		curl_close($curlHandle);

		if ($httpCode !== 200) {
			return 'API Fehler: ' . $response;
		}

		$data = json_decode($response, true);

		if (!is_array($data)) {
			return 'Ungültige API Antwort';
		}

		return $this->resolveParagraphs($data, $withTimestamps);
	}

	private function resolveParagraphs($data, $withTimestamps = false) {
		if (empty($data['segments'] ?? [])) {
			return trim($data['text'] ?? 'Kein Audio erkannt');
		}

		$output = '';

		foreach ($data['segments'] as $segment) {
			$part = trim($segment['text'] ?? '');

			if ($part === '') {
				continue;
			}

			if ($withTimestamps) {
				$startTime = $this->formatTimestamp($segment['start'] ?? 0);
				$output .= '[' . $startTime . '] ';
			}

			$output .= $part;

			if ($this->endsSentence($part)) {
				$output .= "\n";
			} else {
				$output .= ' ';
			}
		}

		return trim($output);
	}

	private function endsSentence($text) {
		$trimmedText = trim($text);

		return str_ends_with($trimmedText, '.')
			|| str_ends_with($trimmedText, '!')
			|| str_ends_with($trimmedText, '?')
			|| str_ends_with($trimmedText, ':');
	}

	private function formatTimestamp($seconds) {
		$totalSeconds = (int) floor((float) $seconds);
		$hours = (int) floor($totalSeconds / 3600);
		$minutes = (int) floor(($totalSeconds % 3600) / 60);
		$remainingSeconds = $totalSeconds % 60;

		if ($hours > 0) {
			return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
		}

		return sprintf('%02d:%02d', $minutes, $remainingSeconds);
	}
}