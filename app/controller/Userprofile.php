<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\auth\Auth;

class Userprofile extends Controller {

	public function __construct() {
		if (!Auth::logged_in() && !Auth::valid_ip()) {Auth::loginpage();}

		$this->view('DefaultLayout');
		$this->models('Prompts');
	}

	public function index() {

		//$categories = $prompts = $this->Prompts->categories();

		$prompts = $this->Prompts->list_all();
		$categories = array_group_by('category', $prompts);

		$this->view->categories = $categories;

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
