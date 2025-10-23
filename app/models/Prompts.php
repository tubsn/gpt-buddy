<?php

namespace app\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;
use app\models\Callbacks;
use app\models\Knowledge;

class Prompts extends Model
{

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'prompts';
		$this->db->orderby = 'title';
	}
	
	public function get_and_track($id = null, $tracking = true) {
		if ($id == 'default') {return ['content' => DEFAULTPROMPT];}
		if ($id == 'unbiased') {return;}
		$prompt = $this->get($id);
		if (empty($prompt)) {throw new \Exception("Prompt not Found", 404);}
		
		$prompt = $this->apply_callback($prompt);
		$prompt = $this->apply_knowledge($prompt);
		$prompt = $this->apply_post_processing($prompt);	
		if ($tracking) {$this->increase_hits($id);}
		return $prompt;
	}

	public function get_for_api($id = null) {
		$prompt =  $this->get_and_track($id, false);
		unset($prompt['history']);
		return $prompt; 
	}

	public function get_flat_content($id) {

		$prompt = $this->get($id);
		$prompt = $this->apply_callback($prompt);
		$prompt = $this->apply_knowledge($prompt);
		$prompt = $this->apply_post_processing($prompt);		
		
		$content = [];
		$content[0] = $prompt['content'];
		$content = array_merge($content, $prompt['knowledges']);
		$content = implode("\n\n", $content);
		$content = $content . "\n\n" . $prompt['afterthought'];

		return $content;
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

	public function in_categories($categories = []) {
		if (empty($categories)) {return [];}
		$table = $this->db->table;

		$categorieNames = implode(',', array_fill(0, count($categories), '?'));
		$SQLstatement = $this->db->connection->prepare(
			"SELECT * FROM $table WHERE `category` IN ($categorieNames)
			 AND (inactive IS NULL OR inactive = '0') ORDER BY `title`"
		);

		$SQLstatement->execute($categories);
		$output = $SQLstatement->fetchAll();
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
		$SQLstatement = $this->db->connection->prepare("SELECT * FROM $table ORDER BY `category`, `title`");
		$SQLstatement->execute();
		$output = $SQLstatement->fetchall();
		return $output;
	}

	public function copy($id) {
		$copy = $this->db->read($id);

		if (empty($copy)) {
			throw new \Exception("Prompt ID not found", 400);
		}

		$copy['history'] = null;
		unset($copy['id'], $copy['edited'], $copy['created'], $copy['hits']);

		$copy['title'] .= ' (Kopie)';
		return $copy;
	}

	public function most_hits() {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("SELECT id,hits,title FROM $table WHERE hits >= 10 ");
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
		if (!isset($prompt['callback'])) {return $prompt;}
		$callbacks = new Callbacks();
		return $callbacks->run($prompt['callback'], $prompt);
	}

	public function apply_knowledge($prompt) {
		if (!isset($prompt['callback'])) {return $prompt;}
		$knowledgebase = new Knowledge();
		return $knowledgebase->run($prompt['callback'], $prompt);
	}

	public function apply_post_processing($prompt) {
		$prompt['content'] = $this->replace_random_tokens($prompt['content']);
		$prompt['content'] = $this->replace_tokens($prompt['content']);
		$prompt['content'] = $this->replace_ignore_tokens($prompt['content']);
		return $prompt;
	}

	public function replace_random_tokens($content) {
		$pattern = '/\{\{\{\s*([^}|]+(?:\|[^}]+)+)\s*\}\}\}/';
		if (preg_match($pattern, $content)) {
			$content = preg_replace_callback($pattern, function($matches) {
				$options = explode('|', $matches[1]);
				$options = array_map('trim', $options);
				return $options[array_rand($options)];
			}, $content);
		}

		return $content;
	}

	public function replace_ignore_tokens($content) {
		$content = preg_replace('/\{\{\{\#.*?\#\}\}\}/s', '', $content);
		return $content;
	}

	public function replace_tokens($content) {
		$pattern = '/\{\{\{\s*([a-zA-Z_]+)\s*\}\}\}/';
		if (preg_match($pattern, $content)) {
			$content = preg_replace_callback($pattern, function($matches) {
				switch (strtolower($matches[1])) {
					case 'time': return date('H:i');
					case 'zeit': return date('H:i');
					case 'uhrzeit': return date('H:i');
					case 'date': return date('Y-m-d');
					case 'datum': return date('Y-m-d');
					case 'weekday': return date('l');
					case 'today': return date('Y-m-d');
					case 'heute': return date('Y-m-d');
					case 'now': return date('Y-m-d H:i');
					default: return $matches[0];
				}
			}, $content);
		}
		return $content;
	}

	private function increase_hits($id) {
		$table = $this->db->table;
		$SQLstatement = $this->db->connection->prepare("UPDATE $table SET `hits` = IFNULL(`hits`, 0) + 1 WHERE `id` = :id");
		$SQLstatement->execute([':id' => $id]);
		$output = $SQLstatement->fetch();
	}


}
