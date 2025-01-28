<?php
/**
 * The file that define the Paylands API Client
 *
 * This file have a paylands api client,. Load the the requests
 * and let us to create paylands request.
 *
 * @link       http://paylands.com/contacto/
 * @since      1.0.0
 *
 * @package    Paylands_Woocommerce
 * @subpackage Paylands_Woocommerce/includes/api
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paylands_Api_Client Class
 */
class Paylands_Api_Client {
	const POST_URL = "https://api.paylands.com/v1";
	const SANDBOX = "/sandbox";
	const REDIRECT_URL = "/payment/process/";
	const REDIRECT_CHECKOUT_URL = "/payment/checkout/"; //para pagos con tarjeta
	const REDIRECT_BIZUM_URL = "/payment/bizum/"; //para pagos con bizum
	const CREATE_ORDER_URL = "/payment";
	const CUSTOMER = "/customer";
	const SERVICES = "/client/services";
	const RESOURCES = "/client/resources";

	/**
	 * PNP User Api key - obtained from User PNP account
	 * @var string $api_key
	 */
	private $api_key;

	/**
	 * PNP User signature - obtained from User PNP account
	 * @var string $signature
	 */
	private $signature;

	/**
	 * PNP Checkout UUID - obtained from User PNP account
	 * @var string $checkout_uuid
	 */
	private $checkout_uuid;

	/**
	 * Extra config like mode or other stuff that can fit here
	 * @var array
	 */
	private $config;

	/**
	 * The Lang defined by the user.
	 *
	 * @var string
	 */
	private $lang;

	/**
	 * PNPPaylands constructor.
	 * @param string $api_key
	 * @param string $signature
	 * @param string $service
	 * @param array $config
	 */
	public function __construct($api_key, $signature, $checkout_uuid, $config = ['mode' => 'sandbox'], $lang = 'es') {
		$this->api_key = $api_key;
		$this->signature = $signature;
		$this->checkout_uuid = $checkout_uuid;
		$this->config = $config;
		$this->lang = $lang;
	}

	/**
	 * Get Environment
	 * @return string
	 */
	private function getEnvironment() {
		if ($this->config['mode'] == 'sandbox') {
			return 'sandbox';
		}

		return 'prod';
	}

	/**
	 * Get Api URl
	 * @return string
	 */

	private function getApiUrl() {
		$mode = $this->getEnvironment();
		if ($mode == 'sandbox') {
			return self::POST_URL.self::SANDBOX;
		}

		return self::POST_URL;
	}

	/**
	 * @param float $number
	 * @return float
	 */
	private function toCents($number) {
		return floor(100 * $number);
	}

