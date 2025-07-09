<?php
/**
 * The plugin class to manage the Paylands account connection with the plugin.
 */
class Paylands_Woocommerce_Account_Connect {

	use Paylands_Helpers;

	private $onboarding_status;

	private $business_status;

	private $business_id;

	private $business_data;

	private $has_credentials;
	
	private $login_data;

	private $error_message;

	private const ONBOARDING_STATUS_CODES = array(1 => 'ACCOUNT_NOT_FOUND',
												  2 => 'LOGIN_REQUIRED',
												  3 => 'PENDING_VALIDATION',
												  4 => 'CONNECTED',
												  5 => 'REFRESH_TOKEN'
												);

	public function __construct() {
		$this->load_data();
	}

	private function get_generic_error_message() {
		return __( 'An error has occurred, please try again in a few minutes. If the error persists, please contact Paylands.', 'paylands-woocommerce');
	}

	public function load_data() {
		$this->login_data = unserialize(get_option('woocommerce_paylands_connect_data', false));
		$this->set_onboarding_status();
		$credentials = get_option('woocommerce_paylands_business_credentials', false);
		//TODO syl ver si consultar de bbdd o de las settings. Ver si influye que esten solo las de test o pro
		if (!empty($credentials)) {
			$this->has_credentials = true;
		}else{
			$this->has_credentials = false;
		}
	}

	public static function get_create_account_url() {
		$url = '';
		if (woocommerce_paylands_is_dev_mode()) {
			if (defined('PAYLANDS_TEST_ONBOARDING_URL')) {
				$url = PAYLANDS_TEST_ONBOARDING_URL;
			}
		}elseif (defined('PAYLANDS_PRO_ONBOARDING_URL')) {
			$url = PAYLANDS_PRO_ONBOARDING_URL;
		}
		return $url;
	}

	public static function is_business_connected() {
		$business_id = get_option('woocommerce_paylands_business_id', false);
		if (empty($business_id)) {
			return false;
		}else{
			return true;
		}
	}

	/**
	 * devuelve un array indicando si hay credenciales de test y de pro
	 */
	public static function has_credentials() {
		$has_credentials = array('test' => 'no', 'pro' => 'no');
		$credentials = get_option('woocommerce_paylands_business_credentials', false);
		if (!empty($credentials)) {
			if (isset($credentials['test'])) {
				$has_credentials['test'] = 'yes';
			}
			if (isset($credentials['pro'])) {
				$has_credentials['pro'] = 'yes';
			}
		}
		return $has_credentials;
	}

	private function set_onboarding_status() {
		if (empty($this->login_data)) {
			$this->onboarding_status = 1;
		}else{
			$this->business_id = get_option('woocommerce_paylands_business_id', false);
			if (empty($this->business_id)) {
				$this->onboarding_status = 2;
			}else{
				$this->business_data = get_option('woocommerce_paylands_business_data', false);
				$this->set_business_status();
				if ($this->business_status != 'APPROVED' && $this->business_status != 'UPDATING') {
					//comprueba el estado de la cuenta
					$this->onboarding_status = 3;
				}else{
					$connection_status = $this->check_connection_status();
					if ($connection_status == 'ok') {
						$this->onboarding_status = 4;
					}elseif ($connection_status == 'expired_token') {
						//TODO syl ver si refrescar aqui
						$this->onboarding_status = 5;
					}else{
						$this->onboarding_status = 2;
					}
					
				}
			}
		}
	}

	public function get_onboarding_status() {
		if (!empty($this->onboarding_status)) {
			return SELF::ONBOARDING_STATUS_CODES[$this->onboarding_status];
		}
		return false;
	}

	public function is_connected() {
		return !empty($this->login_data);
	}

	public function get_connection_data() {
		if ($this->is_connected()) {
			return $this->login_data;
		}
		return false;
	}

	public function get_error_message() {
		return $this->error_message;
	}

	public function get_business_id() {
		return $this->business_id;
	}

	public function get_business_name() {
		if (!empty($this->business_data) && !empty($this->business_data['business_name'])) {
			return $this->business_data['business_name'];
		}
		return false;
	}

	public function get_contact_name() {
		if (!empty($this->business_data) && !empty($this->business_data['contact_name'])) {
			return $this->business_data['contact_name'];
		}
		return false;
	}

