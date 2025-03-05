<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class that will be inherited by all payment methods.
 *
 * @extends WC_Payment_Gateway_CC
 *
 * @since 1.0.0
 */
abstract class Paylands_WC_Gateway extends WC_Payment_Gateway {

	use Paylands_Helpers;

	/**
	 * Payland Gateway Constructor (needs to be called from child's constructor)
	 */
	public function init() {

		//Paylands_Logger::dev_debug_log('abstract_gateway_init '.$this->id);
		// Define the gateway stuffs.
		$this->has_fields 			= false;
		$this->support 				= array(
			'products',
			'add_payment_method'
		);

		$this->init_form_fields();
		$this->init_settings();

		//TODO syl probar
		$main_settings = new Paylands_Gateway_Settings();
		$this->testmode = $main_settings->is_test_mode_active();
		$this->api_key = $main_settings->get_api_key();
		$this->signature = $main_settings->get_signature_key();
		$this->checkout_uuid = $main_settings->get_checkout_uuid();

		// Load Setting Values
		$this->title 			= $this->get_option( 'title' );
		$this->description 		= $this->get_option( 'description' );
		$this->service 			= $this->get_option( 'uuid_service_key' );
		$this->secure_payment 	= 'yes' === $this->get_option( 'pnp_secure_payments' ); //TODO syl probar
		$this->order_status		= $this->get_option( 'order_status' );
		$this->image		= $this->get_option( 'image' );

		//reemplazamos el logo si lo han personalizado
		if (!empty($this->image)) $this->icon = $this->image;
		
		// Save hook for settings
		add_action('woocommerce_update_options_payment_gateways_' . $this->id,	array( $this, 'process_admin_options' ));

		// Enqueue for admin and theme.
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		//add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'woocommerce_payment_complete', array( $this, 'change_order_status_after_payment' ) );
	}

