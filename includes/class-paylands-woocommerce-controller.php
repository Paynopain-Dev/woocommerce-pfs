<?php
/**
 * The file that define the Paylands Ajax Controller
 *
 * This file has the routes used to handle all the Paylands
 * views while the user pay with Paylands.
 *
 * @link       http://paylands.com/contacto/
 * @since      1.0.0
 *
 * @package    Paylands_Woocommerce
 * @subpackage Paylands_Woocommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Paylands Controller Class
 */
class Paylands_Controller {

	use Paylands_Helpers;

	/**
	 * Paylands Index View
	 *
	 * @return [type] [description]
	 */
	public function paylands_index() {
		Paylands_Logger::dev_debug_log('paylands_index');
		try {

			$this->paylands_api_loader();

			if ( $this->is_gateway_ready ) {
				// Retrieve the order id to process payment.
				$order 			= wc_get_order( (int)$_GET['order_id'] );
				$total 			= $order->get_total();
				$customer_id 	= $this->get_customer_id($order);
				$urls 			= $this->get_actions_url( $order->get_id() );
				$order_names	= "{$order->get_billing_first_name()} {$order->get_billing_last_name()} - {$order->get_id()}";

				try {
					Paylands_Logger::dev_debug_log('paylands_index createOrder');

					$current_gateway = $this->get_selected_payment_gateway();
					Paylands_Logger::dev_debug_log('paylands_index current_gateway '.json_encode($current_gateway));//, JSON_PRETTY_PRINT));
					if (empty($current_gateway)) {
						Paylands_Logger::log('error no hay pasarela seleccionada');
						// load error page
						return header( "Location:" . $urls['ko'] );
					}

					/*
					Segun email de Pascual el 08/07/2024:
					ahora mismo no tenemos una forma de indicar si un método de pago es de tarjeta o no porque algunos son válidos tanto para tarjeta como sin ella.
					Lo que podemos hacer de momento es indicar en el nombre del método de pago algo tipo "[Card]". 
					Entonces, para saber si tenéis que redirigir al checkout o a /payment/process, podéis consultar si existe el string "[Card]" dentro del nombre del método de pago. 
					Aunque no es lo más correcto, ahora mismo es la única forma de indicarlo.
					*/
					$is_card = false;
					$is_bizum = false;
					if (isset($current_gateway->method_title) && !empty($current_gateway->method_title)) {
						if (str_contains($current_gateway->method_title, '[Card]')) {
							$is_card = true;
						}elseif (str_contains(strtolower($current_gateway->method_title), 'bizum')) {
							$is_bizum = true;
						}
					}
					Paylands_Logger::dev_debug_log('payment current_gateway '.json_encode($current_gateway));
					//$is_card = true;
					

					$payland_order = $this->api->createOrder(
						$total,
						$order->get_currency(),
						"AUTHORIZATION",
						(string)$customer_id, // String format because is neccessary
						$order_names,
						(string)$order->get_id(), // String format because is neccessary
						$current_gateway->service,
						$current_gateway->secure_payment, //TODO syl probar
						$urls['callback'],
						$order->get_checkout_order_received_url(),
						$urls['ko'],
						'',
						$is_card,
						$order
					);
				} catch (Exception $e) {
					$error = sprintf(
						__( 'Something failed creating the payland order. Error: %s', 'paylands-woocommerce'),
						$e->getMessage()
					);

					Paylands_Logger::log( $error );
					return header( "Location:" . $urls['ko'] );
				}

				// validate if order was created
				if ( empty( $payland_order ) || $payland_order['code'] !== 200 ) {
					Paylands_Logger::log( $payland_order );
					// load error page
					return header( "Location:" . $urls['ko'] );
				}

				$this->orders->save( $payland_order );

				if ($is_card) {
					$redirect_url = $this->api->getRedirectCheckoutUrl($payland_order["order"]["token"]);
				}elseif ($is_bizum) {
					$redirect_url = $this->api->getRedirectBizumUrl($payland_order["order"]["token"]);
				}else{
					$redirect_url = $this->api->getRedirectUrl($payland_order["order"]["token"]);
					//TODO syl añadir el código del apm a la url?
					/*"BIZUM" "IDEAL" "SOFORT" "KLARNA" "VIACASH" "COFIDIS" "PIX" "CRYPTO" "GIROPAY" "TRANSFER" "PSE" "PAYPAL" "PAYLATER" "SPEI" "MULTIBANCO" "MBWAY" "FLOA" "PAYSAFECARD" "PAGO_FACIL" "EFECTY" "BOLETO" "LOTERICA" "PAYSHOP" "PICPAY" "MACH" "KLAP" "KHIPU" "SERVIPAG"*/
				}
				
				Paylands_Logger::dev_debug_log('payment redirect_url '.$redirect_url);

				header( "Location:" . $redirect_url ); // redirect to Payment Card Form
				exit();
			}
		} catch ( Exception $e ) {
			$data['success'] = false;
			$data['error']   = $e->getMessage();
		}
	}

