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

    use Paylands_Helpers;

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

        // Sólo para one-click mostramos fields
        if ( 'paylands_woocommerce_one_click' === $this->name ) {
            $this->has_fields = true;
        }
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

        $data = $this->get_payment_method_data();

         // Recoge tarjetas sólo para One-Click
        if ( $this->name === 'paylands_woocommerce_one_click' ) {
            $user_id         = get_current_user_id();
            $only_successful = 'yes' === ( $this->settings['only_successful_cards'] ?? 'no' );

            try {
                $this->paylands_api_loader();
                $raw_cards = $this->api->getCustomerCards( $user_id, $only_successful );
            } catch ( Exception $e ) {
                Paylands_Logger::log( sprintf(
                    __( 'Error recuperando tarjetas One-Click: %s', 'paylands-woocommerce' ),
                    $e->getMessage()
                ) );
                $raw_cards = [];
            }

            $cards = [];
            foreach ( $raw_cards as $card ) {
                // tokenización: "card_uuid|service_uuid"
                $token          = $card['uuid'];
                $service_uuid   = $card['service_uuid'] ?? '';
                $cards[] = [
                    'value'  => esc_js( "{$token}|{$service_uuid}" ),
                    'brand'  => esc_js( strtoupper( $card['brand']  ) ),
                    'last4'  => esc_js( $card['last4']               ),
                    'expiry' => esc_js( $card['expiry']              ),
                ];
            }

            $data['cards'] = $cards;
        }
        // Pasar SOLO la configuración de esta pasarela a JavaScript
        wp_localize_script(
            'wc-paylands-blocks-' . $this->name,
            'paylandsPaymentMethod_' . $this->name,
            $data
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
     * Añade 'additional_fields' cuando sea One-Click, 
     * para que Blocks incluya tus campos extra en payment_data[].additional_fields
     */
    public function get_supported_features() {
        // Arrancamos con los del padre (normalmente: products, add_payment_method…)
        $features = parent::get_supported_features();

        if ( $this->name === 'paylands_woocommerce_one_click' ) {
            // añadimos additional_fields
            $features[] = 'additional_fields';
        }

        return $features;
    }

}