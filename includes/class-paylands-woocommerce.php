<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */
class Paylands_Woocommerce {

	//The loader that's responsible for maintaining and registering all hooks that power the plugin.
	protected $loader;

	//The unique identifier of this plugin.
	protected $plugin_name;

	//The current version of the plugin.
	protected $version;

	public $locales;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 */
	public function __construct() {
		if ( defined( 'PAYLANDS_WOOCOMMERCE_VERSION' ) ) {
			$this->version = PAYLANDS_WOOCOMMERCE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woocommerce-paylands';
		$this->locales = PAYLANDS_ROOT_PATH . 'languages';
		$this->set_locale();

		$this->define_constants();
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_woocommerce_hooks();

		new Paylands_Woocommerce_Admin_Sections_Overwrite();
	}

	/**
	 * Define the Plugin constants
	 */
	private function define_constants() {
		define( 'PAYLANDS_WOOCOMMERCE_PLUGIN_NAME', $this->plugin_name );
        define( 'PAYLANDS_WOOCOMMERCE_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
        define( 'PAYLANDS_WOOCOMMERCE_INCLUDES', PAYLANDS_WOOCOMMERCE_PATH . 'includes' );
        define( 'PAYLANDS_WOOCOMMERCE_ADMIN', PAYLANDS_WOOCOMMERCE_PATH . 'admin' );
        define( 'PAYLANDS_WOOCOMMERCE_PUBLIC', PAYLANDS_WOOCOMMERCE_PATH . 'public' );
        define( 'PAYLANDS_WOOCOMMERCE_ASSETS', plugin_dir_url( __FILE__ ) . 'assets' );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Paylands_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - Paylands_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - Paylands_Woocommerce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 */
	private function load_dependencies() {

		//The class responsible for orchestrating the actions and filters of the core plugin.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-loader.php';

		//The class responsible for defining all actions that occur in the admin area.
		require_once PAYLANDS_WOOCOMMERCE_ADMIN . '/class-paylands-woocommerce-admin.php';
		require_once PAYLANDS_WOOCOMMERCE_ADMIN . '/class-paylands-woocommerce-admin-sections-overwrite.php';
		require_once PAYLANDS_WOOCOMMERCE_ADMIN . '/class-paylands-woocommerce-admin-customization-settings.php';


		//The class responsible to load the Paylands Logger
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-logger.php';

		//The class responsible for defining all actions that occur in the public-facing side of the site.
		require_once PAYLANDS_WOOCOMMERCE_PUBLIC . '/class-paylands-woocommerce-public.php';

		//The Trait responsible to define some helpers used on ajax and controller.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/traits/trait-paylands-helpers.php';

		//The abstract class responsible to define the Paylands Gateways for WooCommerce.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/abstract-paylands-woocommerce-gateway.php';

		//The class responsible to define the Paylands Gateways for WooCommerce.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-gateway.php';

		//The class responsible to define the Paylands Gateways One Click Payment for WooCommerce.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-gateway-one-click.php';

		//The class responsible to define the Paylands Gateways Main Settings for WooCommerce.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-gateway-settings.php';

		//The class responsible to load the Paylands Gateway Loader.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-gateway-loader.php';

		//The class responsible to load the Paylands Orders Model.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-orders.php';

		//The class responsible to connect the Paylands account
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-account-connect.php';

		//The class responsible to load the Paylands Gateway Loader.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/api/class-paylands-api-client.php';

		//The class responsible api calls
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/api/class-paylands-onboarding-api.php';

		//The class responsible to load the Paylands Gateway Ajax hooks.
		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/class-paylands-woocommerce-ajax.php';

		require_once PAYLANDS_WOOCOMMERCE_INCLUDES . '/actions.php';

		$this->loader = new Paylands_Woocommerce_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	public function set_locale() {
		load_plugin_textdomain(
			'paylands-woocommerce',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages/'
		);
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Paylands_Woocommerce_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_paylands_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_admin_notices', 30);

		$this->loader->add_filter( 'plugin_action_links_'. plugin_basename(PAYLANDS_PLUGIN_FILE), $plugin_admin, 'add_plugin_links');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 */
	private function define_public_hooks() {
		$plugin_public = new Paylands_Woocommerce_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Register the woocommerce hooks to can see the payment gateway in the setting payment options.
	 */
	private function define_woocommerce_hooks() {
		//Paylands_Logger::dev_debug_log('define_woocommerce_hooks');
		$pnp_loader = new Paylands_Gateway_Loader();	
		$admin_settings = new Paylands_Gateway_Settings();

		$this->loader->add_action( 'plugins_loaded', $pnp_loader, 'paylands_payment_resources_init', 20 ); //priority 20 because has to be executed after de run_paylands_woocommerce function
		$this->loader->add_filter( 'woocommerce_payment_gateways', $pnp_loader, 'add_pnp_paylands_gateway');

		//añade compatibilidad con el checkout por bloques
		$this->loader->add_action( 'woocommerce_blocks_loaded', $pnp_loader, 'add_pnp_paylands_gateway_blocks_support');

		$this->loader->add_action( 'woocommerce_settings_checkout', $admin_settings, 'admin_main_settings_content' );
		$this->loader->add_action( 'woocommerce_settings_save_checkout', $admin_settings, 'save_main_settings' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		//Paylands_Logger::dev_debug_log('loader->run');
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
