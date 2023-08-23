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


	public function apply_callback($prompt) {
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
