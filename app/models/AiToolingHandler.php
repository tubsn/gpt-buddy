<?php

namespace app\models;

use \app\models\mcp\GeneralTools;
use \app\models\mcp\PoliceArticle;
use \app\models\mcp\PipedreamMCPConnector;
use \app\models\mcp\DriveMixer;
use flundr\utility\Log;

class AiToolingHandler {

	private $ai;
	public $usedTools = [];

	public function __construct($aiHandler = null) {$this->ai = $aiHandler;}
	public function connect($aiHandler) {$this->ai = $aiHandler;}

	public function use($toolnames) {

		if (!is_array($toolnames)) {$toolnames = [$toolnames];}

		foreach ($toolnames as $toolname) {
			if (method_exists($this, $toolname)) {
				call_user_func([$this, $toolname]);
				$this->make_use($toolname);
			} else {
				Log::error("Tool: $toolname not found or causing an error");
			}
		}

	}

	public function make_use($toolname) {
		if (in_array($toolname, $this->usedTools)) {return;}
		array_push($this->usedTools, $toolname);
	}

	public function used() {
		if (empty($this->usedTools)) {return null;}
		return implode(',', $this->usedTools);
	}	

	public function list() {
		$reflection = new \ReflectionClass($this);
		$methods = $reflection->getMethods(\ReflectionMethod::IS_PRIVATE);

		$privateMethods = [];
		foreach ($methods as $method) {
			$privateMethods[] = $method->getName();
		}

		$methodNames = array_map(function($name) {
			$name = str_replace('_', ' ', $name);
			return ucwords($name);
		}, $privateMethods);

		$privateMethods = array_combine($privateMethods, $methodNames);
		asort($privateMethods);
		return $privateMethods;
	}

	private function Search() {
		$this->ai->register_tool('web_search', ['type' => 'web_search']);
	}

	private function File_Search() {
		$this->ai->register_tool('file_search', ['type' => 'file_search']);
	}

