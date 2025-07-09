<?php

/**
 * Define the Paylands Gateway Main Settings
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Paylands_Gateway_Settings {

	private static $settings_url_params = [
		'page'    => 'wc-settings',
		'tab'     => 'checkout',
		'section' => 'paylands_woocommerce_gateway' 
	];

	public static $main_settings_fields_ids = array('woocommerce_paylands_settings_form_lang',
													 'woocommerce_paylands_settings_test_mode',
													 'woocommerce_paylands_settings_error_message',
													 'woocommerce_paylands_settings_api_key_test',
													 'woocommerce_paylands_settings_signature_key_test',
													 'woocommerce_paylands_settings_checkout_uuid_test',
													 'woocommerce_paylands_settings_api_key_pro',
													 'woocommerce_paylands_settings_signature_key_pro',
													 'woocommerce_paylands_settings_checkout_uuid_pro',
													 'woocommerce_paylands_settings_debug_logs');

	private $main_settings;

	public function __construct() {
		$this->load_main_settings();
	}

	protected function load_main_settings() {
		$this->main_settings = array();
		//carga las opciones de la bbdd
		foreach (self::$main_settings_fields_ids as $field_id) {
			$option_value = get_option($field_id);
			$option_name = str_replace('woocommerce_paylands_settings_', '', $field_id);
			$this->main_settings[$option_name] = $option_value;
		}
	}

	/*public static function is_paylands_settings_section() {
		if (isset($_GET['section']) && $_GET['section'] == 'paylands_woocommerce_gateway') {
			return true;
		}
		return false;
	}*/

	public static function is_paylands_gateway_settings_section() {
		if (isset($_GET['section']) && str_starts_with($_GET['section'], 'paylands_woocommerce_gateway_')) {
			return true;
		}
		return false;
	}

	public static function is_paylands_gateway_saving_settings() {
		if (isset($_POST['woocommerce_paylands_settings_form_lang'])) {
			return true;
		}
		return false;
	}

	/**
	 * Returns the URL of the configuration screen for paylands, for use in internal links.
	 */
	public static function get_main_settings_url() {
		return admin_url( add_query_arg( self::$settings_url_params, 'admin.php' ) ); 
	}

	public static function get_gateway_settings_url($gateway_id, $source='') {
		if (empty($gateway_id)) return false;
		$params = self::$settings_url_params;
		$params['section'] = $gateway_id;
		$params['source'] = $source;
		return admin_url( add_query_arg($params , 'admin.php' ) ); 
	}

	public static function is_paylands_main_settings_section() {
		if( isset($_GET[ 'section' ]) && $_GET[ 'section' ] == 'paylands_woocommerce_gateway') {
			return true;
		}
		return false;
	}

	//creamos una funcion estatica para consultar el modo pruebas de la bbdd y no tener que crear un objeto de la clase solo para esto
	public static function is_test_mode_active_static() {
		$mode = get_option('woocommerce_paylands_settings_test_mode');
		if (!empty($mode) && $mode == 'yes') return true;
		return false;
	}

	public static function is_checkout_uuid_static($mode='') {
		//Paylands_Logger::dev_debug_log('is_checkout_uuid_static '.$mode);
		if (empty($mode)) {
			if (self::is_test_mode_active_static()) {
				$mode = 'test';
			} else {
				$mode = 'pro';
			}
		}
		$checkout_uuid = get_option('woocommerce_paylands_settings_checkout_uuid_'.$mode);
		//Paylands_Logger::dev_debug_log('is_checkout_uuid_static checkout_uuid '.$checkout_uuid);
		if (!empty($checkout_uuid)) return true;
		return false;
	}

	public function is_test_mode_active() {
		if (!empty($this->main_settings)) {
			$mode = $this->main_settings['test_mode'];
			if ($mode == 'yes') return true;
		}
		return false;
	}

	public static function is_debug_log_active_static() {
		$mode = get_option('woocommerce_paylands_settings_debug_logs');
		if (!empty($mode) && $mode == 'yes') return true;
		return false;
	}

	public static function get_error_message_static() {
		$text = get_option('woocommerce_paylands_settings_error_message');
		if (empty($text)) $text = __( "There was an error processing the payment", 'paylands-woocommerce' );
		return $text;
	}

	public function get_form_lang() {
		if (!empty($this->main_settings) && !empty($this->main_settings['form_lang'])) {
			return $this->main_settings['form_lang'];
		}
		return 'es'; //por defecto castellano
	}

	public function get_api_key($mode='') {
		Paylands_Logger::dev_debug_log('get_api_key '.$mode);
		Paylands_Logger::dev_debug_log('get_api_key main_settings'.json_encode($this->main_settings));
		if (!empty($this->main_settings)) {
			if (empty($mode)) {
				if ($this->is_test_mode_active() && isset($this->main_settings['api_key_test']) && !empty($this->main_settings['api_key_test'])) {
					return $this->main_settings['api_key_test'];
				}elseif (!$this->is_test_mode_active() && isset($this->main_settings['api_key_pro']) && !empty($this->main_settings['api_key_pro'])) {
					return $this->main_settings['api_key_pro'];
				}
			}elseif ($mode == 'test' && isset($this->main_settings['api_key_test']) && !empty($this->main_settings['api_key_test'])) {
				return $this->main_settings['api_key_test'];
			}elseif ($mode == 'pro' && isset($this->main_settings['api_key_pro']) && !empty($this->main_settings['api_key_pro'])) {
				return $this->main_settings['api_key_pro'];
			}
		}else{
			Paylands_Logger::dev_debug_log('get_api_key empty main_settings');
		}
		return false;
	}

	public function get_checkout_uuid($mode='') {
		if (!empty($this->main_settings)) {
			if (empty($mode)) {
				if ($this->is_test_mode_active() && isset($this->main_settings['checkout_uuid_test'])) {
					return $this->main_settings['checkout_uuid_test'];
				}elseif (!$this->is_test_mode_active() && isset($this->main_settings['checkout_uuid_pro'])) {
					return $this->main_settings['checkout_uuid_pro'];
				}
			}elseif ($mode == 'test' && isset($this->main_settings['checkout_uuid_test'])) {
				return $this->main_settings['checkout_uuid_test'];
			}elseif ($mode == 'pro' && isset($this->main_settings['checkout_uuid_pro'])) {
				return $this->main_settings['checkout_uuid_pro'];
			}
		}
		return false;
	}

	public function get_signature_key($mode='') {
		if (!empty($this->main_settings)) {
			if (empty($mode)) {
				if ($this->is_test_mode_active() && isset($this->main_settings['signature_key_test'])) {
					return $this->main_settings['signature_key_test'];
				}elseif (!$this->is_test_mode_active() && isset($this->main_settings['signature_key_pro'])) {
					return $this->main_settings['signature_key_pro'];
				}
			}elseif ($mode == 'test' && isset($this->main_settings['signature_key_test'])) {
				return $this->main_settings['signature_key_test'];
			}elseif ($mode == 'pro' && isset($this->main_settings['signature_key_pro'])) {
				return $this->main_settings['signature_key_pro'];
			}
		}
		return false;
	}

	public function are_keys_set($mode='') {
		Paylands_Logger::dev_debug_log('are_keys_set');
		$api_key = $this->get_api_key($mode);
		$signature_key = $this->get_signature_key($mode);
		return (!empty($api_key) && !empty($signature_key));
	}

	public function admin_main_settings_content() { 
		if (!$this->is_paylands_main_settings_section()) {
			return;
		}
		?>
		<div id="wc-paylands-get-started-container">
			<div id="wc-paylands-get-started-body">
				<?php 
				woocommerce_paylands_print_logo_html();
				
				//contenido de servicios de pago disponibles
				$this->admin_services_settings_content();

				//contenido de advanced settings
				global $hide_save_button;
				$hide_save_button    = true;
				$settings = $this->admin_main_settings_fields(array(), '');
				WC_Admin_Settings::output_fields( $settings );
				?>
				<p class="submit">
					<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
					<?php wp_nonce_field( 'woocommerce-settings' ); ?>
				</p>
				<?php woocommerce_paylands_print_help_link(); ?>
			</div>
		</div>
		<?php
	}

	private function admin_services_settings_content() { 
		Paylands_Logger::dev_debug_log('admin_services_settings_content');
		//recupera el listado de servicios disponibles de Paylands
		$loader = new Paylands_Gateway_Loader();

		$services = $loader->get_gateways_list('');
		$test_services = $services['test'];
		$pro_services = $services['pro'];

		$apple_pay = $loader->get_apple_pay();
		$google_pay = $loader->get_google_pay();
		$test_apple_pay_enabled = false;
		$test_google_pay_enabled = false;
		$pro_apple_pay_enabled = false;
		$pro_google_pay_enabled = false;
		if (isset($apple_pay['test'])) {
			$test_apple_pay_enabled = $apple_pay['test'];
		}
		if (isset($google_pay['test'])) {
			$test_google_pay_enabled = $google_pay['test'];
		}
		if (isset($apple_pay['pro'])) {
			$pro_apple_pay_enabled = $apple_pay['pro'];
		}
		if (isset($google_pay['pro'])) {
			$pro_google_pay_enabled = $google_pay['pro'];
		}

		$is_test_mode = $this->is_test_mode_active();

		$have_keys = $this->are_keys_set(); //TODO syl ver para ambos entornos
		//echo "****** despues ******";
		//echo "<pre>"; print_r($services); echo "</pre>";
		?>
		<div id="paylands_services_list_page">
			<div class="paylands-cols">
				<div class="paylands-col-text">
					<h2><?php _e( 'Payment methods', 'paylands-woocommerce' );?></h2>
					<div class="description">
						<?php if (empty($services)) { ?>
							<p><?php _e( 'No active payment methods found.', 'paylands-woocommerce' );?></p>
							<?php if (!$have_keys) { 
								$onboarding_url = admin_url('admin.php?page=wc-paylands');
								?>
								<p><?php echo sprintf(__( '<a href="%s">Access</a> to connect your Paylands account and view available payment methods.', 'paylands-woocommerce' ), $onboarding_url);?></p>
								<?php /*<p><?php _e( 'You can manually enter your keys below.', 'paylands-woocommerce' );?></p> */ ?>
							<?php } ?>
						<?php }else{ ?>	
							<p><?php _e( 'These are the payment methods available for your store.', 'paylands-woocommerce' );?></p>
							<p><?php _e( 'To receive payments from a service in your store, you must activate it using the Configure button.', 'paylands-woocommerce' );?></p>
							<p><?php _e( 'Active methods are the ones that will appear as available in the store checkout.', 'paylands-woocommerce' );?></p>
						<?php } ?>	
					</div>
				</div>
				<div class="paylands-col-image">
					<img class="wc-paylands-section-image" src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/metodos-pago-paylands-1.png', PAYLANDS_PLUGIN_FILE ) ); ?>" alt=""/>
				</div>
			</div>

			<div class="paylands-tabs">
				<div class="paylands-tab-link <?php if ($is_test_mode) {?>active<?php } ?>" data-tab="test"><?php _e( 'Payment methods for test payments', 'paylands-woocommerce' );?> <?php if ($is_test_mode) {?><span class="wc-paylands-service-status enabled">Activo</span><?php } ?></div>
				<div class="paylands-tab-link <?php if (!$is_test_mode) {?>active<?php } ?>" data-tab="production"><?php _e( 'Payment methods for real payments', 'paylands-woocommerce' );?> <?php if (!$is_test_mode) {?><span class="wc-paylands-service-status enabled">Activo</span><?php } ?></div>
			</div>

			<div class="paylands-tab-content-container">
			<div id="test" class="paylands-tab-content <?php if ($is_test_mode) {?>active<?php } ?>">
				<?php $this->print_services($test_services, $test_apple_pay_enabled, $test_google_pay_enabled, 'test', $is_test_mode);?>
			</div>
			<div id="production" class="paylands-tab-content <?php if (!$is_test_mode) {?>active<?php } ?>">
				<?php $this->print_services($pro_services, $pro_apple_pay_enabled, $pro_google_pay_enabled, 'pro', !$is_test_mode);?>
			</div>

			</div>
			<?php
			if ($have_keys) {
				?>
				<div id="paylands-new-service-container">
					<p><?php _e( 'If you need it, you can <a href="#" id="paylands-new-service-open">request a new payment method</a>', 'paylands-woocommerce' );?></p>
					<div id="paylands-new-service">
						<h2><?php _e( 'Request a new payment method', 'paylands-woocommerce' );?></h2>
						<p><?php _e( 'Enter the name and type of payment method you want to request from the available options.', 'paylands-woocommerce' );?></p>
						<table class="form-table">
							<tbody>
							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="service-name"><?php _e( 'Payment method name', 'paylands-woocommerce');?></label>
								</th>
								<td class="forminp forminp forminp-text">
									<input type="text" name="service-name" id="service-name" value=""/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="service-type"><?php _e( 'Payment method type', 'paylands-woocommerce');?></label>
								</th>
								<td class="forminp forminp-select">
									<select name="service-type" id="service-type">
										<option value="Tarjeta_Paynopain"><?php _e( 'Card by Paynopain - Europe', 'paylands-woocommerce');?></option>
										<option value="Bizum"><?php _e( 'Bizum - Europe', 'paylands-woocommerce');?></option>
										<option value="GooglePay"><?php _e( 'GooglePay', 'paylands-woocommerce');?></option>
										<option value="Applepay"><?php _e( 'Applepay', 'paylands-woocommerce');?></option>
										<option value="Ideal"><?php _e( 'Ideal payments - Netherlands', 'paylands-woocommerce');?></option>
										<option value="Sofort"><?php _e( 'Sofort payments - Europe', 'paylands-woocommerce');?></option>
										<option value="Klarna"><?php _e( 'Klarna payments - Europe', 'paylands-woocommerce');?></option>
										<option value="Giropay"><?php _e( 'Giropay payments - Germany', 'paylands-woocommerce');?></option>
										<option value="Bank_transfer"><?php _e( 'Bank transfer - Europe', 'paylands-woocommerce');?></option>
										<option value="COFIDIS"><?php _e( 'COFIDIS - Spain', 'paylands-woocommerce');?></option>
										<option value="FLOA"><?php _e( 'FLOA - Europe', 'paylands-woocommerce');?></option>
										<option value="Cryptocurrency"><?php _e( 'Cryptocurrency payments', 'paylands-woocommerce');?></option>
										<option value="PSE"><?php _e( 'PSE payments - Colombia', 'paylands-woocommerce');?></option>
										<option value="PayPal"><?php _e( 'PayPal payments', 'paylands-woocommerce');?></option>
									</select>
								</td>
							</tr>
							</tbody>
						</table>		
						<p>
							<a href="" id="paylands-new-service-send" class="wc-paylands-button wc-paylands-button-secondary"><?php _e( 'Request', 'paylands-woocommerce' );?></a>
							<a href="" id="paylands-new-service-close"><?php _e( 'No, thanks', 'paylands-woocommerce' );?></a>
						</p>
						<div id="wc-paylands-service-form-message" style="display: none;"></div>
					</div>
				</div>
			<?php } ?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				// Manejo de pestañas
				jQuery('.paylands-tab-link').on('click', function() {
					var tab_id = jQuery(this).data('tab');

					jQuery('.paylands-tab-link').removeClass('active');
					jQuery('.paylands-tab-content').removeClass('active');

					jQuery(this).addClass('active');
					jQuery('#' + tab_id).addClass('active');
				});

				//solicitud de nuevos servicios
				jQuery('#paylands-new-service-open').on('click', function(e) {
					e.preventDefault();
					jQuery('#paylands-new-service').toggle('slide');
				});
				jQuery('#paylands-new-service-close').on('click', function(e) {
					e.preventDefault();
					jQuery('#paylands-new-service').hide('slide');
				});
				jQuery('#paylands-new-service-send').on('click', function(e) {
					e.preventDefault();
					submitNewServiceForm();
				});

				//ajustes avanzados
				jQuery('.collapsible-link').on('click', function(e) {
					e.preventDefault();
					jQuery('.collapsible-content').toggle('fade');
				});
			});

			function startLoadingNewService() {
				jQuery('#paylands-new-service-send').addClass('wc-paylands-loading-button');
			}

			function stopLoadingNewService() {
				jQuery('#paylands-new-service-send').removeClass('wc-paylands-loading-button');
			}

			function submitNewServiceForm() {
				startLoadingNewService();
				var name = jQuery('#service-name').val();
				var type = jQuery('#service-type').val();

				jQuery.ajax({
					url: '<?php echo admin_url("admin-ajax.php"); ?>', // La URL de admin-ajax.php
					type: 'POST',
					data: {
						action: 'paylands_send_new_service', // La acción de WordPress para manejar la solicitud AJAX
						paylands_service_name: name, 
						paylands_service_type: type
					},
					dataType: 'json',
					success: function(data){
						console.log('Send new service');
						console.log(data);
						if (data.success) {
							jQuery('#wc-paylands-service-form-message').html('<?php _e( 'Request sent', 'paylands-woocommerce');?>').show();
							jQuery('#paylands-new-service-close').text('<?php _e( 'Close', 'paylands-woocommerce');?>')
						}else{
							jQuery('#wc-paylands-service-form-message').html('<?php _e( 'Failed to send request', 'paylands-woocommerce');?>').show();
						}
						stopLoadingNewService();
					},
					error: function(data) {
						jQuery('#wc-paylands-service-form-message').html('<?php _e( 'An error has occurred, please try again in a few minutes. If the error persists, please contact Paylands.', 'paylands-woocommerce');?>').show();
						console.log(data);
						stopLoadingNewService();
					}
				});
				}
			</script>
			

		</div>

		<?php
	}

	private function print_services($services, $apple_pay_enabled, $google_pay_enabled, $mode, $mode_active=true) {
		if (!empty($services)) { 
			if (!empty($this->get_checkout_uuid($mode))) {
				//si tiene checkout uuid el metodo de pago se seleccionara en el checkout de paylands
				//mostramos un metodo de pago generico
				$this->print_services_in_checkout($services, $apple_pay_enabled, $google_pay_enabled, $mode, $mode_active);
			} else {
				//el metodo de pago se seleccionara en el checkout de WC
				//mostramos los metodos disponibles
				$this->print_services_no_checkout($services, $apple_pay_enabled, $google_pay_enabled, $mode, $mode_active);
			}
		}else{
			echo '<p>'.__( 'No payment methods available', 'paylands-woocommerce').'</p>';
		}
	}

	private function print_services_in_checkout($services, $apple_pay_enabled, $google_pay_enabled, $mode, $mode_active=true) {
		if (!empty($services)) { 
			//listado de pasarelas declaradas en woocommerce
			$wc_gateways = WC()->payment_gateways()->payment_gateways();
			//comprueba que exista la pasarela de paylands
			$gateway_id = 'paylands_woocommerce_payment_gateway';
			if ( isset( $wc_gateways[$gateway_id] ) ) {
				$paylands_gateway = $wc_gateways[$gateway_id];
				$is_available = $paylands_gateway->is_available();
				$settings = $paylands_gateway->settings;
				if (!empty($settings['image'])) {
					$image = $settings['image'];
				}else{
					$image = $paylands_gateway->icon;
				}
			}
			?>
			<ul id="wc-paylands-service-list">
				<?php $this->print_one_click_payment_service($wc_gateways, $mode_active); ?>
				<li class="wc-paylands-service <?php if (!$is_available) { echo "disabled"; } ?>">
					<div class="wc-paylands-service-image">
						<img src="<?php echo $image; ?>" alt=""/>
					</div>
					<div class="wc-paylands-service-content">
						<h3 class="wc-paylands-service-name"><?php echo $paylands_gateway->method_title;?> <?php if ($is_available) {?><span class="wc-paylands-service-status enabled"><?php _e( 'Active', 'paylands-woocommerce');?></span><?php } ?></h3>
						<p class="wc-paylands-service-title">
							<?php echo $settings['title'];?><br>	
						</p>
						<p class="wc-paylands-service-desc">
							<?php if ($settings['description']) { echo $settings['description'];?><br><?php }?>
						</p>
					</div>
					<div class="wc-paylands-service-action">
						<?php if ($mode_active) { //Si esta en este modo (test/pro) y el servicio esta activo en paylands
							$button_text = __( 'Configure', 'paylands-woocommerce');
							?> 
							<a href="<?php echo $this->get_gateway_settings_url($gateway_id, 'main');?>" class="wc-paylands-button wc-paylands-button-secondary"><?php echo $button_text;?></a>
						<?php } ?>
					</div>
				</li>
			</ul>

			<span class="wc-paylands-service-title"><?php _e( 'Payment methods included in Paylands Checkout', 'paylands-woocommerce');?></span>
			<ul id="wc-paylands-extra-service-list">
			<?php foreach ($services as $gateway) { 
				//echo "**service -> <pre>"; print_r($gateway); echo "</pre>";
				$gateway_id = Paylands_Gateway_Loader::get_gateway_id($gateway);
				$is_available = $gateway['enabled'];
				$image = Paylands_WC_Gateway::get_gateway_default_icon($gateway['name'], $gateway['type']);
				?>
				<li class="wc-paylands-service <?php if (!$is_available) { echo "disabled"; } ?>">
					<div class="wc-paylands-service-image">
						<img src="<?php echo $image; ?>" alt=""/>
					</div>
					<div class="wc-paylands-service-content">
						<h3 class="wc-paylands-service-name"><?php echo $gateway['name'];?> <span class="wc-paylands-service-type"><?php echo $gateway['type'];?></span> <?php if ($is_available) {?><span class="wc-paylands-service-status enabled"><?php _e( 'Active', 'paylands-woocommerce');?></span><?php } ?></h3>
						<?php /* <p class="wc-paylands-service-title">
							<?php echo $gateway['name'];?><br>	
						</p> */ ?>
						<p class="wc-paylands-service-desc">
							<?php if ($gateway['uuid']) { echo $gateway['uuid'];?><br><?php }?>
						</p>
					</div>
					<div class="wc-paylands-service-action">
					</div>
				</li>
			<?php } ?>
			</ul> <?php 

			$this->print_google_and_apple_services($apple_pay_enabled, $google_pay_enabled, $mode);
		}
	}

	private function print_services_no_checkout($services, $apple_pay_enabled, $google_pay_enabled, $mode, $mode_active=true) {
		if (!empty($services)) { 
			//listado de pasarelas declaradas en woocommerce
			$wc_gateways = WC()->payment_gateways()->payment_gateways();
			?>
			<ul id="wc-paylands-service-list">
			<?php $this->print_one_click_payment_service($wc_gateways, $mode_active); ?>
			<?php foreach ($services as $gateway) { 
				//echo "**service -> <pre>"; print_r($gateway); echo "</pre>";
				$gateway_id = Paylands_Gateway_Loader::get_gateway_id($gateway);
				$is_available = false;
				if (isset($wc_gateways[$gateway_id])) {
					//echo "<pre>"; print_r($wc_gateways[$gateway_id]); echo "</pre>";
					$is_available = $wc_gateways[$gateway_id]->is_available();
					$settings = $wc_gateways[$gateway_id]->settings;
					if (!empty($settings['image'])) {
						$image = $settings['image'];
					}else{
						$image = $wc_gateways[$gateway_id]->icon;
					}
					//echo "<pre>settings: "; print_r($settings); echo "</pre>";
				}else if ($mode_active && $gateway['enabled']) {
					$is_available = true;
				}
				?>
				<li class="wc-paylands-service <?php if (!$is_available) { echo "disabled"; } ?>">
					<div class="wc-paylands-service-image">
						<img src="<?php echo $image; ?>" alt=""/>
					</div>
					<div class="wc-paylands-service-content">
						<h3 class="wc-paylands-service-name"><?php echo $gateway['name'];?> <span class="wc-paylands-service-type"><?php echo $gateway['type'];?></span> <?php if ($is_available) {?><span class="wc-paylands-service-status enabled"><?php _e( 'Active', 'paylands-woocommerce');?></span><?php } ?></h3>
						<p class="wc-paylands-service-title">
							<?php echo $settings['title'];?><br>	
						</p>
						<p class="wc-paylands-service-desc">
							<?php if ($settings['uuid_service_key']) { echo $settings['uuid_service_key'];?><br><?php }?>
						</p>
					</div>
					<div class="wc-paylands-service-action">
						<?php if ($mode_active && $gateway['enabled']) { //Si esta en este modo (test/pro) y el servicio esta activo en paylands
							$button_text = __( 'Configure', 'paylands-woocommerce');
							?> 
							<a href="<?php echo $this->get_gateway_settings_url($gateway_id, 'main');?>" class="wc-paylands-button wc-paylands-button-secondary"><?php echo $button_text;?></a>
						<?php } ?>
					</div>
				</li>

			<?php } ?>
			</ul> <?php 

			$this->print_google_and_apple_services($apple_pay_enabled, $google_pay_enabled, $mode);
		}
	}

	private function print_one_click_payment_service($wc_gateways, $mode_active) {
		//comprueba que exista la pasarela de paylands
		$gateway_id = 'paylands_woocommerce_one_click';
		if ( isset( $wc_gateways[$gateway_id] ) ) {
			$paylands_gateway = $wc_gateways[$gateway_id];
			$is_available = $paylands_gateway->is_available();
			$settings = $paylands_gateway->settings;
			if (!empty($settings['image'])) {
				$image = $settings['image'];
			}else{
				$image = $paylands_gateway->icon;
			}
			?>
			<li class="wc-paylands-service <?php if (!$is_available) { echo "disabled"; } ?>">
				<div class="wc-paylands-service-image">
					<img src="<?php echo $image; ?>" alt=""/>
				</div>
				<div class="wc-paylands-service-content">
					<h3 class="wc-paylands-service-name"><?php echo $paylands_gateway->method_title;?> <?php if ($is_available) {?><span class="wc-paylands-service-status enabled"><?php _e( 'Active', 'paylands-woocommerce');?></span><?php } ?></h3>
					<p class="wc-paylands-service-title">
						<?php echo $settings['title'];?><br>	
					</p>
					<p class="wc-paylands-service-desc">
						<?php if ($settings['description']) { echo $settings['description'];?><br><?php }?>
					</p>
				</div>
				<div class="wc-paylands-service-action">
					<?php if ($mode_active) { //Si esta en este modo (test/pro) y el servicio esta activo en paylands
						$button_text = __( 'Configure', 'paylands-woocommerce');
						?> 
						<a href="<?php echo $this->get_gateway_settings_url($gateway_id, 'main');?>" class="wc-paylands-button wc-paylands-button-secondary"><?php echo $button_text;?></a>
					<?php } ?>
				</div>
			</li>
			<?
		}
	}

	private function print_google_and_apple_services($apple_pay_enabled, $google_pay_enabled, $mode) {
		if ($apple_pay_enabled || $google_pay_enabled) { ?>
			<span class="wc-paylands-service-title"><?php _e( 'Other payment methods', 'paylands-woocommerce');?></span>
			<ul id="wc-paylands-extra-service-list">
				<?php 
				if ($mode == 'test') {
					$desc_text_disabled = __('This payment method is not available for test payments. You can request it using the link below.', 'paylands-woocommerce' );
					$desc_text_enabled = __('This method is available for test payments', 'paylands-woocommerce' );
				}else{
					$desc_text_disabled = __('This payment method is not available for real payments. You can request it using the link below.', 'paylands-woocommerce' );
					$desc_text_enabled = __('This method is available for real payments', 'paylands-woocommerce' );
				}
				?>
				<li class="wc-paylands-service">
					<div class="wc-paylands-service-image">
						<img class="wc-paylands-extra-service-image" src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/apple_pay.svg', PAYLANDS_PLUGIN_FILE ) ); ?>" alt=""/>
					</div>
					<div class="wc-paylands-service-content">
						<h3 class="wc-paylands-service-name">Apple Pay<?php if ($apple_pay_enabled) {?><span class="wc-paylands-service-status enabled"><?php _e( 'Active', 'paylands-woocommerce' );?></span><?php } ?></h3>
						<p class="wc-paylands-service-title">
							<?php if ($apple_pay_enabled) {echo $desc_text_enabled;}else{echo $desc_text_disabled;}?><br>	
						</p>
					</div>
					<div class="wc-paylands-service-action"></div>
				</li>
				<li class="wc-paylands-service">
					<div class="wc-paylands-service-image">
						<img class="wc-paylands-extra-service-image" src="<?php echo esc_url_raw( plugins_url( 'admin/assets/images/methods/google_pay.svg', PAYLANDS_PLUGIN_FILE ) ); ?>" alt=""/>
					</div>
					<div class="wc-paylands-service-content">
						<h3 class="wc-paylands-service-name">Google Pay<?php if ($google_pay_enabled) {?><span class="wc-paylands-service-status enabled"><?php _e( 'Active', 'paylands-woocommerce' );?></span><?php } ?></h3>
						<p class="wc-paylands-service-title">
							<?php if ($google_pay_enabled) {echo $desc_text_enabled;}else{echo $desc_text_disabled;}?><br>
						</p>
					</div>
					<div class="wc-paylands-service-action"></div>
				</li>
			</ul>
		<?php 
		} 
	}

	public function admin_main_settings_fields( $settings, $current_section ) {
		// we need the fields only on our custom section
		if (!$this->is_paylands_main_settings_section()) {
			return $settings;
		}
			//acciones para añadir tipos de campo propios al formulario
			add_action( 'woocommerce_admin_field_paylands_toggle', array($this, 'custom_render_toggle_field' ));
			add_action( 'woocommerce_admin_field_collapsible_title', array($this, 'custom_render_collapsible_title_field' ));
			add_action( 'woocommerce_settings_woocommerce_paylands_settings_advanced_title_section_after', array($this, 'settings_advanced_title_after'));
			add_filter( 'woocommerce_admin_settings_sanitize_option', array($this, 'custom_value_toggle_field'), 10, 3);

			$test_cards_url = "https://docs.paylands.com/docs/category/payment-services";
			$test_mode_info = '<span class="test-mode-info-line">'.__( 'Simulate transactions using', 'paylands-woocommerce' );
			$test_mode_info .= " <a href='$test_cards_url' target='blank'>".__( 'test payment data', 'paylands-woocommerce' )."</a>.</span>";
			$test_mode_info .= '<span class="test-mode-info-line">'.__( 'If test mode is active, customers will NOT be able to make real payments through Woocommerce Paylands.', 'paylands-woocommerce' ).'</span>';
			$test_mode_info .= '<span class="test-mode-info-line">'.__( 'You must save for the change to take effect.', 'paylands-woocommerce' ).'</span>';

			$is_connected = Paylands_Woocommerce_Account_Connect::is_business_connected();
			if ($is_connected) {
				$advanced_info = __( 'Your Paylands account is connected, and the advanced configuration has been automatically filled in. If you want to view it, you can do so by clicking <span class="collapsible-link">here</span>.', 'paylands-woocommerce' );
			}else{
				$onboarding_url = admin_url('admin.php?page=wc-paylands');
				//$advanced_info = __( 'Al hacer <a href="'.$onboarding_url.'">login</a> y conectar tu cuenta de Paylands las configuración avanzada se rellena automáticamente y no deberías tocarla. Si aún así quieres verla o modificarla manualmente puedes hacerlo pulsando <span class="collapsible-link">aquí</span>.', 'paylands-woocommerce' );
				//$advanced_info = __( 'Al hacer <a href="'.$onboarding_url.'">login</a> y conectar tu cuenta de Paylands las configuración avanzada se rellena automáticamente. Si aún así prefieres introducirla manualmente puedes hacerlo pulsando <span class="collapsible-link">aquí</span>.', 'paylands-woocommerce' );
				$advanced_info = sprintf(__('When you <a href="%s">log in</a> and connect your Paylands account, the advanced settings are automatically filled in. If you want to view them, you can do so by clicking <span class="collapsible-link">here</span>.', 'paylands-woocommerce'), $onboarding_url);
			}

			$custom_attributes = array('readonly' =>'readonly');
			
			$settings = array(
				array(
					'name' => __( 'General Settings', 'paylands-woocommerce' ),
					'type' => 'title',
					'desc' => '',
				),
				array(
					'name'     => __( 'Test Mode', 'paylands-woocommerce' ),
					'desc'     => __( 'Enable test mode', 'paylands-woocommerce' ),
					'desc_tip' => $test_mode_info,
					'id'       => 'woocommerce_paylands_settings_test_mode',
					'default'  => 'yes',
					'type'     => 'paylands_toggle', //custom type 
				),
				array(
					'name'     => __( 'Payment Form Language', 'paylands-woocommerce' ),
					'desc'     => __( 'Select the language to be used in the Paylands payment form.', 'paylands-woocommerce' ),
					'id'       => 'woocommerce_paylands_settings_form_lang',
					'type'     => 'select',
					'default'  => 'es',
					'options'  => array(
						'en' => __( 'English', 'paylands-woocommerce' ),
						'es' => __( 'Spanish', 'paylands-woocommerce' )
					),
				),
				array(
					'name' => __( 'Error message', 'paylands-woocommerce' ),
					'type' => 'textarea',
					'desc' => __( 'Message displayed to the customer if the payment was not successful', 'paylands-woocommerce' ),
					'desc_at_end' => true,
					'id'       => 'woocommerce_paylands_settings_error_message',
					'default'  => __( 'There was an error processing the payment', 'paylands-woocommerce' ),
				),
				array(
					'type' => 'sectionend',
				),
				array(
					'id'   => 'woocommerce_paylands_settings_advanced_title',
					'name' => __( 'Advanced Settings', 'paylands-woocommerce' ),
					'type' => 'collapsible_title', //custom type
					'desc' => $advanced_info,
				),
				array(
					'name' => __( 'Live API Key', 'paylands-woocommerce' ),
					'type' => 'text',
					'desc' => __( 'Your User API Key for live payments.', 'paylands-woocommerce' ),
					'id'       => 'woocommerce_paylands_settings_api_key_pro',
					'custom_attributes' => $custom_attributes,
				),
				array(
					'name' => __( 'Live Signature Key', 'paylands-woocommerce' ),
					'type' => 'text',
					'desc' => __( 'Your User Signature Key for live payments.', 'paylands-woocommerce' ),
					'id'       => 'woocommerce_paylands_settings_signature_key_pro',
					'custom_attributes' => $custom_attributes,
				),
				array(
					'name' => __( 'Live Checkout UUID', 'paylands-woocommerce' ),
					'type' => 'text',
					'desc' => __( 'Your Checkout UUID for live payments.', 'paylands-woocommerce' ),
					'id'       => 'woocommerce_paylands_settings_checkout_uuid_pro',
					'custom_attributes' => $custom_attributes,
				),
				array(
					'name' => __( 'Test API Key', 'paylands-woocommerce' ),
					'type' => 'text',
					'desc' => __( 'Your User API Key for test payments.', 'paylands-woocommerce' ),
					'id'       => 'woocommerce_paylands_settings_api_key_test',
					'custom_attributes' => $custom_attributes,
				),
				array(
					'name' => __( 'Test Signature Key', 'paylands-woocommerce' ),
					'type' => 'text',
					'desc' => __( 'Your User Signature Key for test payments.', 'paylands-woocommerce' ),
					'id'       => 'woocommerce_paylands_settings_signature_key_test',
					'custom_attributes' => $custom_attributes,
				),
				array(
					'name' => __( 'Test Checkout UUID', 'paylands-woocommerce' ),
					'type' => 'text',
					'desc' => __( 'Your Checkout UUID for test payments.', 'paylands-woocommerce' ),
					'id'       => 'woocommerce_paylands_settings_checkout_uuid_test',
					'custom_attributes' => $custom_attributes,
				),
				array(
					'title'       => __( 'Debug', 'paylands-woocommerce' ),
					'label'       => __( 'Activate debug logs', 'paylands-woocommerce' ),
					'id'       	  => 'woocommerce_paylands_settings_debug_logs',
					'type'        => 'checkbox',
					'desc' => __( 'Enable this option to activate debug logs', 'paylands-woocommerce' ),
					'default'     => 'no',
				),
				array(
					'id'   => 'woocommerce_paylands_settings_advanced_title_section',
					'type' => 'sectionend',
				),
			);

		return $settings;
	}

	public function save_main_settings() {
		if ($this->is_paylands_main_settings_section()) {
			$settings = $this->admin_main_settings_fields(array(), '');
			WC_Admin_Settings::save_fields($settings);
			delete_option('paylands_gateway_routes_flushed');
			$this->load_main_settings();
		}
	}

	public function custom_render_toggle_field( $value ) {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['name'] ); ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<label class="switch">
					<input type="checkbox" id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" value="1" <?php checked($value['value'], 'yes' ); ?> />
					<span class="slider round"></span>
				</label>
				<label for="<?php echo esc_attr( $value['id'] ); ?>" class="toggle-label"><?php echo esc_html( $value['desc'] ); ?></label>
				<p class="description toogle-info"><?php echo $value['desc_tip']; ?></p>
			</td>
		</tr>
		<?php
	}

	public function custom_value_toggle_field($value, $option, $raw_value) {
		if ($option['type'] == 'paylands_toggle') {
			$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
		}
		return $value;
	}

	public function custom_render_collapsible_title_field( $value ) {
		if ( ! empty( $value['title'] ) ) {
			echo '<h2 class="collapsible-title">' . esc_html( $value['title'] ) . '</h2>';
		}

		if ( ! empty( $value['desc'] ) ) {
			echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
			echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
			echo '</div>';
		}
		
		echo '<div id="wc-paylands-advanced-settings" class="collapsible-content" style="display:none;">';
		echo '<table class="form-table">' . "\n\n";
	}

	public function settings_advanced_title_after() {
		echo '</div>';
	}

}