	public function init_form_fields() {
		$custom_attributes = array('readonly' =>'readonly');

		$upload_media_url = admin_url('media-new.php');
		$image_help_desc = __( 'URL of the image that the customer will see when selecting the payment method on the checkout page (optional). If not specified, the default image will appear.', 'paylands-woocommerce' );
		$image_help_desc .= '<p class="upload-help-links"><span><a href="' . esc_url( $upload_media_url ) . '" target="_blank">' . __('Upload new image', 'paylands-woocommerce') . '</a></span>';
		$media_library_url = admin_url('upload.php');
		$image_help_desc .= '<span><a href="' . esc_url( $media_library_url ) . '" target="_blank">' . __('Open Media Library', 'paylands-woocommerce') . '</a></span></p>';


		$fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'paylands-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Activate', 'paylands-woocommerce' ).' '.$this->method_title,
				'description' => __( 'If the method is marked as active, it will appear as a payment option on the checkout page.', 'paylands-woocommerce' ),
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'paylands-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The title the customer will see when selecting the payment method on the checkout page.', 'paylands-woocommerce' ),
				'default'     => $this->method_title,
			),
			'description' => array(
				'title'       => __( 'Description', 'paylands-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'The description that the customer will see when selecting the payment method on the checkout page (optional).', 'paylands-woocommerce' ),
				'default'     => $this->method_description,
			),
			'image' => array(
				'title'       => __( 'Logo', 'paylands-woocommerce' ),
				'type'        => 'url',
				'description' => $image_help_desc,
				'default'     => '',
				'placeholder' => 'https://',
			),
			'order_status' => array(
				'title'       => __( 'Final order status', 'paylands-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Set the status in which the order should be when the customer makes a successful payment using this method.', 'paylands-woocommerce' ),
				'default'     => 'default',
				'options'     => array_merge(array(
					'default'   => __( 'Default order status', 'paylands-woocommerce' )
				), wc_get_order_statuses() ),
			),
			'pnp_secure_payments' => array(
				'title'       => __( 'Secure Payment', 'paylands-woocommerce' ),
				'label'       => __( 'Enable Secure Payment', 'paylands-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this option if the payment will require 3D Secure', 'paylands-woocommerce' ),
				'default'     => 'yes',
			),
			'uuid_service_key' => array(
				'title'       => __( 'Service UUID', 'paylands-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your service UUID for payments.', 'paylands-woocommerce' ),
				'default'     => '',
				'custom_attributes' => $custom_attributes,
				'placeholder' => ''
			)
		);

		$this->form_fields = apply_filters( 'paylands_gateway_fields_'.$this->id, $fields );
	}

	/**
	 * Payment Form shown on checkout page.
	 */
	public function payment_fields() {

		$description = $this->description;
		require dirname( __FILE__ ) . '/views/paylands-form.php';
	}

	/**
	 * Validate fields method
	 * @return [type] [description]
	 */
	public function validate_fields() {
		return true;
	}

	public function process_payment($order_id) {
		//Paylands_Logger::dev_debug_log('process_payment');
		try {

			$this->paylands_api_loader();

			if ( $this->is_gateway_ready ) {
				// Retrieve the order id to process payment.
				$order 			= wc_get_order($order_id);
				$total 			= $order->get_total();
				$customer_id 	= $this->get_customer_id();
				$urls 			= $this->get_actions_url( $order->get_id() );
				$order_names	= "{$order->get_billing_first_name()} {$order->get_billing_last_name()} - {$order->get_id()}";

				try {
					Paylands_Logger::dev_debug_log('process_payment createOrder');

					Paylands_Logger::dev_debug_log('process_payment current_gateway '.json_encode($this));//, JSON_PRETTY_PRINT));
					if (empty($this)) {
						Paylands_Logger::log('error no hay pasarela seleccionada');
						// load error page
						return array(
							'result' 	=> 'failure',
							'redirect'	=> $urls['ko']
						);
					}

					/*
					Segun email de Pascual el 08/07/2024:
					ahora mismo no tenemos una forma de indicar si un método de pago es de tarjeta o no porque algunos son válidos tanto para tarjeta como sin ella.
					Lo que podemos hacer de momento es indicar en el nombre del método de pago algo tipo "[Card]". 
					Entonces, para saber si tenéis que redirigir al checkout o a /payment/process, podéis consultar si existe el string "[Card]" dentro del nombre del método de pago. 
					Aunque no es lo más correcto, ahora mismo es la única forma de indicarlo.
					*/
					$is_card = false;
					$is_bizum = false;
					if (isset($this->method_title) && !empty($this->method_title)) {
						if (str_contains($this->method_title, '[Card]')) {
							$is_card = true;
						}elseif (str_contains(strtolower($this->method_title), 'bizum')) {
							$is_bizum = true;
						}
					}
					//$is_card = true;
					

					$payland_order = $this->api->createOrder(
						$total,
						"AUTHORIZATION",
						(string)$customer_id, // String format because is neccessary
						$order_names,
						(string)$order->get_id(), // String format because is neccessary
						$this->service,
						$this->secure_payment, //TODO syl probar
						$urls['callback'],
						$order->get_checkout_order_received_url(),
						$urls['ko'],
						'',
						$is_card,
						$order
					);
				} catch (Exception $e) {
					$error = sprintf(
						__( 'Something failed creating the payland order. Error: %s', 'paylands-woocommerce'),
						$e->getMessage()
					);
					Paylands_Logger::log( $error );
					return array(
						'result' 	=> 'failure',
						'redirect'	=> $urls['ko']
					);
				}

				// validate if order was created
				if ( empty( $payland_order ) || $payland_order['code'] !== 200 ) {
					Paylands_Logger::log( $payland_order );
					// load error page
					return array(
						'result' 	=> 'failure',
						'redirect'	=> $urls['ko']
					);
				}

				$this->orders->save( $payland_order );

				if ($is_card) {
					$redirect_url = $this->api->getRedirectCheckoutUrl($payland_order["order"]["token"]);
				}elseif ($is_bizum) {
					$redirect_url = $this->api->getRedirectBizumUrl($payland_order["order"]["token"]);
				}else{
					$redirect_url = $this->api->getRedirectUrl($payland_order["order"]["token"]);
					//TODO syl añadir el código del apm a la url?
					/*"BIZUM" "IDEAL" "SOFORT" "KLARNA" "VIACASH" "COFIDIS" "PIX" "CRYPTO" "GIROPAY" "TRANSFER" "PSE" "PAYPAL" "PAYLATER" "SPEI" "MULTIBANCO" "MBWAY" "FLOA" "PAYSAFECARD" "PAGO_FACIL" "EFECTY" "BOLETO" "LOTERICA" "PAYSHOP" "PICPAY" "MACH" "KLAP" "KHIPU" "SERVIPAG"*/
				}
				
				Paylands_Logger::dev_debug_log('payment redirect_url '.$redirect_url);

				return array(
					'result' 	=> 'success',
					'redirect'	=> $redirect_url
				);
			}
		} catch ( Exception $e ) {
			return array(
				'result' 	=> 'failure',
				'redirect'	=> $urls['ko']
			);
			Paylands_Logger::log('process payment error redirect_url '.$e->getMessage());
		}
	}

	/**
	 * Scripts for admin pages
	 */
	public function admin_scripts() {
		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		wp_enqueue_script(
			'paylands_gateway_admin_scripts',
			PAYLANDS_WOOCOMMERCE_ASSETS . '/js/paylands-admin.js',
			array(), date( 'Ymdhs' ), true
		);
	}

	/**
	 * Scripts for gateway in theme
	 */
	public function payment_scripts() {
		Paylands_Logger::dev_debug_log('payment_scripts_init '.$this->method_title);
		// If Paylands is not enabled fail.
		if ( 'no' === $this->enabled ) {
			Paylands_Logger::log( 'Paylands WooCommerce gateway not enabled '.$this->method_title );
			return;
		}

		// If keys are not set bail.
		if ( ! $this->are_keys_set() ) {
			Paylands_Logger::log( 'The Paylands keys are required '.$this->method_title );
			return;
		}
	}

	/**
	 * Sets de initial config from the api results (used on onboarding)
	 */
	public function set_config($options) {
		Paylands_Logger::dev_debug_log('set_config '.json_encode($options));
		if (empty($options)) return false;
		if (isset($options['enabled'])) {
			$this->update_option( 'enabled', $options['enabled']);
		}
		if (isset($options['uuid_service_key'])) {
			$this->update_option( 'uuid_service_key', $options['uuid_service_key']);
		}
	}

	/**
	 * Checks if keys are set.
	 *
	 * @return bool
	 */
	public function are_keys_set() {
		return ! empty( $this->api_key ) && ! empty( $this->signature ) && ! empty( $this->service );
	}

	/**
	 * Update Order status after payment
	 *
	 * @param integer $order_id
	 * @since 3.5.0
	 */
	public function change_order_status_after_payment( $order_id = 0 ) {
		Paylands_Logger::dev_debug_log('change_order_status_after_payment '.$this->method_title.' - '.$this->order_status.' - '.$order_id);
		// Only if the user define another order status afte payment complete
		if ( 'default' !== $this->order_status ) {
			$order 		= wc_get_order( $order_id );
			$woo_status = explode( 'wc-', $this->order_status );
			$woo_status = count( $woo_status ) === 2 ? $woo_status[1] : $woo_status[0];

			// Update Order Status
			$order->update_status( $woo_status );
		}
	}

	public function admin_options() {
		echo '<div id="woocommerce-paylands-settings-container">';

		woocommerce_paylands_print_logo_html();

		echo '<h2>' . esc_html( $this->get_method_title() );
		if (isset($_REQUEST['source']) && $_REQUEST['source'] == 'main') {
			$back_url = Paylands_Gateway_Settings::get_main_settings_url();
			wc_back_link( __( 'Return to Paylands', 'woocommerce-gateway-stripe' ), $back_url);
		}else{
			$back_url = admin_url('admin.php?page=wc-settings&tab=checkout');
			wc_back_link( __( 'Return to payments', 'woocommerce-gateway-stripe' ), $back_url);
		}
		
		echo '</h2>';
		
		global $hide_save_button;
		$hide_save_button    = true;

		echo '<div id="woocommerce-paylands-settings-fields-container">';
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
		?>
		<p class="submit">
			<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
			<?php wp_nonce_field( 'woocommerce-settings' ); ?>
		</p>
		<?php
		echo '</div>';
		woocommerce_paylands_print_help_link();
		echo '</div>';
	}

	public static function get_gateway_default_icon($title, $type) {
		if (str_contains($title, '[Card]')) {
			$type = 'card';
		}elseif (str_contains(strtolower($title), 'bizum')) {
			$type = 'bizum';
		}else{
			$type = strtolower($type);
		}

		$icon = '';
		switch ($type) {
			case 'card':
				$icon = esc_url_raw( plugins_url( 'admin/assets/images/methods/cc.svg', PAYLANDS_PLUGIN_FILE ) );;
				break;
			case 'bizum':
				$icon = esc_url_raw( plugins_url( 'admin/assets/images/methods/bizum.png', PAYLANDS_PLUGIN_FILE ) );
				break;
			case 'nuvei':
				$icon = esc_url_raw( plugins_url( 'admin/assets/images/methods/nuvei.png', PAYLANDS_PLUGIN_FILE ) );
				break;
			case 'inespay':
				$icon = esc_url_raw( plugins_url( 'admin/assets/images/methods/inespay.png', PAYLANDS_PLUGIN_FILE ) );
				break;
			case 'credorax':
				$icon = esc_url_raw( plugins_url( 'admin/assets/images/methods/credorax.png', PAYLANDS_PLUGIN_FILE ) );
				break;
			default:
				$icon = esc_url_raw( plugins_url( 'admin/assets/images/methods/paylands-woocommerce.png', PAYLANDS_PLUGIN_FILE ) );
		}
		return $icon;
	}
}
