<?php

/**
 * Define the Paylands Gateway Loader
 *
 * Load the Paylands Gateway and registe it into WooCommerce.
 *
 * @link       http://paylands.com/contacto/
 * @since      1.0.0
 *
 * @package    Paylands_Woocommerce
 * @subpackage Paylands_Woocommerce/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Paylands_Gateway_Loader {

	use Paylands_Helpers;

	public $gateways_list;

	private $gateways_ids;

	private $applePay;

	private $googlePay;

	/**
	 * Recupera la lista de servicios
	 * mode = test  -> recupera la lista de servicios de test
	 * mode = pro	-> recupera la lista de servicios de pro
	 * mode = ''	-> recupera la lista de test y pro en dos arrays independientes (como esta en la bbdd)
	 */
	public function get_gateways_list($mode='') {
		
		$this->set_gateways_list();
		
		if ($mode == 'test') {
			if (!empty($this->gateways_list['test'])) {
				return $this->gateways_list['test'];
			}
		}elseif ($mode == 'pro') {
			if (!empty($this->gateways_list['pro'])) {
				return $this->gateways_list['pro'];
			}
		}elseif ($mode == 'mixed') {
			//junta en un array los servicios de test y pro
			$gateways = array();
			if (!empty($this->gateways_list['pro'])) {
				foreach ($this->gateways_list['pro'] as $key=>$gateway) {
					$gateways[$key] = $gateway;
				}
			}
			if (!empty($this->gateways_list['test'])) {
				foreach ($this->gateways_list['test'] as $key=>$gateway) {
					$gateways[$key] = $gateway;
				}
			}
			return $gateways;
		}else{
			return $this->gateways_list;
		}
		return false;
	}

	public function get_apple_pay() {
		$this->set_apple_pay();
		return $this->applePay;
	}

	public function get_google_pay() {
		$this->set_google_pay();
		return $this->googlePay;
	}

	protected function set_gateways_list() {
		if (empty($this->gateways_list)) {
			$this->gateways_list = get_option('woocommerce_paylands_services');
		}
	}

	protected function set_apple_pay() {
		if (empty($this->applePay)) {
			$this->applePay = get_option('woocommerce_paylands_services_applePay');
		}
	}

	protected function set_google_pay() {
		if (empty($this->googlePay)) {
			$this->googlePay = get_option('woocommerce_paylands_services_googlePay');
		}
	}

	public function update_resources_from_api() {
		Paylands_Logger::dev_debug_log('update_resources_from_api');

		//consulta los servicios actuales para ver si hay cambios
		$previous_services = get_option('woocommerce_paylands_services');

		////////////////////////////////////////////////////
		//carga del listado de servicios o metodos de pago
		////////////////////////////////////////////////////
		$services = array();
		//conectamos a la api para solicitar los servicios de test
		$this->paylands_api_loader('test');
		if ($this->api) {
			$resources_test = $this->api->retrieveResources();

			if (isset($resources_test['services'])) {
				$services_test = $resources_test['services'];
				//echo 'services_test<BR/>';
				//print_r($services_test); 
				$final_services_test = array();
				if (!empty($services_test) && isset($services_test[0]['uuid'])) {
					//echo 'dentro if<BR/>';
					//los guarda en un array con el tipo como indice
					foreach ($services_test as $serv) {
						//echo 'serv<BR/>';
						//print_r($serv); 
						$service_unique_uuid = $this->get_gateway_unique_uuid($serv);
						$final_services_test[$service_unique_uuid] = $serv;
					}
					$services['test'] = $final_services_test;
				}
			}
		}

		$final_services_pro = array();
		//conectamos a la api para solicitar los servicios de pro
		$this->paylands_api_loader('pro'); 
		if ($this->api) {
			$resources_pro = $this->api->retrieveResources(); 
			if (isset($resources_pro['services'])) {
				$services_pro = $resources_pro['services'];
				//echo 'services_pro<BR/>';
				//print_r($services_pro); 
				if (!empty($services_pro) && isset($services_pro[0]['uuid'])) {
					//los guarda en un array con el tipo como indice
					foreach ($services_pro as $serv) {
						$service_unique_uuid = $this->get_gateway_unique_uuid($serv);
						$final_services_pro[$service_unique_uuid] = $serv;
					}
					$services['pro'] = $final_services_pro;
				}
			}
		}

		//echo 'services<BR/>';
		//print_r($services); 

		//comprueba si ha habido cambios en los servicios
		$has_changed = true;
		if (!empty($previous_services)) {
			if ($services === $previous_services) {
				$has_changed = false;
				Paylands_Logger::dev_debug_log('update_resources_from_api no hay cambios');
			}else{
				Paylands_Logger::dev_debug_log('update_resources_from_api hay cambios');
				update_option('woocommerce_paylands_services_previous', $previous_services);
				update_option('woocommerce_paylands_services_last_change', date("Y-m-d H:i:s"));
				//si se han añadido nuevos servicios hay que hacer la configuración inicial
				//comprueba servicios de test
				$new_test_services = $services['test']; //TODO syl pasar a funcion
				if (!empty($new_test_services)) {
					foreach ($new_test_services as $key=>$gateway) {
						Paylands_Logger::dev_debug_log('comprobando servicio de test '.$key.' '.$gateway['type']);
						if (!isset($previous_services['test'][$key]) || !$previous_services['test'][$key]['enabled']) {
							//si es nuevo o lo han pasado a activo levanta la pasarela
							if ($gateway['enabled']) {
								Paylands_Logger::log('nuevo servicio en test desde api '.$key);
								$gateway_unique_uuid = $this->get_gateway_unique_uuid($gateway);
								$this->generate_class($gateway_unique_uuid, $gateway['name'], $gateway['type']);
								$this->set_initial_gateway_config($gateway, false);
							}
						}else{
							Paylands_Logger::dev_debug_log('ya estaba');
						}
					}
				}
				//comprueba servicios de pro
				$new_pro_services = $services['pro'];
				if (!empty($new_pro_services)) {
					foreach ($new_pro_services as $key=>$gateway) {
						if (!isset($previous_services['pro'][$key]) || $previous_services['pro'][$key]['enabled']) {
							//si es nuevo o lo han pasado a activo levanta la pasarela
							if ($gateway['enabled']) {
								Paylands_Logger::log('nuevo servicio en pro desde api '.$key);
								$gateway_unique_uuid = $this->get_gateway_unique_uuid($gateway);
								$this->generate_class($gateway_unique_uuid, $gateway['name'], $gateway['type']);
								$this->set_initial_gateway_config($gateway, false);
							}
						}
					}
				}
			}
		}//si esta vacio es que es la primera carga

		if (!empty($services)) {
			update_option('woocommerce_paylands_services', $services);
			update_option('woocommerce_paylands_services_last_update', date("Y-m-d H:i:s"));
		}

		////////////////////////////////////////////////////
		//carga del checkout UUID
		////////////////////////////////////////////////////
		if (isset($resources_test['checkouts']) && isset($resources_test['checkouts'][0]) && isset($resources_test['checkouts'][0]['uuid'])) {
			update_option('woocommerce_paylands_settings_checkout_uuid_test', $resources_test['checkouts'][0]['uuid']);
		}
		if (isset($resources_pro['checkouts']) && isset($resources_pro['checkouts'][0]) && isset($resources_pro['checkouts'][0]['uuid'])) {
			update_option('woocommerce_paylands_settings_checkout_uuid_pro', $resources_pro['checkouts'][0]['uuid']);
		}

		////////////////////////////////////////////////////
		//carga del googlePay y applePay
		////////////////////////////////////////////////////
		$services_google = array();
		if (isset($resources_test['googlePay']) && isset($resources_test['googlePay']['enabled'])) {
			$services_google['test'] = $resources_test['googlePay']['enabled'];
		}
		if (isset($resources_pro['googlePay']) && isset($resources_pro['googlePay']['enabled'])) {
			$services_google['pro'] = $resources_pro['googlePay']['enabled'];
		}
		update_option('woocommerce_paylands_services_googlePay', $services_google);

		$services_apple = array();
		if (isset($resources_test['applePay']) && isset($resources_test['applePay']['enabled'])) {
			$services_apple['test'] = $resources_test['applePay']['enabled'];
		}
		if (isset($resources_pro['applePay']) && isset($resources_pro['applePay']['enabled'])) {
			$services_apple['pro'] = $resources_pro['applePay']['enabled'];
		}
		update_option('woocommerce_paylands_services_applePay', $services_apple);

		return $has_changed;
	}

	/**
	 * Se sustituye por update_resources_from_api
	 */
	/*public function update_services_from_api() {
		//ya no se usa, se usa la de resources
		Paylands_Logger::dev_debug_log('update_services_from_api');
		$services = array();
		//conectamos a la api para solicitar los servicios de test
		$this->paylands_api_loader('test');
		if ($this->api) {
			$services_test = $this->api->retrieveServices();
			$final_services_test = array();
			if (!empty($services_test) && isset($services_test[0]['uuid'])) {
				//los guarda en un array con el tipo como indice
				foreach ($services_test as $serv) {
					$final_services_test[$serv['type']] = $serv;
				}
				$services['test'] = $final_services_test;
			}
		}

		$final_services_pro = array();
		//conectamos a la api para solicitar los servicios de pro
		$this->paylands_api_loader('pro');
		if ($this->api) {
			$services_pro = $this->api->retrieveServices();  //TODO syl ver el caso que no viniera ningun servicio
			if (!empty($services_pro) && isset($services_pro[0]['uuid'])) {
				//los guarda en un array con el tipo como indice
				foreach ($services_pro as $serv) {
					$final_services_pro[$serv['type']] = $serv;
				}
				$services['pro'] = $final_services_pro;
			}
		}

		if (!empty($services)) {
			update_option('woocommerce_paylands_services', $services);
			update_option('woocommerce_paylands_services_last_update', date("Y-m-d H:i:s"));
		}
	}*/

	protected function generate_gateway_classname($gateway) {
		$gateway_unique_uuid = $this->get_gateway_unique_uuid($gateway);
		$gateway_class_name = 'Paylands_Gateway_'.$gateway_unique_uuid;
		return $gateway_class_name;
	}

	public static function get_gateway_id($gateway) {
		if (!empty($gateway)) {
			$gateway_unique_uuid = self::get_gateway_unique_uuid($gateway);
			return 'paylands_woocommerce_gateway_'.$gateway_unique_uuid;
		}
		return false;
	}

	public function get_gateways_ids() {
		if (!empty($this->gateways_ids)) {
			foreach ($this->get_gateways_list() as $gateway) {
				$gateway_class_name = $this->generate_gateway_classname($gateway);
				$this->gateways_ids[] = $gateway_class_name;
			}
		}
		//Paylands_Logger::dev_debug_log('get_gateways_ids '.implode('; ', $this->gateways_ids));
		return $this->gateways_ids;
	}

	/**
	 * configuración inicial de las pasarelas cuando conectan la cuenta de paylands
	 */
	public function set_initial_gateways_config() {
		Paylands_Logger::dev_debug_log('set_initial_gateways_config');
		$this->update_resources_from_api();
		$this->paylands_payment_gateway_init('initial_gateways_config');

		/*if (Paylands_Gateway_Settings::is_test_mode_active_static()) {
			$gateways_list = $this->get_gateways_list('test');
		}else{
			$gateways_list = $this->get_gateways_list('pro');
		}*/
		$gateways_list = $this->get_gateways_list('mixed');

		if (!empty($gateways_list)) {
			foreach ($gateways_list as $key=>$gateway) {
				$this->set_initial_gateway_config($gateway);
			}
		}
	}

	public function set_initial_gateway_config($gateway, $enabled=true) {
		Paylands_Logger::dev_debug_log('set_initial_gateway_config '.json_encode($gateway));
		if ($gateway['enabled']) {
			$gateway_class_name = $this->generate_gateway_classname($gateway);
			$gateway_object = new $gateway_class_name;
			if ($enabled) {
				$options = array('enabled' => 'yes');
			}else{
				$options = array('enabled' => 'no');
			}
			$options['uuid_service_key'] = $gateway['uuid'];
			Paylands_Logger::dev_debug_log('set_initial_gateway_config options '.json_encode($options));
			$gateway_object->set_config($options);
		}
	}

	/**
	 * Paylands Payment Gateway Init.
	 *
	 * Registe the Paylands Instance for WooCommerce
	 */
	public function paylands_payment_gateway_init($from='', $mode='') {
		//recupera los servicios activos
		//Paylands_Logger::dev_debug_log('paylands_payment_gateway_init');
		if (empty($mode)) {
			if ($from == 'initial_gateways_config') {
				$gateways_list = $this->get_gateways_list('mixed');
			}else{
				//sino se indica el entorno coje el modo de las opciones
				if (Paylands_Gateway_Settings::is_test_mode_active_static()) {
					$gateways_list = $this->get_gateways_list('test');
				}else{
					$gateways_list = $this->get_gateways_list('pro');
				}
			}
		}else{
			//cuando se indica el entorno es porque estamos en el proceso de guardado y cogemos el valor del post ya que en la bbdd aun no se ha actualizado
			$gateways_list = $this->get_gateways_list($mode);
		}		
		if (!empty($gateways_list)) {
			foreach ($gateways_list as $gateway) {
				if ($gateway['enabled']) {
					$gateway_unique_uuid = $this->get_gateway_unique_uuid($gateway);
					$this->generate_class($gateway_unique_uuid, $gateway['name'], $gateway['type']);
					//Paylands_Logger::dev_debug_log($from.'->declarada clase fly '.$gateway_unique_uuid.' - '.$gateway['name'].' - '.$gateway['type'].' modo: '.$mode);
				}else{
					Paylands_Logger::dev_debug_log($from.'->no declarada clase fly no enabled '.$gateway['name'].' - '.$gateway['type'].' modo: '.$mode);
				}
			}
		}else{
			Paylands_Logger::dev_debug_log($from.'->paylands_payment_gateway_init no hay servicios. modo: '.$mode);
		}
	}

	public function paylands_payment_resources_init() {
		$mode = '';
		if (is_admin() && Paylands_Gateway_Settings::is_paylands_main_settings_section()) {
			//TODO syl comprobar si se han introducido las keys manualmente
			if (!Paylands_Gateway_Settings::is_paylands_gateway_saving_settings()) {
				//si estamos en la página de metodos de pago y no esta guardando
				//comprobamos si el comercio ya tiene credenciales de producción (al inicio solo lo crean en sandbox)
				$has_credentials = Paylands_Woocommerce_Account_Connect::has_credentials();
				/*echo '*** CREDENTIALS ***</br>';
				print_r($has_credentials);
				echo '</br>';*/

				if ($has_credentials['pro'] == 'yes' || $has_credentials['test'] == 'yes') {
					//tiene que tener almenos una de las dos, sino tiene ninguna es que aun no han conectado la cuenta
					if ($has_credentials['pro'] == 'no') {
						//comprueba si ya han pasado el comercio a producción
						//TODO syl limitar la comprobación a cada hora?
						$account = new Paylands_Woocommerce_Account_Connect();
						$account->update_business_credentials('pro');
					}elseif ($has_credentials['test'] == 'no') {
							//TODO syl actualizar tb credenciales de test?
					}
				}
				

				//comprueba la api a ver si hay cambios de servicios
				$has_changed = $this->update_resources_from_api();
			}else{
				//Paylands_Logger::dev_debug_log('**paylands_payment_resources_init ');
				//si esta guardando las opciones de configuración pasamos el entorno al init ya que aun no se ha actualizado el valor en la bbdd cuando esto se ejecuta
				if ($_POST['woocommerce_paylands_settings_test_mode']) {
					$mode = 'test';
				}else{
					$mode = 'pro';
				}
				//Paylands_Logger::dev_debug_log('***paylands_payment_resources_init mode '.$mode);
			}
		}

		$this->paylands_payment_gateway_init('paylands_payment_resources_init', $mode);

	}

	public function add_pnp_paylands_gateway_blocks_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once PAYLANDS_ROOT_PATH . 'includes/blocks/class-wc-paylands-blocks-payment.php';

			//Paylands_Logger::dev_debug_log('add_pnp_paylands_gateway_blocks_support');
			$mode = '';
			if (Paylands_Gateway_Settings::is_test_mode_active_static()) {
				$gateways_list = $this->get_gateways_list('test');
				$mode = 'test';
			}else{
				$gateways_list = $this->get_gateways_list('pro');
				$mode = 'pro';
			}
			if (!empty($gateways_list)) {
				Paylands_Logger::dev_debug_log('add_pnp_paylands_gateway_blocks_support hay servicios '.$mode);
				foreach ($gateways_list as $gateway) {
					if ($gateway['enabled']) {
						$gateway_name = $this->get_gateway_id($gateway);
						$icon = Paylands_WC_Gateway::get_gateway_default_icon($gateway['name'], $gateway['type']);
						$this->register_paylands_gateway_block($gateway_name, $icon);
						//Paylands_Logger::dev_debug_log('añadida gateway block '.print_r($gateway, true));
						//Paylands_Logger::dev_debug_log('añadida gateway block '.$gateway_class_name);
					}else{
						Paylands_Logger::dev_debug_log('no añadida gateway block no enabled '.$gateway['type']);
					}
				}
			}else{
				Paylands_Logger::dev_debug_log('add_pnp_paylands_gateway_blocks_support no hay servicios '.$mode);
			}
		}
	}
	
	/**
	 * Función para registrar la pasarela en WooCommerce Blocks
	 */
	private function register_paylands_gateway_block($gateway_name, $icon) {
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) use ($gateway_name, $icon) {
				$payment_method_registry->register(new WC_Paylands_Blocks_Payment($gateway_name, $icon));
			}
		);
	}

	/**
	 * Add Paylands Gateway Instance to the WooCommerce
	 * Payment Method Class list.
	 *
	 * @param array $gateways - The Current Payment Gateways register in WooCommerce
	 * @return  array - The Payment Methods of WooCommerce with Paylands.
	 */
	public function add_pnp_paylands_gateway( $gateways ) {
		//Paylands_Logger::dev_debug_log('add_pnp_paylands_gateway');
		$mode = '';
		if (Paylands_Gateway_Settings::is_test_mode_active_static()) {
			$gateways_list = $this->get_gateways_list('test');
			$mode = 'test';
		}else{
			$gateways_list = $this->get_gateways_list('pro');
			$mode = 'pro';
		}
		if (!empty($gateways_list)) {
			Paylands_Logger::dev_debug_log('add_pnp_paylands_gateway hay servicios '.$mode);
			foreach ($gateways_list as $gateway) {
				if ($gateway['enabled']) {
					$gateway_class_name = $this->generate_gateway_classname($gateway);
					$gateways[] = $gateway_class_name;
					//Paylands_Logger::dev_debug_log('añadida gateway '.$gateway_class_name);
				}else{
					Paylands_Logger::dev_debug_log('no añadida gateway no enabled '.$gateway['type']);
				}
			}
		}else{
			Paylands_Logger::dev_debug_log('add_pnp_paylands_gateway no hay servicios '.$mode);
		}

		return $gateways;
	}

	public static function get_gateway_unique_uuid($gateway) {
		$gateway_class_name = strtolower(str_replace('-','',$gateway['uuid']));
		return $gateway_class_name;
	}

	private function generate_class($name, $title, $type) {
		//Paylands_Logger::dev_debug_log("generate_class $name, $title, $type");
		$icon = Paylands_WC_Gateway::get_gateway_default_icon($title, $type);
		$title = sanitize_text_field($title);
		$filepath = PAYLANDS_WOOCOMMERCE_INCLUDES."/class-paylands-woocommerce-gateway-generator.php";
		$eval = str_replace("GATEWAYNAME", $name, trim(substr(trim(file_get_contents($filepath)), 5, -2)));
		$eval = str_replace("GATEWAYTITLE", $title, $eval); 
		$eval = str_replace("GATEWAYTYPE", $type, $eval); 
		$eval = str_replace("GATEWAYICON", $icon, $eval); 
		// Eval the string to create the class
		eval($eval);
	}

}
