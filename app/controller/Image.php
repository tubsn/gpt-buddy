<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;

class Image extends Controller {

	public function __construct() {
		$this->view('DefaultLayout');
		$this->view->interface = 'default';
		$this->view->title = 'ChatGPT Assistent';
		$this->models('ChatGPTApi,Prompts,OpenAIImage,OpenAIImage3');
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}
	}

	public function index() {
		$path = PUBLICFOLDER . 'generated/';
		if (file_exists($path)) {
			$files = scandir($path, SCANDIR_SORT_DESCENDING);
			$files = array_diff($files, array('.', '..'));
			$files = array_slice($files, 0, 21);
		} else {$files = [];}

		$this->view->lastimages = $files;
		$this->view->title = 'Image Assistent';
		$this->view->render('image');
	}

	public function archive() {

		$path = PUBLICFOLDER . 'generated/';
		if (file_exists($path)) {
			$files = scandir($path, SCANDIR_SORT_DESCENDING);
			$files = array_diff($files, array('.', '..'));
		} else {$files = [];}

		$this->view->lastimages = $files;
		$this->view->title = 'Image Assistent';
		$this->view->render('image');

	}

}
