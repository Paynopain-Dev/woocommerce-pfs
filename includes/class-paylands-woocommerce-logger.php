<?php
/**
 * The file that define the Paylands Logger
 *
 * This file contains a class that extends
 * from WC_Logger, to let extends the WooCommerce
 * Loger system, to can handle the Paylands Errors and
 * Logs.
 *
 * @link       http://paylands.com/contacto/
 * @since      1.0.0
 *
 * @package    Paylands_Woocommerce
 * @subpackage Paylands_Woocommerce/includes
 */

defined( 'ABSPATH' ) || exit;

class Paylands_Logger {

	/**
	 * The logger var
	 * @var object instance
	 */
	public static $logger;

	/**
	 * The Log file name
	 */
	const LOG_FILENAME = 'paylands-woocommerce-logs';

	/**
	 * Utilize WC logger class
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public static function log( $message, $start_time = null, $end_time = null ) {
		if ( ! class_exists( 'WC_Logger' ) ) {
			return;
		}

		if (self::is_logs_active()) {

			if ( empty( self::$logger ) ) {
				self::$logger = new WC_Logger();
			}

			if (empty($start_time)) $start_time = current_time('timestamp');

			if ( ! is_null( $start_time ) ) {

				$formatted_start_time = date_i18n( get_option( 'date_format' ) . ' g:ia', $start_time );
				$end_time             = is_null( $end_time ) ? current_time( 'timestamp' ) : $end_time;
				$formatted_end_time   = date_i18n( get_option( 'date_format' ) . ' g:ia', $end_time );
				$elapsed_time         = round( abs( $end_time - $start_time ) / 60, 2 );

				$log_entry = $message . " | Time: " . $formatted_start_time . " (" . $elapsed_time . ")";

			} else {
				$log_entry = $message;

			}

			self::$logger->add( self::LOG_FILENAME, $log_entry );
		}
	}

	public static function dev_debug_log( $message, $start_time = null, $end_time = null ) {
		if (self::is_logs_active()) {
			if ( ! class_exists( 'WC_Logger' ) ) {
				return;
			}
	
			if ( empty( self::$logger ) ) {
				self::$logger = new WC_Logger();
			}
	
			if (empty($start_time)) $start_time = current_time('timestamp');
	
			if ( ! is_null( $start_time ) ) {
	
				$formatted_start_time = date_i18n( get_option( 'date_format' ) . ' g:ia', $start_time );
				$end_time             = is_null( $end_time ) ? current_time( 'timestamp' ) : $end_time;
				$formatted_end_time   = date_i18n( get_option( 'date_format' ) . ' g:ia', $end_time );
				$elapsed_time         = round( abs( $end_time - $start_time ) / 60, 2 );
	
				/*$log_entry  = "\n" . '====Paylands Version: ' . PAYLANDS_WOOCOMMERCE_VERSION . '====' . "\n";
				$log_entry .= '====Start DEV Log ' . $formatted_start_time . '====' . "\n" . $message . "\n";
				$log_entry .= '====End DEV Log ' . $formatted_end_time . ' (' . $elapsed_time . ')====' . "\n\n";*/

				//$log_entry = $message .' '.$_SERVER['REQUEST_URI']. "\n";
				$log_entry = $message . "\n";
	
			} else {
				$log_entry  = "\n" . '====Paylands Version: ' . PAYLANDS_WOOCOMMERCE_VERSION . '====' . "\n";
				$log_entry .= '====Start DEV Log====' . "\n" . $message . "\n" . '====End Log====' . "\n\n";
	
			}
	
			self::$logger->add( self::LOG_FILENAME, $log_entry );
		}
	}

	public static function is_logs_active() {
		if (woocommerce_paylands_is_dev_mode()) {
			return true;
		}
		return Paylands_Gateway_Settings::is_debug_log_active_static();
	}
}
