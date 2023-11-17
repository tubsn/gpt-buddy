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

}