	public function paylands_ko() {
		//TODO syl dejar personalizar el mensaje de error?
		$error_message = __( "There was an error processing the payment. Please contact the store's administrator to get more details.", 'paylands-woocommerce' );
		wc_add_notice( $error_message, 'error' );
		wp_safe_redirect( wc_get_checkout_url() );
	}

	/**
	 * Paylands Callback
	 *
	 * Used to response the paylands callback after
	 * success a payment. This callback is called throught
	 * the WP API REST.
	 *
	 * @param  array $data 	The API Response data
	 */
	public function paylands_callback( $data ) {
		
		$data = $data->get_params();

		Paylands_Logger::dev_debug_log('paylands_callback '.json_encode($data));

		if ( ! empty( $data ) ) {
			$this->paylands_api_loader();

			$order_id = $data['order']['additional'];
			$order = wc_get_order( $order_id );

			switch ( $data['order']['status'] ) {
                case 'SUCCESS':
                    $order->payment_complete();
					//TODO syl añadir con que pasarela se ha hecho el pago
					$note = __("Payland Charge Completed", "paylands-woocommerce");
                    break;
                case 'CREATED':
                    $note = __("Payland Charge Completed", "paylands-woocommerce");
                    break;
                case 'REFUSED':
                	$note = __("Payment Refused by merchant", "paylands-woocommerce");
                    break;
                case 'ERROR':
                	$note = __("Payment Error", "paylands-woocommerce");
                    break;
                case 'BLACKLISTED':
                	$note = __("Payment Blacklisted", "paylands-woocommerce");
                    break;
                case 'CANCELLED':
                	$note = __("Payment Cancelled", "paylands-woocommerce");
                    break;
                case 'EXPIRED':
                	$note = __("Payment Expired", "paylands-woocommerce");
                    break;
                case 'FRAUD':
                	$note = __("Payment Fraud", "paylands-woocommerce");
                    break;
                case 'PARTIALLY_REFUNDED':
                	$note = _("Payment Partially Refunded", "paylands-woocommerce");
                    break;
                case 'PENDIG_CONFIRMATION':
                	$note = __("Payment Pending of confirmation", "paylands-woocommerce");
                    break;
                case 'REFUNDED':
                	$note = __("Payment Refunded", "paylands-woocommerce");
                    break;
                case 'PENDING_PROCESSOR_RESPONSE':
                	$note = __("Payment Pending Processor Response", "paylands-woocommerce");
                    break;
                case 'PENDING_3DS_RESPONSE':
                	$note = __("Payment Pending 3DS Response", "paylands-woocommerce");
                    break;
                case 'PENDING_CARD':
                	$note = __("Payment Pending Card", "paylands-woocommerce");
                    break;
                default:
                	$note = __("Payment Error", "paylands-woocommerce");
            }

            $order->add_order_note( $note );
		}
	}
}