	public function get_business_email() {
		if (!empty($this->business_data) && !empty($this->business_data['email'])) {
			return $this->business_data['email'];
		}
		return false;
	}

	public function get_business_status() {
		if (empty($this->business_status)) {
			$this->business_status = get_option('woocommerce_paylands_business_status', false);
		}
		return $this->business_status;
		
	}

	public function show_onboarding() {
		return ($this->check_connection_status() == 'not_connected');
	}

	public function get_access_token() {
		if (isset($this->login_data['access_token'])) {
			return $this->login_data['access_token'];
		}
		return false;
	}

	private function is_access_token_expired() {
		if (isset($this->login_data['access_token'])) {
			$expiration = new DateTime($this->login_data['token_expiration']);
			$now = new DateTime();
			if ($expiration > $now) {
				return false;
			}
		}
		return true;
	}

	public function is_account_set() {
		//TODO syl ver si hace falta distinguir entre credenciales de test y pro
		return $this->has_credentials;
	}

	private function get_refresh_token() {
		if (isset($this->login_data['refresh_token'])) {
			return $this->login_data['refresh_token'];
		}
		return false;
	}


	public function check_connection_status() {
		//TODO syl no fiarse de que el token sea valido aunque no haya expirado
		if (!empty($this->get_access_token()))  {
			//comprueba si ha expirado
			if ($this->is_access_token_expired()) {
				//el token ha caducado
				//TODO syl ver si hay que refrescar aqui el token
				return 'expired_token';
			}else{
				//el token es valido
				return 'ok';
			}
		}

		return 'not_connected';
	}

	public function set_business_status() {
		Paylands_Logger::log('set_business_status2');
		if (empty($this->business_id)) return false;

		$this->business_status = get_option('woocommerce_paylands_business_status', false);
		if (empty($this->business_status) || $this->business_status == 'PENDING') { //TODO syl comprobar siempre por si le quitan acceso??
			//consulta en la api el estado del comercio
			$this->error_message = '';
			$this->paylands_api_onboarding_loader();
			$token = $this->get_access_token();
			try {
				if (!empty($token)) {
					$business_data = $this->api->businessRequest($this->business_id, $token);
					if ($business_data && isset($business_data['kyb']['status'])) {
						if ($business_data['kyb']['status'] == 'APPROVED' || $business_data['kyb']['status'] == 'UPDATING') {
							$is_active = $this->api->isBusinessActive($this->business_id, $token);
							if ($is_active) {
								$this->business_status = 'APPROVED';
							}else{
								Paylands_Logger::log('set_business_status no hay comercios activos');
								$this->business_status = 'PENDING';
							}
						}else{
							$this->business_status = $business_data['kyb']['status'];
						}
						update_option('woocommerce_paylands_business_status', $this->business_status);
					}else{
						Paylands_Logger::log('set_business_status no hay status');
						$this->error_message = $this->get_generic_error_message();
						return false;
					}

					//business_data
					$this->business_data = get_option('woocommerce_paylands_business_data', false);
					//name
					if ($business_data && !empty($business_data['name'])) {
						$business_name = $business_data['name'];
						$this->business_data['business_name'] = $business_name;
						update_option('woocommerce_paylands_business_data', $this->business_data);
					}else{
						Paylands_Logger::log('set_business_status no hay name');
					}
					
					return true;

				}else{
					Paylands_Logger::log('set_business_status no hay token');
					$this->error_message = $this->get_generic_error_message();
					return false;
				}
			}catch (Exception $e) {
				Paylands_Logger::log('set_business_status error '.$e->getCode().' '.$e->getMessage());
				//TODO handle exception
				$this->error_message = $this->get_generic_error_message();
				return false;
			}
		//}elseif ($this->business_status == 'APPROVED') {
			//el comercio ya estaba ok y validado
		}
		return false;
	}

