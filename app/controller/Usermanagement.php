<?php

namespace app\controller;
use flundr\mvc\Controller;
use flundr\utility\Session;
use flundr\auth\Auth;
use flundr\controlpanel\models\User;

class Usermanagement extends Controller {

	private $User;

	public function __construct() {

		if (!Auth::logged_in()) { Auth::loginpage(); }
		if (Auth::get('level') != 'Admin') {
			throw new \Exception("Sie haben keine Berechtigung diese Seite aufzurufen", 403);
		}

		$this->view('DefaultLayout');
		$this->view->title = 'Benutzerverwaltung';

		// this is the default Usermodel feel free to use your own
		$this->User = new User;

		// You can change all Fields like Userlevel, Rights or Passwords
		$this->User->set_protected_fields(null);

	}

	public function index() {

		$viewData['users'] = $this->User->list();
		$this->view->render('admin/user-list', $viewData);

	}

	public function new() {
		$this->view->title = 'Neuen Nutzer anlegen';
		$this->view->render('admin/user-new');
	}

	public function create() {

		if (!hash_equals($_POST['CSRFToken'], Session::get('CSRFToken'))) {
			throw new \Exception("Token Missmatch", 403); die;
		}

		if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			throw new \Exception("Bitte gültige E-Mail eintragen", 400); die;
		}

		if (strlen($_POST['password']) < 5) {
			throw new \Exception("Bitte Passwort mit mindestens 5 Zeichen eingeben", 400); die;
		}

		$this->User->create($_POST);
		$this->view->redirect('/admin');
	}


	public function show($id) {
		$viewData['user'] = $this->User->get($id);

		$this->view->title = 'Benutzer Editieren - ID: ' . $viewData['user']['id'];
		$this->view->render('admin/user-edit', $viewData);
	}

	public function update($id) {

		if (!hash_equals($_POST['CSRFToken'], Session::get('CSRFToken'))) {
			throw new \Exception("Token Missmatch", 403); die;
		}

		if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			throw new \Exception("Bitte Gültige E-Mail eintragen", 400); die;
		}

		if (!empty($_POST['password']) && strlen($_POST['password']) < 5) {
			throw new \Exception("Bitte Passwort mit mindestens 5 Zeichen eingeben", 400); die;
		}

		$this->User->update($_POST, $id);
		$this->view->redirect('/admin');

	}

	public function delete($id, $token) {

		if (!hash_equals($token, Session::get('CSRFToken'))) {
			throw new \Exception("Token Missmatch", 403); die;
		}

		$this->User->delete($id);
		$this->view->redirect('/admin');

	}


}
