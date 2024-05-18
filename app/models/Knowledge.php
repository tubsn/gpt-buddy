<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;
use app\models\Callbacks;

class Knowledge extends Model
{

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'knowledge';
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