	/**
	 * configuración inicial de las credenciales para conectar a la api de pagos cuando se hace login con la cuenta de paylands
	 */
	public function set_business_credentials() {
		if (empty($this->business_id)) {
			Paylands_Logger::log('set_business_credentials no hay business_id');
			$this->error_message = $this->get_generic_error_message();
			return false;
		}
		Paylands_Logger::log('set_business_credentials');
		$credentials = get_option('woocommerce_paylands_business_credentials', false);
		if (empty($credentials)) { 
			//consulta en la api las credenciales del comercio
			$this->error_message = '';
			$this->paylands_api_onboarding_loader();
			$token = $this->get_access_token();
			try {
				if (!empty($token)) {
					$credentials = array();
					$are_test_credentials = false;
					$are_pro_credentials = false;
					//recupera las credenciales para el entorno de sandbox
					$business_data = $this->api->businessCredentialsRequest($this->business_id, $token, 'test');
					if ($business_data && !empty($business_data['api_key']) && !empty($business_data['signature'])) {
						$this->has_credentials = true; 
						$are_test_credentials = true;
						$credentials['test'] = $business_data; 
						update_option('woocommerce_paylands_business_credentials', $credentials);
						update_option('woocommerce_paylands_settings_api_key_test', $business_data['api_key']);
						update_option('woocommerce_paylands_settings_signature_key_test', $business_data['signature']);
					}else{
						Paylands_Logger::log('set_business_credentials api para test respuesta incorrecta o no hay');
						//$this->error_message = $this->get_generic_error_message();
						//return false; //TODO syl esto tiene que quedar comentado?
					}

					//recupera las credenciales para el entorno de pro
					$business_data = $this->api->businessCredentialsRequest($this->business_id, $token, 'pro');
					if ($business_data && !empty($business_data['api_key']) && !empty($business_data['signature'])) {
						$this->has_credentials = true; 
						$credentials['pro'] = $business_data; 
						update_option('woocommerce_paylands_business_credentials', $credentials);
						update_option('woocommerce_paylands_settings_api_key_pro', $business_data['api_key']);
						update_option('woocommerce_paylands_settings_signature_key_pro', $business_data['signature']);
					}else{
						Paylands_Logger::log('set_business_credentials api para pro respuesta incorrecta o no hay');
						//$this->error_message = $this->get_generic_error_message();
						//return false;
					}

					if ($are_test_credentials || $are_pro_credentials) {
						update_option('woocommerce_paylands_settings_form_lang', 'es');
						if (woocommerce_paylands_is_dev_mode()) {
							update_option('woocommerce_paylands_settings_test_mode', 'yes');
						}elseif ($are_test_credentials) {
							update_option('woocommerce_paylands_settings_test_mode', 'yes');
						}else{
							update_option('woocommerce_paylands_settings_test_mode', 'no');
						}	
						return true;
					}else{
						Paylands_Logger::log('set_business_credentials error no hay credenciales ni de test ni de pro');
						$this->error_message = $this->get_generic_error_message();
						return false;
					}
					
				}else{
					Paylands_Logger::log('set_business_credentials error no hay token');
					$this->error_message = $this->get_generic_error_message();
					return false;
				}
			}catch (Exception $e) {
				//TODO syl handle exception
				Paylands_Logger::log('set_business_credentials error '.$e->getCode().' '.$e->getMessage());
				$this->error_message = $this->get_generic_error_message();
				return false;
			}
		}else{
			Paylands_Logger::log('set_business_credentials ya habia credenciales');
			$this->has_credentials = true;
			return true;
		}
	}

