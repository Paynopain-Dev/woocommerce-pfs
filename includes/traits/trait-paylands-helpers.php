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
	private $api;

	/**
	 * The Paylands Gateway WooCommerce Instance
	 * @var Object
	 */
	private $gateway_main_settings;

	/**
	 * Validate if the gateway has the settings field saved.
	 * @var boolean
	 */
	private $is_gateway_ready;

	/**
	 * The Paylands Orders Model Instance
	 * @var Object
	 */
	private $orders;

	/**
	 * The Paylands Cards Model Instance.
	 * @var Object
	 */
	//private $cards;

	/**
	 * Load Payland Models
	 */
	private function load_models() {
		$this->orders 	= new Paylands_Orders();
	}

	public function get_selected_payment_gateway() {
		if (!empty(WC()->session)) {
			$selected_payment_method_id = WC()->session->get( 'chosen_payment_method' );
			if (!empty($selected_payment_method_id)) {
				$current_gateway = WC()->payment_gateways()->payment_gateways()[ $selected_payment_method_id ];
				return $current_gateway;
			}
		}
		return false;
	}

	/**
	 * Initialize the Paylands Api
	 */
	public function paylands_api_loader($mode='') {
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
	 * @return integer - The customer id
	 */
	public function get_customer_id() {
		return is_user_logged_in() ? get_current_user_id() : '';
	}

	/**
	 * Get Actions URL for Paylands Responses.
	 *
	 * @return array - The Action URLs
	 */
	private function get_actions_url( $id ) {
		if ( get_option( 'permalink_structure' ) ) {
			return array(
	    		'callback'		=> get_site_url() . "/wp-json/paylands-woocommerce/v1/callback",
				'ko' 			=> get_site_url() . "/paylands-ko?id={$id}",
	    	);
		}

		return array(
    		'callback'		=> get_site_url() . "/index.php?rest_route=/paylands-woocommerce/v1/callback",
    		'ko' 			=> get_site_url() . "/?paylands_routes=paylands_ko&id={$id}",
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
    private function response_json( $data ) {
    	header("Content-Type: application/json");
    	echo json_encode( $data );
    }
}