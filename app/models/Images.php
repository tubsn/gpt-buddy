<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;

class Images extends Model
{

	public $folderName = 'generated';
	public $internalPath;
	public $externalPath;

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'images';

		$this->internalPath = PUBLICFOLDER . $this->folderName . DIRECTORY_SEPARATOR;
		$this->externalPath = '/' . $this->folderName . '/';
	}

	public function count_files() {
		$files = glob($this->internalPath . '*');
		return count($files);
	}


	public function read_directory($limit, $offset = 0) {
		if (!file_exists($this->internalPath)) {return [];}

		$filenames = array_diff(scandir($this->internalPath), ['.', '..']);

		usort($filenames, function($firstFilename, $secondFilename) {
			$firstTimestamp = filemtime($this->internalPath . $firstFilename);
			$secondTimestamp = filemtime($this->internalPath . $secondFilename);

			return $secondTimestamp <=> $firstTimestamp;
		});

		$filenames = array_slice($filenames, $offset, $limit);

		return array_map(function($filename) {
			$info = getimagesize($this->internalPath . $filename, $meta);

			$file = [];
			$file['path'] = $this->externalPath . $filename;
			$file['width'] = $info[0];
			$file['height'] = $info[1];
			$file['name'] = $filename;
			$file['prompt'] = $this->extract_prompt_data($meta);

			return $file;
		}, $filenames);
	}

	public function extract_prompt_data($meta) {
		if (!isset($meta['APP13'])) {return '';} // IPTC Comment Field
		$comments = explode('--PROMPT--', $meta['APP13']);
		$prompt = $comments[1] ?? '';
		return trim($prompt);
	}

	public function delete_generated_file($filename) {
		$file = $this->internalPath . $filename;
		if (file_exists($file)) {
			unlink($file);
			return true;
		}
		return false;
	}

}
