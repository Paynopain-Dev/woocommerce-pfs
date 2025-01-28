<?php
if ( !class_exists('Paylands_Gateway_GATEWAYNAME') ) {
class Paylands_Gateway_GATEWAYNAME extends Paylands_WC_Gateway {
	public function __construct() {
		// Define the gateway stuffs.
		$this->id 					= 'paylands_woocommerce_gateway_GATEWAYNAME';
		$this->method_title 		= 'GATEWAYTITLE (Paylands)';
		$this->method_description 	= __( 'Payment Gateway by Paylands - GATEWAYTYPE', 'paylands-woocommerce' );
		$this->icon 		= 'GATEWAYICON';
		
		parent::init();
	}
}
}
?>