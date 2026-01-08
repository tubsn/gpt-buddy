<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;
use app\models\Prompts;

class Usage extends Model
{

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'stats';
		$this->db->orderby = 'date';
		$this->db->order = 'DESC';
	}



	public function conversations_by_hour() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DATE_FORMAT(date, '%H') as hour, count(*) as 'usage'
			FROM $table 
			WHERE date >= '2025-01-01'
			GROUP BY hour ORDER BY hour ASC
			");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}

	public function conversations_by_day($days = 30) {

		$days = intval($days);

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DATE_FORMAT(date, '%Y-%m-%d') as day, count(*) as 'usage'
			FROM $table 
			WHERE date > CURDATE() - INTERVAL :days DAY
			GROUP BY day ORDER BY day ASC
			");
		$SQLstatement->execute([':days' => $days]);
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}

	public function conversations_by_weekday() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DAYOFWEEK(date) as weekday, count(*) as 'usage'
			FROM $table 
			WHERE date >= '2025-01-01'
			GROUP BY weekday ORDER BY weekday ASC
			");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}

	public function conversations_by_week() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DATE_FORMAT(date, '%Y-%v') as week, count(*) as 'usage'
			FROM $table 
			WHERE date >= '2025-01-01'
			GROUP BY week ORDER BY week ASC
			");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}

	public function conversations_by_month() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DATE_FORMAT(date, '%Y-%m') as month, count(*) as 'usage'
			FROM $table 
			WHERE date >= '2025-01-01'
			GROUP BY month ORDER BY month ASC
			");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}


	public function stats_gap_december_2025() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT count(*) from $table 
			WHERE date BETWEEN '2025-12-01' AND '2025-12-31';
		");
		$SQLstatement->execute();
		$output = $SQLstatement->fetch(\PDO::FETCH_COLUMN);
		return $output;
	}


	public function number_of_conversations() {
		$SQLstatement = $this->db->connection->prepare("SELECT count(*) from stats");
		$SQLstatement->execute();
		$output = $SQLstatement->fetch(\PDO::FETCH_COLUMN);
		return $output;
	}

	public function alltime() {

		$SQLstatement = $this->db->connection->prepare("
			SELECT
				stats.prompt_id AS prompt_id,
				CASE
					WHEN stats.prompt_id IS NULL THEN 'Standard Chat'
					WHEN prompts.id IS NULL THEN 'Prompt gelöscht'
					ELSE prompts.title
				END AS title,
				prompts.category AS category,
				COUNT(*) AS hits
			FROM stats LEFT JOIN prompts ON prompts.id = stats.prompt_id
			GROUP BY
				stats.prompt_id,
				prompts.title,
				prompts.category				
			ORDER BY hits DESC, title ASC;
			");

		$SQLstatement->execute();
		$output = $SQLstatement->fetchall();
		return $output;

	}

	public function alltime_categories() {

		$SQLstatement = $this->db->connection->prepare("
			SELECT prompts.category AS category, COUNT(*) AS hits
			FROM stats LEFT JOIN prompts ON prompts.id = stats.prompt_id
			GROUP BY prompts.category	
			ORDER BY hits DESC");

		$SQLstatement->execute();
		$output = $SQLstatement->fetchall();
		return $output;

	}

	public function prompts_by($timeframe = 'month') {

		$iterator = 30;
		if ($timeframe == 'day') {$iterator = 1;}
		if ($timeframe == 'week') {$iterator = 7;}
		if ($timeframe == 'year') {$iterator = 365;}
		
		$SQLstatement = $this->db->connection->prepare("
			SELECT
				stats.prompt_id AS prompt_id,
				CASE
					WHEN stats.prompt_id IS NULL THEN 'Standard Chat'
					WHEN prompts.id IS NULL THEN 'Prompt gelöscht'
					ELSE prompts.title
				END AS title,
				prompts.category AS category,
				COUNT(*) AS hits
			FROM stats
			LEFT JOIN prompts
				ON prompts.id = stats.prompt_id
			WHERE stats.date >= (NOW() - INTERVAL :iterator DAY)
			AND title is not null
			GROUP BY
				stats.prompt_id,
				prompts.title,
				prompts.category
			HAVING hits >= 10				
			ORDER BY
				hits DESC,
				title ASC;
			");

		$SQLstatement->execute(['iterator' => $iterator]);
		$output = $SQLstatement->fetchall();
		return $output;

	}

	public function categories_by($timeframe = 'month') {

		$iterator = 30;
		if ($timeframe == 'day') {$iterator = 1;}
		if ($timeframe == 'week') {$iterator = 7;}
		if ($timeframe == 'year') {$iterator = 365;}
		
		$SQLstatement = $this->db->connection->prepare("
			SELECT
				prompts.category AS category,
				COUNT(*) AS hits
			FROM stats
			LEFT JOIN prompts
				ON prompts.id = stats.prompt_id
			WHERE stats.date >= (NOW() - INTERVAL $iterator DAY)
			AND prompts.category is not null
			GROUP BY
				prompts.category
			HAVING hits >= 10				
			ORDER BY
				hits DESC
			");
		
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;

	}

}
