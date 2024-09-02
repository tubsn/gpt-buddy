<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;
use flundr\cache\RequestCache;
use flundr\date\Datepicker;
use app\models\ChatGPT;
use app\models\FileReader;
use app\models\Prompts;
use \Smalot\PdfParser\Parser;
use \Smalot\PdfParser\Config;

class MultiImport extends Controller {

	public $prompt = null;

	public function __construct() {
		$this->view('MultiImportLayout');
		$this->view->title = 'ChatGPT Assistent';
		$this->models('ChatGPTApi,Prompts,OpenAIVision,Prompts,Imports');
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}
	}

	public function index() {
		$this->view->prompts = $this->Prompts->category('importer');
		$this->view->title = 'KI-Import Assistent';
		$this->view->render('multiimport/index');
	}

	public function archive() {

		$options = [];
		$ressort = $_GET['ressort'] ?? '';
		if (!empty($ressort)) {
			$options['ressort'] = $ressort;
		}

		$from = $_GET['from'] ?? null;
		$to = $_GET['to'] ?? null;
		if (!empty($from)) {$options['from'] = $from;}
		if (!empty($to)) {$options['to'] = $to;}

		$location = $_GET['location'] ?? '';
		if (!empty($location)) {
			$options['location'] = $location;
		}

		$stats['ressort'] = $this->Imports->gather_stats('ressort');
		$stats['location'] = $this->Imports->gather_stats('location');

		$this->view->from = $from;
		$this->view->to = $to;

		$this->view->stats = $stats;
		$this->view->selectedRessort = $ressort;
		$this->view->selectedLocation = $location;
		$this->view->locations = $this->Imports->distinct_locations();
		$this->view->events = $this->Imports->filter($options);
		$this->view->title = 'Importierte Einträge [' . count($this->view->events) . ']';
		$this->view->render('multiimport/archive');
	}


	public function imported_today() {
		$data = $this->Imports->latest();
		$this->view->json($data);
	}

	public function import() {
		if ($_FILES) {$this->upload(); return;}
		if (isset($_POST['textarea']) && !empty($_POST['textarea'])) {$this->import_text(); return;}

		if (empty($data)) {
			echo 'keine Daten erkannt';
			return;			
		}		
	}

	public function import_text() {
		$text = $_POST['textarea'];

		$this->prompt = $this->Prompts->get($_POST['prompt']);
		$this->Imports->ressort = $_POST['ressort'];
		$this->Imports->prompt = $this->prompt;

		$ChatGPT = new ChatGPT();
		$ChatGPT->jsonMode = true;

		$prompt = $this->prompt['content'];
		$date = date('d.m.Y', time());
		$prompt = $prompt . "\n" . 'Wir haben heute den: ' . $date;
		$prompt = $prompt . "\n" . $text;
		
		$response = $ChatGPT->direct($prompt);
		$data = json_decode($response,1);
		$data = $data['data'];
		
		$ids = $this->Imports->add($data);

		if (empty($data)) {
			throw new \Exception("Keine Daten erkannt", 400);
			return;			
		}

		$this->view->json(['entries' => $data, 'importIDs' => $ids]);
	}

	public function upload() {

		$file = $_FILES['file'];
		if ($file['size'] > 1024 * 1024 * 25) {throw new \Exception('Achtung: Datei zu groß', 400);}

		$filetype = $this->detect_type($file);
		$this->prompt = $this->Prompts->get($_POST['prompt']);

		$this->Imports->ressort = $_POST['ressort'];
		$this->Imports->prompt = $this->prompt;
		
		$data = [];

		if ($filetype == 'pdf') {$data = $this->pdf($file);}
		if ($filetype == 'image') {$data = $this->image($file);}
		if ($filetype == 'word') {$data = $this->docx($file['tmp_name']);}
		if ($filetype == 'excel') {$data = $this->excel($file['tmp_name']);}
		//if ($filetype == 'text') {$data = $this->default($file);}
		if ($filetype == false) {throw new \Exception('Unknown Filetype ', 400);}

		$ids = $this->Imports->add($data);
		$this->view->json(['entries' => $data, 'importIDs' => $ids]);
	}


	public function pdf($file) {

		$config = new Config();
		$config->setFontSpaceLimit(-50);
		$parser = new Parser([], $config);

		try {
			$pdf = $parser->parseFile($file['tmp_name']);
			$text = $pdf->getText();

			if (empty($text)) {
				return 'Achtung: PDF Datien mit eingescanntem 
				Foto werden zur Zeit nicht unterstüzt. Bitte mache einen Screenshot von dem PDF Inhalt.';
			}

			$text = preg_replace('/[^\S\r\n]+/', ' ', $text); // Remove Multiple Whitespaces
			$content = strip_tags($text);
		} 
		catch (\Exception $e) {
			return $e->getMessage();
		}

		$ChatGPT = new ChatGPT();
		$ChatGPT->jsonMode = true;

		$prompt = $this->prompt['content'];
		$date = date('d.m.Y', time());
		$prompt = $prompt . "\n" . 'Wir haben heute den: ' . $date;
		$prompt = $prompt . $content;
		
		$response = $ChatGPT->direct($prompt);
		$response = json_decode($response,1);
		return $response['data'];


	}

	public function excel($filepath) {
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
		$data = array(1,$spreadsheet->getActiveSheet()->toArray(null,true,true,true));
		$data = json_encode($data);

		$ChatGPT = new ChatGPT();
		$ChatGPT->jsonMode = true;

		$prompt = $this->prompt['content'];
		$date = date('d.m.Y', time());
		$prompt = $prompt . "\n" . 'Wir haben heute den: ' . $date;		
		$prompt = $prompt . $content;
		
		$response = $ChatGPT->direct($prompt);
		$response = json_decode($response,1);
		return $response['data'];

	}



	public function docx($file) {

		$zip = new \ZipArchive();
		if (!$zip->open($file)) {return null;}

		$content = $zip->getFromName("word/document.xml");
		$zip->close();
		$content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
		$content = str_replace('</w:r></w:p>', "\r\n", $content);

		$content = strip_tags($content);

		$ChatGPT = new ChatGPT();
		$ChatGPT->jsonMode = true;

		$prompt = $this->prompt['content'];
		$date = date('d.m.Y', time());
		$prompt = $prompt . "\n" . 'Wir haben heute den: ' . $date;
		$prompt = $prompt . $content;
		
		$response = $ChatGPT->direct($prompt);
		$response = json_decode($response,1);
		return $response['data'];

	}


	public function image($file) {

		$path = $file['tmp_name'];
		$filename = $file['name'];

		$prompt = $this->prompt['content'];
		$date = date('d.m.Y', time());
		$prompt = $prompt . "\n" . 'Wir haben heute den: ' . $date;

		$cache = new RequestCache($filename, 0 * 60 * 60);
		$data = $cache->get();

		if (!$data) {
			$data = $this->OpenAIVision->see($path, $prompt);
			$cache->save($data);
		}

		$data = json_decode($data,1);
		return $data['data'];

	}



	public function detect_type($file) {
		switch ($file['type']) {
			case 'text/plain': case 'text/html': case 'text/csv': return 'text'; break;
			case 'application/pdf': return 'pdf'; break;
			case 'application/msword': return 'word'; break;
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': return 'word'; break;
			case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': return 'powerpoint'; break;
			case 'audio/mpeg': return 'audio'; break;
			case 'audio/mp4': return 'audio'; break;
			case 'audio/x-m4a': return 'audio'; break;
			case 'application/postscript': return 'eps'; break;
			case 'application/x-zip-compressed': return 'zip'; break;
			case 'application/zip': return 'zip'; break;
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': case 'application/vnd.ms-excel': return 'excel'; break;
			case "image/jpeg": case "image/gif": case "image/png": case "image/webp": return 'image'; break;
		}
		return false;
	}

	public function new() {
		$todayWhileAgo = 
		$this->view->event = ['ressort' => 'Karlsruhe'];
		$this->view->prompts = $this->Prompts->category('importer');
		$this->view->render('/multiimport/edit');
	}

	public function create() {
		if (isset($_POST['birthday']) && empty($_POST['birthday'])) {unset($_POST['birthday']);}
		$this->Imports->create($_POST);
		$this->view->redirect('/multiimport/archive');
	}


	public function edit($id) {
		$event = $this->Imports->get($id);
		if (empty($event)) {throw new \Exception("Eintrag nicht vorhanden", 404);}
		$this->view->event = $event;
		$this->view->prompts = $this->Prompts->category('importer');
		$this->view->render('/multiimport/edit');
	}

	public function update($id) {
		$this->Imports->update($_POST, $id);
		$this->view->redirect('/multiimport/archive');
	}

	public function delete($id) {
		$this->Imports->delete($id);
		$this->view->redirect('/multiimport/archive');
	}

	public function mass_delete() {
		$ids = $_POST['ids'];
		$ids = explode(',',$ids);
		foreach ($ids as $id) {
			$this->Imports->delete($id);
		}
		echo 'done';
	}

	public function wipe_db() {
		$this->Imports->clear_db();
		$this->view->redirect('/multiimport/archive');
	}

	public function wipe_old() {
		$this->Imports->remove_old_entries();
		$this->view->redirect('/multiimport/archive');
	}


}
