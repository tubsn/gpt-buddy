<?php

define('APP_START', microtime(true));
error_reporting(E_ALL);

// Pathing
define('ROOT', dirname(__DIR__,2) . DIRECTORY_SEPARATOR);
define('APP', ROOT . 'app' . DIRECTORY_SEPARATOR);
define('ENV_PATH', ROOT . '.env');
define('CONFIGPATH', APP . 'config' . DIRECTORY_SEPARATOR);
define('ROUTEFILE', CONFIGPATH . 'routes.php');
define('TEMPLATES', APP . 'templates' . DIRECTORY_SEPARATOR);
define('TEMPLATE_EXTENSION', '.tpl');
define('LOGS', ROOT . 'logs' . DIRECTORY_SEPARATOR);
define('PUBLICFOLDER', ROOT . 'public' . DIRECTORY_SEPARATOR);
define('PAGEURL', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]");

// Internal Flundr Config
define('CONTROLLER_NAMESPACE', '\app\controller\\');
define('MODEL_NAMESPACE', '\app\models\\');
define('VIEW_NAMESPACE', '\app\views\\');

// Load Environment Config
include_once ENV_PATH;
if (!defined('ENV_PRODUCTION')) {define('ENV_PRODUCTION', false);}
if (ENV_PRODUCTION) {error_reporting(0);}

// Load App Config
include_once CONFIGPATH . 'config.php';

// Run Flundr App
new flundr\core\Application;
