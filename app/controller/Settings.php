<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;
use \app\models\AphexChart;

class Settings extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}

		if (!Auth::has_right('chatgpt') && !$this->user_can_see_config()) {
			throw new \Exception("Sie haben keine Berechtigung diese Seite aufzurufen", 403);
		}

		$this->view('DefaultLayout');
		$this->view->title = 'Settings';
		$this->models('Prompts,Knowledge,Scrape');
	}

	public function index() {

		$prompts = $this->Prompts->list_all();

		if (!Auth::has_right('chatgpt')) {
			$usersCategories = $this->editable_rights();
			$prompts = array_filter($prompts, fn ($set) => in_array($set['category'], $usersCategories));
		}

		$categories = array_group_by('category', $prompts);
		ksort($categories);

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
		}
		
		$this->view->prompt = $prompt;
		if (!$this->view->prompt) {throw new \Exception("Prompt not Found", 404);}
		$categories = array_keys(CATEGORIES);
		$categories = array_filter($categories, fn ($set) => $set != 'user');
		asort($categories);

		$postProcessCategories = array_keys(array_filter(CATEGORIES, fn($set) => isset($set['postProcess']) && $set['postProcess']));
		$postProcessPrompts = array_column($this->Prompts->in_categories($postProcessCategories), 'title', 'id');
		$this->view->postProcessPrompts = $postProcessPrompts;

		if (!Auth::has_right('chatgpt')) {
			$usersCategories = $this->editable_rights();
			$categories = array_filter($categories, fn ($set) => in_array($set, $usersCategories));
		}

		$this->view->aimodels = AIMODELS ?? [];
		$this->view->knowledges = $this->Knowledge->distinct();
		$this->view->categories = $categories;
		$this->view->render('admin/edit-prompt');
	}


	public function new() {
		$categories = array_keys(CATEGORIES);
		$categories = array_filter($categories, fn ($set) => $set != 'user');		
		asort($categories);

		$postProcessCategories = array_keys(array_filter(CATEGORIES, fn($set) => isset($set['postProcess']) && $set['postProcess']));
		$postProcessPrompts = array_column($this->Prompts->in_categories($postProcessCategories), 'title', 'id');
		$this->view->postProcessPrompts = $postProcessPrompts;

		if (!Auth::has_right('chatgpt')) {
			$usersCategories = $this->editable_rights();
			$categories = array_filter($categories, fn ($set) => in_array($set, $usersCategories));
		}

		$this->view->aimodels = AIMODELS ?? [];
		$this->view->knowledges = $this->Knowledge->distinct();
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
		$category = $_POST['category'];
		if ($this->view->referer() == '/archiv' || $this->view->referer() == '/settings') {$this->view->back();}
		if ($category == 'alle') {$category = '';}
		$backlink = '/' . $category . '#' . $id;
		$this->view->redirect($backlink);
	}

	public function copy($id) {
		$prompt = $this->Prompts->copy($id);
		$this->view->prompt = $prompt;
		$categories = array_keys(CATEGORIES);
		$categories = array_filter($categories, fn ($set) => $set != 'user');
		$this->view->aimodels = AIMODELS ?? [];
		$this->view->knowledges = $this->Knowledge->distinct();		
		$this->view->categories = $categories;
		$this->view->render('admin/edit-prompt');
	}

	public function delete($id) {
		$this->Prompts->delete($id);
		$this->view->redirect('/settings');
	}

	public function knowledges() {
		$this->view->knowledges = $this->Knowledge->all();
		$this->view->title = 'Knowledge Informationen';
		$this->view->referer('/settings/knowledge');
		$this->view->render('admin/list-knowledge');
	}

	public function new_knowledge() {
		$this->view->render('admin/new-knowledge');
	}

	public function create_knowledge($id = null) {
		$this->Knowledge->create($_POST);
		$this->view->redirect('/settings/knowledge');
	}

	public function edit_knowledge($id) {
		$knowledge = $this->Knowledge->get($id);
		$this->view->knowledge = $knowledge;
		if (!$this->view->knowledge) {throw new \Exception("Knowledge not Found", 404);}

		$import = null;
		if ($knowledge['url']) {
			$import = $this->Scrape->by_class_plain($knowledge['url'], $knowledge['selector']);
		}

		$this->view->import = $import;
		$this->view->render('admin/edit-knowledge');
	}

	public function save_knowledge($id) {
		$this->Knowledge->update($_POST, $id);
		$this->view->back();
	}

	public function delete_knowledge($id) {
		$this->Knowledge->delete($id);
		$this->view->redirect('/settings/knowledge');
	}

	public function user_can_see_config() {
		$configureableCategories = $this->editable_rights();
		if (count($configureableCategories) > 0) {
			return true;
		}
		return false;
	}

	public function editable_rights() {
		$usersCategories = explode_and_trim(',', auth('rights'));
		$categories = array_keys(CATEGORIES);
		$categories = array_filter($categories, fn ($category) => in_array($category, $usersCategories));
		return $categories;
	}


}
