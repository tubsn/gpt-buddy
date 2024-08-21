<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\utility\Session;
use flundr\date\Datepicker;



class Export extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('MultiImportLayout');
		$this->models('Prompts,Imports');
	}

	public function cue_congrats() {

		$dateHelper = new Datepicker();
		$dateHelper->intervalFormat = 'P1W';
		$dateHelper->includeCurrentDate = false;
		$dateHelper->pickerFormat = 'W. \K\w ';
		$months = $dateHelper->months('last month', 'next Month');

		$from = $_GET['date'] ?? 'today';
		$to = date("Y-m-d", strtotime($from . '+7 days'));

		$filter = $_GET['filter'] ?? null;
		$data = $this->Imports->gather($from, $to, $filter);

		$this->view->filter = $_GET['filter'] ?? null;
		$this->view->from = $from;
		$this->view->to = $to;
		$this->view->months = $months;
		$this->view->currentWeek = date('W');
		$this->view->events = $data;
		$this->view->render('multiimport/cue-export');
	}

}
