<?php

define('APP_NAME', 'GPT-Buddy'); // Name des Programmes
define('CHART_COLOR', '#1d5e55'); // Farbwert in Statistiken


// Hier werden die Verfügbaren Modelle Konfiguriert.
// 'apiname' ist der Name der OpenAI Modelle. Description und der Keyname sind frei wählbar.
define('AIMODELS', [
	'GPT-4o' 		=> ['apiname' => 'gpt-4o', 'description' => 'standard Model, gut, günstig'],
	'GPT-4o-mini' 	=> ['apiname' => 'gpt-4o-mini', 'description' => 'am günstigsten, wenig Wissen'],
	'o3-Mini-Low'	=> ['apiname' => 'o3-mini', 'description' => 'bessere Logik, langsamer'],
	'o3-Mini-High'	=> ['apiname' => 'o3-mini', 'description' => 'beste Logik, sehr langsam', 'reasoning' => 'high'],
]);


// Hier wird der Standard Prompt hinterlegt, dieser kann im Mandantenmodus 
// je nach Portal unterschiedlich eingestellt werden
$prompt = 'Du bist ein KI-Assistent names GPT-Buddy, Du arbeitest bei einer deutschen Tageszeitung. Deine Aufgabe ist es den Redakteuren den Redaktionsalltag zu erleichtern';

define('DEFAULTPROMPT', $prompt);


/*
im Folgenden werden die Promptkategorien definiert. 
Hinterlegte Kategorieren lassen sich über www.aibuddy.de/kagetoriename aufrufen 
und z.B. in der Hauptnavigation integrieren (app/templates/navigation)

Der Kategorienname muss nicht dem Navigationsnamen entsprechen!
Kategorien stehen im Frontend beim Prompt anlegen (www.aibuddy.de/settings) zur Auswahl.

Folgende Optionen sind für die Kategorien zulässig:
"title" => 'Seiten-Überschrift' 

"articleImport" => true zeigt für diese Promptkategorie immer die Artikel Import Funktion an (das funktioniert aber nur, wenn ihr die Artikel Daten in irgendeiner Form - vorbei an der Paywall z.B. als RSS Feed oder per API aus dem CMS zur Verfügung stellen könnt.

"hideDefault" => true versteckt für diese Prompt Kategorie den Standard Chat z.B. wenn du für diese Prompt Kategorie einen eigenen Basic Prompt anlegen willst z.B. Rubrik "Webdesign" hier macht es ja keinen Sinn das ChatGPT bei der Tageszeitung arbeitet sondern vielleicht in der Agentur des Hauses

"promptless" => true schaltet in der Promptkategorie die Möglichkeit frei die ChatGPT API komplett ohne Systemprompt zu nutzen. Das ist z.B. fürs Testen kann gut. Die Auswahl heißt dann "Chat ohne Prompt"
*/

define('CATEGORIES', [

	'redaktion'		=> ['title' => 'Redaktions Helfer', 'articleImport' => true],
	'sport'			=> ['title' => 'Alles für den Spocht!', 'articleImport' => true],
	'support'		=> ['title' => 'Kundenservice', 'hideDefault' => true],
	'sales'			=> ['title' => 'Anzeigenverkauf', 'hideDefault' => true],
	'social'		=> ['title' => 'Social Media', 'articleImport' => true, 'hideDefault' => true],
	'coding'		=> ['title' => 'Webentwicklung', 'hideDefault' => true],
	'tests'			=> ['title' => 'Testprompts', 'promptless' => true, 'hideDefault' => true],

	'bilder'		=> ['title' => 'Prompts für den Bildgenerator', 'hideDefault' => true],
	'translate'		=> ['title' => 'Übersetzer', 'hideDefault' => true],
	'spelling'		=> ['title' => 'Rechtschreibung Korrigieren', 'hideDefault' => true],
	'system'		=> ['title' => 'System Prompts', 'hideDefault' => true],

]);
