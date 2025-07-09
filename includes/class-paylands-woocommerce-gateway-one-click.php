<?php
class Paylands_WC_Gateway_One_Click extends Paylands_WC_Gateway {

	public function __construct() {
		$this->id                 = 'paylands_woocommerce_one_click';
		$this->method_title       = 'Paylands One-Click Payment';
		$this->method_description = __( 'Select a saved card for a faster one-click payment', 'paylands-woocommerce' );
		$this->icon               = $this->get_gateway_default_icon( 'One-Click Payment', 'one-click' );
		$this->is_checkout        = false;

		parent::init();

		$this->secure_payment	  = false;

		$this->has_fields = true;

		// Ahora modificamos los form_fields
		unset($this->form_fields['pnp_secure_payments']);
		unset($this->form_fields['uuid_service_key']);

		$this->form_fields['only_successful_cards'] = array(
			'title'       => __( 'Only successful cards', 'paylands-woocommerce' ),
			'type'        => 'checkbox',
			'label'       => __( 'Allow only previously successful cards', 'paylands-woocommerce' ),
			'description' => __( 'Only allow cards that have been used successfully in previous payments.', 'paylands-woocommerce' ),
			'default'     => 'no',
		);
	}

	public function is_available() {
		Paylands_Logger::dev_debug_log('Paylands_WC_Gateway_One_Click is_available');

		// Check parent availability (enabled, config, etc.)
		if ( ! parent::is_available() ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id = get_current_user_id();
  		$only_successful = 'yes' === $this->get_option( 'only_successful_cards', 'no' );
		$cards = false;
		try {
			$this->paylands_api_loader();
			// You should call your API here
			$cards = $this->api->getCustomerCards($user_id, $only_successful);
		} catch (Exception $e) {
			$error = sprintf(
				__( 'Something failed getting the customer cards. Error: %s', 'paylands-woocommerce'),
				$e->getMessage()
			);
			Paylands_Logger::log( $error );
		}

		//Paylands_Logger::log($cards);
		return ! empty( $cards );
	}

	public function payment_fields() {
		Paylands_Logger::dev_debug_log('Paylands_WC_Gateway_One_Click payment_fields');

		if ( ! empty( $this->description ) ) {
			echo '<p>' . esc_html($this->description) . '</p>';
		}

		$user_id = get_current_user_id();
		$only_successful = 'yes' === $this->get_option( 'only_successful_cards', 'no' );
		$cards   = $this->api->getCustomerCards($user_id, $only_successful);

		if ( ! empty( $cards ) ) {
			echo '<select id="paylands_saved_card_select" name="paylands_saved_card_token">';
			foreach ( $cards as $card ) {
				$last4   = esc_html( $card['last4'] );
				$brand   = esc_html( strtoupper( $card['brand'] ) );
				$expires = esc_html( $card['expiry'] );
				$token   = esc_attr( $card['uuid'] );

				// concatenamos card_uuid|service_uuid
				$value = esc_attr( $card['uuid'] . '|' . $card['service_uuid'] );

				echo "<option value='{$value}'>{$brand} **** {$last4} ({$expires})</option>";
			}
			echo '</select>';
		} else {
			echo '<p>' . esc_html__( 'You have no saved cards.', 'paylands-woocommerce' ) . '</p>';
		}
	}

	public function process_payment( $order_id ) {
		Paylands_Logger::dev_debug_log('one_click process_payment');
		try {
			$urls    = $this->get_actions_url( $order_id );
			$ko_url  = $urls['ko'];

			$raw = '';
			if (isset($_POST['paylands_saved_card_token'] ) && !empty($_POST['paylands_saved_card_token'])) {
				$raw = wc_clean(wp_unslash($_POST['paylands_saved_card_token']));
			}

			if ( ! $raw || false === strpos( $raw, '|' ) ) {
				Paylands_Logger::log( 'One-Click: payload malformed [' . $raw . ']' );
				return [ 'result' => 'failure', 'redirect' => $ko_url ];
			}
	
			list( $card_uuid, $service_uuid ) = explode( '|', $raw, 2 );
			if ( empty( $card_uuid ) || empty( $service_uuid ) ) {
				Paylands_Logger::log( 'One-Click: no card selected [' . $raw . ']' );
				return [ 'result' => 'failure', 'redirect' => $ko_url ];
			}

			// 2) Creamos la orden en Paylands (no checkout)
			$this->service = $service_uuid;
			$payland_order = $this->create_paylands_order( $order_id );
			if ( ! $payland_order ) {
				return [ 'result' => 'failure', 'redirect' => $ko_url ];
			}
			$order_uuid = $payland_order['order']['uuid'] ?? '';
		
			// 3) Llamada directa síncrona
			$user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
			
			$direct = $this->api->postDirectPayment( [
				'signature'   => $this->signature,
				'order_uuid'  => $order_uuid,
				'card_uuid'   => $card_uuid,
				'customer_ip' => $user_ip,
			] );
		
			// 4) HTTP 303 → redirigir al checkout externo
			if ( isset( $direct['code'] ) && $direct['code'] === 303 && ! empty( $direct['details'] ) ) {
				return [ 'result' => 'success', 'redirect' => $direct['details'] ];
			}
		
			// 5) Código 200 síncrono
			if ( ( $direct['code'] ?? 0 ) === 200 ) {
				$status = $direct['order']['status'] ?? '';

				$order   = wc_get_order( $order_id );
				$return_url = $order->get_checkout_order_received_url();
		
				switch ( $status ) {
					case 'SUCCESS':
						// Pedido completado
						$order->set_transaction_id( $direct['order']['uuid'] ?? '' );
						$order->payment_complete();
						$order->add_order_note( __( 'One-Click payment succeeded.', 'paylands-woocommerce' ) );
						return [ 'result' => 'success', 'redirect' => $return_url ];
					
					case 'REFUSED':
						// Pedido completado
						$order->add_order_note( __( 'One-Click payment refused.', 'paylands-woocommerce' ) );
						return [ 'result' => 'success', 'redirect' => $ko_url ];
		
					default:
						// Pendientes o fallos se gestionan en el callback async
						//$order->update_status( 'on-hold', __( 'One-Click payment status: ', 'paylands-woocommerce' ) . $status );
						Paylands_Logger::log( 'One-Click unexpected status: ' . $status );
						$order->add_order_note( __( 'One-Click payment failure', 'paylands-woocommerce' ).': '.$status );
						return [ 'result' => 'failure', 'redirect' => $ko_url ];
				}
			}
		
			// 6) Cualquier otro caso
			Paylands_Logger::log( 'One-Click unexpected response: ' . print_r( $direct, true ) );
			return [ 'result' => 'failure', 'redirect' => $ko_url ];

		} catch ( Exception $e ) {
			Paylands_Logger::log( 'One-Click direct payment exception: ' . $e->getMessage() );
			return [ 'result' => 'failure', 'redirect' => $ko_url ];
		}
	}
}
?>