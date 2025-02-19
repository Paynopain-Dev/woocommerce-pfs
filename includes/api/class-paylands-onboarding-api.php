<?php
/**
 * The file that define the Paylands API Client
 *
 * @package    Paylands_Woocommerce
 * @subpackage Paylands_Woocommerce/includes/api
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paylands_Onboarding_Api Class
 */
class Paylands_Onboarding_Api {
	const TEST_API_URL = PAYLANDS_TEST_API_ONBOARDING_URL;
	const PRO_API_URL = PAYLANDS_PRO_API_ONBOARDING_URL;
	const OAUTH_URL = "/oauth/v2/token";
	const ME_URL = "/me";
	const BUSINESS_URL = "/business/{business_id}";
	const BUSINESS_LOCATIONS_URL = "/business/{business_id}/merchant_locations";
	const BUSINESS_CREDENTIALS_URL = "/paylands/business/{business_id}/credentials";
	
	private $client_id;

	private $client_secret;

	private $username;

	private $mode;

	/**
	 * PNPPaylands constructor.
	 * @param string $client_id
	 * @param string $client_secret
	 */
	public function __construct($client_id, $client_secret, $mode = 'test') {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->mode = $mode;
	}

	/**
	 * Get Environment
	 * @return string
	 */
	private function getEnvironment() {
		if ($this->mode == 'test') {
			return 'test';
		}
		return 'pro';
	}

	/**
	 * Get Api URl
	 * @return string
	 */

	private function getApiUrl() {
		$environment = $this->getEnvironment();
		if ($environment == 'test') {
			return self::TEST_API_URL;
		}

		return self::PRO_API_URL;
	}

	/**
	 * @param string $url
	 * @param array $data
	 * @return array
	 */
	private function sendRequest($url, $data=array(), $token='', $allowEmptyResponse=false) {
		if (is_array($data)) {
			$log_data = $data;
			if (in_array('password', $log_data)) {
				//por seguridad, quita la contraseña del log
				unset($log_data['password']);
			}
			//TODO syl no poner data en el log porque se ve el pass en la url
			$log_message = 'paylands sendRequest url: ['.$url.'] data: ['.implode($log_data).'] token: ['.$token.']';
		}else{
			$log_message = 'paylands sendRequest url: ['.$url.'] data: ['.$data.'] token: ['.$token.']';
		}
		
		$ch = curl_init();

		$options = array(
			"Content-Type: application/json"
		);

		if (!empty($token)) {
			array_push($options, "Authorization: Bearer ".$token);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		if (!empty($data)) {
			$payload_json = json_encode($data);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_json);
		}else{
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		}
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			$options
		);

		$reponse_raw = curl_exec($ch);
		$response = json_decode($reponse_raw, true);
		curl_close($ch);

		$log_message .= ' response: ['.$reponse_raw.']';
		if (defined( 'PAYLANDS_API_ONBOARDING_LOGS' ) && PAYLANDS_API_ONBOARDING_LOGS) {
			Paylands_Logger::log($log_message);
		}

		if ( !$response && !$allowEmptyResponse) {
			throw new Exception(__('Something went wrong processing the request', 'paylands-woocommerce'));
		}

		return $response;
	}


	public function getOauthUrl() {
		$url = $this->getApiUrl().self::OAUTH_URL;
		Paylands_Logger::log("getOauthUrl ".$url);
		return $url;
	}

	public function getMeUrl() {
		return $this->getApiUrl().self::ME_URL;
	}

	public function getBusinessUrl($business_id) {
		$url = $this->getApiUrl().self::BUSINESS_URL;
		$url = str_replace('{business_id}', $business_id, $url);
		return $url;
	}

	public function getBusinessLocationsUrl($business_id) {
		$url = $this->getApiUrl().self::BUSINESS_LOCATIONS_URL;
		$url = str_replace('{business_id}', $business_id, $url);
		return $url;
	}

	public function getBusinessCredentialsUrl($business_id, $mode) {
		$url = $this->getApiUrl().self::BUSINESS_CREDENTIALS_URL;
		$url = str_replace('{business_id}', $business_id, $url);
		if ($mode == 'test') {
			$url = add_query_arg([ 'use_sandbox' => '1' ], $url);
		}else{
			$url = add_query_arg([ 'use_sandbox' => '0' ], $url);
		}
		return $url;
	}

	/**
	 * Connects account by username and password
	 */
	public function oauthRequest($username, $password) {
		$url = $this->getOauthUrl();
        $data = [
			"grant_type" => 'https://changeit.paynopain.com/server/password', 
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret,
            "username" => $username,
            "password" => $password
		];

		Paylands_Logger::log("antes sendRequest".$url);
		return $this->sendRequest($url, $data);
	}

	/**
	 * Connects account by refresh token
	 */
	public function oauthRequestRefresh($refresh_token) {
		$url = $this->getOauthUrl();
        $data = [
			"grant_type" => 'refresh_token', 
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret,
            "refresh_token" => $refresh_token
		];

		return $this->sendRequest($url, $data);
	}

	/**
	 * Gets the account data
	 */
	public function meRequest($token) {
		$url = $this->getMeUrl();
		return $this->sendRequest($url, '', $token);
	}

	/**
	 * Gets the business data
	 */
	public function businessRequest($business_id, $token) {
		$url = $this->getBusinessUrl($business_id);
		return $this->sendRequest($url, '', $token);
	}

	public function businessLocationsRequest($business_id, $token) {
		$url = $this->getBusinessLocationsUrl($business_id);
		return $this->sendRequest($url, '', $token, true);
	}

	/**
	 * Gets the business credentials
	 */
	public function businessCredentialsRequest($business_id, $token, $mode) {
		$url = $this->getBusinessCredentialsUrl($business_id, $mode);
		return $this->sendRequest($url, '', $token);
	}

	public function isBusinessActive($business_id, $token) {
		/*
		Cambio solicitado por correo el 02/12/24
		 Ahora mismo llamas a bussines/{bussines_id} y tiene que llamar a /bussines/{bussines_id}/merchant_locations.
		 En la respuesta obtendrás un array por cada uno de las "locations" del merchant. La idea es recorrerlas y si encontráis una que tenga "status": "ACTIVE", y  "business_kyc_status": "APPROVED",entonces toda la cuenta es activa.
		 De esta manera evitamos que le demos el ok a un comercio   "business_kyc_status": "APPROVED",y que aún no tenga la cuenta configurada  "status": "ACTIVE", por lo tanto que explote el login porque no os pasamos credenciales de paylands.
		 */
		$locations = $this->businessLocationsRequest($business_id, $token);
		if (!is_array($locations)) {
			return false; // La estructura del JSON no es la esperada
		}
	
		foreach ($locations as $location) {
			// Validamos que cada location sea un array y tenga las claves necesarias
			if (is_array($location) &&
				isset($location['status'], $location['business_kyc_status']) &&
				$location['status'] === 'ACTIVE' &&
				$location['business_kyc_status'] === 'APPROVED' || $location['business_kyc_status'] === 'UPDATING') {
				return true; // El comercio está activo
			}
		}
		return false; // No se encontró ninguna ubicación activa y aprobada
	}

}
