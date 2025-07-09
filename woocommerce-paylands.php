<?php
/**
 * Plugin Name: WooCommerce Paylands
 * Plugin URI: https://docs.paylands.com/docs/ecommerce/plugin-woocommerce
 * Description: Accept payments on your store using Paylands gateways.
 * Author: Paylands
 * Author URI: https://paylands.com/pasarela-pago-ecommerce/
 * Text Domain: paylands-woocommerce
 * Domain Path: /languages
 * WC requires at least: 7.5
 * WC tested up to: 9.6
 * Requires at least: 6.0
 * Requires PHP: 7.3
 * Version: 1.5.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'PAYLANDS_WOOCOMMERCE_VERSION', '1.5.1' );
define( 'PAYLANDS_ROOT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PAYLANDS_PLUGIN_FILE', __FILE__ );

//modo desarrollador, si esta activado se usan las apis de sandbox para el onboarding y pagos
define('PAYLANDS_DEV_MODE', false);

//si esta activado se pueden ver logs de las llamadas a la api en wp-admin/admin.php?page=wc-status&tab=logs
define('PAYLANDS_API_ONBOARDING_LOGS', true);

/**
 * API connection data
 */
define('PAYLANDS_TEST_ONBOARDING_URL', 'https://test-panel-payment-entity.paynopain.com#/auth/business_external_registration/reference/a6b632cd-0c89-4a72-9d1b-28b988db2f5c/partner');
define('PAYLANDS_TEST_CLIENT_ID', '17_Wq0G7P3ZhNk0e84zC');
define('PAYLANDS_TEST_CLIENT_SECRET', 'rP8WjkVSW9SiphrBXbiOD5tJtLXRBvZ6yq56HBj6LjL7518CyjzEM9ICb33fmwMG');
define('PAYLANDS_TEST_API_ONBOARDING_URL', 'https://preproduccion.paynopain.com:3443/changeit-wallet-api-payment-entity');

define('PAYLANDS_PRO_ONBOARDING_URL', 'https://accounts.paynopain.com/#/auth/business_external_registration/reference/87f37d42-e4b7-41aa-83a4-7846a910f23f/partner');
define('PAYLANDS_PRO_CLIENT_ID', '15_i57H76yECmefR8Ge');
define('PAYLANDS_PRO_CLIENT_SECRET', 'fvUCW6cTYvbokpmfWNyOF5B9hRM6XADKOkNk56jtgPL8no89Dxy31zD3pGzH6t8l');
define('PAYLANDS_PRO_API_ONBOARDING_URL', 'https://pfs-accounts-api.paynopain.com');

//mail para enviar los mensajes de soporte
//define('PAYLANDS_HELP_EMAIL', "sylviaordinas@kamalyon.com"); 
define('PAYLANDS_HELP_EMAIL', "soporte@paylands.com");

/**
 * WooCommerce fallback notice.
 */
