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
 * Version: 1.5.9
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'PAYLANDS_WOOCOMMERCE_VERSION', '1.5.2' );
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
	<div class="logo" style="text-align:center;">
		<a href="https://paylands.com" target="_blank">
            <svg width="181" height="50" viewBox="0 0 181 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_2210_617)">
                    <path d="M66.8098 27.114H63.2551V33.3334C63.2551 34.1926 62.534 34.9116 61.639 34.9116C60.744 34.9116 60.0483 34.1962 60.0483 33.3334V16.4223C60.0483 15.5343 60.7694 14.8477 61.639 14.8477H66.8098C70.2484 14.8477 73.0204 17.5979 73.0204 21.0096C73.0204 24.4213 70.2484 27.114 66.8098 27.114ZM63.2551 23.9324H66.8098C68.4838 23.9324 69.7846 22.613 69.8136 21.0096C69.7846 19.3487 68.4838 18.0293 66.8098 18.0293H63.2551V23.9324Z" fill="#39386C"/>
                    <path d="M86.3184 27.7716V33.3907C86.3184 34.2212 85.6264 34.9114 84.7857 34.9114C84.0357 34.9114 83.4559 34.365 83.282 33.6783C82.1841 34.4513 80.8832 34.9114 79.4375 34.9114C77.4735 34.9114 75.7705 34.1097 74.5276 32.8191C73.2848 31.4997 72.5637 29.7238 72.5637 27.7752C72.5637 25.8267 73.2848 24.022 74.5276 22.7314C75.7705 21.412 77.4735 20.5815 79.4375 20.5815C80.8832 20.5815 82.1841 21.0705 83.282 21.8434C83.4559 21.128 84.032 20.5815 84.7857 20.5815C85.6227 20.5815 86.3184 21.2682 86.3184 22.131V27.7788V27.7716ZM83.253 27.7716C83.253 26.5673 82.8182 25.5355 82.1261 24.8201C81.434 24.0472 80.4484 23.6445 79.4375 23.6445C78.4265 23.6445 77.4156 24.0472 76.7525 24.8201C76.0604 25.5355 75.6545 26.5673 75.6545 27.7716C75.6545 28.976 76.0604 29.979 76.7525 30.7232C77.4156 31.4386 78.3975 31.8412 79.4375 31.8412C80.4774 31.8412 81.4304 31.4386 82.1261 30.7232C82.8182 29.979 83.253 28.9472 83.253 27.7716Z" fill="#39386C"/>
                    <path d="M100.878 22.0988V32.9343C100.878 36.7738 97.8158 39.7829 93.9749 39.7829C92.935 39.7829 91.8661 39.4953 91.0001 39.0675C90.192 38.7224 89.9022 37.8344 90.25 37.0327C90.6232 36.346 91.6088 36.0297 92.3009 36.4035C92.8517 36.6624 93.3988 36.8062 93.9749 36.8062C95.678 36.7774 97.1237 35.6881 97.5876 34.1962C96.7795 34.6564 95.7976 34.8828 94.8446 34.8828C91.5218 34.8828 88.8079 32.1614 88.8079 28.8647V22.0988C88.8079 21.2396 89.5289 20.5781 90.308 20.5781C91.203 20.5781 91.8371 21.236 91.8371 22.0988V28.8647C91.8371 30.5256 93.1959 31.7875 94.841 31.7875C96.486 31.7875 97.8158 30.5256 97.8158 28.8647V22.0988C97.8158 21.2396 98.4499 20.5781 99.3196 20.5781C100.189 20.5781 100.878 21.236 100.878 22.0988Z" fill="#39386C"/>
                    <path d="M116.426 26.5389V33.3875C116.426 34.2179 115.763 34.9082 114.922 34.9082C114.082 34.9082 113.393 34.2215 113.393 33.3875V26.5389C113.393 24.9355 112.034 23.6449 110.389 23.6449C108.744 23.6449 107.501 24.9355 107.501 26.5389V33.3875C107.501 33.4738 107.501 33.56 107.443 33.6751C107.298 34.3617 106.664 34.9082 105.911 34.9082C105.045 34.9082 104.378 34.2215 104.378 33.3875V22.0954C104.378 21.265 105.045 20.5747 105.911 20.5747C106.516 20.5747 107.067 20.9198 107.327 21.4627C108.193 20.9162 109.262 20.5747 110.389 20.5747C113.741 20.5747 116.426 23.2962 116.426 26.5353" fill="url(#paint0_linear_2210_617)"/>
                    <path d="M120.528 32.8191C119.285 31.5572 118.535 29.7813 118.535 27.7752C118.535 25.7692 119.285 24.1335 120.528 22.7601C121.829 21.412 123.503 20.5815 125.496 20.5815C127.489 20.5815 129.137 21.412 130.322 22.7601C131.623 24.1371 132.344 25.8267 132.344 27.7752C132.344 29.7238 131.623 31.5572 130.322 32.8191C129.137 34.1672 127.405 34.9977 125.496 34.9977C123.586 34.9977 121.825 34.1672 120.528 32.8191ZM121.684 27.7716C121.684 29.0048 122.061 30.0078 122.724 30.8095C123.445 31.4961 124.398 31.87 125.496 31.87C126.507 31.87 127.431 31.4961 128.155 30.8095C128.876 30.0078 129.282 29.0048 129.282 27.7716C129.282 26.5385 128.876 25.5643 128.155 24.7913C127.434 24.1586 126.51 23.702 125.496 23.702C124.398 23.702 123.445 24.1622 122.724 24.7913C122.061 25.5643 121.684 26.5961 121.684 27.7716Z" fill="url(#paint1_linear_2210_617)"/>
                    <path d="M141.443 27.114H137.888V33.3334C137.888 34.1926 137.167 34.9116 136.272 34.9116C135.377 34.9116 134.681 34.1962 134.681 33.3334V16.4223C134.681 15.5343 135.402 14.8477 136.272 14.8477H141.443C144.881 14.8477 147.653 17.5979 147.653 21.0096C147.653 24.4213 144.881 27.114 141.443 27.114ZM137.888 23.9324H141.443C143.117 23.9324 144.417 22.613 144.446 21.0096C144.417 19.3487 143.117 18.0293 141.443 18.0293H137.888V23.9324Z" fill="url(#paint2_linear_2210_617)"/>
                    <path d="M160.951 27.7716V33.3907C160.951 34.2212 160.259 34.9114 159.419 34.9114C158.669 34.9114 158.089 34.365 157.918 33.6783C156.821 34.4513 155.52 34.9114 154.074 34.9114C152.11 34.9114 150.403 34.1097 149.164 32.8191C147.921 31.4997 147.2 29.7238 147.2 27.7752C147.2 25.8267 147.921 24.022 149.164 22.7314C150.407 21.412 152.11 20.5815 154.074 20.5815C155.52 20.5815 156.817 21.0705 157.918 21.8434C158.092 21.128 158.669 20.5815 159.419 20.5815C160.256 20.5815 160.951 21.2682 160.951 22.131V27.7788V27.7716ZM157.889 27.7716C157.889 26.5673 157.455 25.5355 156.763 24.8201C156.07 24.0472 155.085 23.6445 154.074 23.6445C153.063 23.6445 152.052 24.0472 151.389 24.8201C150.697 25.5355 150.291 26.5673 150.291 27.7716C150.291 28.976 150.697 29.979 151.389 30.7232C152.052 31.4386 153.034 31.8412 154.074 31.8412C155.114 31.8412 156.067 31.4386 156.763 30.7232C157.455 29.979 157.889 28.9472 157.889 27.7716Z" fill="url(#paint3_linear_2210_617)"/>
                    <path d="M166.872 16.9973C166.872 17.8565 166.209 18.5755 165.314 18.5755C164.419 18.5755 163.752 17.8601 163.752 16.9973V16.3969C163.752 15.5377 164.444 14.8511 165.314 14.8511C166.184 14.8511 166.872 15.5377 166.872 16.3969V16.9973ZM166.872 22.185V33.362C166.872 34.2212 166.209 34.9115 165.314 34.9115C164.419 34.9115 163.752 34.2248 163.752 33.362V22.185C163.752 21.2682 164.444 20.6068 165.314 20.6068C166.184 20.6068 166.872 21.2646 166.872 22.185Z" fill="url(#paint4_linear_2210_617)"/>
                    <path d="M181 26.5389V33.3875C181 34.2179 180.337 34.9082 179.496 34.9082C178.656 34.9082 177.967 34.2215 177.967 33.3875V26.5389C177.967 24.9355 176.608 23.6449 174.963 23.6449C173.318 23.6449 172.075 24.9355 172.075 26.5389V33.3875C172.075 33.4738 172.075 33.56 172.017 33.6751C171.872 34.3617 171.238 34.9082 170.488 34.9082C169.622 34.9082 168.956 34.2215 168.956 33.3875V22.0954C168.956 21.265 169.622 20.5747 170.488 20.5747C171.093 20.5747 171.644 20.9198 171.905 21.4627C172.771 20.9162 173.84 20.5747 174.967 20.5747C178.319 20.5747 181.004 23.2962 181.004 26.5353" fill="url(#paint5_linear_2210_617)"/>
                    <path d="M25.1977 0C11.2835 0 0 11.195 0 25C0 38.805 11.2835 50 25.1977 50C39.1118 50 50.3953 38.805 50.3953 25C50.3953 11.195 39.1154 0 25.1977 0ZM38.6045 23.8424L35.3724 33.6137C34.6948 35.6593 32.7707 37.047 30.5967 37.047H20.0378C17.8674 37.047 15.9397 35.6629 15.2621 33.6137L12.0299 23.8424C11.3451 21.7788 12.0915 19.5104 13.8707 18.2413L22.3822 12.1657C24.136 10.9146 26.4985 10.9146 28.2523 12.1657L36.7638 18.2413C38.5429 19.5104 39.2858 21.7788 38.6045 23.8424Z" fill="url(#paint6_linear_2210_617)"/>
                </g>
                <defs>
                    <linearGradient id="paint0_linear_2210_617" x1="104.378" y1="27.7432" x2="116.426" y2="27.7432" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#E11E52"/>
                        <stop offset="0.98" stop-color="#EE1B4E"/>
                        <stop offset="1" stop-color="#EE1B4E"/>
                    </linearGradient>
                    <linearGradient id="paint1_linear_2210_617" x1="118.535" y1="27.786" x2="132.344" y2="27.786" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#E11E52"/>
                        <stop offset="0.98" stop-color="#EE1B4E"/>
                        <stop offset="1" stop-color="#EE1B4E"/>
                    </linearGradient>
                    <linearGradient id="paint2_linear_2210_617" x1="134.685" y1="24.8779" x2="147.653" y2="24.8779" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#E11E52"/>
                        <stop offset="0.98" stop-color="#EE1B4E"/>
                        <stop offset="1" stop-color="#EE1B4E"/>
                    </linearGradient>
                    <linearGradient id="paint3_linear_2210_617" x1="147.2" y1="27.7429" x2="160.951" y2="27.7429" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#E11E52"/>
                        <stop offset="0.98" stop-color="#EE1B4E"/>
                        <stop offset="1" stop-color="#EE1B4E"/>
                    </linearGradient>
                    <linearGradient id="paint4_linear_2210_617" x1="163.752" y1="24.8777" x2="166.872" y2="24.8777" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#E11E52"/>
                        <stop offset="0.98" stop-color="#EE1B4E"/>
                        <stop offset="1" stop-color="#EE1B4E"/>
                    </linearGradient>
                    <linearGradient id="paint5_linear_2210_617" x1="168.952" y1="0.000216864" x2="181" y2="0.000216864" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#E11E52"/>
                        <stop offset="0.98" stop-color="#EE1B4E"/>
                        <stop offset="1" stop-color="#EE1B4E"/>
                    </linearGradient>
                    <linearGradient id="paint6_linear_2210_617" x1="0" y1="25" x2="50.3953" y2="25" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#E11E52"/>
                        <stop offset="0.98" stop-color="#EE1B4E"/>
                        <stop offset="1" stop-color="#EE1B4E"/>
                    </linearGradient>
                    <clipPath id="clip0_2210_617">
                        <rect width="181" height="50" fill="white"/>
                    </clipPath>
                </defs>
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
