<?php
/**
 * The file that define the Paylands Ajax Endpoints
 *
 * This file have the ajax endpoints, necessary to use in the paylands account login
 *
 * @link       http://paylands.com/contacto/
 * @since      1.0.0
 *
 * @package    Paylands_Woocommerce
 * @subpackage Paylands_Woocommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paylands WooCommerce Ajax Class
 */
class Paylands_WooCommerce_Ajax {

	use Paylands_Helpers;

	public function __construct() {
        add_action( 'wp_ajax_paylands_connect_login_account', array($this, 'connect_login_account' ));
        add_action( 'wp_ajax_nopriv_paylands_connect_login_account', array($this, 'connect_login_account' ));

        add_action( 'wp_ajax_paylands_disconnect_login_account', array($this, 'disconnect_login_account' ));
        add_action( 'wp_ajax_nopriv_paylands_disconnect_login_account', array($this, 'disconnect_login_account' ));

        add_action( 'wp_ajax_paylands_send_new_service', array($this, 'send_new_service' ));
        add_action( 'wp_ajax_nopriv_paylands_send_new_service', array($this, 'send_new_service' ));
	}

    /**
     * connect_login_account
     * */
    public function connect_login_account() {
        if ( ! is_user_logged_in() ) {
            Paylands_Logger::log('paylands login attack');
			$this->send_error( __( 'Invalid request parameters', 'paylands-woocommerce' ) );
		}

		if ( ! isset( $_POST['paylands_username'] ) || ! isset( $_POST['paylands_password'] ) ) {
            Paylands_Logger::log('paylands login attempt empty username or password');
			$this->send_error( __( 'Missing data', 'paylands-woocommerce' ) );
		}

        $account = new Paylands_Woocommerce_Account_Connect();
        $connection_success = $account->connect_account($_POST['paylands_username'], $_POST['paylands_password']);
        if ($connection_success) {
            Paylands_Logger::log('paylands login successful');
            $account->load_data();
            $data = $account->get_connection_data();
            $status = $account->get_onboarding_status();
            if ($status == 'PENDING_VALIDATION') {
                Paylands_Logger::log('paylands login successful account pending validation');
                $business_status = $account->get_business_status();
                $account->delete_account(); 
                if ($business_status == 'CREATED') {
                    $message = '<p>'.__( 'We are waiting for you to send your documentation.', 'paylands-woocommerce' ).'</p>';
                    $message .= '<p>'.__( 'Check the email we sent you after registration with instructions on how to access your private area and upload all the documentation needed to complete your registration.', 'paylands-woocommerce' ).'</p>';
                    $message .= '<p>'.__( 'For any questions, please contact your Account Manager.', 'paylands-woocommerce' ).'</p>';
                }else{
                    $message = '<p>'.__( 'We are validating your business', 'paylands-woocommerce' ).'</p>';
                    $message .= '<p>'.__( 'You will soon receive an email notifying you of the status of your business so you can start selling as soon as possible.', 'paylands-woocommerce' ).'</p>';
                    $message .= '<p>'.__( 'If we need additional information, we will let you know.', 'paylands-woocommerce' ).'</p>';
                }
                $this->send_error($message);
            }else{
                $data['onboarding_status'] = $status;
                return $this->send_success($data);
            }
        }else{
            Paylands_Logger::log('paylands login error '.$account->get_error_message());
            $account->delete_account();
            $this->send_error($account->get_error_message());
        }
        
        // Cerramos la conexión
        wp_die();
    }

    /**
     * disconnect_login_account
     * */
    public function disconnect_login_account() {
        if ( ! is_user_logged_in() ) {
            Paylands_Logger::log('paylands login attack');
			$this->send_error( __( 'Invalid request parameters', 'paylands-woocommerce' ) );
		}
        $account = new Paylands_Woocommerce_Account_Connect();
        if (!empty($account->get_business_id())) {
            $business_id = $account->get_business_id();
            $account->delete_account();
            Paylands_Logger::log('paylands disconnecting account '.$business_id );
            return $this->send_success('ok');
        }else{
            Paylands_Logger::log('paylands trying to disconnect a non existing account');
			$this->send_error( __( 'Paylands trying to disconnect a non existing account', 'paylands-woocommerce' ) );
        }
        wp_die();
    }

    /**
     * send_new_service
     * */
    public function send_new_service() {
        if ( ! is_user_logged_in() ) {
            Paylands_Logger::log('paylands login attack');
			$this->send_error( __( 'Invalid request parameters', 'paylands-woocommerce' ) );
		}

		if ( ! isset( $_POST['paylands_service_name'] ) || ! isset( $_POST['paylands_service_type'] ) ) {
            Paylands_Logger::log('paylands login attempt empty name or type');
			$this->send_error( __( 'Missing data', 'paylands-woocommerce' ) );
		}

        $result = false;
        //envia el email
        $email = woocommerce_paylands_email();
        if (empty($email)) {
            Paylands_Logger::log('No se ha podido enviar la solicitud de nuevo servicio porque no hay un email indicado');
        }else{
            $service_name = $_POST['paylands_service_name'];
            $service_type = $_POST['paylands_service_type'];
            $merchant_id = get_option('woocommerce_paylands_business_id');
            $message = "Un comercio ha solicitado el alta de un nuevo servicio.\n";
            $message .= "ID del comercio: $merchant_id\n";
            $message .= "Nombre del servicio: $service_name\n";
            $message .= "Tipo de servicio: $service_type";
            $result = wp_mail($email, "Solicitud nuevo servicio desde Plugin Paylands Woocommerce", $message);
        }

        if ($result) {
            Paylands_Logger::log('paylands send new service successful');
            return $this->send_success(array());
        }else{
            $this->send_error(array());
        }
        
        // Cerramos la conexión
        wp_die();
    }
}

/**
 * Initialize the Ajax Class
 */
new Paylands_WooCommerce_Ajax();
