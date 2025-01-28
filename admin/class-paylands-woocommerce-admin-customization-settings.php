<?php

/**
 * Define the Paylands Gateway Main Settings
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Paylands_Customization_Settings {

	public static $customization_settings_fields_ids = array('woocommerce_paylands_custom_title', 
															'woocommerce_paylands_custom_logo', 
															'woocommerce_paylands_custom_background_color', 
															'woocommerce_paylands_custom_accent_color', 
															'woocommerce_paylands_custom_text_color', 
															'woocommerce_paylands_custom_font', 
															'woocommerce_paylands_custom_description', 
															'woocommerce_paylands_custom_footer_logo', 
															'woocommerce_paylands_custom_footer_terms_and_conditions');

	private $customization_settings;

	public function __construct() {
		$this->load_customization_settings();
	}

	protected function load_customization_settings() {
		$this->customization_settings = array();
		//carga las opciones de la bbdd
		foreach (self::$customization_settings_fields_ids as $field_id) {
			$option_value = get_option($field_id);
			$option_name = str_replace('woocommerce_paylands_custom_', '', $field_id);
			$this->customization_settings[$option_name] = $option_value;
		}
	}

	public function get_customization_settings() {
		if (!empty($this->customization_settings)) {
			return $this->customization_settings;
		}
		return array(); 
	}

	public function get_custom_title() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['title'])) {
			return $this->customization_settings['title'];
		}
		return false;
	}

	public function get_custom_background_color() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['background_color'])) {
			return $this->customization_settings['background_color'];
		}
		return false;
	}

	public function get_custom_font() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['font'])) {
			return $this->customization_settings['font'];
		}
		return false;
	}

	public function get_custom_description() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['description'])) {
			return $this->customization_settings['description'];
		}
		return false;
	}

	public function get_custom_accent_color() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['accent_color'])) {
			return $this->customization_settings['accent_color'];
		}
		return false;
	}

	public function get_custom_text_color() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['text_color'])) {
			return $this->customization_settings['text_color'];
		}
		return false;
	}

	public function get_custom_logo() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['logo'])) {
			return $this->customization_settings['logo'];
		}
		return false;
	}

	public function get_custom_footer_logo() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['footer_logo'])) {
			return $this->customization_settings['footer_logo'];
		}
		return false;
	}

	public function get_custom_footer_terms_and_conditions() {
		if (!empty($this->customization_settings) && !empty($this->customization_settings['footer_terms_and_conditions'])) {
			return $this->customization_settings['footer_terms_and_conditions'];
		}
		return false;
	}

	public static function is_paylands_customization_settings_section() {
		if( isset($_GET[ 'page' ]) && $_GET[ 'page' ] == 'wc-paylands-customization') {
			return true;
		}
		return false;
	}

	public function admin_customization_settings_content() { 
		if (!$this->is_paylands_customization_settings_section()) {
			return;
		}
		?>
		<div id="wc-paylands-get-started-container">
			<div id="wc-paylands-get-started-body">
				<?php 
				woocommerce_paylands_print_logo_html();
				?>
				<div id="wc-paylands-settings-form">
					<?php
					//contenido de advanced settings
					global $hide_save_button;
					$hide_save_button    = true;
					$settings = $this->admin_customization_settings_fields(array(), '');
					WC_Admin_Settings::output_fields( $settings );
					?>
				</div>
				<div id="wc-paylands-settings-form-submit">
					<p class="submit">
						<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
						<?php wp_nonce_field( 'woocommerce-settings' ); ?>
					</p>
					<?php woocommerce_paylands_print_help_link(); ?>
				</div>
			</div>
		</div>
		<script>
			//Recoloca el submit debajo del form
			jQuery(document).ready(function() {
				jQuery('#wc-paylands-settings-form').append(jQuery('#wc-paylands-settings-form-submit').html());
				jQuery('#wc-paylands-settings-form-submit').html('');
			});
		</script>
		<?php
	}


	public function admin_customization_settings_fields( $settings, $current_section ) {
		// we need the fields only on our custom section
		if (!$this->is_paylands_customization_settings_section()) {
			return $settings;
		}
		$settings = array(
			array(
				'name' => __( 'Style Customization', 'paylands-woocommerce' ),
				'type' => 'title',
				'desc' => __('Configure the visual appearance of the Paylands card payment form using the following settings. Fields that are not indicated will take the default value.', 'paylands-woocommerce'),
			),
			array(
				'title' => __('Title', 'paylands-woocommerce'),
				'type' => 'text',
				'id' => 'woocommerce_paylands_custom_title',
				//'default' => __('My Store', 'paylands-woocommerce'),
				'description' => __( 'Checkout title.', 'paylands-woocommerce' ),
			),
			array(
				'title' => __('Logo', 'paylands-woocommerce'),
				'type' => 'url',
				'id' => 'woocommerce_paylands_custom_logo',
				'description' => __( 'Checkout logo URL.', 'paylands-woocommerce' ),
				'placeholder' => 'https://',
			),
			array(
				'title' => __('Background Color', 'paylands-woocommerce'),
				'type' => 'color',
				'id' => 'woocommerce_paylands_custom_background_color',
				'description' => __( 'Checkout background color.', 'paylands-woocommerce' ),
				'css' => 'width:6em;',
				'default' => '#ffffff',
				'autoload' => false,
				'desc_tip' => true,
			),
			array(
				'title' => __('Accent Color', 'paylands-woocommerce'),
				'type' => 'color',
				'id' => 'woocommerce_paylands_custom_accent_color',
				'description' => __( 'Checkout accent color.', 'paylands-woocommerce' ),
				'default' => '#000000',
				'autoload' => false,
				'desc_tip' => true,
			),
			array(
				'title' => __('Text Color', 'paylands-woocommerce'),
				'description' => __( 'Checkout text color.', 'paylands-woocommerce' ),
				'type' => 'color',
				'id' => 'woocommerce_paylands_custom_text_color',
				'default' => '#000000',
				'autoload' => false,
				'desc_tip' => true,
			),
			array(
				'title' => __('Font', 'paylands-woocommerce'),
				'type' => 'select',
				'id' => 'woocommerce_paylands_custom_font',
				'description' => __( 'Checkout text font.', 'paylands-woocommerce' ),
				'default' => 'Sans Serif',
				'options' => array(
					'' => 'Default',
					'Sans Serif' => 'Sans Serif',
					'Arial' => 'Arial',
					'Verdana' => 'Verdana',
					'Helvetica' => 'Helvetica',
					'Tahoma' => 'Tahoma',
					'Trebuchet MS' => 'Trebuchet MS',
					'Times New Roman' => 'Times New Roman',
					'Georgia' => 'Georgia',
					'Garamond' => 'Garamond',
					'Courier New' => 'Courier New',
					'Brush Script MT' => 'Brush Script MT',
				),
			),
			array(
				'title' => __('Description', 'paylands-woocommerce'),
				'type' => 'textarea',
				'id' => 'woocommerce_paylands_custom_description',
				'description' => __( 'Checkout description.', 'paylands-woocommerce' ),
			),
			array(
				'title' => __('Footer Logo', 'paylands-woocommerce'),
				'type' => 'url',
				'id' => 'woocommerce_paylands_custom_footer_logo',
				'description' => __( 'Checkout footer logo URL.', 'paylands-woocommerce' ),
				'placeholder' => 'https://',
			),
			array(
				'title' => __('Terms and Conditions', 'paylands-woocommerce'),
				'type' => 'url',
				'id' => 'woocommerce_paylands_custom_footer_terms_and_conditions',
				'description' => __( 'Terms and conditions and/or privacy policy page URL.', 'paylands-woocommerce' ),
				'placeholder' => 'https://',
			),
		);
	
		return $settings;
	}
	
	public function save_customization_settings() {
		if ($this->is_paylands_customization_settings_section()) {
			$settings = $this->admin_customization_settings_fields(array(), '');
			WC_Admin_Settings::save_fields($settings);
			$this->load_customization_settings();
		}
	}


}