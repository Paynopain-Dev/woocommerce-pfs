<?php
//add_action( 'plugins_loaded', 'woocommerce_paylands_ini_rest', 999 );
//function woocommerce_paylands_ini_rest() {
add_action('rest_api_init', 'paylands_register_routes');
//}

function paylands_register_routes() {
    register_rest_route( 'paylands-woocommerce/v1', '/callback', array(
        'methods' => 'POST',
        'callback' => 'paylands_process_callback',
        'permission_callback' => function () { return true; }
      ) );
}

function paylands_process_callback($data) {
	$data = $data->get_params();

    if (Paylands_Gateway_Settings::is_debug_log_active_static()) {
        $logger = new WC_Logger();
        $logger->add('paylands-woocommerce-logs', 'process_paylands_callback '.print_r($data, true));
    }

    if ( ! empty( $data ) ) {
        if (isset($data['order']) && isset($data['order']['additional']) && isset($data['order']['status'])) {
            $order_id = $data['order']['additional'];
            $order = wc_get_order( $order_id );
            
            if (!empty($order)) {
                $payment_service = '';
                if (isset($data['order']['service'])) {
                    $payment_service = ' ('.$data['order']['service'].')';
                }
                switch ( $data['order']['status'] ) {
                    case 'SUCCESS':
                        $order->payment_complete();
                        $note = __("Payland Charge Completed", "paylands-woocommerce");
                        break;
                    case 'CREATED':
                        $note = __("Payland Charge Created", "paylands-woocommerce");
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
                    case 'PENDING_CONFIRMATION':
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
                $order->add_order_note( $note.$payment_service );

                $order->set_transaction_id( $data['order']['uuid'] ?? '' );
            }
        }
    }
}