<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;

class Knowledge extends Model
{

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'knowledge';
	}

	public function run($knowledgeTitles, $prompt = null) {

		$knowledgeTitles = explode_and_trim(',', $knowledgeTitles);

		$content = [];
		foreach ($knowledgeTitles as $index => $title) {
			if ($this->is_known($title)) {
				$content[$index] = $this->apply_knowledgebase($title);
			}
		}

		$prompt['knowledges'] = $content;
		return $prompt;
	}

	private function is_known($knowledge) {
		$knowledgenames = $this->distinct();
		$knowledgenames = array_map('strtolower', $knowledgenames);
		if (in_array(strtolower($knowledge), $knowledgenames)) {return true;}
		return false;
	}


	private function apply_knowledgebase($knowledgeTitle) {

		$knowledge = $this->search($knowledgeTitle, 'title');
		$knowledge = $knowledge[0] ?? [];
		
		if (empty($knowledge)) {return null;}

		if ($knowledge['url']) {
			$Scrape = new Scrape();
			$import = $Scrape->by_class_plain($knowledge['url'], $knowledge['selector']);
			$knowledge['content'] = $knowledge['content'] . "\n" . $import;			
		}

		return $knowledge['content'];
	}



	public function distinct() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare(
			"SELECT distinct title FROM $table"
		);

		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_COLUMN);
		return $output;
	}	

}
