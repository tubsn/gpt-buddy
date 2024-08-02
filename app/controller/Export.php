<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;

class Export extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->models('Prompts,Imports');
	}

	public function cue_congrats() {

		$from = '2024-06-07';
		$to = '2024-09-13';


		$data = $this->Imports->gather($from, $to);

	

		$this->view->events = $data;
		$this->view->render('multiimport/cue-export');
	}

}
