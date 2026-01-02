<?php

namespace app\models\mcp;

class PipedreamMCPConnector
{

	public $MCPServerURL = 'https://remote.mcp.pipedream.net';
	public $tokenUrl = 'https://api.pipedream.com/v1/oauth/token';
	public $appsUrl  = 'https://api.pipedream.com/v1/apps';
	public $clientName = 'aibuddy_mcp';
	private $accessToken = null;

	public function __construct(private $clientID, private $clientSecret) {}

	public function access_token() {
		if (!empty($this->accessToken)) {return $this->accessToken;}
		$tokenPayload = $this->create_access_token($this->tokenUrl, [
			'grant_type' => 'client_credentials',
			'client_id' => $this->clientID,
			'client_secret' => $this->clientSecret,
		]);

		if (empty($tokenPayload)) {throw new \Exception("Error creating Pipedream Accesstoken", 400);}

		$this->accessToken = $tokenPayload['access_token'] ?? null;
		return $this->accessToken;
	}

	public function create_tool_schema($appSlug, $projectID = 'proj_9lsvxeZ', $environment = 'development') {

		$app = 'slack_v2'; // Name of the desired MCP App

		return [
			'type' => 'mcp',
			'server_label' => $appSlug ?? 'PipepreamMCP',
			'server_url' => $this->MCPServerURL,
			'headers' => [
			  'Authorization' => 'Bearer ' . $this->access_token(),
			  'x-pd-project-id' => $projectID,
			  'x-pd-environment' => $environment,
			  'x-pd-external-user-id' => $this->clientName,
			  'x-pd-app-slug' => $appSlug,
			],
			'require_approval' => 'never',
		  ];
	}

	public function fetch_app_slug(string $query = 'notion'): ?string {

		$appsPayload = $this->queryApps($this->appsUrl, [
			'Authorization: Bearer ' . $this->access_token(),
			'Accept: application/json',
		], ['q' => $query]);

		if (!is_array($appsPayload) || empty($appsPayload['data'][0]['name_slug'])) {
			return null;
		}

		return $appsPayload['data'][0]['name_slug'];
	}

	private function create_access_token(string $url, array $body, array $headers = []): ?array	{
		$requestHeaders = array_merge([
			'Content-Type: application/json',
			'Accept: application/json',
		], $headers);

		$curlHandle = curl_init($url);
		curl_setopt_array($curlHandle, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $requestHeaders,
			CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			CURLOPT_TIMEOUT => 15,
		]);

		$responseBody = curl_exec($curlHandle);
		$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		if ($responseBody === false || $httpCode < 200 || $httpCode >= 300) {
			curl_close($curlHandle);
			return null;
		}
		curl_close($curlHandle);

		$decoded = json_decode($responseBody, true);
		return is_array($decoded) ? $decoded : null;
	}

	private function queryApps(string $url, array $headers = [], array $query = []): ?array	{
		
		if (!empty($query)) {
			$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
		}

		$requestHeaders = array_merge([
			'Accept: application/json',
		], $headers);

		$curlHandle = curl_init($url);
		curl_setopt_array($curlHandle, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $requestHeaders,
			CURLOPT_TIMEOUT => 15,
		]);

		$responseBody = curl_exec($curlHandle);
		$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		if ($responseBody === false || $httpCode < 200 || $httpCode >= 300) {
			curl_close($curlHandle);
			return null;
		}
		curl_close($curlHandle);

		$decoded = json_decode($responseBody, true);
		return is_array($decoded) ? $decoded : null;
	}

}