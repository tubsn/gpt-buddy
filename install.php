<?php

require 'vendor/autoload.php';

if (!file_exists('.env')) { die('Please rename your example.env to .env (and edit it!!!)');}

require_once '.env';

$installer = new flundr\auth\AuthInstall();
$installer->install();
