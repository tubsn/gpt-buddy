<?php

namespace app\controller;

use flundr\auth\Auth;
use flundr\auth\LoginHandler;
use flundr\database\SQLdb;
use flundr\mvc\Controller;
use flundr\utility\Session;
use TheNetworg\OAuth2\Client\Provider\Azure;

class EntraAuthentication extends Controller {
	private $provider;
	private $userDB;
	private $userTable;

	public function __construct() {
		$this->view('DefaultLayout');
		$this->assert_configuration();
		$this->provider = $this->create_provider();
	}

	private function create_provider() {
		$provider = new class([
			'clientId' => ENTRA_CLIENT_ID,
			'clientSecret' => ENTRA_CLIENT_SECRET,
			'redirectUri' => ENTRA_REDIRECT_URI,
			'scopes' => ['openid', 'profile', 'email'],
			'defaultEndPointVersion' => Azure::ENDPOINT_VERSION_2_0,
		]) extends Azure {
			// Method name is defined by league/oauth2-client.
			protected function getPkceMethod() {
				return self::PKCE_METHOD_S256;
			}
		};

		$provider->tenant = ENTRA_TENANT_ID;
		$provider->scope = 'openid profile email';
		return $provider;
	}

	public function login() {
		if (Auth::logged_in()) {
			$this->view->redirect('/');
			return;
		}

		$nonce = bin2hex(random_bytes(24));
		$loginURL = $this->provider->getAuthorizationUrl([
			'scope' => $this->provider->scope,
			'nonce' => $nonce,
		]);
		Session::set('entra.oauth.state', $this->provider->getState());
		Session::set('entra.oauth.nonce', $nonce);
		Session::set('entra.oauth.pkce', $this->provider->getPkceCode());

		header('Location: ' . $loginURL);
		exit;
	}

	public function callback() {
		if (isset($_GET['error'])) {
			$this->clear_oauth_session();
			throw new \RuntimeException('Microsoft-Anmeldung wurde abgebrochen oder abgelehnt.', 401);
		}

		$code = $_GET['code'] ?? '';
		$this->validate_callback($code);

		$this->provider->setPkceCode(Session::get('entra.oauth.pkce'));
		$token = $this->provider->getAccessToken('authorization_code', [
			'code' => $code,
			'scope' => $this->provider->scope,
		]);
		$claims = $token->getIdTokenClaims();
		$expectedNonce = Session::get('entra.oauth.nonce');
		$this->clear_oauth_session();
		$this->validate_claims($claims, $expectedNonce);

		$userID = $this->find_or_create_user($claims);
		session_regenerate_id(true);
		(new LoginHandler())->login_by_id($userID);
		$this->remember_entra_login();

		$redirectURL = $this->local_redirect(Session::get('referer'));
		Session::unset('referer');
		$this->view->redirect($redirectURL);
	}

	public function logout() {
		// Entra logins deliberately do not create Flundr's persistent auth cookie.
		// Avoid asking its cookie handler to invalidate a cookie that does not exist.
		$cookieName = 'auth';
		if (defined('LOGINCOOKIE_NAME')) {$cookieName = LOGINCOOKIE_NAME;}

		if (isset($_COOKIE[$cookieName])) {
			(new LoginHandler())->logout();
		} else {
			Auth::checkout();
			Session::unset('authUser');
		}
		session_regenerate_id(true);
		$postLogoutURL = PAGEURL . '/';
		if (defined('ENTRA_POST_LOGOUT_REDIRECT_URI')) {
			$postLogoutURL = ENTRA_POST_LOGOUT_REDIRECT_URI;
		}

		header('Location: ' . $this->provider->getLogoutUrl($postLogoutURL));
		exit;
	}

	private function assert_configuration() {
		foreach (['ENTRA_TENANT_ID', 'ENTRA_CLIENT_ID', 'ENTRA_CLIENT_SECRET', 'ENTRA_REDIRECT_URI'] as $name) {
			if (!defined($name) || !is_string(constant($name)) || trim(constant($name)) === '') {
				throw new \RuntimeException("Entra-Konfiguration fehlt: {$name}", 500);
			}
		}
	}

	private function validate_callback($code) {
		$expectedState = Session::get('entra.oauth.state');
		$receivedState = $_GET['state'] ?? '';
		$pkceCode = Session::get('entra.oauth.pkce');
		$validState = is_string($expectedState)
			&& is_string($receivedState)
			&& hash_equals($expectedState, $receivedState);
		$validCode = is_string($code) && $code !== '';
		$validPkce = is_string($pkceCode) && $pkceCode !== '';

		if ($validState && $validCode && $validPkce) {return;}

		$this->clear_oauth_session();
		throw new \RuntimeException('Ungültige oder abgelaufene Entra-Anmeldung.', 401);
	}

