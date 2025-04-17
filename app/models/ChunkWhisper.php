<?php 

namespace app\models;

class ChunkWhisper
{
	private $openAiApiKey;
	private $ffmpegPath;

	public function __construct()
	{
		$this->openAiApiKey = CHATGPTKEY;
		$this->ffmpegPath = FFMPEGPATH;
	}

	public function compressAudio($inputFile, $outputFile)
	{

		$outputDir = dirname($outputFile);
		if (!is_dir($outputDir)) {mkdir($outputDir, 0777, true);}

		$cmd = sprintf(
			'%s -y -i %s -c:a libopus -b:a 12k -application voip %s',
			escapeshellcmd($this->ffmpegPath),
			escapeshellarg($inputFile),
			escapeshellarg($outputFile)
		);

		exec($cmd, $output, $returnVar);

		return $returnVar === 0;
	}

	public function splitAudio($inputFile, $chunkDir, $chunkSizeMB = 15)
	{
		if (!is_dir($chunkDir)) {
			mkdir($chunkDir, 0777, true);
		}

		$duration = $this->getAudioDuration($inputFile);
		if ($duration === false) return false;


		$fileSize = filesize($inputFile);
		$numChunks = ceil($fileSize / ($chunkSizeMB * 1024 * 1024));
		$chunkDuration = ceil($duration / $numChunks);

		$chunkFiles = [];
		for ($i = 0; $i < $numChunks; $i++) {
			$start = $i * $chunkDuration;
			$chunkFile = $chunkDir . '/chunk_' . $i . '.ogg';
			$cmd = sprintf(
				'%s -y -i %s -ss %d -t %d -c copy %s',
				escapeshellcmd($this->ffmpegPath),
				escapeshellarg($inputFile),
				$start,
				$chunkDuration,
				escapeshellarg($chunkFile)
			);
			exec($cmd, $output, $returnVar);
			if ($returnVar !== 0) return false;
			$chunkFiles[] = $chunkFile;
		}
		return $chunkFiles;
	}

	private function getAudioDuration($file)
	{
		$cmd = sprintf(
			'%s -i %s 2>&1',
			escapeshellcmd($this->ffmpegPath),
			escapeshellarg($file)
		);
		$output = shell_exec($cmd);
		if (preg_match('/Duration: (\d+):(\d+):(\d+\.\d+)/', $output, $matches)) {
			$seconds = ($matches[1] * 3600) + ($matches[2] * 60) + floatval($matches[3]);
			return $seconds;
		}
		return false;
	}

	public function transcribeChunk($chunkFile, $language = 'de')
	{
		$ch = curl_init();
		$postFields = [
			'file' => new \CURLFile($chunkFile, 'audio/ogg', basename($chunkFile)),
			'model' => 'whisper-1',
			'language' => $language
		];
		curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer ' . $this->openAiApiKey
		]);
		$response = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($response, true);

		return $data['text'] ?? '';
	}

	public function transcribe($inputFile, $tmpDir, $language = 'de')
	{
		$compressedFile = $tmpDir . '\compressed.ogg';
		if (!$this->compressAudio($inputFile, $compressedFile)) {
			return 'Audio-Komprimierung fehlgeschlagen'; die;
		}
		$chunks = $this->splitAudio($compressedFile, $tmpDir . '\chunks');
		if (!$chunks) {
			return 'Audio-Splitting fehlgeschlagen'; die;
		}
		$result = '';
		foreach ($chunks as $chunk) {
			$result .= $this->transcribeChunk($chunk, $language) . ' ' . "\n\n";
		}
		return trim($result);
	}
}