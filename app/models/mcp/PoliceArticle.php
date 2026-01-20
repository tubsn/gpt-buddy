<?php

namespace app\models\mcp;
use flundr\utility\Log;
use flundr\utility\Session;
use \app\models\ai\OpenAI;
use \app\models\ai\ConnectionHandler;
use \app\models\Prompts;

class PoliceArticle
{

	public function __construct() {}

	public function create(array $args) {
		$connection = new ConnectionHandler(CHATGPTKEY, 'https://api.openai.com', '/v1/responses');
		$ai = new OpenAI($connection);
		$prompts = new Prompts();
		$ai->model = 'gpt-5.2';
		$ai->reasoning = 'none';

		$prompt = $prompts->get_for_api(17);
		$ai->add_message($prompt['content'],'system');

		$input = $args['input'] ?? '';
		$ai->add_message($input, 'user');
		return $ai->resolve();
	}

}
