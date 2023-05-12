<?php

namespace app\controller;

use flundr\mvc\Controller;

class Error extends Controller {

	function __construct($errorData) {

		$this->view('DefaultLayout');

		$errorCode = $errorData->getCode();
		$viewData['error']['code'] = $errorCode;
		$viewData['error']['message'] = $errorData->getMessage();

		if (!ENV_PRODUCTION) {
			$viewData['error']['trace'] = $errorData->getTraceAsString();
			$viewData['error']['line'] = $errorData->getLine();
			$viewData['error']['file'] = $errorData->getFile();
		}

		if (preg_match('/^\d{3}$/', $errorCode)) { http_response_code(intval($errorCode)); }
		else { http_response_code(404); }

		$this->view->navigation = null;
		$this->view->templates['footer'] = null;
		$this->view->render('layout/error', $viewData);
	}



}
