<?php

namespace app\views;
use \flundr\mvc\views\htmlView;

class MultiImportLayout extends DefaultLayout {

	public $css = ['/styles/flundr/css/defaults.css', '/styles/css/main.css',
		'/styles/css/multiimport.css',
	];

	public $js = '/styles/js/multiimport.js';
	public $framework = [
		'/styles/js/vue34.min.js',
		'/styles/js/components/vue-upload.js',
	];

}
