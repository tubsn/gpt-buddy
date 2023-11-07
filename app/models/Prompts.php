<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;
use app\models\Callbacks;

class Prompts extends Model
{

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'prompts';
		$this->db->orderby = 'title';
	}

	public function get_and_track($id = null) {
		if ($id == 'default') {return ['content' => DEFAULTPROMPT];}
		if ($id == 'unbiased') {return;}
		$prompt = $this->get($id);
		$prompt = $this->apply_callback($prompt);
		$this->increase_hits($id);
		return $prompt;
	}

	public function category($category = '') {

		if ($category == 'user') {return $this->user_prompts(auth('id'));}

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare(
			"SELECT * FROM $table WHERE `category` = :category
			 AND (inactive IS NULL OR inactive = '0') ORDER BY `title`"
		);

		$SQLstatement->execute([':category' => $category]);
		$output = $SQLstatement->fetchall();
		return $output;

	}

	public function user_prompts($userID) {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare(
			"SELECT * FROM $table WHERE `category` = 'user'
			 AND `user` = :userID
			 AND (inactive IS NULL OR inactive = '0')"
		);

		$SQLstatement->execute([':userID' => $userID]);
		$output = $SQLstatement->fetchall();
		return $output;

	}

	public function categories() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("SELECT DISTINCT `category` FROM $table ORDER BY `category`");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_COLUMN);
		return $output;
	}

	public function list_all() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("SELECT * FROM $table ORDER BY `category`");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall();
		return $output;
	}

	public function most_hits() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("SELECT hits,title FROM $table WHERE hits >= 10 ");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall();
		return $output;
	}

	public function most_hits_by_type() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("SELECT sum(hits) as hits, category FROM $table WHERE hits >= 10 GROUP BY category ORDER BY hits DESC");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall();
		return $output;
	}

	public function update_with_history($new, $id) {

		$old = $this->get($id,['content','history']);
		if (!$old || $new['content'] == $old['content']) {return $this->update($new, $id);}

		$newHistory['content'] = $old['content'];
		$newHistory['edited'] = date("Y-m-d H:i");
		$newHistory['editor'] = auth('id') ?? null;

		// No further Processing needed on first Entry
		if (empty($old['history'])) {
			$new['history'] = [$newHistory];
			return $this->update($new, $id);
		}

		$history = json_decode($old['history'],1);
		
		// History should have a maximum of 10 entries
		if (count($history) >= 11) {$history = array_slice($history, -10);}

		array_push($history, $newHistory);
		$new['history'] = $history;

		return $this->update($new, $id);

	}


	public function apply_callback($prompt) {

		if (isset($prompt['format']) && $prompt['format']) {
			$prompt['content'] = $prompt['content'] . "\nVerwende Markdown falls du Formatierungen im Text platzierst.";
		}

		if (!isset($prompt['callback'])) {return $prompt;}
		$callbacks = new Callbacks();
		return $callbacks->run($prompt['callback'], $prompt);
	}

	private function increase_hits($id) {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("UPDATE $table SET `hits` = IFNULL(`hits`, 0) + 1 WHERE `id` = :id");
		$SQLstatement->execute([':id' => $id]);
		$output = $SQLstatement->fetch();
	}


}
