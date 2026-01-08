<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use flundr\auth\JWTAuth;
use flundr\cache\RequestCache;
use flundr\utility\Session;
use \app\models\AphexChart;
use \app\models\Conversations;

class Stats extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}		
		$this->view('DefaultLayout');
		$this->view->title = 'Statistiken';
		$this->models('Conversations,Prompts,Stats,Usage');
	}


	public function prompt_stats($timeframe = 'month') {

		$options = ['day', 'week', 'month', 'year'];
		if (!in_array($timeframe, $options)) {throw new \Exception("This Timeframe is not supported", 404);}

		$stats = $this->Usage->prompts_by($timeframe);
		//$stats = $this->Usage->categories_by($timeframe);

		dd($stats);

	}


	public function index() {

		$conversationsByMonth = $this->Stats->conversations_by_month();


		if (isset($conversationsByMonth['2025-12'])) {
			$conversationsByMonth['2025-12'] = $conversationsByMonth['2025-12'] + $this->Usage->stats_gap_december_2025();
		}

		$conversationsByMonth = array_merge($conversationsByMonth, $this->Usage->conversations_by_month());

		// Anfragen nach Monat
		$monthly = new AphexChart();
		$monthly->metric = array_values($conversationsByMonth);
		$monthly->dimension = array_keys($conversationsByMonth);
		$monthly->color = CHART_COLOR;
		//$monthly->height = 150;
		$monthly->xfont = '14px';
		$monthly->legend = 'top';
		$monthly->name = 'Gespräche';
		$monthly->template = 'charts/default_bar_chart';

		$this->view->monthly = $conversationsByMonth;		
		$this->view->monthlyChart = $monthly->create();

		$conversationsByDay = $this->Usage->conversations_by_day(14);

		// Anfragen nach Tag
		$daily = new AphexChart();
		$daily->metric = array_values($conversationsByDay);
		$daily->dimension = array_keys($conversationsByDay);
		$daily->color = CHART_COLOR;
		//$chart->height = 800;
		$daily->xfont = '14px';
		$daily->legend = 'top';
		$daily->name = 'Gespräche';
		$daily->template = 'charts/default_bar_chart';

		$this->view->daily = $conversationsByDay;		
		$this->view->dailyChart = $daily->create();


		$type = $this->Stats->conversations_by_type();

		$whiteListesTypes['Empfehlungen'] = $type['Empfehlungen'] ?? null;
		$whiteListesTypes['Kreativität'] = $type['Kreativität'] ?? null;
		$whiteListesTypes['Problemlösungen'] = $type['Problemlösungen'] ?? null;
		$whiteListesTypes['Sprachliche Unterstützung'] = $type['Sprachliche Unterstützung'] ?? null;
		$whiteListesTypes['Unterhaltung'] = $type['Unterhaltung'] ?? null;
		//$whiteListesTypes['Wissensaufbau'] = $type['Wissensaufbau'];

		// Anfragen nach Rubrik
		$chart = new AphexChart();
		$chart->metric = array_values($whiteListesTypes);
		$chart->dimension = array_keys($whiteListesTypes);
		$chart->color = CHART_COLOR;
		//$chart->height = 800;
		$chart->xfont = '14px';
		$chart->legend = 'top';
		$chart->name = 'Rubrik';
		$chart->template = 'charts/default_pie_chart';

		$prompts = $this->Prompts->all(['hits']);
		$promptusage = array_sum(array_column($prompts,'hits'));
		$promptusage += $this->Usage->number_of_conversations_with_prompt();

		$this->view->type = $whiteListesTypes;		
		$this->view->typeChart = $chart->create();

		$usage = $this->Stats->count() + $this->Usage->number_of_conversations();

		$this->view->usage = $usage;
		$this->view->promptusage = $promptusage;
		$this->view->length = $this->Usage->avglength();	

		$this->view->render('stats');

	}

	public function daily_stats() {
		$stats = $this->Usage->conversations_by_day(90);
		if (empty($stats)) {throw new \Exception("Stats not Available", 404);}

		$chart = new AphexChart();
		$chart->metric = array_values($stats);
		$chart->dimension = array_keys($stats);
		$chart->color = CHART_COLOR;
		$chart->height = 500;
		$chart->xfont = '14px';
		$chart->legend = 'top';
		$chart->name = 'Gespräche';
		$chart->template = 'charts/default_bar_chart';

		$this->view->data = $stats;		
		$this->view->chart = $chart->create();

		$this->view->title = 'Nutzung nach Tag' ;
		$this->view->render('stats-detail');

	}

	public function weekly_stats() {
		$stats = $this->Usage->conversations_by_week();
		if (empty($stats)) {throw new \Exception("Stats not Available", 404);}

		$chart = new AphexChart();
		$chart->metric = array_values($stats);
		$chart->dimension = array_keys($stats);
		$chart->color = CHART_COLOR;
		$chart->height = 500;
		$chart->xfont = '14px';
		$chart->legend = 'top';
		$chart->name = 'Gespräche';
		$chart->template = 'charts/default_bar_chart';

		$this->view->data = $stats;		
		$this->view->chart = $chart->create();

		$this->view->title = 'Nutzung nach Kalenderwoche' ;
		$this->view->render('stats-detail');

	}


	public function weekday_stats() {
		$stats = $this->Usage->conversations_by_weekday();
		if (empty($stats)) {throw new \Exception("Stats not Available", 404);}

		$chart = new AphexChart();
		$chart->metric = array_values($stats);
		$chart->dimension = array_keys($stats);
		$chart->color = CHART_COLOR;
		$chart->height = 500;
		$chart->xfont = '14px';
		$chart->legend = 'top';
		$chart->name = 'Gespräche';
		$chart->template = 'charts/default_bar_chart';

		$this->view->data = $stats;		
		$this->view->chart = $chart->create();

		$this->view->title = 'Nutzung nach Wochentag (1 = Sonntag)' ;
		$this->view->render('stats-detail');

	}


	public function hourly_stats() {
		$stats = $this->Usage->conversations_by_hour();
		if (empty($stats)) {throw new \Exception("Stats not Available", 404);}

		$chart = new AphexChart();
		$chart->metric = array_values($stats);
		$chart->dimension = array_keys($stats);
		$chart->color = CHART_COLOR;
		$chart->height = 500;
		$chart->xfont = '14px';
		$chart->legend = 'top';
		$chart->name = 'Gespräche';
		$chart->template = 'charts/default_bar_chart';

		$this->view->data = $stats;		
		$this->view->chart = $chart->create();

		$this->view->title = 'Nutzung nach Uhrzeit' ;
		$this->view->render('stats-detail');

	}


	public function import_and_summarize() {
		$this->Stats->import_conversations_from_disk();
		$importlog = $this->Stats->summarize_conversations('today -2days','tomorrow');

		$Conversations = new Conversations();
		$Conversations->delete_all_conversations();

	}

	public function export_legacy_stats() {
		$csv = new \flundr\mvc\views\csvView();
		$stats = $this->Prompts->most_hits();
		$csv->title = 'Legacy-Prompt-Stats-'.date("dmY").'.csv';
		$csv->export($stats);
	}

	public function export_prompt_stats() {
		$csv = new \flundr\mvc\views\csvView();
		$stats = $this->Usage->alltime();
		$csv->title = 'Prompt-Stats-'.date("dmY").'.csv';
		$csv->export($stats);
	}

	public function export_category_stats() {
		$csv = new \flundr\mvc\views\csvView();
		$stats = $this->Usage->alltime_categories();
		$csv->title = 'Category-Stats-'.date("dmY").'.csv';
		$csv->export($stats);
	}




}
