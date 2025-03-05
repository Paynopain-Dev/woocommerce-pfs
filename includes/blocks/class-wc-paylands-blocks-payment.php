<?php
/**
 * Paylands Blocks Payment Method
 *
 * @package WooCommerce\Paylands
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

/**
 * WC_Paylands_Blocks_Payment class.
 */
class WC_Paylands_Blocks_Payment extends AbstractPaymentMethodType {
    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name;

    protected $default_icon;

    protected $has_fields = false;


    public function __construct($name, $icon = '') {
        $this->name = $name;
        $this->default_icon = $icon;
    }

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option("woocommerce_{$this->name}_settings", array());
        //$logger = new WC_Logger();
		//$logger->add('paylands-woocommerce-logs', 'WC_Paylands_Blocks_Payment initialize '.print_r($this->settings, true));
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        $is_active =  !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
        return $is_active;
    }

    public function get_payment_method_script_handles() {
        $script_path = '/build/paylands-blocks.js';
        $script_asset_path = PAYLANDS_ROOT_PATH . 'build/paylands-blocks.asset.php';
    
        $script_asset = file_exists( $script_asset_path )
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version'      => '1.0.0',
            );
    
        $script_url = plugins_url( $script_path, dirname(plugin_dir_path(__FILE__)) );
    
        wp_register_script(
            'wc-paylands-blocks-' . $this->name,
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
    
        // Pasar SOLO la configuración de esta pasarela a JavaScript
        wp_localize_script(
            'wc-paylands-blocks-' . $this->name,
            'paylandsPaymentMethod_' . $this->name,
            $this->get_payment_method_data()
        );
    
        return array('wc-paylands-blocks-' . $this->name);
    }
    

    public function get_payment_method_data() {
        //$logger = new WC_Logger();
        //$logger->add('paylands-woocommerce-logs', 'WC_Paylands_Blocks_Payment get_payment_method_data');
        if (!empty($this->settings['image'])) {
            $icon = $this->settings['image'];
        }else{
            $icon = $this->default_icon;
        }

        $error_message = Paylands_Gateway_Settings::get_error_message_static();
        
        return array(
            'name' => $this->name,
            'title'       => $this->settings['title'] ?? __('Paylands', 'woocommerce-paylands'),
            'description' => $this->settings['description'] ?? '',
            'icon'        => $icon,
            'error_message'  => $error_message,
            'supports'    => $this->get_supported_features(),
        );
    }
    
    /**
     * Returns an array of supported features.
     *
     * @return array
     */
    /*public function get_supported_features() {
        return array(
            'products',
            'add_payment_method'
        );
    }*/
}