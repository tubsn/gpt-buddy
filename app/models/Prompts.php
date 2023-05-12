<?php

namespace app\models;

class Prompts
{

	private $path = CONFIGPATH . 'prompts' . DIRECTORY_SEPARATOR;
	private $extension = '.ini';
	

	public function list($ignoreInactive = false) {
		$files = array_diff(scandir($this->path), array('.', '..'));

		$settings = [];
		foreach ($files as $filename) {
			$filename = substr($filename, 0, - strlen($this->extension));
			$settings[$filename] = $this->get($filename);
		}

		uasort($settings, function($a, $b) {
			return $a['name'] <=> $b['name'];
		});

		if ($ignoreInactive) {
			$settings = array_filter($settings, function($prompt) {
				if (isset($prompt['inactive']) && $prompt['inactive'] == 1) {return null;}
				return $prompt;
			});
		}

		return $settings;

	}

	public function get_and_track($name) {

		$prompt = $this->get($name);
		if (!$prompt) {return;}
		if (isset($prompt['hits'])) {$prompt['hits']++;}
		else {$prompt['hits'] = 1;}
		$this->save($name, $prompt);

		return $prompt;
	}

	public function get($name) {
		$filename = $this->path . $name . $this->extension;
		if (!file_exists($filename)) {return null;}
		
		$settings = file_get_contents($filename);
		$settings = json_decode($settings, 1);
		$settings['edited'] = filemtime($filename);

		return $settings;
	} 

	public function save($filename, $data) {
		$file = $this->path . $filename . $this->extension;
		$data = json_encode($data);
		file_put_contents($file, $data);
	}

	public function delete($filename) {
		$filename = $filename . $this->extension;
		$files = array_diff(scandir($this->path), array('.', '..'));
		if (in_array($filename, $files)) {
			unlink($this->path . $filename);
		}
	}

}
