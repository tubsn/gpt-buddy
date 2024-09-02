<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;
use \PDO;

class Imports extends Model
{

	public $ressort;
	public $prompt;

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'imports';
	}

	public function filter($options = []) {

		$filters = [];
		$parameters = [];
		$whereSelector = 'WHERE';

		if (isset($options['ressort'])) {
			if (count($filters) > 0) {$whereSelector = 'AND';}
			array_push($filters, $whereSelector . ' ressort = :ressort');
			$parameters['ressort'] = $options['ressort'];
		}

		if (isset($options['location'])) {
			if (count($filters) > 0) {$whereSelector = 'AND';}
			array_push($filters, $whereSelector . ' location = :location');
			$parameters['location'] = $options['location'];
		}

		$from = null;
		$to = null;

		if (isset($options['from'])) {$from = $options['from'];}
		if (isset($options['to'])) {$to = $options['to'];}
		if ($from || $to) {
			
			
			if ($to < $from) {
				$frombackup = $from; $from = $to; $to = $frombackup;
			}
			

			if ($from) {
				$from = substr($from,5);
				if (count($filters) > 0) {$whereSelector = 'AND';}
				array_push($filters, $whereSelector . " DATE_FORMAT(birthday, '%m-%d') >= :from");
				$parameters['from'] = $from;
			}

			if ($to) {
				$to = substr($to,5);
				if (count($filters) > 0) {$whereSelector = 'AND';}
				array_push($filters, $whereSelector . " DATE_FORMAT(birthday, '%m-%d') <= :to");
				$parameters['to'] = $to;
			}

		}


		$filters = implode(' ', $filters);


		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT * FROM $table
			$filters
			ORDER BY created DESC
		");
		$SQLstatement->execute($parameters);

		$output = $SQLstatement->fetchALL();
		return $output;

	}


	public function gather_stats($field = 'ressort') {

		if (!in_array($field, $this->table_fields())) {return [];}
	
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT $field, count(*) FROM $table 
			GROUP BY $field
		");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_COLUMN);	
		return $output;
	}

	public function table_fields() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("SHOW columns FROM $table");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchAll();	
		return array_column($output,'Field');
	}


	public function distinct_locations() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT distinct location
			FROM $table 
		");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchAll(PDO::FETCH_COLUMN);
		return array_filter($output);
	}

	public function latest() {

		$from = 'yesterday';
		$to = 'tomorrow';

		$from = date('y-m-d', strtotime($from));
		$to = date('y-m-d', strtotime($to));

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT *
			FROM $table 
			WHERE created BETWEEN :startdate AND :enddate
			ORDER BY created DESC

		");
		$SQLstatement->execute([':startdate' => $from, 'enddate' => $to]);
		$output = $SQLstatement->fetchAll();
		return $output;
	}


	public function gather_by_week() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT 
			WEEK(concat(DATE_FORMAT(NOW(), '%Y-'),DATE_FORMAT(birthday, '%m-%d')), 1) AS calendar_week,
			count(*)
			FROM $table 
			GROUP BY calendar_week
			ORDER BY calendar_week DESC

		");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_COLUMN);
		return $output;

	}


	public function gather($from = 'today', $to = 'today +7 days', $filter = null) {

		$ressortFilter = '';
		if (in_array($filter, IMPORT_RESSORTS) ) {
			$ressortFilter = "AND ressort = '" . $filter ."'";
		}

		$from = date('m-d', strtotime($from));
		$to = date('m-d', strtotime($to));

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DATE_FORMAT(birthday, '%m-%d') as birthdate,
			firstname, lastname, location, DATE_FORMAT(birthday, '%d.%m.%Y') as birthday, age
			FROM $table 
			WHERE DATE_FORMAT(birthday, '%m-%d') BETWEEN :startdate AND :enddate
			$ressortFilter
			ORDER BY birthdate, age DESC, lastname
		");
		$SQLstatement->execute([':startdate' => $from, 'enddate' => $to]);
		$output = $SQLstatement->fetchAll(PDO::FETCH_GROUP);
		
		//$output = array_map([$this, 'map_date_keys'], $output);

		$output = $this->map_date_keys($output);

		return $output;

	}


	public function map_date_keys($input) {

		$days = [
			0 => 'Montag',
			1 => 'Dienstag',
			2 => 'Mittwoch',
			3 => 'Donnerstag',
			4 => 'Freitag',
			5 => 'Samstag',
			6 => 'Sonntag',
		];

		$out = [];
		foreach ($input as $date => $set) {
			$date = date('Y') . '-' . $date;
			$date = $days[date('w', strtotime($date))] . ' ' . date('d.m.', strtotime($date));
			$out[$date] = $set;
		}
		return $out;
	}



	public function add($data, array $options = []) {
		$ids = [];
		if (!is_array($data)) {return false;}
		foreach ($data as $set) {
			if (!is_array($set)) {continue;}
			$import = $this->fill_import($set);
			$import['ressort'] = $this->ressort ?? null;
			$newID = $this->create($import);
			array_push($ids, $newID);
		}
		return $ids;
	}

	public function fill_import(array $data) {

		$out = $this->base($this->prompt['title']);
		foreach ($data as $key => $value) {
			if (strToLower($key) == 'name') {$out['lastname'] = $value;}
			if (strToLower($key) == 'vorname') {$out['firstname'] = $value;}
			if (strToLower($key) == 'nachname') {$out['lastname'] = $value;}
			if (strToLower($key) == 'straße') {$out['location'] = $value;}
			if (strToLower($key) == 'anschrift') {$out['location'] = $value;}
			if (strToLower($key) == 'adresse') {$out['location'] = $value;}
			if (strToLower($key) == 'wohnort') {$out['location'] = $value;}
			if (strToLower($key) == 'ort') {$out['location'] = $value;}
			if (strToLower($key) == 'datum') {$out['birthday'] = $value;}
			if (strToLower($key) == 'alter') {$out['age'] = $value;}
			if (array_key_exists($key, $out)) {$out[$key] = $value;}
		}

		if (!empty($out['birthday'])) {
			$out['birthday'] = date('Y-m-d', strtotime($out['birthday']));
		}

		if (empty($out['age']) && !empty($out['birthday'])) {
			$out['age'] = $this->calculate_age($out['birthday']);
		}

		$out['age'] = intval($out['age']);
		$out['raw'] = $data;

		return $out;
	}


	public function base($type = 'birthday') {
		return [
			'type' => $type,
			'ressort' => null,
			'firstname' => null,
			'lastname' => null,
			'location' => null,
			'birthday' => null,
			'age' => null,
		];
	}

	public function calculate_age($birthday) {
		$birthday = new \DateTime($birthday);
		$today = new \DateTime('today');
		$age = $today->diff($birthday)->y;
		return $age;
	}

	public function count() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT count(*) as 'usage' FROM $table 
		");
		$SQLstatement->execute();
		$output = $SQLstatement->fetch(\PDO::FETCH_COLUMN);
		return $output;

	}

	public function clear_db() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			TRUNCATE TABLE $table;  
		");
		$SQLstatement->execute();
		$output = $SQLstatement->fetch();
		return $output;
	}

	public function remove_old_entries() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			DELETE FROM $table
			WHERE DATE_FORMAT(birthday, '%m-%d') < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -1 WEEK), '%m-%d');
		");
		$SQLstatement->execute();
		$output = $SQLstatement->fetch();
		return $output;
	}



}
