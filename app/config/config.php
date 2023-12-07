<?php

define('APP_NAME', 'Ai-Buddy'); // Name des Programmes
define('CHART_COLOR', '#1d5e55'); // Farbwert in Statistiken

/*
im Folgenden werden die Promptkategorien definiert
Hinterlegte Kategorieren lassen sich über www.aibuddy.de/kagetoriename aufrufen.
und so z.B. in der Hauptnavigation integrieren (app/templates/navigation)
Diese Kategorien stehen im Frontend beim Prompt anlegen (www.aibuddy.de/settings) zur Auswahl.

Folgende Optionen sind für die Kategorien zulässig:
"title" => 'Überschrift' 

"articleImport" => true zeigt für diese Promptkategorie immer die Artikel Import Funktion an (das funktioniert aber nur, wenn ihr die Artikel Daten in irgendeiner Form - vorbei an der Paywall z.B. als RSS Feed oder per API aus dem CMS zur Verfügung stellen könnt.

"hideDefault" => true versteckt für diese Prompt Kategorie den Standard Chat z.B. wenn du für diese Prompt Kategorie einen eigenen Basic Prompt anlegen willst z.B. Rubrik "Webdesign" hier macht es ja keinen Sinn das ChatGPT bei der Tageszeitung arbeitet sondern vielleicht in der Agentur des Hauses

"promptless" => true schaltet in der Promptkategorie die möglichkeit frei die ChatGPT API komplett ohne Systemprompt zu nutzen. Das ist z.B. fürs Testen kann gut. Die Auswahl heißt dann "Chat ohne Prompt"
*/

define('CATEGORIES', [

	'lr'			=> ['title' => 'ChatGPT Assistent',],
	'moz'			=> ['title' => 'MOZ Assistent',],
	'swp'			=> ['title' => 'SWP Assistent',],

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


// Hier wird der Standard Prompt hinterlegt, dieser kann im Mandantenmodus je z.B. nach Portal variieren
$prompt = 'Du bist ein KI-Assistent names AI-Buddy, Du arbeitest bei der Lausitzer Rundschau in Cottbus, einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern';

/*
if (PORTAL == 'MOZ') {
	$prompt = 'Du bist ein KI-Assistent names AI-Buddy, Du arbeitest bei der Märkischen Oderzeitung in Frankfurt/Oder, einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern';
}
if (PORTAL == 'SWP') {
	$prompt = 'Du bist ein KI-Assistent names AI-Buddy, Du arbeitest bei der Südwest Presse in Ulm, einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern';
}
*/

define('DEFAULTPROMPT', $prompt);