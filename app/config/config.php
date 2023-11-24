<?php

define('APP_NAME', 'Ai-Buddy');
define('CHART_COLOR', '#1d5e55');

define('CATEGORIES', [

	'user'			=> ['title' => 'Meine Prompts (Bereich in Arbeit)'],

	'lr'			=> ['title' => 'ChatGPT Assistent', 'directActions' => false],
	'moz'			=> ['title' => 'MOZ Assistent', 'directActions' => false],
	'swp'			=> ['title' => 'SWP Assistent', 'directActions' => false],

	'redaktion'		=> ['title' => 'Redaktions Helfer', 'articleImport' => true],
	'redaktion-swp'	=> ['title' => 'Redaktion SWP', 'articleImport' => true],
	'sport'			=> ['title' => 'Alles für den Spocht!', 'articleImport' => true],
	'horoskope'		=> ['title' => 'das große Astro-Portal'],
	'salessupport'	=> ['title' => 'Sales'],
	'lesermarkt'	=> ['title' => 'Lesermarkt'],
	'planbar'		=> ['title' => 'Planbar Magazin'],
	'kse'			=> ['title' => 'Kundenservice und Event'],
	'pr'			=> ['title' => 'PR-Service'],
	'social'		=> ['title' => 'Social Media', 'articleImport' => true],
	'coding'		=> ['title' => 'Webentwicklung', 'hideDefault' => true],
	'tests'			=> ['title' => 'Testprompts', 'promptless' => true, 'articleImport' => true],

	'translate'		=> ['title' => 'Übersetzer', 'hideDefault' => true],
	'shorten'		=> ['title' => 'Textlängen Anpasssen', 'hideDefault' => true],
	'spelling'		=> ['title' => 'Rechtschreibung Korrigieren', 'hideDefault' => true],

]);

// Direct Actions per ID Hinterlegen

$prompt = 'Du bist ein KI-Assistent names AI-Buddy, Du arbeitest bei der Lausitzer Rundschau in Cottbus, einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern';

if (PORTAL == 'MOZ') {
	$prompt = 'Du bist ein KI-Assistent names AI-Buddy, Du arbeitest bei der Märkischen Oderzeitung in Frankfurt/Oder, einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern';
}
if (PORTAL == 'SWP') {
	$prompt = 'Du bist ein KI-Assistent names AI-Buddy, Du arbeitest bei der Südwest Presse in Ulm, einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern';
}

define('DEFAULTPROMPT', $prompt);