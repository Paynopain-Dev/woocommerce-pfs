<?php
/**
 * Fired during plugin activation.
 * This class defines all code necessary to run during the plugin's activation.
 */
class Paylands_Woocommerce_Activator {

	public static function activate() {
		
		//crea la tabla en bbdd //TODO syl queremos la tabla??
		global $wpdb;
		$charset_collate 	= $wpdb->get_charset_collate();
		$order_table_name 	= $wpdb->prefix . 'paylands_orders';

		$order_table_sql	= "CREATE TABLE IF NOT EXISTS $order_table_name (
         	`id` int(11) NOT NULL AUTO_INCREMENT,
        	`customer_id` VARCHAR (100) NOT NULL,
        	`additional` VARCHAR(500),
        	`order_uuid` VARCHAR(100) NOT NULL,
        	`client_uuid` VARCHAR(100) NOT NULL,
        	`oc_order_id` INT(11) NOT NULL,
        	`refunded` VARCHAR(100),
        	`antifraud` VARCHAR(100),
        	`order_token` VARCHAR(500) NOT NULL,
        	`ip` VARCHAR(100) NOT NULL,
        	`amount` DECIMAL (10, 2) NOT NULL,
        	`currency` VARCHAR(10) NOT NULL,
        	`status` VARCHAR(11) NOT NULL DEFAULT 'SUCCESS',
        	`paid` TINYINT(1) NOT NULL,
        	`service` VARCHAR(15) NOT NULL DEFAULT 'REDSYS',
        	`safe` TINYINT(1) NOT NULL,
        	`raw_order` TEXT NOT NULL,
        	`created_at` DATETIME NOT NULL,
        	`updated_at` DATETIME NOT NULL,
          PRIMARY KEY (`id`)
        ) $charset_collate;";

        $order_table 	= $wpdb->query( $order_table_sql );
	}

}
