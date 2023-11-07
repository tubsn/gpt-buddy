<?php

namespace app\models;
use \Smalot\PdfParser\Parser;
use \Smalot\PdfParser\Config;
use \app\models\OpenAIWhisper;

class FileReader
{

	public function __construct() {

	}

	public function import($file) {

		if ($file['size'] > 1024 * 1024 * 25) {return 'Achtung: Datei zu groß';}

		//dd($file['type']);

		$filepath = $file['tmp_name'];
		$type = $this->detect_type($file);

		if ($type == 'pdf') {return $this->pdf($filepath);}
		if ($type == 'audio') {return $this->audio($file);}
		if ($type == 'word') {return $this->docx($filepath);}
		if ($type == 'excel') {return $this->excel($filepath);}
		if ($type == 'text') {return $this->default($filepath);}

		return 'Achtung: Datei Typ wurde nicht erkannt';

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

	private function default($filepath) {
		return strip_tags(file_get_contents($filepath));
	}


	private function docx($filepath) {
		$content = $this->readDocx($filepath);
		return strip_tags($content);
	}

	private function excel($filepath) {
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
		$data = array(1,$spreadsheet->getActiveSheet()->toArray(null,true,true,true));
		return json_encode($data);
	}

	private function pdf($filepath) {

		//$parser = new Parser();

		$config = new Config();
		$config->setFontSpaceLimit(-50);
		$parser = new Parser([], $config);


		// Base 64 Parsing
		//$pdf = $parser->parseContent(base64_decode($base64PDF));

		//Specific Page
		//$pdf->getPages()[0]->getText();

		//$metaData = $pdf->getDetails();
		//dd($metaData);

		try {
			$pdf = $parser->parseFile($filepath);
			$text = $pdf->getText();
			$text = preg_replace('/[^\S\r\n]+/', ' ', $text); // Remove Multiple Whitespaces
			return strip_tags($text);
		} 
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}


	private function audio($file) {

		$tmp_file = $_FILES['file']['tmp_name'];
		$file_name = basename($_FILES['file']['name']);
		$c_file = curl_file_create($tmp_file, $_FILES['file']['type'], $file_name);

		$tts = new OpenAIWhisper();
		$output = $tts->transcribe($c_file);

		return $output;

	}



    private function readDocx($filename)
    {

        $zip = new \ZipArchive();
        if ($zip->open($filename)) {
            $content = $zip->getFromName("word/document.xml");
            $zip->close();
            $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
            $content = str_replace('</w:r></w:p>', "\r\n", $content);

            return strip_tags($content);
        }
        return false;

    }



}
