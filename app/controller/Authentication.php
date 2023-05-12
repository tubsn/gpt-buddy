<?php

namespace app\controller;

use flundr\mvc\Controller;
use flundr\utility\Session;
use flundr\auth\LoginHandler;
use flundr\auth\PasswordReset;
use flundr\auth\Auth;

class Authentication extends Controller {

	private $loginHandler;
	
	public function __construct() {
		$this->view('DefaultLayout');
		$this->loginHandler = new LoginHandler();
	}

	public function login() {

		if (Auth::logged_in()) {$this->view->redirect('/profile');}

		$viewData['message'] = null;

		if (isset($_POST['username']) && isset($_POST['password'])) {

			try {
				$loggedIn = $this->loginHandler->login($_POST['username'], $_POST['password']);
			}
			catch (\Exception $e) {
				$loggedIn = false;
				$viewData['originalMessage'] = $e->getMessage();
				$viewData['message'] = 'Login Fehlgeschlagen,<br/> E-Mail Adresse oder Passwort falsch';
				$viewData['username'] = $_POST['username'];
			}

			if ($loggedIn) {
				$redirectUrl = Session::get('referer') ?? $_POST['referer'] ?? '/';
				$this->view->redirect($redirectUrl);
			}

		}

		$this->view->title = 'Login';
		$this->view->render('auth/login',$viewData);

	}

	public function logout() {
		$this->loginHandler->logout();
		$this->view->redirect('/');
	}


	public function profile() {

		if (!Auth::logged_in()) {$this->view->redirect('/login');}

		// Update Current Authuser with Info from the UserDB
		Auth::refresh_auth(); // e.g. to quickly Refresh Rights

		$this->view->title = 'Nutzer-Profil';
		$this->view->render('auth/profile', ['logins' => $this->loginHandler->list_logins()]);
	}

	public function latest_logins() {
		$this->loginHandler->list_logins();
	}

	public function edit_profile() {

		if (!Auth::logged_in()) {$this->view->redirect('/login');}

		if ($_POST) {

			if (!hash_equals($_POST['CSRFToken'], Session::get('CSRFToken'))) {
				throw new \Exception("Token Missmatch", 403); die;
			}

			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				throw new \Exception("Bitte Gültige E-Mail eintragen", 400); die;
			}

			if (!empty($_POST['password']) && strlen($_POST['password']) < 5) {
				throw new \Exception("Bitte Passwort mit mindestens 5 Zeichen eingeben", 400); die;
			}

			$this->loginHandler->update_profile($_POST);
			$this->view->redirect('/profile');

		}

		$viewData['user'] = Auth::profile();
		$this->view->title = 'Edit Profile';
		$this->view->render('auth/profile-edit',$viewData);

	}


	public function password_reset_form() {
		$this->view->title = 'Passwort zurücksetzen';
		$this->view->layout = 'login';
		$this->view->render('auth/reset-pw-request');
	}

	public function password_reset_send_mail() {
		$resetHandler = new PasswordReset();
		$resetHandler->mailSubject = 'Passwort zurücksetzen - Anfrage';
		$resetHandler->mailTemplate = 'auth/reset-pw-email';

		$resetHandler->by_email($_POST['email']);

		usleep(600000);

		$this->view->layout = 'login';
		$this->view->title = 'E-Mail Versand abgeschlossen';
		$viewData['message'] = 'Ist uns diese Adresse bekannt, erhalten Sie in den nächsten Minuten eine E-Mail mit weiteren Informationen. Sie können dieses Fenster jetzt schließen oder weitere Adressen angeben.';
		$this->view->render('auth/reset-pw-request', $viewData);

	}

	public function password_change_form($resetToken = null) {

		if (is_null($resetToken)) {
			$this->view->redirect('/password-reset'); return false;
		}

		$resetHandler = new PasswordReset();
		$resetHandler->check_token_integrity($resetToken);

		$this->view->title = 'Passwort zurücksetzen';
		$this->view->layout = 'login';
		$viewData['changeToken'] = $resetToken;
		$this->view->render('auth/reset-pw-change', $viewData);
	}


	public function password_change_process($resetToken = null) {

		if (!hash_equals($_POST['CSRFToken'], Session::get('CSRFToken'))) {throw new \Exception("Token Missmatch", 403); die;}

		try {
			$resetHandler = new PasswordReset();
			$resetHandler->change_password($_POST['changeToken'], $_POST['password']);
		}

		catch (\Exception $error) {
			$this->password_change_error_response($error);
			return false;
		}

		// if Everything worked the User should be logged in by now and can be redirected to his origin
		$this->view->redirect(Session::get('referer') ?? '/');
	}


	private function password_change_error_response($error) {

		$this->view->layout = 'login';
		$viewData['changeToken'] = $_POST['changeToken'];

		$originalErrorMessage = $error->getMessage();
		$code = $error->getCode();

		switch ($code) {

			// 408 - Token Expired
			case 408:
				$this->view->title = 'Token Abgelaufen';
				$viewData['message'] = 'Ihr Passwort Reset hat zulange gedauert. Sie können den Prozess gern erneut starten.';
				$this->view->render('auth/reset-pw-request', $viewData);
			break;

			// 400 - Password malformed
			case 400:
				$this->view->title = 'Passwort Fehler';
				$viewData['message'] = 'Ihr Passwort muss mindestens 5 Zeichen haben.';
				$this->view->render('auth/reset-pw-change', $viewData);
			break;

			default:
				$this->view->title = 'Passwort Zurücksetzen';
				$viewData['message'] = 'Es ist ein fehler aufgetreten bitte starten Sie den Prozess erneut. (' . $originalErrorMessage .')';
				$this->view->render('auth/reset-pw-request', $viewData);
			break;
		}

	}

}
