<?php
/**
 * This file is a class that define the conection with the Paylands Order Table
 *
 *
 * @link       http://paylands.com/contacto/
 * @since      1.0.0
 *
 * @package    Paylands_Woocommerce
 * @subpackage Paylands_Woocommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Paylands Orders Class
 */
class Paylands_Orders {

	/**
	 * The Table Name saved in the database.
	 * @var string
	 */
	private $table_name;

	/**
	 * The WordPress DB Global Handler.
	 * @var object
	 */
	private $wpdb;

	/**
	 * The Paylands Orders Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb 		= $wpdb;
		$this->table_name 	= $this->wpdb->prefix . 'paylands_orders';
	}

	public function save( $order ) {
		$order_json = json_encode($order);
		$now = date('Y-m-d H:i:s');

		$sql = sprintf(
            "INSERT INTO {$this->table_name} (customer_id, client_uuid, additional, order_uuid, oc_order_id, refunded, antifraud, order_token, ip, amount, currency, status, paid, service, safe, raw_order, created_at, updated_at)
            VALUES ('%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', %s, '%s', '%s', %d, '%s', %d, '%s', '%s', '%s')",
            $order['order']['customer'],
            $order['client']['uuid'],
            $order['order']['additional'],
            $order['order']['uuid'],
            $order['order']['additional'],
            $order['order']['refunded'],
            isset( $order['order']['antifraud'] ) ? $order['order']['antifraud'] : '',
            $order['order']['token'],
            empty($order['order']['ip']) ? '1' : $order['order']['ip'],
            $order['order']['amount'],
            $order['order']['currency'],
            $order['order']['status'],
            $order['order']['paid'],
            $order['order']['service'],
            $order['order']['safe'],
            $order_json,
            $now,
            $now
        );

        $this->wpdb->query( $sql );

	}
}
