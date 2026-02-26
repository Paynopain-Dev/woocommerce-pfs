<?php
/**
 * This file have a helpers used on the Paylands Gatewy Class Ajax.
 *
 *
 * @link       http://paylands.com/contacto/
 * @since      1.0.0
 *
 * @package    Paylands_Woocommerce
 * @subpackage Paylands_Woocommerce/includes/traits
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Ajax Helper Trait
 */
trait Paylands_Helpers {

	/**
	 * The Paylands Gateway Api Client Instance
	 * @var Object
	 */
	protected $api;

	/**
	 * The Paylands Gateway WooCommerce Instance
	 * @var Object
	 */
	protected $gateway_main_settings;

	/**
	 * Validate if the gateway has the settings field saved.
	 * @var boolean
	 */
	protected $is_gateway_ready;

	/**
	 * The Paylands Orders Model Instance
	 * @var Object
	 */
	protected $orders;

	/**
	 * The Paylands Cards Model Instance.
	 * @var Object
	 */
	//protected $cards;

	/**
	 * Load Payland Models
	 */
	protected function load_models() {
		$this->orders 	= new Paylands_Orders();
	}

	public function is_paylands_checkout($mode='') {
		//Paylands_Logger::dev_debug_log('is_paylands_checkout '.$mode);
		if (!empty($this->gateway_main_settings) && $this->gateway_main_settings instanceof Paylands_Gateway_Settings) {
			$checkout_uuid = $this->gateway_main_settings->get_checkout_uuid($mode);
			//Paylands_Logger::dev_debug_log('is_paylands_checkout checkout_uuid '.$checkout_uuid);
			return !empty($checkout_uuid);
		} else {
			return Paylands_Gateway_Settings::is_checkout_uuid_static($mode);
		}
	}

	/**
	 * Initialize the Paylands Api
	 */
	public function paylands_api_loader($mode='') {
		Paylands_Logger::dev_debug_log('paylands_api_loader '.$mode);
		$this->gateway_main_settings = new Paylands_Gateway_Settings();

		$this->is_gateway_ready = $this->gateway_main_settings->are_keys_set($mode);

		$form_lang = $this->gateway_main_settings->get_form_lang();
		$api_key = $this->gateway_main_settings->get_api_key($mode);
		$signature_key = $this->gateway_main_settings->get_signature_key($mode);
		$checkout_uuid = $this->gateway_main_settings->get_checkout_uuid($mode);

		if ($mode == 'test') {
			$config = ['mode' => 'sandbox'];
		}elseif ($mode == 'pro') {
			$config = ['mode' => 'live'];
		}else{
			//sino especificamos entorno recupera el entorno de la configuración
			if ($this->gateway_main_settings->is_test_mode_active()) {
				$config = ['mode' => 'sandbox'];
			}else{
				$config = ['mode' => 'live'];
			}
		}

		$this->load_models(); //TODO syl ver que hacemos con esto

		if ($this->is_gateway_ready) {
			$this->api = new Paylands_Api_Client(
				$api_key,
				$signature_key,
				$checkout_uuid,
				$config,
				$form_lang
			);
		}else{
			Paylands_Logger::dev_debug_log('paylands_api_loader no_gateway_ready '.$mode);
			$this->api = false;
		}
	}

