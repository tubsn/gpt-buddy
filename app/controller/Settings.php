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

		$prompts = $this->Prompts->list_all();

		$categories = array_group_by('category', $prompts);
		$this->view->categories = $categories;

		$stats = $this->Prompts->most_hits();
		$chart = new AphexChart();
		$chart->metric = array_column($stats,'hits');
		$chart->dimension = array_column($stats,'title');
		$chart->color = CHART_COLOR;
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
		$chartgrouped->color = CHART_COLOR;
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
		$prompt = $this->Prompts->get($id);

		if (isset($prompt['history'])) {
			$prompt['history'] = json_decode($prompt['history'],1);
			$prompt['history'] = array_reverse($prompt['history']);

			/*
			if (count($prompt['history'])>1) {
				array_shift($prompt['history']);
			}
			*/

		}
		$this->view->prompt = $prompt;
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

	public function create($id = null) {
		$_POST['user'] = auth('id');
		$this->Prompts->create($_POST);
		$this->view->redirect('/settings');
	}

	public function save($id) {
		$this->Prompts->update_with_history($_POST, $id);
		$this->view->back();
		//$this->view->redirect('/settings');
	}

	public function copy($id) {
		$prompt = $this->Prompts->copy($id);
		$this->view->prompt = $prompt;
		$categories = array_keys(CATEGORIES);
		$categories = array_filter($categories, fn ($set) => $set != 'user');
		$this->view->categories = $categories;
		$this->view->render('admin/edit-prompt');
	}

	public function delete($id) {
		$this->Prompts->delete($id);
		$this->view->redirect('/settings');
	}

}
