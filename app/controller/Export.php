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

		$stats['ressort'] = $this->Imports->gather_stats('ressort');
		$weeks = $this->Imports->gather_by_week();
		$weeks = $this->map_weeks($weeks);
		$currentWeek = date('W');
		$selectedWeek = $currentWeek;

		if (!isset($weeks[$currentWeek])) {
			$weeks[$currentWeek]['week'] = $currentWeek;
			$weeks[$currentWeek]['from'] = date('Y-m-d', strtotime('monday this week'));
			$weeks[$currentWeek]['to'] = date('Y-m-d', strtotime('sunday this week'));
			$weeks[$currentWeek]['entries'] = 0;
			krsort($weeks);
		}

		if (isset($_GET['kw'])) {$selectedWeek = intval($_GET['kw']);}

		$from = $weeks[$selectedWeek]['from'] ?? null;
		$to = $weeks[$selectedWeek]['to'] ?? null;

		$filter = $_GET['filter'] ?? null;
		$data = $this->Imports->gather($from, $to, $filter);

		$this->view->filter = $_GET['filter'] ?? null;
		$this->view->from = $from;
		$this->view->to = $to;
		$this->view->weeks = $weeks;
		$this->view->stats = $stats;
		$this->view->selectedWeek = $selectedWeek;
		$this->view->currentWeek = $currentWeek;
		$this->view->events = $data;
		$this->view->render('multiimport/cue-export');
	}

	public function map_weeks($weeks) {
		$out = [];
		foreach ($weeks as $week => $entries) {
			$dates = $this->get_start_and_end_date($week);
			$out[$week]['week'] = $week;
			$out[$week]['from'] = $dates['from'];
			$out[$week]['to'] = $dates['to'];
			$out[$week]['entries'] = $entries;
		}
		return $out;
	}

	public function get_start_and_end_date($week) {
		$dto = new \DateTime();
		$currentYear = date('Y');
		$dto->setISODate($currentYear, $week);
		$startDate = $dto->format('Y-m-d');
		$dto->modify('+6 days');
		$endDate = $dto->format('Y-m-d');
		return ['from' => $startDate, 'to' => $endDate];
	}

}