	/**
	 * Initialize the Paylands Api
	 */
	public function paylands_api_onboarding_loader() {
		if (woocommerce_paylands_is_dev_mode()) { 
			//en modo desarrollador activa la api de onboarding esta en test. Los comercios que creamos no son reales
			$mode = 'test';
			if (!defined('PAYLANDS_TEST_CLIENT_ID') || !defined('PAYLANDS_TEST_CLIENT_SECRET')) {
				Paylands_Logger::dev_debug_log('paylands_api_onboarding_loader no defined test');
				return false;
				//TODO syl ver si avisamos de algo
			}
			$client_id = PAYLANDS_TEST_CLIENT_ID;
			$client_secret = PAYLANDS_TEST_CLIENT_SECRET;
		}else{
			//sino es modo desarrollador tenemos que hacer el onboarding en real
			$mode = 'pro';
			if (!defined('PAYLANDS_PRO_CLIENT_ID') || !defined('PAYLANDS_PRO_CLIENT_SECRET')) {
				Paylands_Logger::dev_debug_log('paylands_api_onboarding_loader no defined pro');
				return false;
				//TODO syl ver si avisamos de algo
			}
			$client_id = PAYLANDS_PRO_CLIENT_ID;
			$client_secret = PAYLANDS_PRO_CLIENT_SECRET;
		}

		if (empty($client_id) || empty($client_secret)) return false; //TODO syl ver si avisamos de algo

		$this->api = new Paylands_Onboarding_Api($client_id, $client_secret, $mode);

	}

	/**
	 * Get Customer Id
	 *
	 * If is user logged in, load return the user id, but if not
	 * just return a random uniqid
	 *
	 */
	public function get_customer_id($order = null) {
		if ( ! empty($order) && is_callable( array($order, 'get_billing_email') ) ) {
			$email = $order->get_billing_email();
			if (!empty($email)) {
				return $email;
			}
		}

		if (is_user_logged_in()) {
			$current_user = wp_get_current_user();
			if (!empty($current_user->user_email)) {
				return $current_user->user_email;
			}
			return get_current_user_id();
		}
		
		return '';
	}

	/**
	 * Get Actions URL for Paylands Responses.
	 *
	 * @return array - The Action URLs
	 */
	protected function get_actions_url( $id ) {
		$checkout_url = wc_get_checkout_url().'?paylands_error=1';
		$ko_url = add_query_arg('paylands_error', '1', $checkout_url);
		$site_url = get_site_url();
		if ( get_option( 'permalink_structure' ) ) {
			return array(
	    		'callback'		=> $site_url . "/wp-json/paylands-woocommerce/v1/callback",
				'ko' 			=> $ko_url,
	    	);
		}

		return array(
    		'callback'		=> $site_url . "/index.php?rest_route=/paylands-woocommerce/v1/callback",
    		'ko' 			=> $ko_url,
    	);
	}

	/**
     * Hooks a function on to a specific action.
     *
     * @param     $tag
     * @param     $function
     * @param int $priority
     * @param int $accepted_args
     */
    public function action( $tag, $function, $priority = 10, $accepted_args = 1 ) {
        add_action( $tag, [ $this, $function ], $priority, $accepted_args );
    }

    /**
     * Hooks a function on to a specific filter.
     *
     * @param     $tag
     * @param     $function
     * @param int $priority
     * @param int $accepted_args
     */
    public function filter( $tag, $function, $priority = 10, $accepted_args = 1 ) {
        add_filter( $tag, [ $this, $function ], $priority, $accepted_args );
    }

    /**
     * Verify request nonce
     *
     * @param  string  the nonce action name
     *
     * @return void
     */
    public function verify_nonce( $action ) {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], $action ) ) {
            $this->send_error( __( 'Error: Nonce verification failed', 'kindhumans' ) );
        }
    }

    /**
     * Wrapper function for sending success response
     *
     * @param  mixed $data
     *
     * @return void
     */
    public function send_success( $data = null ) {
        wp_send_json_success( $data );
    }

    /**
     * Wrapper function for sending error
     *
     * @param  mixed $data
     *
     * @return void
     */
    public function send_error( $data = null ) {
        wp_send_json_error( $data );
    }

    /**
     * Method/Shortcut to return a response as content/type application json
     *
     * @param  array $data - The data sent from method.
     * @return json - data in json format {}
     */
    protected function response_json( $data ) {
    	header("Content-Type: application/json");
    	echo json_encode( $data );
    }

	public static function is_checkout_block_used() {
		if ( ! class_exists( \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class ) ) {
			return false;
		}

		// @phpstan-ignore-next-line.
		return class_exists( \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::class ) && \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default();
	}
}