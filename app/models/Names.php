<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;

class Names extends Model
{

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'ai_namen';
		$this->db->orderby = 'date';
	}

}
