<?php

namespace app\models;

use \app\models\mcp\GeneralTools;
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
		return $privateMethods;
	}

	private function Search() {
		$this->ai->register_tool('web_search', ['type' => 'web_search']);
	}

	private function File_Search() {
		$this->ai->register_tool('file_search', ['type' => 'file_search']);
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


	private function Aibuddy_Github() {

		$name = 'AiBuddy Github';
		$schema = [
			'type' => 'mcp',
			'server_label' => 'AiBuddyGithub',
			'server_url' => 'https://gitmcp.io/tubsn/gpt-buddy',
			'require_approval' => 'never',
		];

		$this->ai->register_tool($name, $schema);
	}

	private function Pipedream() {
		$pipedream = new PipedreamMCPConnector(PIPEDREAM_CLIENT_ID, PIPEDREAM_CLIENT_SECRET);
		$toolSchema = $pipedream->create_tool_schema($app = 'slack_v2', $project = 'proj_9lsvxeZ', 'development');
		$this->ai->register_tool('SlackMCP', $toolSchema);
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

	private function Piano() {

		$ai->register_tool(
			'Piano',
			[
				'type' => 'mcp',
				'server_label' => 'piano-analytics-mcp-server',
				'server_url' => 'https://analytics-api-eu.piano.io/mcp/',
				'headers' => [
				  'x-api-key' => PIANOKEY,
				],
				'require_approval' => 'never',	
			],		
		);

	}

	private function BNN_MCP() {

		$this->ai->register_tool(
			'ChristianMCP',
			[
				'type' => 'mcp',
				'server_label' => 'ChristianMCP',
				'server_url' => 'https://compulsory-brown-dormouse.fastmcp.app/mcp',
				'require_approval' => 'never',
				'headers' => [
					'Authorization' => 'Bearer ' . MCP_TESTSERVER_AUTH,
				],				
			],		
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