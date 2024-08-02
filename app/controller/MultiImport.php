<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;
use flundr\cache\RequestCache;
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
		$this->view->title = 'Import Assistent';
		$this->view->render('multiimport/index');
	}

	public function imported_today() {
		$data = $this->Imports->all();
		$this->view->json($data);
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
		if ($filetype == 'text') {$data = $this->default($file);}
		if ($filetype == false) {throw new \Exception('Unknown Filetype ', 400);}

		$this->Imports->add($data);
		$this->view->json($data);
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
			return strip_tags($text);
		} 
		catch (\Exception $e) {
			return $e->getMessage();
		}

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






}
