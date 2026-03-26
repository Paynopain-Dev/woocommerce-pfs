<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://paynopain.com/contacto/
 * @since      1.0.0
 *
 * @package    PaynoPain_Woocommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// if uninstall not called from WordPress exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
?>