	/**
	 * @param string $url
	 * @param array $data
	 * @return array
	 */
	private function sendRequest($url, $data) {
		// error_log("PNP::REQUEST {$url} => " . json_encode($data, JSON_PRETTY_PRINT), 4);
		// Paylands_Logger::log( "PNP::REQUEST {$url} => " . json_encode($data, JSON_PRETTY_PRINT) );
		$payload_json = json_encode($data);
		$token = $this->api_key;

		Paylands_Logger::dev_debug_log('api sendRequest '.$url.' *** '.$payload_json);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_json);
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				"Content-Type: application/json",
				"Authorization: Bearer ".$token,
			)
		);
		$reponse_raw = curl_exec($ch);
		$response = json_decode($reponse_raw, true);
		curl_close($ch);
		$log_message = 'paylands_api sendRequest url: ['.$url.'] token: ['.$token.'] data: ['.$payload_json.']';
		$log_message .= ' response: ['.$reponse_raw.']';
		Paylands_Logger::dev_debug_log($log_message);

		if ( !$response ) {
			//TODO syl, controlar respuestar 401. Ver si lo mejor es lanzar excepcion porque el wp se queda en casque sino se controla try catch
			throw new Exception(__('Something went wrong processing the request', 'paylands-woocommerce'));
		}

		return $response;
	}

	private function sendGetRequest($url) {

		$token = base64_encode($this->api_key);

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

		$reponse_raw = curl_exec($ch);
		$response = json_decode($reponse_raw, true);
		curl_close($ch);

		$log_message = 'paylands_api sendGetRequest url: ['.$url.'] token: ['.$token.']';
		$log_message .= ' response: ['.$reponse_raw.']';
		Paylands_Logger::dev_debug_log($log_message);

		if ( !$response ) {
			throw new Exception(__('Something went wrong processing the request', 'paylands-woocommerce'));
		}

		return $response;
	}

	/**
	 * Get Create Order URL
	 * @return string
	 */
	public function getCreateOrderUrl() {
		return $this->getApiUrl().self::CREATE_ORDER_URL."?lang={$this->lang}";
	}

	/**
	 * Get Services URL
	 * @return string
	 */
	public function getServicesUrl() {
		return $this->getApiUrl().self::SERVICES;
	}

	/**
	 * Get Resources URL
	 * @return string
	 */
	public function getResourcesUrl() {
		return $this->getApiUrl().self::RESOURCES;
	}

	/**
	 * Get Redirect URL
	 * @return string
	 */
	public function getRedirectUrl($token) {
		return $this->getApiUrl().self::REDIRECT_URL.$token."?lang={$this->lang}";
	}

	public function getRedirectCheckoutUrl($token) {
		return $this->getApiUrl().self::REDIRECT_CHECKOUT_URL.$token."?lang={$this->lang}";
	}

	public function getRedirectBizumUrl($token) {
		return $this->getApiUrl().self::REDIRECT_BIZUM_URL.$token."?lang={$this->lang}";
	}

	/**
	 * Get Ip Address
	 * @return string
	 */
	public function getRealIpAddr() {
		if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
		{
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
		{
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	/**
	 * @param float $amount
	 * @param string $operative
	 * @param string $customer_ext_id
	 * @param string $description
	 * @param string $additional
	 * @param bool $secure
	 * @param string $url_post
	 * @param string $url_ok
	 * @param string $url_ko
	 * @param string $source_uuid
	 * @return array
	 */
	public function createOrder(
		$amount,
		$operative,
		$customer_ext_id = '',
		$description,
		$additional,
		$service = '',
		$secure,
		$url_post,
		$url_ok,
		$url_ko,
		$source_uuid = '',
		$is_checkout = '',
		$order = null
	) {
		Paylands_Logger::dev_debug_log('api createOrder '.$service);
		
		if (empty($service)) {
			Paylands_Logger::dev_debug_log('api createOrder sin servicio');
			return false;
		}

		$amount_in_cents = $this->toCents($amount);
		$payload = [
			"amount" => $amount_in_cents,
			"operative" => $operative,
			"signature" => $this->signature,
			"description" => $description,
			"service" => $service,
			"secure" => $secure,
			"additional" => $additional,
			"url_post" => $url_post,
			"url_ok" => $url_ok,
			"url_ko" => $url_ko,
			"reference" => $additional,
		];

		if (!empty($source_uuid)) {
			$payload['source_uuid'] = $source_uuid;
		}

		if (!empty($customer_ext_id)) {
			$payload['customer_ext_id'] = $customer_ext_id;
		}

		if (!empty($is_checkout) && !empty($this->checkout_uuid)) {
			/*
				"checkout": {
				"uuid": "4acae331-1ade-46f9-8159-8e7715145e0a",
				"payment_methods": 
			[
				"PAYMENT_CARD",
				"BIZUM"
			],
			"customization": 
			{
				"background_color": "#000000",
				"font": "Sans Serif",
				"description": "Checkout with a black background color",
				"payment_details": 
					{
						"order_number": "632863"
					}
				}
			}

			"extra_data":{"checkout":{"uuid":"46AC4508-8601-423D-BA4E-EA696C927CF6",
			                          "customization":{"title":null,"logo":null,"background_color":null,
													   "accent_color":null,"text_color":null,"font":null,
													   "description":null,"payment_details":null,"language":null,
													   "theme_type":null,"footer_logo":null,"footer_terms_and_conditions":null},
									  "payment_methods":null } }
			*/

			$customization_class = new Paylands_Customization_Settings();
			$customization = array();

			$title = $customization_class->get_custom_title();
			if ($title) $customization['title'] = $title;

			$background_color = $customization_class->get_custom_background_color();
			if ($background_color) $customization['background_color'] = $background_color;

			$font = $customization_class->get_custom_font();
			if ($font) $customization['font'] = $font;

			$description = $customization_class->get_custom_description();
			if ($description) $customization['description'] = $description;
			else $customization['description'] = '';

			$accent_color = $customization_class->get_custom_accent_color();
			if ($accent_color) $customization['accent_color'] = $accent_color;

			$text_color = $customization_class->get_custom_text_color();
			if ($text_color) $customization['text_color'] = $text_color;

			$logo = $customization_class->get_custom_logo();
			if ($logo) $customization['logo'] = $logo;

			$footer_logo = $customization_class->get_custom_footer_logo();
			if ($footer_logo) $customization['footer_logo'] = $footer_logo;

			$footer_terms_and_conditions = $customization_class->get_custom_footer_terms_and_conditions();
			if ($footer_terms_and_conditions) $customization['footer_terms_and_conditions'] = $footer_terms_and_conditions;

			/*$customization = array("background_color" => "#000000",
								   "font" => "Arial",
								   "description" => "Checkout with a black background color",
								  );*/
								  					  
			
			if (!empty($order)) {
				$payment_details = array(__('Nº de pedido', 'paylands-woocommerce') => $order->get_id());
				$customization['payment_details'] = $payment_details;
			}

			$payment_methods = array("PAYMENT_CARD","GOOGLE_PAY","APPLE_PAY");
			//posibles valores ["PAYMENT_CARD","BIZUM","SOFORT","IDEAL","CRYPTO","PIX","VIACASH","KLARNA","GIROPAY","GOOGLE_PAY","APPLE_PAY","CLICKTOPAY"]
			//$payment_methods = array();

			$extra_data = [
				"checkout" => array("uuid" => $this->checkout_uuid,
									"customization" => $customization,
									"payment_methods" => $payment_methods)
			];
			$payload['extra_data'] = $extra_data;
			Paylands_Logger::dev_debug_log('api createOrder extra data '.json_encode($extra_data));

		}else if (!empty($order)) {
			//para los otros metodos de pago que requieren la info en el extra_data (ej: nuvei)
			$extra_data = [
				"profile" => array("first_name" => $order->get_billing_first_name(),
								"last_name"  => $order->get_billing_last_name(),
								"email"      => $order->get_billing_email(),
							),
							"address" => array(
								"city"       => $order->get_billing_city(),
								"country"    => $this->convert_country_code_alpha2_to_alpha3($order->get_billing_country()),
								"address1"   => $order->get_billing_address_1(),
								"zip_code"   => $order->get_billing_postcode(),
								"state_code" => $order->get_billing_state(),
							)
			];
			$payload['extra_data'] = $extra_data;
		}

		$api_url = $this->getCreateOrderUrl();

		return $this->sendRequest($api_url, $payload);
	}

	public function retrieveServices() {
		$url = $this->getServicesUrl();

		$response = $this->sendGetRequest($url);
		if (isset($response['code'])) return false;
		return $response;
	}

	public function retrieveResources() {
		$url = $this->getResourcesUrl();

		$response = $this->sendGetRequest($url);
		if (isset($response['code'])) return false;
		return $response;
	}

	public function retrieveProfile($customer_id) {
		$url = $this->getApiUrl().self::CUSTOMER . '/profile/' . $customer_id;

		return $this->sendRequest($url, ['external_id' => (string)$customer_id]);
	}

	public function createProfile($customer_id) {
		$url = $this->getApiUrl().self::CUSTOMER;
		$data = array(
			'customer_ext_id' => (string)$customer_id,
			'signature' => $this->signature
		);

		return $this->sendRequest($url, $data);
	}

	//funcion para convertir el codigo del pais de 2 digitos (woo) a 3
	function convert_country_code_alpha2_to_alpha3($alpha2) {
		if (empty($alpha2)) return '';
		// Mapa de países Alpha-2 a Alpha-3
		$alpha2_to_alpha3 = array(
			'AF' => 'AFG', 'AL' => 'ALB', 'DZ' => 'DZA', 'AS' => 'ASM', 'AD' => 'AND',
			'AO' => 'AGO', 'AI' => 'AIA', 'AQ' => 'ATA', 'AG' => 'ATG', 'AR' => 'ARG',
			'AM' => 'ARM', 'AW' => 'ABW', 'AU' => 'AUS', 'AT' => 'AUT', 'AZ' => 'AZE',
			'BS' => 'BHS', 'BH' => 'BHR', 'BD' => 'BGD', 'BB' => 'BRB', 'BY' => 'BLR',
			'BE' => 'BEL', 'BZ' => 'BLZ', 'BJ' => 'BEN', 'BM' => 'BMU', 'BT' => 'BTN',
			'BO' => 'BOL', 'BA' => 'BIH', 'BW' => 'BWA', 'BR' => 'BRA', 'BN' => 'BRN',
			'BG' => 'BGR', 'BF' => 'BFA', 'BI' => 'BDI', 'CV' => 'CPV', 'KH' => 'KHM',
			'CM' => 'CMR', 'CA' => 'CAN', 'KY' => 'CYM', 'CF' => 'CAF', 'TD' => 'TCD',
			'CL' => 'CHL', 'CN' => 'CHN', 'CO' => 'COL', 'KM' => 'COM', 'CG' => 'COG',
			'CD' => 'COD', 'CR' => 'CRI', 'HR' => 'HRV', 'CU' => 'CUB', 'CY' => 'CYP',
			'CZ' => 'CZE', 'DK' => 'DNK', 'DJ' => 'DJI', 'DM' => 'DMA', 'DO' => 'DOM',
			'EC' => 'ECU', 'EG' => 'EGY', 'SV' => 'SLV', 'GQ' => 'GNQ', 'ER' => 'ERI',
			'EE' => 'EST', 'ET' => 'ETH', 'FJ' => 'FJI', 'FI' => 'FIN', 'FR' => 'FRA',
			'GA' => 'GAB', 'GM' => 'GMB', 'GE' => 'GEO', 'DE' => 'DEU', 'GH' => 'GHA',
			'GI' => 'GIB', 'GR' => 'GRC', 'GL' => 'GRL', 'GD' => 'GRD', 'GU' => 'GUM',
			'GT' => 'GTM', 'GN' => 'GIN', 'GW' => 'GNB', 'GY' => 'GUY', 'HT' => 'HTI',
			'HN' => 'HND', 'HK' => 'HKG', 'HU' => 'HUN', 'IS' => 'ISL', 'IN' => 'IND',
			'ID' => 'IDN', 'IR' => 'IRN', 'IQ' => 'IRQ', 'IE' => 'IRL', 'IL' => 'ISR',
			'IT' => 'ITA', 'JM' => 'JAM', 'JP' => 'JPN', 'JO' => 'JOR', 'KZ' => 'KAZ',
			'KE' => 'KEN', 'KI' => 'KIR', 'KP' => 'PRK', 'KR' => 'KOR', 'KW' => 'KWT',
			'KG' => 'KGZ', 'LA' => 'LAO', 'LV' => 'LVA', 'LB' => 'LBN', 'LS' => 'LSO',
			'LR' => 'LBR', 'LY' => 'LBY', 'LI' => 'LIE', 'LT' => 'LTU', 'LU' => 'LUX',
			'MO' => 'MAC', 'MG' => 'MDG', 'MW' => 'MWI', 'MY' => 'MYS', 'MV' => 'MDV',
			'ML' => 'MLI', 'MT' => 'MLT', 'MH' => 'MHL', 'MR' => 'MRT', 'MU' => 'MUS',
			'MX' => 'MEX', 'FM' => 'FSM', 'MD' => 'MDA', 'MC' => 'MCO', 'MN' => 'MNG',
			'ME' => 'MNE', 'MA' => 'MAR', 'MZ' => 'MOZ', 'MM' => 'MMR', 'NA' => 'NAM',
			'NR' => 'NRU', 'NP' => 'NPL', 'NL' => 'NLD', 'NZ' => 'NZL', 'NI' => 'NIC',
			'NE' => 'NER', 'NG' => 'NGA', 'NO' => 'NOR', 'OM' => 'OMN', 'PK' => 'PAK',
			'PW' => 'PLW', 'PA' => 'PAN', 'PG' => 'PNG', 'PY' => 'PRY', 'PE' => 'PER',
			'PH' => 'PHL', 'PL' => 'POL', 'PT' => 'PRT', 'PR' => 'PRI', 'QA' => 'QAT',
			'RO' => 'ROU', 'RU' => 'RUS', 'RW' => 'RWA', 'WS' => 'WSM', 'SM' => 'SMR',
			'ST' => 'STP', 'SA' => 'SAU', 'SN' => 'SEN', 'RS' => 'SRB', 'SC' => 'SYC',
			'SL' => 'SLE', 'SG' => 'SGP', 'SK' => 'SVK', 'SI' => 'SVN', 'SB' => 'SLB',
			'SO' => 'SOM', 'ZA' => 'ZAF', 'ES' => 'ESP', 'LK' => 'LKA', 'SD' => 'SDN',
			'SR' => 'SUR', 'SZ' => 'SWZ', 'SE' => 'SWE', 'CH' => 'CHE', 'SY' => 'SYR',
			'TW' => 'TWN', 'TJ' => 'TJK', 'TZ' => 'TZA', 'TH' => 'THA', 'TL' => 'TLS',
			'TG' => 'TGO', 'TO' => 'TON', 'TT' => 'TTO', 'TN' => 'TUN', 'TR' => 'TUR',
			'TM' => 'TKM', 'TV' => 'TUV', 'UG' => 'UGA', 'UA' => 'UKR', 'AE' => 'ARE',
			'GB' => 'GBR', 'US' => 'USA', 'UY' => 'URY', 'UZ' => 'UZB', 'VU' => 'VUT',
			'VE' => 'VEN', 'VN' => 'VNM', 'YE' => 'YEM', 'ZM' => 'ZMB', 'ZW' => 'ZWE',
		);
	
		// Retornar el código Alpha-3 si existe, de lo contrario devuelve el original
		return isset($alpha2_to_alpha3[$alpha2]) ? $alpha2_to_alpha3[$alpha2] : $alpha2;
	}
	
}
