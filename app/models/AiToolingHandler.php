<?php

namespace app\models;

use \app\models\mcp\GeneralTools;
use \app\models\mcp\PoliceArticle;
use \app\models\mcp\PipedreamMCPConnector;
use \app\models\mcp\DriveMixer;
use flundr\utility\Log;

class AiToolingHandler {

	private $ai;

	public function __construct($aiHandler = null) {$this->ai = $aiHandler;}
	public function connect($aiHandler) {$this->ai = $aiHandler;}

	public function use($toolnames) {

		if (!is_array($toolnames)) {$toolnames = [$toolnames];}

		foreach ($toolnames as $toolname) {
			if (method_exists($this, $toolname)) {
				call_user_func([$this, $toolname]);
			} else {
				Log::error("Tool: $toolname not found or causing an error");
			}
		}

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
			'DriveRAG',
			[
				'name' => 'DriveRAG',
				'description' => 'Search Engine, that grants Access to an archive of articles published by bnn.de. You can gather valid information here on local news covering topics in Karlsruhe and Baden Württemberg. This function will supply you with a number of articles that are relevant to your search topic, the results include a "score" from 0 to 1 which determins hoch relevant that article is to your search. 1 Means highly relevant 0 not so relevant. Search the database with a query which consists of boiled down semantic tags which fit the users request.',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'query' => [
							'type' => 'string',
							'description' => 'The topic you are looking for. Broke down into 1-6 short seo like tags.',
						],
						'from' => [
							'type' => 'string',
							'description' => 'Daterange starting from in YYYY-MM-DD',
						],
						'to' => [
							'type' => 'string',
							'description' => 'Daterange to in YYYY-MM-DD',
						],
						'limit' => [
							'type' => 'integer',
							'description' => 'Maximum amount of Article Items',
						],
						'summary' => [
							'type' => 'boolean',
							'description' => 'Flag to request only a short Version of the article without the Full content default should be false. Do not use this field until I explicitly you for it',
						],
						'tags' => [
							'type' => 'string',
							'description' => 'A comma seperated list of Tags to filter articles with these tags. Do not use this field until I explicitly instruct you to do so and name the tag or tags!',
						],
						'section' => [
							'type' => 'string',
							'description' => 'Allowys to filter Articles by a specific section. Do not use this field until I explicitly instruct you to do so and tell you the section!',
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

				if ($args['tags']) {$filters['tags'] = $args['tags'];}
				if ($args['section']) {$filters['ressorts'] = $args['section'];}

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