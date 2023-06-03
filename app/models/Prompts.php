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

		if (isset($this->predefined_prompts()[$name])) {
			return $this->predefined_prompts()[$name];
		}

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

		if (!isset($settings['markdown'])) {$settings['markdown'] = false;}
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

	private function predefined_prompts() {

		$prompts = [];

		$prompts['general']['content'] = 'Du bist ein KI-Assistent names AI-Buddy, Du arbeitest bei der Lausitzer Rundschau in Cottbus, einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern';

		$prompts['general']['markdown'] = true;

		$prompts['spelling-only']['content'] = "Korrigiere ausschließlich die Rechtschreibung nach deutschem Duden. Gramatik beibehalten! Verändere keine Eigennamen!\n2. Gib mit eine Liste der Änderungen";
		
		$prompts['spelling-grammar']['content'] = "Korrigiere Rechtschreibung, Gramatik und Lesbarkeit nach deutschem Duden. Verändere keine Eigennamen!\nGib mit eine Liste der Änderungen";
		
		$prompts['spelling-comma']['content'] = "Du bist ein Rechtschreibexperte und mit der deutschen Rechtschreibung sehr gut vertraut. Als nächstes werde ich dir einen Text schicken, den du auf Kommasetzung und Rechtschreibung kontrollierst und mir das korrigierte Ergebnis als Antwort zurück gibst.";

		$prompts['translate-de']['content'] = 'Translate into german';
		$prompts['translate-en']['content'] = 'Translate into english';
		$prompts['translate-spain']['content'] = 'Translate into spanish';
		$prompts['translate-pl']['content'] = 'Translate into polish';
		$prompts['translate-sorb']['content'] = 'Übersetze nach Sorbish';
		$prompts['translate-fr']['content'] = 'Translate into french';
		$prompts['translate-cz']['content'] = 'Translate into czech';
		$prompts['translate-ukr']['content'] = 'Translate into ukrainian';
		$prompts['translate-klingon']['content'] = 'Translate into klingon';
		
		$prompts['shorten-s']['content'] = 'Shorten the text to 60 Words, do not change the context!';
		$prompts['shorten-m']['content'] = 'Shorten the text to 120 Words, do not change the context!';
		$prompts['shorten-l']['content'] = 'Shorten the text to 300 Words, do not change the context!';
		$prompts['shorten-xl']['content'] = 'Shorten the text to 500 Words, do not change the context!';

		return $prompts;
	}

}
