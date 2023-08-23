<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\auth\JWTAuth;
use flundr\cache\RequestCache;
use flundr\utility\Session;
use \app\models\AphexChart;

class Stats extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->title = 'Statistiken';
		$this->models('Conversations,Prompts,Stats');
	}

	public function index() {


		$conversationsByMonth = $this->Stats->conversations_by_month();

		// Anfragen nach Monat
		$monthly = new AphexChart();
		$monthly->metric = array_values($conversationsByMonth);
		$monthly->dimension = array_keys($conversationsByMonth);
		$monthly->color = '#1d5e55';
		$monthly->height = 150;
		$monthly->xfont = '14px';
		$monthly->legend = 'top';
		$monthly->name = 'Gespräche';
		$monthly->template = 'charts/default_bar_chart';

		$this->view->monthly = $conversationsByDay;		
		$this->view->monthlyChart = $monthly->create();



		$conversationsByDay = $this->Stats->conversations_by_day();

		// Anfragen nach Tag
		$daily = new AphexChart();
		$daily->metric = array_values($conversationsByDay);
		$daily->dimension = array_keys($conversationsByDay);
		$daily->color = '#1d5e55';
		//$chart->height = 800;
		$daily->xfont = '14px';
		$daily->legend = 'top';
		$daily->name = 'Gespräche';
		$daily->template = 'charts/default_bar_chart';

		$this->view->daily = $conversationsByDay;		
		$this->view->dailyChart = $daily->create();


		$type = $this->Stats->conversations_by_type();

		// Anfragen nach Rubrik
		$chart = new AphexChart();
		$chart->metric = array_values($type);
		$chart->dimension = array_keys($type);
		$chart->color = '#1d5e55';
		//$chart->height = 800;
		$chart->xfont = '14px';
		$chart->legend = 'top';
		$chart->name = 'Rubrik';
		$chart->template = 'charts/default_pie_chart';

		$this->view->type = $type;		
		$this->view->typeChart = $chart->create();

		$this->view->usage = $this->Stats->count();	
		$this->view->length = $this->Stats->avglength();	

		$this->view->render('stats');

	}

	public function monthly_stats() {
		$data = $this->Stats->usage_by_month();
		dd($data);
	}


	public function import_and_summarize() {
		$this->Stats->import_conversations_from_disk();
		$importlog = $this->Stats->summarize_conversations('today -2days','tomorrow');
		//$importlog = $this->Stats->summarize_conversations('2023-07-14','2023-07-17');
	}
}
