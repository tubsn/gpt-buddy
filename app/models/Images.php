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


	public function read_directory(int $limit = null, int $offset = 0) {

		if (file_exists($this->internalPath)) {
			$files = scandir($this->internalPath, SCANDIR_SORT_DESCENDING);
			$files = array_diff($files, array('.', '..'));
			$files = array_slice($files, $offset, $limit);
		} else {$files = [];}


		$files = array_map(function($filename) {
			
			$info = getimagesize($this->internalPath . $filename);
			$file['path'] = $this->externalPath . $filename;
			$file['width'] = $info[0];
			$file['height'] = $info[1];
			$file['name'] = $filename;

			return $file;

		}, $files);

		return $files;

	}

}