	private function validate_claims($claims, $expectedNonce) {
		if (!is_array($claims)) {
			throw new \RuntimeException('Das Entra ID-Token enthält keine gültigen Claims.', 401);
		}

		$nonce = $claims['nonce'] ?? '';
		$tenantID = $claims['tid'] ?? '';
		$validNonce = is_string($expectedNonce)
			&& is_string($nonce)
			&& hash_equals($expectedNonce, $nonce);
		$validTenant = is_string($tenantID)
			&& hash_equals(strtolower(ENTRA_TENANT_ID), strtolower($tenantID));

		if (!$validNonce || !$validTenant) {
			throw new \RuntimeException('Das Entra ID-Token konnte nicht bestätigt werden.', 401);
		}
	}

	private function clear_oauth_session() {
		Session::unset('entra.oauth.state');
		Session::unset('entra.oauth.nonce');
		Session::unset('entra.oauth.pkce');
	}

	public static function has_login_cookie() {
		$cookieName = self::login_cookie_name();
		return isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === '1';
	}

	private function remember_entra_login() {
		setcookie(self::login_cookie_name(), '1', [
			'expires' => time() + 31536000,
			'path' => '/',
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
	}

	private static function login_cookie_name() {
		if (defined('ENTRA_LOGIN_COOKIE_NAME')) {return ENTRA_LOGIN_COOKIE_NAME;}
		return 'entra_login';
	}

	private function local_redirect($url) {
		if (!is_string($url) || !$url || $url[0] !== '/' || substr($url, 0, 2) === '//') {
			return '/';
		}

		return $url;
	}

	private function find_or_create_user($claims) {
		$this->init_user_database();
		$objectID = $this->required_guid($claims, 'oid');
		$tenantID = $this->required_guid($claims, 'tid');
		$email = $this->email_from_claims($claims);

		$user = $this->find_by_entra_identity($tenantID, $objectID);
		if ($user) {
			$this->update_user_profile($user, $claims, $email);
			return (int) $user['id'];
		}

		$user = $this->find_by_email($email);
		if ($user) {
			if (!empty($user['entra_oid']) || !empty($user['entra_tid'])) {
				throw new \RuntimeException('Dieses Konto ist bereits mit einer anderen Microsoft-Identität verknüpft.', 409);
			}

			$this->userDB->update([
				'entra_oid' => $objectID,
				'entra_tid' => $tenantID,
			], $user['id']);
			return (int) $user['id'];
		}

		$name = $this->text_claim($claims, 'name');

		[$lastname, $firstname] = array_pad(
			array_map('trim', explode(',', $name, 2)),
			2,
			''
		);

		return (int) $this->userDB->create([
			'email' => $email,
			'firstname' => $firstname,
			'lastname' => $lastname,
			'level' => 'User',
			'groups' => 'ENTRA',
			'auth_source' => 'entra',
			'entra_oid' => $objectID,
			'entra_tid' => $tenantID,
		]);

	}

	private function init_user_database() {
		if (!defined('USER_DB_SETTINGS')) {throw new \RuntimeException('UserDB Config not found', 500);}

		$this->userTable = defined('TABLE_USERS') ? TABLE_USERS : 'users';
		if (!preg_match('/^[A-Za-z0-9_]+$/', $this->userTable)) {
			throw new \RuntimeException('Invalid user table name', 500);
		}

		$this->userDB = new SQLdb(USER_DB_SETTINGS);
		$this->userDB->table = $this->userTable;
	}

	private function find_by_entra_identity($tenantID, $objectID) {
		$statement = $this->userDB->connection->prepare(
			"SELECT * FROM `{$this->userTable}` WHERE `entra_tid` = :tenantID AND `entra_oid` = :objectID LIMIT 1"
		);
		$statement->execute([':tenantID' => $tenantID, ':objectID' => $objectID]);
		return $statement->fetch();
	}

	private function find_by_email($email) {
		$statement = $this->userDB->connection->prepare(
			"SELECT `id`, `entra_oid`, `entra_tid` FROM `{$this->userTable}` WHERE `email` = :email LIMIT 1"
		);
		$statement->execute([':email' => $email]);
		return $statement->fetch();
	}

	private function update_user_profile($user, $claims, $email) {
		$changes = [];
		$firstname = $this->text_claim($claims, 'given_name');
		$lastname = $this->text_claim($claims, 'family_name');

		if ($user['email'] !== $email) {$changes['email'] = $email;}
		if ($firstname !== null && $user['firstname'] !== $firstname) {$changes['firstname'] = $firstname;}
		if ($lastname !== null && $user['lastname'] !== $lastname) {$changes['lastname'] = $lastname;}

		if ($changes) {$this->userDB->update($changes, $user['id']);}
	}

	private function email_from_claims($claims) {
		foreach (['email', 'preferred_username', 'upn'] as $name) {
			$value = $claims[$name] ?? null;
			if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {return strtolower($value);}
		}
		throw new \RuntimeException('Entra hat keine gültige E-Mail-Adresse geliefert.', 422);
	}

	private function required_guid($claims, $name) {
		$value = $claims[$name] ?? null;
		if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
			throw new \RuntimeException("Ungültiger Entra Claim: {$name}", 401);
		}
		return strtolower($value);
	}

	private function text_claim($claims, $name) {
		$value = $claims[$name] ?? null;
		if (!is_string($value) || trim($value) === '') {return null;}
		return substr(trim($value), 0, 120);
	}

}
