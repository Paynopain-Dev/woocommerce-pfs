<?php
/**
 * Overwrites the default payment settings sections in WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paylands_Woocommerce_Admin_Sections_Overwrite Class.
 */
class Paylands_Woocommerce_Admin_Sections_Overwrite {

	/**
	 * Paylands_Woocommerce_Admin_Sections_Overwrite constructor.
	 */
	public function __construct() {

		//añade una seccion específica para paylands
		add_filter( 'woocommerce_get_sections_checkout', [ $this, 'add_checkout_sections' ], 20 );

		//coloca la sección de paylands en primer lugar
		//add_filter( 'option_woocommerce_gateway_order', [ $this, 'set_gateway_top_of_list' ], 3 );
		//add_filter( 'default_option_woocommerce_gateway_order', [$this, 'set_gateway_top_of_list' ], 4 );

		// Before rendering the "Settings" page.
		add_action( 'woocommerce_settings_start', [ $this, 'add_overwrite_payments_tab_url_filter' ] );
		// After outputting tabs on the "Settings" page.
		add_action( 'woocommerce_settings_tabs', [ $this, 'remove_overwrite_payments_tab_url_filter' ] );

	}

	/**
	 * Adds an "all payment methods" and a "paylands" section to the gateways settings page
	 *
	 * @param array $default_sections the sections for the payment gateways tab.
	 *
	 * @return array
	 */
	public function add_checkout_sections( array $default_sections ): array {
		$sections_to_render['paylands_woocommerce_gateway'] = 'Paylands';
		if (isset($default_sections['woocommerce_payments'])) {
			$sections_to_render['woocommerce_payments'] = 'WooPayments';
		}
		$sections_to_render['']                     = __( 'All payment methods', 'paylands-woocommerce' );

		return $sections_to_render;
	}

	/**
	 * By default, new payment gateways are put at the bottom of the list on the admin "Payments" settings screen.
	 * For visibility, we want WooPayments to be at the top of the list.
	 *
	 * @param array $ordering Existing ordering of the payment gateways.
	 *
	 * @return array Modified ordering.
	 */
	public static function set_gateway_top_of_list( $ordering ) {
		//TODO syl ver si lo puedo hacer funcionar. En el array de ordering estan los id de la pasarela en mayuscula
		$loader = new Paylands_Gateway_Loader();
		$gateways_ids = $loader->get_gateways_ids();
		if (empty($gateways_ids)) return $ordering;

		$ordering = (array) $ordering;
		foreach ($gateways_ids as $id) {
			$id = strtolower($id);
			// Only tweak the ordering if the list hasn't been reordered with WooPayments in it already.
			if ( ! isset( $ordering[ $id ] ) || ! is_numeric( $ordering[ $id ] ) ) {
				$ordering[ $id ] = empty( $ordering ) ? 0 : ( min( $ordering ) - 1 );
			}
		}
		return $ordering;
	}

	/**
	 * Add the callback to overwrite the Payments tab URL to the `admin_url` filter.
	 */
	public function add_overwrite_payments_tab_url_filter() {
		add_filter( 'admin_url', [ $this, 'overwrite_payments_tab_url' ], 100, 2 );
	}

	/**
	 * Remove the callback to overwrite the Payments tab URL from the `admin_url` filter.
	 */
	public function remove_overwrite_payments_tab_url_filter() {
		remove_filter( 'admin_url', [ $this, 'overwrite_payments_tab_url' ], 100 );
	}

	/**
	 * Overwrite the Payments tab URL.
	 *
	 * @param string $url The URL to overwrite.
	 * @param string $path Path relative to the admin area URL.
	 *
	 * @return string
	 */
	public function overwrite_payments_tab_url( $url, $path ): string {
		//dirige el enlace base de los ajustes de pago a la seccion de Paylands que hemos situado en primer lugar con add_checkout_sections
		if ( 'admin.php?page=wc-settings&tab=checkout' === $path ) {
			return add_query_arg( [ 'section' => 'paylands_woocommerce_gateway' ], $url );
		}

		return $url;
	}

}
