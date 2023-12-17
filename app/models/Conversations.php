<?php

namespace app\models;
use League\CommonMark\CommonMarkConverter;

class Conversations
{

	private $path = ROOT . 'cache' . DIRECTORY_SEPARATOR . 'conversations' . DIRECTORY_SEPARATOR;

	public function __construct() {
		if (!file_exists($this->path)) {
			mkdir($this->path, 0777, true);
		}
	}

	public function get($id) {
		$filename = $this->path . $id;
		if (!file_exists($filename)) {return null;}
		$data = file_get_contents($filename);
		return $this->decode_and_validate($data);
	}

	public function get_with_markdown($id) {

		$conversation = $this->get($id);

		$converter = new CommonMarkConverter();
		foreach ($conversation['conversation'] as $key => $message) {
			$conversation['conversation'][$key]['content'] = $converter->convert($message['content']);
		}
		
		return $conversation;

	}

	public function get_dialogue($id) {
		$conversationData = $this->get($id);
		return $conversationData['conversation'];
	}

	public function list() {

		$files = array_diff(scandir($this->path), array('.', '..'));

		$conversations = [];
		foreach ($files as $index => $filename) {
			$conversations[$index]['id'] = $filename;
			$conversations[$index]['edited'] = filemtime($this->path . $filename);
			$conversations[$index]['day'] = date('y-m-d', $conversations[$index]['edited']);
			$conversations[$index]['time'] = date('H:i', $conversations[$index]['edited']);
		}

		usort($conversations, function($a, $b) {
			return $b['edited'] <=> $a['edited'];
		});

		return $conversations;

	}

	public function save($data) {
		$id = $this->generate_id();
		$file = $this->path . $id;
		$data = json_encode($data);
		file_put_contents($file, $data);
		return $id;
	}

	public function update($data, $id) {
		$file = $this->path . $id;
		$data = json_encode($data);
		file_put_contents($file, $data);
	}

	public function delete($id) {
		$filename = $this->path . $id;
		if (!file_exists($filename)) {return false;}
		unlink($filename);
	}

	public function delete_all_conversations() {
		$files = glob($this->path . '*');
		foreach ($files as $file) {
			if (is_file($file)) {unlink($file);}
		}
	}

	public function remove_last_entry($id) {
		$data = $this->get($id);
		array_pop($data['conversation']);
		$this->update($data, $id);
	}

	private function generate_id($length = 6) {
		$bytes = random_bytes($length);
		return bin2hex($bytes);
	}

	private function decode_and_validate($data) {
		$data = json_decode($data,1);
		if (!isset($data['conversation'])) {
			$data['conversation'] = [];
			return $data;
		}
		$data['conversation'] = array_filter($data['conversation'], function($set) {
			if (empty($set['content'])) {return false;}
			return $set;
		});
		return $data;
	}

}
