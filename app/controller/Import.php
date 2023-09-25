<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;

class Import extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->models('Scrape,RSS_Adapter,LiveTickerAdapter,FileReader');
	}

	public function article($portal, $id) {

		$data = $this->RSS_Adapter->get_by_id($id);

		if (is_null($data)) {
			$this->view->json(['content' => 'keine Artikeldaten gefunden']);
			die;
		}

		$content = $data['content'];

		$content = strip_tags($content);
		$content = str_replace('	', '', $content);
		$content = str_replace("\n\n", '', $content);
		$content = trim($content, "\n");

		// Remove Newsletter Boxes
		$cutoff = strpos($content, 'Newsletter-Anmeldung');
		if ($cutoff) {$content = substr($content,0,$cutoff);}

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

}
