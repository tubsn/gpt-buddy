<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;

class Import extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->models('Scrape,RSS_Adapter,Json_Adapter,LiveTickerAdapter,FileReader');
	}

	public function article() {

		$url = $_POST['url'];
		$content = $this->Json_Adapter->get_by_url($url);

		if (is_null($content)) {
			$this->view->json(['content' => 'keine Artikeldaten gefunden']);
			die;
		}

		$data['content'] = $content;

		$this->view->json($data);
	}

	public function ticker($id) {
		$ticker = $this->LiveTickerAdapter->get_by_id($id);
		$this->view->json($ticker);
	}

	public function pdf() {
		$this->view->render('upload');
	}

	public function file_upload() {
		$output = $this->FileReader->import($_FILES['file']);
		echo $output;
	}

	public function splitter() {

		$files = [];

		$ff = FFMPEGPATH ?? 'ffmpeg'; // command to open ffmpeg
		$urlpath = 'audio/splitter/';
		$outDir = PUBLICFOLDER . $urlpath;

		if ($_FILES) {
			$tmp_file = $_FILES['audio']['tmp_name'];
			$filename = pathinfo($_FILES['audio']['name'], PATHINFO_FILENAME);
			$extension = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION);

			$in = $tmp_file;
			if (!file_exists($outDir)) {mkdir($outDir, 0777, true);}
			array_map('unlink', array_filter((array) glob($outDir.'*')));
			echo shell_exec("$ff -i $in -f segment -segment_time 600 -c copy ".$outDir.$filename."-%03d." . $extension);
		}

		if (file_exists($outDir)) {
			$files = scandir($outDir, SCANDIR_SORT_ASCENDING);
			$files = array_diff($files, array('.', '..'));
		}

		$this->view->files = $files;
		$this->view->urlpath = $urlpath;
		$this->view->render('audiosplitter');

	}


	public function converter() {

		array_push($this->view->framework, '/styles/flundr/components/fl-upload.js');

		$files = [];

		$ff = 'ffmpeg';
		if (defined('FFMPEGPATH')) {$ff = FFMPEGPATH;}

		$urlpath = 'audio/converter/';
		$outDir = PUBLICFOLDER . $urlpath;

		if ($_FILES) {

			$tmp_file = $_FILES['uploads']['tmp_name'][0];
			$filename = pathinfo($_FILES['uploads']['name'][0], PATHINFO_FILENAME);
			$in = $tmp_file;
			
			if (!file_exists($outDir)) {mkdir($outDir, 0777, true);}
			array_map('unlink', array_filter((array) glob($outDir.'*')));

			// Filename shall not contain any special chars!
			$filename = preg_replace('/[^A-Za-z0-9\-]/', '', $filename);

			echo shell_exec("$ff -i $in -vn -map_metadata -1 -ac 1 -c:a libopus -b:a 12k -application voip ".$outDir.$filename.".ogg");

			Session::unset('tts');
			die;
		}

		if (file_exists($outDir)) {
			$files = scandir($outDir, SCANDIR_SORT_ASCENDING);
			$files = array_diff($files, array('.', '..'));
		}

		$this->view->title = APP_NAME . ' | Audio Converter (Beta)';
		$this->view->files = $files;	
		$this->view->urlpath = $urlpath;
		$this->view->render('audio-converter');

	}

	public function delete_converted_files() {
		$this->clear_converted_files();
		$this->view->redirect('/import/converter');
	}

	private function clear_converted_files() {
		$urlpath = 'audio/converter/';
		array_map('unlink', array_filter((array) glob($urlpath.'*')));
	}

	public function delete_splitted_files() {
		$this->clear_splitted_files();
		$this->view->redirect('/import/splitter');
	}

	private function clear_splitted_files() {
		$urlpath = 'audio/splitter/';
		array_map('unlink', array_filter((array) glob($urlpath.'*')));
	}

	public function transcribe($fileindex) {

		Session::unset('tts');

		$audioDir = PUBLICFOLDER . 'audio/converter/';
		$files = scandir($audioDir, SCANDIR_SORT_ASCENDING);
		$audiofile = $audioDir . $files[$fileindex];

		$file_name = basename($audiofile);
		$c_file = curl_file_create($audiofile, filetype($audiofile), $file_name);

		$tts = new \app\models\OpenAIWhisper();
		$output = $tts->transcribe($c_file);

		Session::set('tts', $output);
		$this->view->redirect('/import/converter');
	}

}