	private function Call_GPT() {

		$this->ai->register_tool(
			'Call_GPT',
			[
				'name' => 'Call_GPT',
				'description' => 'With this tool you can make a subquery to ChatGPT. You can ask questions to the AI, get help with your task or run a predesigned workflow by using a promptID. This allows you to create a chain of thought or chain of prompts like approach to give an improved awnser to your main task.',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'query' => [
							'type' => 'string',
							'description' => 'The Question you want to ask the Ai Model or the specific Task you want to be resolved',
						],
						'promptID' => [
							'type' => 'integer',
							'description' => 'The ID of a predefined Prompt. This is optional and only needed if the user specifically asks you to use a Prompt.',
						],						
					],
					'required' => ['query'],
				],
			],
			function (array $args) {return new GeneralTools()->call_gpt($args);}
		);
	}


	private function URL_Scraper() {

		$this->ai->register_tool(
			'URLScraper',
			[
				'name' => 'URLScraper',
				'description' => 'This tool allows you to cURL a Website for its Plain Text content by passing a url and an optional CSS Selector. Be aware that this does not follow Links on that Website, but you can chain the Tool if neccesary. You can access multiple DOM Nodes via the selector. The Tool is based on the PHP Dom\HTMLDocument',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'url' => [
							'type' => 'string',
							'description' => 'The URL of the Website you want to crawl. You need to add the https:// or http:// prefix.',
						],
						'selector' => [
							'type' => 'string',
							'description' => 'A Valid CSS Selector e.g. main > article:last-child or p or .classname',
						],						
					],
					'required' => ['url'],
				],
			],
			function (array $args) {
				$url = $args['url'];
				$selector = $args['selector'] ?? null;
				return new GeneralTools()->dom_parser($url, $selector);
			}
		);
	}

	private function DriveRag() {

		$this->ai->register_tool(
			'DriveRAG', [
				'name' => 'DriveRAG',
				'description' => 'Durchsucht das interne bnn.de-Artikelarchiv für Inhaltsrecherche und lokale Nachrichten, insbesondere aus Karlsruhe und Baden-Württemberg. Liefert Artikel mit Metadaten, URLs und standardmäßig vollständigem Text.

				Suchregeln:
				- query: 1–8 prägnante Keywords als durch Leerzeichen getrennter String, idealerweise 2–4. Bevorzuge Substantive, Eigennamen und kurze Nomen-Phrasen; keine Stopwörter, vollständigen Fragen oder Füllbegriffe. Wichtige Ortsnamen beibehalten.
				- Bei mehr als 4 relevanten Keywords oder mehreren gleichwichtigen Themen: bis zu 3 fokussierte Suchen nach Hauptthema, Synonymen oder Kontext. Ergebnisse nach Artikel-ID deduplizieren.
				- Standardmäßig insgesamt höchstens 10 Artikel, sofern der Nutzer keine andere Anzahl verlangt. Bei mehreren Aufrufen das Gesamtlimit aufteilen, pro Aufruf aufrunden und die zusammengeführte Auswahl auf das Gesamtlimit begrenzen.

				Zeitraum:
				- from und to immer als YYYY-MM-DD setzen; sie filtern das Veröffentlichungsdatum, nicht Aktualisierung oder Ereignisdatum. from darf nicht nach to liegen.
				- Ausdrückliche Veröffentlichungszeiträume übernehmen, relative Angaben anhand des aktuellen Datums umrechnen.
				- Ohne Zeitvorgabe: bei aktuellen Themen heute minus 90 Tage bis heute; sonst einen historisch passenden Beginn bis heute wählen. Ohne explizite Parameter greift technisch nur ein 7-Tage-Zeitraum.
				- Aktualisierungsdatumsfilter werden nicht unterstützt.

				Bei zu wenigen Treffern:
				- Ohne ausdrückliche Zeitvorgabe schrittweise auf 6 Monate, 1, 2 oder 5 Jahre erweitern; bereits längere Zeiträume nicht verkürzen.
				- Danach unwichtigstes Keyword entfernen, Synonym/Oberbegriff versuchen, zuletzt mit einem Kern-Keyword suchen. Gewünschtes Trefferlimit und ausdrückliche Filter beibehalten.

				summary, tags und section nur auf ausdrücklichen Wunsch setzen; Tags und Ressort müssen vom Nutzer genannt werden, nicht aus query ableiten. Eine gewünschte Antwortzusammenfassung allein rechtfertigt summary nicht. Ergebnisse auf tatsächliche Relevanz prüfen; Suchtreffer allein bestätigen keine Aussagen.',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'query' => [
							'type' => 'string',
							'description' => '1–8 prägnante Keywords als durch Leerzeichen getrennter String, idealerweise 2–4. Substantive, Eigennamen oder kurze Nomen-Phrasen, keine Stopwörter oder vollständigen Fragen. Beispiel: „Karlsruhe Schlosslichtspiele Programm“.',
						],
						'from' => [
							'type' => 'string',
							'description' => 'Beginn des Veröffentlichungszeitraums einschließlich dieses Tages, als YYYY-MM-DD. Immer setzen. Nutzervorgaben haben Vorrang; sonst bei aktuellen Themen heute minus 90 Tage, bei historischen Themen ein passendes Startdatum.',
						],
						'to' => [
							'type' => 'string',
							'description' => 'Ende des Veröffentlichungszeitraums einschließlich dieses Tages, als YYYY-MM-DD. Immer setzen. Ohne Nutzervorgabe heute; darf nicht vor from liegen.',
						],
						'limit' => [
							'type' => 'integer',
							'minimum' => 1,
							'default' => 10,
							'description' => 'Maximale Artikelanzahl, standardmäßig 10. Mehr nur auf ausdrücklichen Nutzerwunsch. Bei mehreren Suchen das Gesamtlimit durch die Anzahl der Aufrufe teilen und aufrunden.',
						],
						'summary' => [
							'type' => 'boolean',
							'default' => false,
							'description' => 'Bei true nur Titel und Teaser statt vollständigem Artikeltext abrufen. Nur setzen, wenn ausdrücklich dieser verkürzte Abruf verlangt wird; nicht allein für eine zusammengefasste Antwort.',
						],
						'tags' => [
							'type' => 'string',
							'description' => 'Kommagetrennte Archiv-Tags zur Filterung. Nur setzen, wenn der Nutzer ausdrücklich nach diesen konkret genannten Tags filtern möchte. Nicht aus query ableiten.',
						],
						'section' => [
							'type' => 'string',
							'description' => 'Ressortfilter. Nur setzen, wenn der Nutzer die Filterung ausdrücklich verlangt und das Ressort nennt. Nicht aus Thema oder Ortsnamen ableiten.',
						],
					],
					'required' => ['query'],
				],
			],
			function (array $args) {
				$mixer = new DriveMixer;

				$query = $args['query'];
				$from = $args['from'] ?? 'today -7days';
				$to = $args['to'] ?? 'today';
				$limit = $args['limit'] ?? '10';
				$filters = null;
				$summary = $args['summary'];

				if ($args['tags'] ?? false) {$filters['tags'] = $args['tags'];}
				if ($args['section'] ?? false) {$filters['ressorts'] = $args['section'];}
				if ($args['exact'] ?? false) {$filters['exact'] = $args['exact'];}

				return $mixer->search($query, $from, $to, $limit, $filters, $summary);
			}
		);

	}

	private function DriveAnalytics() {

		$mixer = new DriveMixer;
		$this->ai->register_tool(
			'DriveAnalytics',
			[
				'name' => 'DriveAnalytics',
				'description' => 'Grants Access to a list of articles from BNN.de sorted by performance. The list containing Stats like views, engagement_rate and the articles content as a Json Array. Important if you are asked for a specific day use from = -1day, to = the day',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'from' => [
							'type' => 'string',
							'description' => 'Daterange starting from in YYYY-MM-DD -1 day',
						],
						'to' => [
							'type' => 'string',
							'description' => 'Daterange to in YYYY-MM-DD',
						],
					],
					'required' => ['from', 'to'],
				],
			],
			function (array $args) use ($mixer) {return $mixer->analytics($args);}
		);
	}

	private function Date() {
		$this->ai->register_tool(
			'current_datetime',
			[
				'name' => 'current_datetime',
				'description' => 'Grants access to the current date and time',
				'parameters' => [
					'type' => 'object',
					'properties' => new \stdClass(), // If Empty Needs to be an empty Object!
				],
			],
			function (array $args) {
				return new GeneralTools()->current_datetime();
			}
		);
	}


	private function Weekday() {
		$this->ai->register_tool(
			'getweekday',
			[
				'name' => 'getweekday',
				'description' => 'Returns the weekday for a given date',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'date' => [
							'type' => 'string',
							'description' => 'Date in YYYY-MM-DD',
						],
					],
					'required' => ['date'],
				],
			],
			function (array $args) {
				return new GeneralTools()->get_weekday($args);
			}
		);

	}

	private function Charcount() {

		$this->ai->register_tool(
			'count_chars',
			[
				'name' => 'count_chars',
				'description' => 'Count the Chars of given string',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'text' => [
							'type' => 'string',
							'description' => 'Text in String format',
						],
					],
					'required' => ['text'],
				],
			],
			function (array $args) {
				return new GeneralTools()->count_chars($args);
			}
		);

	}

}