	/**
	 * se llama regularmente si al comercio le faltan credenciales de test o producción para comprobar si se las han añadido
	 */
	public function update_business_credentials($mode) {
		if (empty($this->business_id)) {
			Paylands_Logger::log('update_business_credentials no hay business_id');
			return false;
		}
		Paylands_Logger::log('update_business_credentials '.$mode);
		$credentials = get_option('woocommerce_paylands_business_credentials', false);
		if (!empty($credentials)) { //tiene que haber las del otro entorno
			//consulta en la api las credenciales del comercio
			$this->error_message = '';
			$this->paylands_api_onboarding_loader();
			$token = $this->get_access_token();
			try {
				if (!empty($token)) {
					//recupera las credenciales para el entorno
					$business_data = $this->api->businessCredentialsRequest($this->business_id, $token, $mode);
					if ($business_data && !empty($business_data['api_key']) && !empty($business_data['signature'])) {
						$credentials[$mode] = $business_data; 
						update_option('woocommerce_paylands_business_credentials', $credentials);
						update_option('woocommerce_paylands_settings_api_key_'.$mode, $business_data['api_key']);
						update_option('woocommerce_paylands_settings_signature_key_'.$mode, $business_data['signature']);
					}elseif ($business_data && isset($business_data['error']) && $business_data['error'] == 'expired_token') {
						//el token ha caducado, intenta refrescarlo
						//TODO syl el refresh_token me devuelve error invalid_grant en la api, en teoria ya lo habian arreglado pero sigue pasando
						Paylands_Logger::log('update_business_credentials api expired token '.$mode);
						$reconnected = $this->reconnect_account();
						if ($reconnected) {
							$business_data = $this->api->businessCredentialsRequest($this->business_id, $token, $mode);
							if ($business_data && !empty($business_data['api_key']) && !empty($business_data['signature'])) {
								$credentials[$mode] = $business_data; 
								update_option('woocommerce_paylands_business_credentials', $credentials);
								update_option('woocommerce_paylands_settings_api_key_'.$mode, $business_data['api_key']);
								update_option('woocommerce_paylands_settings_signature_key_'.$mode, $business_data['signature']);
							}else{
								//TODO syl ver si pueden volver a hacer el login si no hay manera de reconectar y sin tener que borrar la cuenta
								Paylands_Logger::log('update_business_credentials api para respuesta incorrecta o no hay despues de reconectar '.$mode.' '.json_encode($business_data));
							}
						}
					}else{
						Paylands_Logger::log('update_business_credentials api para respuesta incorrecta o no hay '.$mode.' '.json_encode($business_data));
						//$this->error_message = $this->get_generic_error_message();
						//return false; //TODO syl esto tiene que quedar comentado?
					}
				}else{
					Paylands_Logger::log('update_business_credentials error no hay token');
					$this->error_message = $this->get_generic_error_message();
					return false;
				}
			}catch (Exception $e) {
				//TODO syl handle exception
				Paylands_Logger::log('update_business_credentials error '.$e->getCode().' '.$e->getMessage());
				$this->error_message = $this->get_generic_error_message();
				return false;
			}
		}else{
			Paylands_Logger::log('update_business_credentials no habia ningunas credenciales');
			return true;
		}
	}

	private function set_up_account_data() {
		Paylands_Logger::log('set_up_account_data');
		$token = $this->get_access_token();

		//sino hay login nada
		if (empty($token)) {
			Paylands_Logger::log('set_up_account_data no hay token');
			$this->error_message = $this->get_generic_error_message();
			return false;
		}

		try {
			$business_id = get_option('woocommerce_paylands_business_id', false);
			if (empty($business_id)) { //TODO syl, si ha habido un login con problemas con la api y ya se ha guardado id no funciona el login
				$me_data = $this->api->meRequest($token);
				if ($me_data && !empty($me_data['business_id'])) {
					$business_id = $me_data['business_id'];
					update_option('woocommerce_paylands_business_id', $business_id);
					$this->business_id = $business_id;
					//business_data
					$this->business_data = get_option('woocommerce_paylands_business_data', false);
					//name
					if (!empty($me_data['profile']['name'])) {
						$business_name = $me_data['profile']['name'];
						if (!empty($me_data['profile']['surname'])) {
							$business_name .= ' '.$me_data['profile']['surname'];
						}
						$this->business_data['contact_name'] = $business_name;
					}else{
						Paylands_Logger::log('set_up_account_data no hay profile name');
					}
					//email
					if (!empty($me_data['profile']['email'])) {
						$business_email = $me_data['profile']['email'];
						$this->business_data['email'] = $business_email;
					}else{
						Paylands_Logger::log('set_up_account_data no hay profile email');
					}
					update_option('woocommerce_paylands_business_data', $this->business_data);
					//status
					$result_ok = $this->set_business_status();
					if (!$result_ok) {
						Paylands_Logger::log('set_up_account_data false en set_business_status');
						$this->error_message = $this->get_generic_error_message();
						return false;
					}

					$business_status = $this->get_business_status();
					if ($business_status == 'PENDING' || $business_status == 'CREATED' || $business_status == 'UNDER_REVISION') {
						//el comercio esta activo pero aun no ha sido validado por paylands
						//lo dejamos aqui porque aun no podemos recuperar credenciales
						Paylands_Logger::log('set_up_account_data comercio pendiente de validación');
						return true;
					}else if ($business_status == 'APPROVED' || $business_status == 'UPDATING') {
						$result_ok = $this->set_business_credentials();
						if (!$result_ok) {
							Paylands_Logger::log('set_up_account_data false en set_business_credentials');
							$this->error_message = $this->get_generic_error_message();
							return false;
						}
						if ($this->is_account_set()) {
							$loader = new Paylands_Gateway_Loader();
							$loader->set_initial_gateways_config();
							return true;
						}else{
							Paylands_Logger::log('set_up_account_data no is_account_set');
							$this->error_message = $this->get_generic_error_message();
							return false;
						}
					}else{
						//TODO syl pro el login deberia dar error, ahora no aparece ningun mensaje
						Paylands_Logger::log('set_up_account_data business_status diferente de pending, updating o approved');
							return false;
					}
				}else{
					Paylands_Logger::log('set_up_account_data no hay business_id me data');
					$this->error_message = $this->get_generic_error_message();
					return false;
				}
			}else{
				Paylands_Logger::log('set_up_account_data ya hay business_id');
				$this->error_message = $this->get_generic_error_message();
				return false;
			}
		}catch (Exception $e) {
			//TODO syl handle exception
			Paylands_Logger::log('set_up_account_data error '.$e->getCode().' '.$e->getMessage());
			$this->error_message = $this->get_generic_error_message();
			return false;
		}
		return false;
	}

