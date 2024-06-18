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

	public function article($portal, $id) {

		$content = $this->Json_Adapter->get_by_id($id);

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

		$ff = 'ffmpeg'; // command to open ffmpeg
		$outDir = PUBLICFOLDER . 'audio/';

		if ($_FILES) {

			$tmp_file = $_FILES['audio']['tmp_name'];
			$in = $tmp_file;
			
			if (!file_exists($outDir)) {mkdir($outDir, 0777, true);}
			array_map('unlink', array_filter((array) glob($outDir.'*')));
			echo shell_exec("$ff -i $in -f segment -segment_time 300 -c copy ".$outDir."splitted%03d.mp3");
		}

		if (file_exists($outDir)) {
			$files = scandir($outDir, SCANDIR_SORT_ASCENDING);
			$files = array_diff($files, array('.', '..'));
		}

		$this->view->files = $files;	
		$this->view->render('audiosplitter');

	}


	public function converter() {

		array_push($this->view->framework, '/styles/flundr/components/fl-upload.js');

		$files = [];

		$ff = 'ffmpeg'; // command to open ffmpeg
		$outDir = PUBLICFOLDER . 'audio/';

		if ($_FILES) {

			$tmp_file = $_FILES['uploads']['tmp_name'][0];
			$filename = pathinfo($_FILES['uploads']['name'][0], PATHINFO_FILENAME);
			$in = $tmp_file;
			
			if (!file_exists($outDir)) {mkdir($outDir, 0777, true);}
			array_map('unlink', array_filter((array) glob($outDir.'*')));

			echo shell_exec("$ff -i $in -vn -map_metadata -1 -ac 1 -c:a libopus -b:a 12k -application voip ".$outDir.$filename.".ogg");

			Session::unset('tts');
		}

		if (file_exists($outDir)) {
			$files = scandir($outDir, SCANDIR_SORT_ASCENDING);
			$files = array_diff($files, array('.', '..'));
		}

		$this->view->title = APP_NAME . ' | Audio Converter (Beta)';
		$this->view->files = $files;	
		$this->view->render('audio-converter');

	}

	public function transcribe($fileindex) {

		Session::unset('tts');

		$audioDir = PUBLICFOLDER . 'audio/';
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
