<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use \app\models\AphexChart;

class Settings extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}

		if (!Auth::has_right('chatgpt')) {
			throw new \Exception("Sie haben keine Berechtigung diese Seite aufzurufen", 403);
		}

		$this->view('DefaultLayout');
		$this->view->title = 'Settings';
		$this->models('Prompts');
	}

	public function index() {

		//$categories = $prompts = $this->Prompts->categories();

		$prompts = $this->Prompts->list_all();
		$categories = array_group_by('category', $prompts);

		$this->view->categories = $categories;

		/*
		if ($prompts) {
			$prompts = array_map(function($prompt) {
				if (!isset($prompt['hits'])) {$prompt['hits'] = 0;}
				$prompt['title'] = substr($prompt['title'],0,33);
			return $prompt;
			}, $prompts);

			// Votes
			$chart = new AphexChart();
			$chart->metric = array_column($prompts,'hits');
			$chart->dimension = array_column($prompts,'title');
			$chart->color = '#1d5e55';
			$chart->height = 400;
			$chart->xfont = '12px';
			$chart->legend = 'top';
			$chart->name = 'Prompt Nutzung';
			$chart->template = 'charts/default_bar_chart';

			$this->view->usageChart = $chart->create();
		}
		*/

		$stats = $this->Prompts->most_hits();
		$chart = new AphexChart();
		$chart->metric = array_column($stats,'hits');
		$chart->dimension = array_column($stats,'title');
		$chart->color = '#1d5e55';
		$chart->height = 400;
		$chart->xfont = '12px';
		$chart->legend = 'top';
		$chart->name = 'Prompt Nutzung';
		$chart->template = 'charts/default_bar_chart';

		$this->view->usageChart = $chart->create();

		$statsgrouped = $this->Prompts->most_hits_by_type();

		$statsgrouped = array_map(function($item) {
			$item['category'] = ucfirst($item['category']);
			return $item;
		}, $statsgrouped);

		$chartgrouped = new AphexChart();
		$chartgrouped->metric = array_column($statsgrouped,'hits');
		$chartgrouped->dimension = array_column($statsgrouped,'category');
		$chartgrouped->color = '#1d5e55';
		$chartgrouped->height = 400;
		$chartgrouped->xfont = '12px';
		$chartgrouped->legend = 'top';
		$chartgrouped->name = 'Kategorie Nutzung';
		$chartgrouped->template = 'charts/default_bar_chart';

		$this->view->usageChartgrouped = $chartgrouped->create();

		$this->view->referer('/settings');
		$this->view->render('admin/settings');
	}

	public function edit($id) {
		$this->view->prompt = $this->Prompts->get($id);
		if (!$this->view->prompt) {throw new \Exception("Prompt not Found", 404);}
		$categories = array_keys(CATEGORIES);
		$categories = array_filter($categories, fn ($set) => $set != 'user');
		$this->view->categories = $categories;
		$this->view->render('admin/edit-prompt');
	}


	public function new() {
		$categories = array_keys(CATEGORIES);
		$categories = array_filter($categories, fn ($set) => $set != 'user');		
		$this->view->categories = $categories;
		$this->view->selectedCategory = $_GET['category'] ?? null;
		$this->view->render('admin/new-prompt');
	}

	public function create() {
		$_POST['user'] = auth('id');
		$this->Prompts->create($_POST);
		$this->view->redirect('/settings');
	}

	public function save($id) {
		$this->Prompts->update($_POST, $id);
		$this->view->back();
		//$this->view->redirect('/settings');
	}

	public function delete($id) {
		$this->Prompts->delete($id);
		$this->view->redirect('/settings');
	}

}
