<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;
use app\models\Conversations;
use app\models\ChatGPT;
use app\models\Prompts;

class Stats extends Model
{

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'ai_stats';
		$this->db->orderby = 'date';
		$this->db->order = 'DESC';
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

	public function avglength() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT avg(length) as 'length' FROM $table 
		");
		$SQLstatement->execute();
		$output = $SQLstatement->fetch(\PDO::FETCH_COLUMN);
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

	public function conversations_by_month() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DATE_FORMAT(date, '%Y-%m') as month, count(*) as 'usage'
			FROM $table 
			WHERE date >= '2023-06-01'
			GROUP BY month ORDER BY month ASC
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
			WHERE date >= '2023-06-01'
			GROUP BY week ORDER BY week ASC
			");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}

	public function conversations_by_weekday() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DAYOFWEEK(date) as weekday, count(*) as 'usage'
			FROM $table 
			WHERE date >= '2023-06-01'
			GROUP BY weekday ORDER BY weekday ASC
			");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}

	public function conversations_by_hour() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DATE_FORMAT(date, '%H') as hour, count(*) as 'usage'
			FROM $table 
			WHERE date >= '2023-06-01'
			GROUP BY hour ORDER BY hour ASC
			");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}


	public function conversations_by_type() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("SELECT type,count(id) as conversations FROM $table WHERE (`type` is not null AND `type` != '' AND `type` != 'Keine Kategorie zugewiesen') GROUP BY type");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}

	public function usage_by_month() {

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT DATE_FORMAT(date, '%Y-%m') as month, count(*) as 'usage'
			FROM $table 
			GROUP BY month ORDER BY month ASC
			");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall(\PDO::FETCH_UNIQUE|\PDO::FETCH_COLUMN);
		return $output;
	}



	public function summarize_conversations($from = 'today', $to = 'tomorrow') {

		$conversationIDs = $this->conversation_ids($from, $to);
		if (empty($conversationIDs)) {throw new \Exception("Nothing to Import", 400);}

		$userConversations = array_map([$this, 'user_messages_only'], $conversationIDs);
		$conversationChunks = array_chunk($userConversations, 10);

		foreach ($conversationChunks as $chunk) {
			$categorizedIDs = $this->summarize_with_chatGPT($chunk);
			$this->import_gpt_summary($categorizedIDs);
		}

		return 'Jobs done!';
	}


	private function summarize_with_chatGPT($conversations) {

		$prompts = new Prompts();
		$question = $prompts->get(54)['content']; // Loads the Prompt for Detecting Categories
		$question = $question . "\n" . json_encode($conversations);

		$chatGPT = new ChatGPT();
		return $chatGPT->direct($question);
	}


	private function conversation_ids($from, $to) {
		$from = date('Y-m-d', strtotime($from));
		$to = date('Y-m-d', strtotime($to));

		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("
			SELECT * FROM $table 
			WHERE (`date` BETWEEN :from AND :to) AND `type` is null
		");
		$SQLstatement->execute([':from' => $from, ':to' => $to]);
		$output = $SQLstatement->fetchall(\PDO::FETCH_COLUMN);
		return $output;

	}

	private function user_messages_only($conversationID) {

		$conversationDB = new Conversations();
		$conversation = $conversationDB->get_dialogue($conversationID);

		if (!is_array($conversation)) {return;}

		$output['id'] = $conversationID;
		$output['conversation'] = [];

		foreach ($conversation as $entry) {
			if ($entry['role'] == 'assistant') {break;}
			$entry = array_map([$this, 'reduce_content_length'], $entry);
			array_push($output['conversation'], $entry);
		}

		// Adds the Length Metainformation to the DB
		$this->put_conversation_length_to_db($conversation, $conversationID);
		return $output;
	}

	private function reduce_content_length($message) {
		$maxCharacters = 300;
		if (strlen($message) > $maxCharacters) {
			$message = mb_substr($message,0,$maxCharacters) . ' ...';		
		}
		return $message;
	}


	private function put_conversation_length_to_db($conversation, $id) {
		if (!is_array($conversation)) {return;}
		$length = count($conversation);
		$this->update(['length' => $length],$id);
	}

	public function import_conversations_from_disk() {
		$conversationDB = new Conversations();
		$conversations = $conversationDB->list();
		foreach ($conversations as $conversation) {
			$new['id'] = $conversation['id'];
			$new['date'] = date('Y-m-d H:i:s', $conversation['edited']);
			$this->create_or_update($new);
		}
	}


	public function import_gpt_summary($categorizedIDs) {

		dump($categorizedIDs);

		$categorizedIDs = json_decode($categorizedIDs,1);
		if (!is_array($categorizedIDs)) {return [];}
		foreach ($categorizedIDs as $entry) {
			if (!isset($entry['id']) || !isset($entry['category'])) {continue;}
			$this->update(['type' => $entry['category']], $entry['id']);
		}

		return $categorizedIDs;
	}

}
