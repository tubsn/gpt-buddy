<?php

namespace app\models;
use Orhanerday\OpenAi\OpenAi;
use flundr\utility\Session;

class OpenAIVision 
{

	public function __construct() {}

	public function see($file, $prompt = null) {

		$open_ai = new OpenAi(CHATGPTKEY);
		if (defined('CHATGPTBASEURL')) {$open_ai->setBaseURL(CHATGPTBASEURL);}

		if (empty($prompt)) {
			$prompt = 'Extrahiere Daten und gebe als Ergebnis ausschließlich Json zurück. Umschließe alle Datesätze mit "data": [...]. Ich möchte die Daten automatisiert in eine Datenbank importieren. Nutze daher für die Ausgabe keine Formatierungen oder Steuerungszeichen! Bitte wandle alle Datumsangaben ins deutsche Format (z.B. 17.06.2023). Ich benötige Vorname, Nachname, Ort, Anschrift, Datum, und den Typ des Jubiläums oder Termins aus folgendem Datensatz';
		}
		
		$analyzeprompt = 'Analysiere die folgende Daten';
		$imageData = $this->file_to_base64($file);

		$imageTransport = [
			['type' => 'text', 'text' => $analyzeprompt],
			['type' => 'image_url', 'image_url' => ['url' => $imageData]],
		];

		$messages = [ 
			['role' => 'system', 'content' => $prompt],
			['role' => 'user', 'content' => $imageTransport]
		];

		$chat = $open_ai->chat([
			'model' => 'gpt-4o',
			'response_format' => [ 'type' => 'json_object' ],			
			'messages' => $messages,
			//'temperature' => $this->float_temperature(), // has to be valid floatvalue
			// 'max_tokens' => 4096, 
		]);

		$chat = json_decode($chat); // response is in Json
		if (isset($chat->error->message)) {throw new \Exception("Vision GPT-API Error: " . $chat->error->message, 400);}
		return $chat->choices[0]->message->content;

	}


	public function file_to_base64($path) {
		$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		return $base64;
	}


}
