<?php

namespace app\views;
use \flundr\mvc\views\htmlView;

class ImageGeneratorLayout extends DefaultLayout {

	public $js = '/styles/js/imagegenerator.js';
	public $framework = [
		'/styles/js/vue34.min.js',
		'/styles/flundr/components/fl-dialog.js',		
		'/styles/js/components/vue-upload.js',
	];

}
