<?php
/**
 * Fired during plugin deactivation.
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Paylands_Woocommerce_Deactivator {

	public static function deactivate() {

		delete_option('paylands_gateway_routes_flushed');
		//TODO confirmar si se borran todos los datos al desactivar
		/*
		woocoomerce stripe lo hace con la constante
		Only remove ALL product and page data if WC_REMOVE_ALL_DATA constant is set to true in user's
 		wp-config.php. This is to prevent data loss when deleting the plugin from the backend
		and to ensure only the site owner can perform this action.
		*/

		/*global $wpdb;

		$charset_collate 	= $wpdb->get_charset_collate();
		$order_table_name 	= $wpdb->prefix . 'paylands_orders';

		$wpdb->query( "DROP TABLE $order_table_name" );
		*/
	}

}