function woocommerce_paylands_missing_wc_notice() {
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Paylands requires WooCommerce to be installed and active. You can download %s here.', 'paylands-woocommerce' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

/**
 * To know if developer mode is activated
 */
function woocommerce_paylands_is_dev_mode() {
	return (defined( 'PAYLANDS_DEV_MODE' ) && PAYLANDS_DEV_MODE);
}

function woocommerce_paylands_email() {
	if (defined( 'PAYLANDS_HELP_EMAIL' ) && PAYLANDS_HELP_EMAIL) {
		return PAYLANDS_HELP_EMAIL;
	}
}

/**
 * The code that runs during plugin activation.
 */
function woocommerce_paylands_activate() {
	require_once PAYLANDS_ROOT_PATH . 'includes/class-paylands-woocommerce-activator.php';
	Paylands_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function woocommerce_paylands_deactivate() {
	require_once PAYLANDS_ROOT_PATH . 'includes/class-paylands-woocommerce-deactivator.php';
	Paylands_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'woocommerce_paylands_activate' );
register_deactivation_hook( __FILE__, 'woocommerce_paylands_deactivate' );

require_once PAYLANDS_ROOT_PATH.'includes/callback.php';


/**
 * Begins execution of the plugin.
 */
add_action( 'plugins_loaded', 'woocommerce_paylands_run' );

function woocommerce_paylands_run() {
	//para evitar que nuestro codigo se ejecute en el hook plugins_loaded cuando no es necesario
	if (!woocommerce_paylands_execute_plugins_loaded()) return;

	//carga los archivos de traducciones de los textos del plugin
	//load_plugin_textdomain('paylands-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/');

	/**
	 * Doesn't do anything if WooCommerce aren't activated.
	 */
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_paylands_missing_wc_notice' );
		return;
	}

	static $plugin_instance = null;
    if ( null === $plugin_instance ) {
        // Esta parte solo se ejecutará una vez
		require_once PAYLANDS_ROOT_PATH . 'includes/class-paylands-woocommerce.php';

		require_once PAYLANDS_ROOT_PATH . 'includes/class-paylands-woocommerce-logger.php';
		//Paylands_Logger::dev_debug_log('woocommerce_paylands_run');

		$plugin_instance = new Paylands_Woocommerce();
		$plugin_instance->run();
	}
}

add_action( 'init', 'woocommerce_paylands_init' );
function woocommerce_paylands_init() {
	//carga los archivos de traducciones de los textos del plugin
	load_plugin_textdomain('paylands-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/');
}


/**
 * comprueba si lo que se esta ejecutando es una solicitud REST, AJAX y CRON
 * para evitar que nuestro codigo se ejecute en el hook plugins_loaded cuando no es necesario
 */
function woocommerce_paylands_execute_plugins_loaded() {
	if ( defined('DOING_CRON') && DOING_CRON ) {
		return false;
	}
	/*if ( wp_doing_ajax() ) {
		return false;
	}*/
	//comentado solicitudes via api porque sino no funcionaba en el checkout de bloques
	// Verifica si es una solicitud REST
	/*if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}
	// Verifica si la URI contiene /wp-json/ explícitamente
	if ( strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false ) {
		return false;
	}*/

	return true;
}

function woocommerce_paylands_print_help_link() {
	echo '<p class="wc-paylands-footer-small-link">' . sprintf( esc_html__( 'Need help? Contact support %s', 'paylands-woocommerce'), '<a href="mailto:'.woocommerce_paylands_email().'" target="_blank">'.__('here', 'paylands-woocommerce').'</a>').'</p>';
}

function woocommerce_paylands_print_logo_html() {
	?>
	<div class="logo">
		<a href="https://paylands.com" target="_blank">
			<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="193" height="52" viewBox="0 0 193 52">
				<defs>
					<path id="a" d="M0 0h192.783v52H0z"></path>
				</defs>
				<g fill="none" fill-rule="evenodd">
					<g>
						<mask id="b" fill="#fff">
							<use xlink:href="#a"></use>
						</mask>
						<path fill="#EB1C4E" d="M166.783 52H26C11.64 52 0 40.36 0 26S11.64 0 26 0h140.783c14.36 0 26 11.64 26 26s-11.64 26-26 26" mask="url(#b)"></path>
					</g>
					<path fill="#FFF" d="M157.017 28.472c.108-1.446-.53-2.523-1.634-3.403-1.432-1.141-3.8-1.028-5.133.505-1.252 1.439-1.375 3.609-.402 5.213.768 1.266 2.082 2.015 3.444 1.881 2.366-.233 3.816-1.814 3.725-4.196m0-6.008c0-1.72-.018-3.275.013-4.83a3.34 3.34 0 0 1 .274-1.278c.246-.544 1.078-.972 1.502-.882.738.158 1.201.753 1.202 1.598.004 5.677-.001 11.354.008 17.031 0 .473-.04.885-.454 1.201-.663.507-1.22.533-1.86-.013-.239-.205-.4-.5-.628-.793-1.694.766-3.379 1.575-5.393.993-1.884-.544-3.41-1.511-4.398-3.182-1.836-3.099-1.501-6.224 1.07-9.053 2.15-2.363 5.825-2.476 8.665-.792"></path>
					<path fill="#BFBBBB" d="M56.247 18.677v5.926c1.386 0 2.702.013 4.017-.01.268-.005.547-.114.797-.227 1.329-.602 1.884-1.627 1.825-3.09-.043-1.057-1.565-2.626-2.619-2.605-1.315.026-2.632.006-4.02.006m-.071 9.135v2.777c0 1.07.013 2.14-.004 3.21-.014.886-.513 1.567-1.32 1.846-.364.125-.703.129-1.021-.163-.567-.519-.894-1.093-.89-1.91.026-5.637.014-11.274.014-16.92.072-.095.148-.191.22-.29.527-.726 1.21-.974 2.125-.917 1.357.086 2.724.043 4.086.018 2.727-.05 4.815 1.172 6.036 3.492 1.072 2.038.99 4.063-.54 6.218-.736 1.037-1.723 1.809-2.94 2.2a9.203 9.203 0 0 1-2.233.421c-1.141.07-2.29.018-3.533.018M92.62 35.235c-.57.089-1.096.083-1.552.257-1.524.582-2.892.016-4.14-.665-2.098-1.147-2.935-3.128-2.991-5.435-.05-2.054.004-4.11-.021-6.164-.007-.516.112-.972.417-1.356a1.292 1.292 0 0 1 1.544-.369c.56.259 1.053.536 1.038 1.325-.044 2.218.016 4.439-.027 6.657-.028 1.478.887 2.878 2.817 3.254.92.18 2.43-.688 2.919-1.574.32-.58.479-1.178.475-1.84-.014-2.138-.01-4.275-.003-6.412.003-.842.58-1.453 1.4-1.52.627-.051 1.488.705 1.566 1.396.028.244.005.493.005.74 0 3.328.007 6.657-.003 9.987-.005 1.52-.463 2.88-1.315 4.171-.978 1.484-2.393 2.195-3.995 2.725-1.426.471-2.783.158-4.13-.27-1.05-.333-1.384-.958-1.212-2.064.068-.436.443-.867.875-.87.517-.005 1.033-.101 1.55.206.733.437 1.562.42 2.343.094 1.041-.434 2.107-.869 2.44-2.273M77.235 28.554c.104-1.363-.464-2.451-1.506-3.369-1.236-1.087-3.249-1.118-4.477-.192-1.906 1.436-2.212 3.923-1.166 5.824.728 1.323 2.046 2.112 3.556 1.888 2.11-.312 3.667-1.772 3.593-4.151m-.192-6.163c.578-.44.91-.873 1.32-.969 1.178-.275 1.841.323 1.844 1.555.007 3.74.006 7.482 0 11.223-.001.838-.582 1.466-1.405 1.5-.22.01-.49-.078-.659-.217-.347-.285-.641-.635-.99-.992-1.041.678-2.167 1.16-3.36 1.193-1.761.047-3.394-.421-4.797-1.613-1.233-1.045-2.051-2.342-2.305-3.873-.356-2.149-.173-4.248 1.243-6.102.938-1.23 2.052-2.091 3.57-2.474 1.585-.4 3.117-.385 4.615.302.36.165.708.358.924.467"></path>
					<path fill="#FFF" d="M124.808 28.66c0-1.831-.586-3.04-1.871-3.81-1.38-.826-3.605-.726-4.808.6-1.193 1.316-1.343 4.065-.5 5.334.869 1.308 2.496 2.46 4.612 1.729.825-.286 1.397-.856 1.894-1.486.565-.715.84-1.57.673-2.366m.131-6.03c.072-.584.37-1.005.963-1.188.917-.282 1.869.346 1.873 1.312.015 3.78.01 7.562-.004 11.344 0 .265-.108.547-.227.791-.395.813-1.026 1.033-1.76.57-.362-.228-.64-.584-.936-.863-.883.284-1.863.514-2.77.912-1.125.495-2.2.142-3.198-.176-2.67-.85-4.226-2.87-4.673-5.525-.393-2.337.106-4.57 1.935-6.36.864-.845 1.786-1.52 2.956-1.82 1.972-.505 3.846-.371 5.528.908.058.044.148.047.313.094M134.487 22.209c.244-.118.423-.2.598-.288 2.065-1.035 4.955-.576 6.373 1.077.773.901 1.54 1.745 1.74 2.957.058.353.181.704.184 1.057.017 2.259.012 4.518.007 6.777-.002 1.18-.576 1.962-1.395 1.923-.832-.04-1.567-.952-1.573-2-.012-2.137-.069-4.276.023-6.408.049-1.124-.44-1.864-1.221-2.51-1.023-.846-3.385-.561-4.162.658-.35.548-.467 1.273-.568 1.936-.093.602-.022 1.23-.022 1.845 0 1.561.007 3.122-.003 4.683-.006 1.018-.512 1.716-1.272 1.794-.744.076-1.63-.833-1.695-1.753-.018-.245-.003-.493-.003-.74v-9.611c0-.288-.034-.58.007-.862.087-.597.383-1.161.952-1.31.512-.135 1.155-.215 1.579.359.095.129.238.223.45.416M166.936 25.654c.62.881 1.63.95 2.546 1.155 1.416.318 2.8.625 3.947 1.636 1.5 1.32 1.908 3.17.725 4.9-.836 1.223-2.069 1.791-3.476 2.142-2.432.606-4.403-.422-6.286-1.695-1.074-.725-.892-2.1.246-2.627.453-.209.806-.2 1.237.125.766.579 1.591 1.076 2.54 1.338 1.1.305 2.094.03 2.993-.561.748-.491.642-1.11-.142-1.574-1.168-.69-2.51-.8-3.77-1.184-1.688-.516-2.931-1.406-3.347-3.278-.225-1.017.144-1.83.624-2.538.876-1.294 2.17-1.996 3.79-2.114 1.693-.123 3.18.355 4.584 1.238.674.424 1.115.929.991 1.794-.142 1-1.226 1.55-2.083.985-1-.66-2.016-1.222-3.274-1.038-.818.12-1.56.361-1.845 1.296M104.243 32.504c2.517 0 4.901-.014 7.285.013.43.005.94.063 1.268.297 1.079.773 1.16 1.69.054 2.564-.261.208-.67.319-1.014.321-3.008.024-6.018.052-9.025-.005-1.125-.022-1.67-.752-1.787-1.773-.014-.122-.002-.246-.002-.37 0-5.298-.005-10.596.01-15.894.001-.39.104-.791.231-1.164.22-.644.787-.996 1.395-.99.615.004 1.162.38 1.359 1.04.113.378.215.777.216 1.167.015 4.477.01 8.954.01 13.43v1.364M28.155 32.474h3.098c1.293-.002 2.405-.786 2.816-2.017a570.228 570.228 0 0 0 1.916-5.851 2.903 2.903 0 0 0-1.033-3.227 442.307 442.307 0 0 0-5.102-3.671c-1.013-.719-2.336-.708-3.363-.008a95.642 95.642 0 0 0-2.322 1.648c-.883.64-1.769 1.28-2.65 1.925-1.139.834-1.573 2.084-1.14 3.428.621 1.924 1.255 3.844 1.894 5.763.408 1.225 1.519 2.008 2.817 2.01h3.069m0 7.505c-4.097-.033-7.641-1.455-10.544-4.349-2.665-2.658-4.091-5.896-4.287-9.674-.377-7.253 4.697-13.627 11.659-15.063 5.119-1.056 9.66.208 13.493 3.78 2.568 2.392 4.064 5.384 4.451 8.87.475 4.273-.685 8.088-3.51 11.344-2.38 2.742-5.405 4.362-8.987 4.938-.751.12-1.51.17-2.275.154"></path>
				</g>
			</svg>
		</a>
	</div>
	<?php
}

//para que Woocommerce lo tenga como compatible con HPOS y blocks
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

?>