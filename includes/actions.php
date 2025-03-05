<?php
add_action('init', function () {
  if (!get_option( 'paylands_gateway_routes_flushed' ) ) {
    flush_rewrite_rules();
    update_option( 'paylands_gateway_routes_flushed', date("Y-m-d H:i:s"));
  }
});

add_action( 'update_option_woocommerce_custom_orders_table_enabled', 'paylands_handle_update_option_woocommerce_custom_orders_table_enabled', 10, 3 );
function paylands_handle_update_option_woocommerce_custom_orders_table_enabled( $old_value, $new_value, $option ) {
    //si modifican el sistema de guardado de las ordernes hay que volver a refrescar los endpoints para que funcionen
    delete_option( 'paylands_gateway_routes_flushed' );
    //$logger = new WC_Logger();
    //$logger->add('paylands-woocommerce-logs', "update_option_woocommerce_custom_orders_table_enabled HPOS (custom orders table) ha cambiado de {$old_value} a {$new_value}");
}

//mostrar el mensaje de error en el checkout clasico si el pago no ha ido bien
add_action( 'template_redirect', 'maybe_handle_paylands_error' );
function maybe_handle_paylands_error() {
  if (Paylands_Helpers::is_checkout_block_used()) return;
  if ( is_checkout() && isset( $_GET['paylands_error'] ) ) {
      $error_message = Paylands_Gateway_Settings::get_error_message_static();
      wc_add_notice( $error_message, 'error' );
      // Quitar el parámetro de la URL.
      $url = remove_query_arg( 'paylands_error' );
      wp_safe_redirect( $url );
      exit;
  }
}