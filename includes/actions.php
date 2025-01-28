<?php
add_action('init', function () {
  add_rewrite_endpoint('paylands-index', EP_ROOT);
  add_rewrite_endpoint('paylands-ko', EP_ROOT);

  if (!get_option( 'paylands_gateway_routes_flushed' ) ) {
    flush_rewrite_rules();
    update_option( 'paylands_gateway_routes_flushed', date("Y-m-d H:i:s"));
  }
});

add_action( 'rest_api_init', function () {
  register_rest_route( 'paylands-woocommerce/v1', '/callback', array(
    'methods' => 'POST',
    'callback' => 'process_paylands_callback',
  ) );

} );

function process_paylands_callback( WP_REST_Request $request ) {
  Paylands_Logger::dev_debug_log('process_paylands_callback');
	$controller = new Paylands_Controller();
	return $controller->paylands_callback( $request );
}

// Manejar paylands_index
add_action('template_redirect', function () {
  global $wp_query;

  if (isset($wp_query->query_vars['paylands-index'])) {
      $controller = new Paylands_Controller();
      $controller->paylands_index();
      exit;
  }

  if (isset($wp_query->query_vars['paylands-ko'])) {
      $controller = new Paylands_Controller();
      $controller->paylands_ko();
      exit;
  }
});

