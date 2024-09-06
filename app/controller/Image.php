<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;
use flundr\utility\Pager;

class Image extends Controller {

	public function __construct() {
		$this->view('DefaultLayout');
		$this->view->interface = 'default';
		$this->view->title = 'ChatGPT Assistent';
		$this->models('ChatGPTApi,Prompts,OpenAIImage,Images');
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}
	}

	public function index() {

		$items = $this->Images->count_files();
		$itemsPerPage = 30;
		$pager = new Pager($items, $itemsPerPage);

		$files = $this->Images->read_directory($itemsPerPage, $pager->offset);

		$this->view->pager = $pager->htmldata;
		$this->view->lastimages = $files;
		$this->view->title = 'Bildgenerator';
		$this->view->render('image-generator/index');
	}

	public function delete() {
		if (!Auth::has_right('deleteimage')) {
			throw new \Exception("Sie haben keine Berechtigung Bilder zu entfernen", 403);
		}
		$imagename = $_POST['imagename'] ?? '';
		if (empty($imagename)) {throw new \Exception("Request Failed", 404);}
		$success = $this->Images->delete_generated_file($imagename);
		$this->view->json(['status' => $success]);
	}

}
