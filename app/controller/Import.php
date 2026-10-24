<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;

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
		$options['timestamps'] = $_POST['timestamps'] ?? false;
		$output = $this->FileReader->import($_FILES['file'] ?? null, $options);
		echo $output;
	}

	public function splitter() {

		$files = [];

		$ff = 'ffmpeg';
		if (defined('FFMPEGPATH')) {$ff = FFMPEGPATH;}
		
		$urlpath = 'audio/splitter/';
		$outDir = PUBLICFOLDER . $urlpath;

		if ($_FILES) {
			$tmpFile = $_FILES['audio']['tmp_name'];
			$filename = pathinfo($_FILES['audio']['name'], PATHINFO_FILENAME);
			$extension = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION);
			$filename = preg_replace('/[^A-Za-z0-9\-]/', '', $filename);
			$extension = preg_replace('/[^A-Za-z0-9]/', '', $extension);

			$in = $tmpFile;
			if (!file_exists($outDir)) {mkdir($outDir, 0777, true);}
			array_map('unlink', array_filter((array) glob($outDir.'*')));


			$outputPattern = $outDir . $filename . '-%03d.' . $extension;

			$command = sprintf(
				'%s -y -i %s -f segment -segment_time 600 -c copy %s 2>&1',
				escapeshellcmd($ff),
				escapeshellarg($tmpFile),
				escapeshellarg($outputPattern)
			);

			exec($command, $commandOutput, $returnCode);
		}

		if (file_exists($outDir)) {
			$files = scandir($outDir, SCANDIR_SORT_ASCENDING);
			$files = array_diff($files, array('.', '..'));
		}

		$this->view->files = $files;
		$this->view->urlpath = $urlpath;
		$this->view->render('audiosplitter');

	}

	public function delete_splitted_files() {
		$this->clear_splitted_files();
		$this->view->redirect('/import/splitter');
	}

	private function clear_splitted_files() {
		$urlpath = 'audio/splitter/';
		array_map('unlink', array_filter((array) glob($urlpath.'*')));
	}
}
