<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Paylands_Woocommerce_Admin {

	//The ID of this plugin.
	private $plugin_name;

	// The version of this plugin.
	private $version;

	//The url of the admin page
	private $admin_page_url;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->admin_page_url = admin_url('admin.php?page=wc-paylands');
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		//TODO syl refac checkear comentario lo que dice del loader para poder quitarlo
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Paylands_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Paylands_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/paylands-woocommerce-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Paylands_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Paylands_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/paylands-woocommerce-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Creates the admin pages
	 */
	public function add_admin_paylands_menu() {
		$should_render_full_menu = false;
		$top_level_link = $should_render_full_menu ? '/paylands/overview' : '/paylands/connect';

		$menu_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDIwMDEwOTA0Ly9FTiIKICJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy1TVkctMjAwMTA5MDQvRFREL3N2ZzEwLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4wIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiB3aWR0aD0iMzAuMDAwMDAwcHQiIGhlaWdodD0iMzAuMDAwMDAwcHQiIHZpZXdCb3g9IjAgMCAzMC4wMDAwMDAgMzAuMDAwMDAwIgogcHJlc2VydmVBc3BlY3RSYXRpbz0ieE1pZFlNaWQgbWVldCI+Cgo8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLjAwMDAwMCwzMC4wMDAwMDApIHNjYWxlKDAuMTAwMDAwLC0wLjEwMDAwMCkiCmZpbGw9IiMwMDAwMDAiIHN0cm9rZT0ibm9uZSI+CjxwYXRoIGQ9Ik03MSAyNDUgYy00NCAtMzggLTU3IC04NiAtMzcgLTE0MCAyMSAtNTIgNTYgLTc4IDExMiAtNzkgNzggLTIgMTI0CjQ2IDEyNCAxMjkgMCA0MCAtNSA1MiAtMzQgODEgLTMwIDMwIC00MCAzNCAtODQgMzQgLTM5IDAgLTU3IC02IC04MSAtMjV6Cm0xMjEgLTQ2IGMyNiAtMTkgMjkgLTI2IDI1IC01NiAtOCAtNDkgLTI0IC02MyAtNjcgLTYzIC00MyAwIC01OSAxNCAtNjcgNjMKLTQgMjkgLTEgMzcgMjIgNTUgMzUgMjcgNDkgMjcgODcgMXoiLz4KPC9nPgo8L3N2Zz4K';

		add_menu_page( __( 'Paylands', 'paylands-woocommerce'),
					   __( 'Paylands', 'paylands-woocommerce'),
					   'manage_woocommerce',
					   'wc-paylands',
					   array( $this, 'render_menu' ), 
					   $menu_icon,
					   '55.8' );

		add_submenu_page('wc-paylands',
						__( 'Home', 'paylands-woocommerce'),
						__( 'Home', 'paylands-woocommerce'),
						'manage_woocommerce',
						'wc-paylands');

		add_submenu_page('wc-paylands',
						__( 'Payment methods', 'paylands-woocommerce'),
						__( 'Payment methods', 'paylands-woocommerce'),
						'manage_woocommerce',
						Paylands_Gateway_Settings::get_main_settings_url());

		if (Paylands_Gateway_Settings::is_checkout_uuid_static()) {
			//Solo se puede personalizar si es el checkout generico de Paylands
			// Añadir página de personalización
			$hook_suffix = add_submenu_page('wc-paylands',
						__( 'Style', 'paylands-woocommerce'),
						__( 'Style', 'paylands-woocommerce'),
						'manage_woocommerce',
						'wc-paylands-customization',
						array( $this, 'render_customization_page' ));

			// Encolar el color picker solo en la página de personalización
			add_action('admin_enqueue_scripts', function($hook) use ($hook_suffix) {
				if ($hook === $hook_suffix) {
					wp_enqueue_style('wp-color-picker');
					wp_enqueue_script('wp-color-picker');
		
					// Inicializar el selector de color en los inputs con clase "colorpick"
					wp_add_inline_script('wp-color-picker', "
						jQuery(document).ready(function($){
							$('.colorpick').wpColorPicker();
						});
					");
				}
			});
		}

		if (woocommerce_paylands_is_dev_mode()) {
			//si esta activado el modo desarrollador muestra la página de test
			add_submenu_page('wc-paylands',
						__( 'Test', 'paylands-woocommerce'),
						__( 'Test', 'paylands-woocommerce'),
						'manage_woocommerce',
						'wc-paylands-test',
						array( $this, 'render_test_page' ));
		}
	}

	public function render_menu() {
		$this->show_template('onboarding.php');
	}

	protected function show_template($template_page) {
		$template = PAYLANDS_ROOT_PATH. '/admin/views/'.$template_page;
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	public function render_test_page() {
		$account = new Paylands_Woocommerce_Account_Connect();
		echo '<div class="wrap woocommerce">';
		echo '<h1>' . __('Developer test page', 'paylands-woocommerce') . '</h1>';

		$customer_id = get_current_user_id();
		$url = "https://api.paylands.com/v1/sandbox/customer/$customer_id/cards";
		$token = base64_encode('91a6a263d1ad41c78e9f2ced29b39ab5');
		$url = $url . '?unique=true&status=ALL';

		echo '<p>Llamando a '.$url.'</p>';
		echo '<p>Token '.$token.'</p>';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: Basic ".$token,
            )
        );

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if ( !$response ) {
            echo '***ERROR RESPUESTA VACIA***';
        }

        print_r($response);

		echo '</div>';
	}

	public function render_customization_page() {
		echo '<div class="wrap woocommerce">';
		echo '<h1>' . __('Style Customization', 'paylands-woocommerce') . '</h1>';
		
		echo '<form method="post" action="">';  // Iniciar el formulario
		$customization_settings = new Paylands_Customization_Settings();
	
		// Llamar a la función de guardado si los datos han sido enviados
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$customization_settings->save_customization_settings();
		}
	
		// Llamar al método que renderiza el formulario
		$customization_settings->admin_customization_settings_content();
	
		// Agregar el botón de "Guardar cambios"
		//submit_button(__('Guardar cambios', 'paylands-woocommerce'));
	
		echo '</form>';  // Cerrar el formulario
		echo '</div>';

	}
	
	public function display_admin_notices() {
		$this->display_dev_mode_notice();
		$settings_class = new Paylands_Gateway_Settings();
		$this->display_test_mode_notice($settings_class);
		$this->display_onboarding_notice($settings_class);
	}

	/**
	 * Add notice explaining dev mode when it's enabled.
	 */
	public function display_dev_mode_notice() {
		if (woocommerce_paylands_is_dev_mode()) { 
			?>
			<div id="paylands-dev-mode-notice" class="notice notice-paylands">
				<p>
					<b><?php esc_html_e( 'DEV mode active: ', 'paylands-woocommerce' ); ?></b>
					<?php esc_html_e( "Onboarding process and all transactions are simulated. Customers can't make real purchases through WooCommerce Paylands.", 'paylands-woocommerce' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Add notice explaining test mode when it's enabled.
	 */
	public function display_test_mode_notice($settings_class) {
		if ($settings_class->is_test_mode_active()) {
			?>
			<div id="paylands-test-mode-notice" class="notice notice-paylands">
				<p>
					<b><?php esc_html_e( 'Test mode is active: ', 'paylands-woocommerce' ); ?></b>
					<?php esc_html_e( "Customers can't make real purchases through WooCommerce Paylands.", 'paylands-woocommerce' ); ?>
					<?php echo('<a href="' . esc_attr(Paylands_Gateway_Settings::get_main_settings_url()) . '" class="wc-paylands-button wc-paylands-button-secondary">' . esc_html__( 'Settings', 'paylands-woocommerce' ) . '</a>'); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Add notice if onboarding its not complete and Paylands keys are not set
	 */
	public function display_onboarding_notice($settings_class) {
		if (!$settings_class->are_keys_set('test') && !$settings_class->are_keys_set('pro')) {
			?>
			<div id="paylands-onboarding-notice" class="notice notice-paylands is-dismissible">
				<p>
					<b><?php esc_html_e( 'Your Paylands account is not ready yet.', 'paylands-woocommerce' ); ?></b>
					<?php esc_html_e( "Start here to configure you account", 'paylands-woocommerce' ); ?>
					<?php echo('<a href="' . esc_attr($this->admin_page_url) . '" class="wc-paylands-button wc-paylands-button-secondary">' . esc_html__( 'Let\'s go', 'paylands-woocommerce' ) . '</a>'); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Adds links to the plugin's row in the "Plugins" Wp-Admin page.
	 */
	public function add_plugin_links( $links ) {
		//$settings_url = self::get_settings_url();
		$settings_url = $this->admin_page_url;
		$plugin_links = [
			'<a href="' . esc_attr($settings_url) . '">' . esc_html__( 'Settings', 'paylands-woocommerce' ) . '</a>',
		];

		return array_merge( $plugin_links, $links );
	}

}