	//comprueba que la cuenta con la que se ha hecho login coincide con la que teniamos configurada
	private function check_business_id_on_login($access_token) {
		$current_business_id = get_option('woocommerce_paylands_business_id', false);
		if (empty($current_business_id)) return true; //es el primer login

		$me_data = $this->api->meRequest($access_token);
		if ($me_data && !empty($me_data['business_id']) && $me_data['business_id'] != $current_business_id) {
			//el id del nuevo login no es el mismo que teniamos guardado
			return false;
		}
		return true;
	}

	public function reconnect_account() {
		$this->error_message = '';
		$this->paylands_api_onboarding_loader();
		$refresh_token = $this->get_refresh_token();

		/*echo "<br/>reconnect_account antes<br/>"; //TODO syl comentar
    	print_r($this->login_data);
		echo "<br/><br/>";*/
		
		try {
			if (!empty($refresh_token)) {
				$login_data = $this->api->oauthRequestRefresh($refresh_token);
				
				/*echo "<br/>reconnect_account despues <br/>"; //TODO syl comentar
    			print_r($login_data);
				echo "<br/><br/>";*/
				
				if ($login_data) {
					if (!empty($login_data['access_token'])) {
						$now = new DateTime();
						$expires_in = $login_data['expires_in'];
						$now->modify("+ $expires_in second");
						$token_expiration_date = $now->format('Y-m-d H:i:s');
						$this->login_data = array('access_token' => $login_data['access_token'],
												'refresh_token' => $login_data['refresh_token'],
												'token_expiration' => $token_expiration_date);
						update_option('woocommerce_paylands_connect_data', serialize($this->login_data));
						
						if ($this->business_status == 'PENDING' || $this->business_status == 'CREATED' || $this->business_status == 'UNDER_REVISION') {
							//si aun no teniamos el comercio activo comprueba si lo han activado
							//TODO syl probar
							$this->set_up_account_data();
						}

						return true;
					}else if (isset($login_data['error'])) {
						if ($login_data['error'] == 'unauthorized_client') {
							//token no valido
							$this->error_message = __( 'Invalid token', 'paylands-woocommerce');
						}elseif ($login_data['error'] == 'invalid_grant') {
							//refresh token expirado
							$this->error_message = __( 'Refresh token expired', 'paylands-woocommerce');
						}elseif ($login_data['error'] == 'user_inactive') {
							//Cuenta creada, email no validado
							$this->error_message = __( 'Your account is not active. Please check your email inbox and confirm your email.', 'paylands-woocommerce');
						}else{
							$this->error_message = $login_data['description'];
						}
					}else{
						$this->error_message = __( 'Incorrect username or password', 'paylands-woocommerce');
					}
				}
			}else{
				$this->error_message = __( 'Account data not set', 'paylands-woocommerce');
			}
			return false;
		}catch (Exception $e) {
			//TODO syl handle exception
			Paylands_Logger::log("Error during reconnect_account ".$e->getMessage());
			$this->error_message = $this->get_generic_error_message();
			return false;
		}
	}

