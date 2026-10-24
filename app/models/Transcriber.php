<?php

namespace app\models;

class Transcriber {

	private $apiURL = 'https://api.openai.com/v1/audio/transcriptions';
	private $modelName = 'gpt-transcribe';
	private $provider = 'openai';
	private $maxFileSize = 25 * 1024 * 1024;
	private $ffmpegPath;
	private $workDir;
	private $timestamps = false;
	private $chunkStart = 0;
	private $chunkNumber = 1;
	private $multipleChunks = false;

	public function __construct() {
		$this->ffmpegPath = defined('FFMPEGPATH') ? FFMPEGPATH : 'ffmpeg';
		if (defined('EXTERNAL_MODELS') && !empty(EXTERNAL_MODELS['Transcription'])) {
			$config = EXTERNAL_MODELS['Transcription'];
			$this->modelName = $config['apiname'] ?? $this->modelName;
			$this->provider = $config['provider'] ?? $this->provider;
			$this->apiURL = $config['url'] ?? $this->apiURL;
		}
	}

	public function stream($file, $timestamps = false) {
		$this->start_stream();

		try {
			$this->timestamps = filter_var($timestamps, FILTER_VALIDATE_BOOLEAN);
			if ($this->timestamps && $this->provider !== 'openai') {
				throw new \Exception('Sprechererkennung ist derzeit nur mit OpenAI verfügbar.');
			}
			if ($file['size'] > 150 * 1024 * 1024) {
				throw new \Exception('Datei zu groß (maximal 150 MB).');
			}

			$chunks = $this->prepare_audio($file);
			$this->multipleChunks = count($chunks) > 1;
			$transcript = '';

			foreach ($chunks as $index => $chunk) {
				if (connection_aborted()) {break;}
				$this->chunkStart = $chunk['start'];
				$this->chunkNumber = $index + 1;
				$this->send_event('progress', ['current' => $index + 1, 'total' => count($chunks)]);
				$part = $this->transcribe_chunk($chunk['path'], $chunk['name']);
				if (!$this->timestamps) {$part = $this->format_paragraphs($part);}
				$separator = $this->timestamps ? "\n" : "\n\n";
				$transcript .= ($transcript === '' ? '' : $separator) . $part;
				$this->send_event('part_done', ['text' => trim($transcript)]);
			}

			$this->send_event('done', ['text' => trim($transcript)]);
		} catch (\Throwable $error) {
			$this->send_event('error', ['message' => $error->getMessage()]);
		} finally {
			if ($this->workDir) {$this->remove_work_dir($this->workDir);}
		}
	}

	private function prepare_audio($file) {
		if ($file['size'] <= $this->maxFileSize && $this->supported_file($file)) {
			return [['path' => $file['tmp_name'], 'name' => 'audio.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), 'start' => 0]];
		}

		$ffmpeg = escapeshellarg($this->ffmpegPath);
		exec($ffmpeg . ' -version 2>&1', $output, $exitCode);
		if ($exitCode !== 0) {throw new \Exception('FFmpeg wird für diese Audiodatei benötigt.');}

		$workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aibuddy-audio-' . bin2hex(random_bytes(12));
		mkdir($workDir, 0700);
		$this->workDir = $workDir;
		register_shutdown_function(function() use ($workDir) {
			$this->remove_work_dir($workDir);
		});
		$compressed = $workDir . DIRECTORY_SEPARATOR . 'audio.webm';
		$command = sprintf('%s -v error -y -i %s -map 0:a:0 -vn -c:a libopus -b:a 24k %s 2>&1',
			$ffmpeg, escapeshellarg($file['tmp_name']), escapeshellarg($compressed));
		exec($command, $output, $exitCode);
		if ($exitCode !== 0 || !is_file($compressed)) {throw new \Exception('Audio-Komprimierung fehlgeschlagen.');}

		if (filesize($compressed) <= $this->maxFileSize) {
			return [['path' => $compressed, 'name' => 'audio.webm', 'start' => 0]];
		}

		$info = shell_exec(sprintf('%s -i %s 2>&1', $ffmpeg, escapeshellarg($compressed)));
		$duration = 0;
		if (preg_match('/Duration: (\d+):(\d+):(\d+\.\d+)/', $info, $matches)) {
			$duration = $matches[1] * 3600 + $matches[2] * 60 + (float) $matches[3];
		}
		if ($duration <= 0) {throw new \Exception('Audiodauer konnte nicht ermittelt werden.');}

		$chunkCount = (int) ceil(filesize($compressed) / (20 * 1024 * 1024));
		$chunkSeconds = (int) ceil($duration / $chunkCount);
		$chunks = [];
		for ($index = 0; $index < $chunkCount; $index++) {
			$start = $index * $chunkSeconds;
			$path = $workDir . DIRECTORY_SEPARATOR . 'chunk-' . $index . '.webm';
			$command = sprintf('%s -v error -y -ss %d -i %s -t %d -c copy %s 2>&1',
				$ffmpeg, $start, escapeshellarg($compressed), $chunkSeconds, escapeshellarg($path));
			exec($command, $output, $exitCode);
			if ($exitCode !== 0 || !is_file($path) || filesize($path) > $this->maxFileSize) {
				throw new \Exception('Audio-Splitting fehlgeschlagen.');
			}
			$chunks[] = ['path' => $path, 'name' => basename($path), 'start' => $start];
		}
		return $chunks;
	}

	private function supported_file($file) {
		$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		return in_array($extension, ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'], true);
	}

	private function transcribe_chunk($path, $name) {
		if ($this->provider === 'azure') {
			return $this->transcribe_azure_chunk($path, $name);
		}

		$buffer = '';
		$text = '';
		$segments = [];
		$errorBody = '';
		$curl = curl_init($this->apiURL);
		$postFields = [
			'file' => new \CURLFile($path, mime_content_type($path), $name),
			'model' => $this->timestamps ? 'gpt-4o-transcribe-diarize' : $this->modelName,
			'stream' => 'true',
		];
		if ($this->timestamps) {
			$postFields['response_format'] = 'diarized_json';
			$postFields['chunking_strategy'] = 'auto';
			$postFields['language'] = 'de';
		} else {
			$postFields['languages[]'] = 'de';
		}
		curl_setopt_array($curl, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $postFields,
			CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . CHATGPTKEY, 'Accept: text/event-stream'],
			CURLOPT_TIMEOUT => 0,
			CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$buffer, &$text, &$segments, &$errorBody) {
				if (connection_aborted()) {return 0;}
				if (curl_getinfo($curl, CURLINFO_RESPONSE_CODE) >= 400) {
					$errorBody .= $data;
					return strlen($data);
				}
				$buffer .= $data;
				$buffer = str_replace("\r\n", "\n", $buffer);
				while (($boundary = strpos($buffer, "\n\n")) !== false) {
					$event = substr($buffer, 0, $boundary);
					$buffer = substr($buffer, $boundary + 2);
					foreach (explode("\n", $event) as $line) {
						if (!str_starts_with($line, 'data:')) {continue;}
						$payload = json_decode(trim(substr($line, 5)), true);
						if (!$payload || empty($payload['type'])) {continue;}
						if ($payload['type'] === 'transcript.text.segment' && $this->timestamps) {
							$segments[] = $payload;
							$this->send_event('delta', ['text' => $this->format_speaker_segments([$payload]) . "\n"]);
						}
						if ($payload['type'] === 'transcript.text.delta' && !$this->timestamps) {
							$text .= $payload['delta'];
							$this->send_event('delta', ['text' => $payload['delta']]);
						}
						if ($payload['type'] === 'transcript.text.done') {$text = $payload['text'] ?? $text;}
					}
				}
				return strlen($data);
			},
		]);
		$result = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		$error = curl_error($curl);
		curl_close($curl);
		if ($result === false || $status !== 200) {
			throw new \Exception('Transcription API Fehler: ' . ($error ?: $errorBody ?: $status));
		}
		if ($this->timestamps) {
			if (!$segments) {throw new \Exception('Keine Sprecherabschnitte empfangen.');}
			return $this->format_speaker_segments($segments);
		}
		return trim($text);
	}

