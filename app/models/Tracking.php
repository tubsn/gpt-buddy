<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;

class Tracking extends Model
{

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'stats';
		$this->db->orderby = 'date';
		$this->db->order = 'DESC';
	}

}
