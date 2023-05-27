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
		$this->models('Prompts');
	}

	public function index() {

		$prompts = $this->Prompts->list();
		$this->view->prompts = $prompts;

		$prompts = array_map(function($prompt) {
			if (!isset($prompt['hits'])) {$prompt['hits'] = 0;}
		return $prompt;
		}, $prompts);

		// Votes
		$chart = new AphexChart();
		$chart->metric = array_column($prompts,'hits');
		$chart->dimension = array_column($prompts,'name');
		$chart->color = '#1d5e55';
		$chart->height = 400;
		$chart->xfont = '12px';
		$chart->legend = 'top';
		$chart->name = 'Prompt Nutzung';
		$chart->template = 'charts/default_bar_chart';
		
		$this->view->usageChart = $chart->create();

		$this->view->render('admin/settings');
	}

	public function edit($internalName) {
		$this->view->prompt = $this->Prompts->get($internalName);
		if (!$this->view->prompt) {throw new \Exception("Prompt Settings File not Found", 404);}
		$this->view->internalName = $internalName;
		$this->view->render('admin/edit-prompt');
	}


	public function new() {
		$this->view->render('admin/new-prompt');
	}

	public function create() {
		$data['name'] = $_POST['name'];
		$data['content'] = $_POST['content'];
		$data['description'] = $_POST['description'];
		$data['markdown'] = $_POST['markdown'];		
		$this->Prompts->save(slugify($_POST['internalname']), $data);
		$this->view->redirect('/settings');
	}

	public function save($internalName) {
		$data['name'] = $_POST['name'];
		$data['content'] = $_POST['content'];
		$data['inactive'] = $_POST['inactive'];
		$data['description'] = $_POST['description'];
		$data['markdown'] = $_POST['markdown'];
		$data['hits'] = $_POST['hits'];
		$this->Prompts->save(slugify($internalName), $data);
		$this->view->redirect('/settings');
	}

	public function delete($internalName) {
		$this->Prompts->delete($internalName);
		$this->view->redirect('/settings');
	}

}
