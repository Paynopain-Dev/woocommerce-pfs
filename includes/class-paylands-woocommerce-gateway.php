<?php
class Paylands_WC_Gateway_Generic extends Paylands_WC_Gateway {
	public function __construct() {
		// Define the gateway stuffs.
		$this->id 					= 'paylands_woocommerce_payment_gateway';
		$this->method_title 		= 'Paylands Checkout';
		$this->method_description 	= __( 'Payment Gateway by Paylands', 'paylands-woocommerce' );
		$this->icon 		= $this->get_gateway_default_icon('Paylands', 'generic');
		$this->is_checkout = true;
		
		parent::init();
	}
}
?>