	public function connect_account($username, $password) {
		$this->error_message = '';
		$this->paylands_api_onboarding_loader();
		try {
			$login_data = $this->api->oauthRequest($username, $password);
			if ($login_data) {
				if (!empty($login_data['access_token'])) {

					//comprueba que la cuenta con la que se ha hecho login coincide con la que teniamos configurada
					$is_same_account = $this->check_business_id_on_login($login_data['access_token']);

					if (!$is_same_account) {
						//el login no corresponde al comercio que habia hecho el login inicial
						update_option('woocommerce_paylands_connect_data',''); //TODO syl probar
						$this->error_message = __( 'Invalid business_id', 'paylands-woocommerce');
						return false;
					}

					//si la cuenta es correcta continuamos
					$now = new DateTime();
					$expires_in = $login_data['expires_in'];
					$now->modify("+ $expires_in second");
					$token_expiration_date = $now->format('Y-m-d H:i:s');
					$this->login_data = array('access_token' => $login_data['access_token'],
											'refresh_token' => $login_data['refresh_token'],
											'token_expiration' => $token_expiration_date);
					update_option('woocommerce_paylands_connect_data', serialize($this->login_data));

					//configura y guarda los datos de la cuenta
					$set_up_result_ok = $this->set_up_account_data();

					if (!$set_up_result_ok) {
						Paylands_Logger::log('set_up_account_data false en set_up_account_data');
						$this->error_message = $this->get_generic_error_message();
						return false;
					}					

					return true;

				}else if (isset($login_data['error'])) {
					if ($login_data['error'] == 'unauthorized_client') {
						//token no valido
						$this->error_message = __( 'Invalid token', 'paylands-woocommerce');
					}elseif ($login_data['error'] == 'invalid_grant') {
						//Contraseña incorrecta
						$this->error_message = __( 'Incorrect username or password', 'paylands-woocommerce');
					}elseif ($login_data['error'] == 'user_inactive') {
						//Cuenta creada, email no validado
						$this->error_message = __( 'Your account is not active. Please check your email inbox and confirm your email.', 'paylands-woocommerce');
					}else{
						$this->error_message = $login_data['description'];
					}
				}else{
					$this->error_message = __( 'Incorrect username or password', 'paylands-woocommerce');
				}
			}
			return false;
		}catch (Exception $e) {
			//TODO syl handle exception
			Paylands_Logger::log("Error during connect_account ".$e->getMessage());
			$this->error_message = $this->get_generic_error_message();
			return false;
		}
	}

	/**
	 * Borra la configuración de la cuenta de Paylands, no borra compras ni la configuración de las pasarelas
	 */
	public function delete_account() {
		delete_option('woocommerce_paylands_connect_data');
        delete_option('woocommerce_paylands_business_id');
		delete_option('woocommerce_paylands_business_data');
        delete_option('woocommerce_paylands_business_status');
        delete_option('woocommerce_paylands_business_credentials');
		delete_option('woocommerce_paylands_services');
		delete_option('woocommerce_paylands_services_googlePay');
		delete_option('woocommerce_paylands_services_applePay');
		delete_option('woocommerce_paylands_services_last_update');
		delete_option('woocommerce_paylands_services_previous');
		delete_option('woocommerce_paylands_services_last_change');
		delete_option('paylands_gateway_routes_flushed'); //TODO syl cuando se desconecta y se meten keys manualmente no iban las rutas, esto lo arregla?
		$settings = Paylands_Gateway_Settings::$main_settings_fields_ids;
		foreach ($settings as $option_name) {
			delete_option($option_name);
		}

		//borra las opciones de las pasarelas
		delete_option('woocommerce_paylands_woocommerce_payment_gateway_settings');
		delete_option('woocommerce_paylands_woocommerce_one_click_settings');
		global $wpdb;
		$sql = "delete from $wpdb->options where option_name like 'woocommerce_paylands_woocommerce_gateway_%'";
		$wpdb->query($sql);

		//borra las opciones de la personalizacion
		global $wpdb;
		$sql = "delete from $wpdb->options where option_name like 'woocommerce_paylands_custom_%'";
		$wpdb->query($sql);
	}

}