	private function transcribe_azure_chunk($path, $name) {
		$curl = curl_init($this->apiURL);
		curl_setopt_array($curl, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => [
				'file' => new \CURLFile($path, mime_content_type($path), $name),
				'model' => $this->modelName,
				'language' => 'de',
			],
			CURLOPT_HTTPHEADER => ['api-key: ' . AZUREKEY, 'Accept: application/json'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 0,
		]);
		$response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		$error = curl_error($curl);
		curl_close($curl);
		if ($response === false || $status !== 200) {
			throw new \Exception('Azure Transcription API Fehler: ' . ($error ?: $response ?: $status));
		}
		$data = json_decode($response, true);
		if (!isset($data['text'])) {throw new \Exception('Ungültige Azure Transcription API Antwort.');}
		return trim($data['text']);
	}

	private function format_speaker_segments($segments) {
		$lines = [];

		foreach ($segments as $segment) {
			$text = trim($segment['text'] ?? '');
			if ($text === '') {continue;}

			$speaker = $segment['speaker'] ?? '?';
			if ($this->multipleChunks) {$speaker = $this->chunkNumber . '-' . $speaker;}
			$seconds = (int) ($this->chunkStart + ($segment['start'] ?? 0));
			$time = $seconds >= 3600 ? gmdate('H:i:s', $seconds) : gmdate('i:s', $seconds);
			$lines[] = '[' . $time . '] Sprecher ' . $speaker . ': ' . $text;
		}

		return implode("\n", $lines);
	}

	private function format_paragraphs($text) {
		$sections = preg_split('/\n\s*\n/u', trim($text));
		$paragraphs = [];

		foreach ($sections as $section) {
			$section = preg_replace('/\s+/u', ' ', trim($section));
			if ($section === '') {continue;}

			$sentences = preg_split('/(?<=[.!?])\s+(?=\p{Lu}|[„"\(])/u', $section);
			$paragraph = '';
			$sentenceCount = 0;

			foreach ($sentences as $sentence) {
				if ($paragraph !== '') {$paragraph .= ' ';}
				$paragraph .= $sentence;
				$sentenceCount++;
				if ($sentenceCount < 3 && strlen($paragraph) < 450) {continue;}
				$paragraphs[] = $paragraph;
				$paragraph = '';
				$sentenceCount = 0;
			}

			if ($paragraph !== '') {$paragraphs[] = $paragraph;}
		}

		foreach ($paragraphs as $index => $paragraph) {
			if (strlen($paragraph) > 600) {
				$paragraphs[$index] = wordwrap($paragraph, 500, "\n\n");
			}
		}

		return implode("\n\n", $paragraphs);
	}

	private function start_stream() {
		@ini_set('zlib.output_compression', '0');
		while (ob_get_level() > 0) {ob_end_clean();}
		header('Content-Type: text/event-stream; charset=utf-8');
		header('Cache-Control: no-cache');
		header('X-Accel-Buffering: no');
	}

	private function send_event($type, $data) {
		echo 'data: ' . json_encode(['type' => $type] + $data, JSON_UNESCAPED_UNICODE) . "\n\n";
		flush();
	}

	private function remove_work_dir($workDir) {
		if (!is_dir($workDir)) {return;}
		foreach (glob($workDir . DIRECTORY_SEPARATOR . '*') as $path) {unlink($path);}
		rmdir($workDir);
	}
}
