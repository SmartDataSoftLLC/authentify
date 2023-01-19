<?php

/**
 * Fired during plugin activation
 *
 * @link       smartdatasoft.com
 * @since      1.0.0
 *
 * @package    Authentify
 * @subpackage authentify/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Authentify
 * @subpackage authentify/includes
 * @author     SmartDataSoft <support@smartdatasoft.com>
 */
class Authentify_Installer extends Authentify_Installer_Core{

	private $app_key = '';
	private $shop = '';
	private $user = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $key, $shop, $user ) {
		// this key will be used to get the api key dynamiccaly saved at database.
		$this->app_key = $key;
		$this->shop = $shop;
		$this->user = $user;
		parent::__construct( $this->app_key );
		// try {
		// 	parent::__construct( $this->app_key );
		// } catch (Exception $e) {
		// 	echo $e->getMessage();
		// 	die(__FILE__ . ' : ' . __LINE__);
		// }

		add_rewrite_rule( $this->slug . '/([a-z0-9-]+)[/]?$', 'index.php?app=' . $key, 'top' );
		add_rewrite_rule( $this->slug_redirect . '/([a-z0-9-]+)[/]?$', 'index.php?app=' . $key, 'top' );
		add_filter( 'query_vars', function( $query_vars ) {
			$query_vars[] = 'app';
			$query_vars[] = 'hmac';
			$query_vars[] = 'code';
			$query_vars[] = 'host';
			$query_vars[] = 'timestamp';

			return $query_vars;
		});
	}

	public function authentify_install_app($param){

		if (isset($param->query_vars['app']) && $param->query_vars['app'] == $this->app_key && $param->query_vars['pagename'] == $this->slug) {
			
			// Need to get this url from a function
			$param_qs = [
				'app' => $this->app_key,
			];
			$redirect_url = get_home_url() . '/' . $this->slug . '_redirect/?' . http_build_query($param_qs);
			// Build install/approval URL to redirect to
			$install_url = "https://" . $this->shop . "/admin/oauth/authorize?client_id=" . $this->authentify_get_install_data('api_key') . "&scope=" . $this->authentify_get_install_data('scopes') . "&redirect_uri=" . $redirect_url;

			// Redirect
			header("Location: " . $install_url);
			die();
		}elseif(isset($param->query_vars['app']) && $param->query_vars['app'] == $this->app_key && $param->query_vars['pagename'] == $this->slug_redirect){
			
			$params = $param->query_vars;
			$hmac = $params['hmac'];
			$host = $params['host'];
			$params['shop'] = $this->shop;
			unset($params['page']);
			unset($params['pagename']);
			unset($params['hmac']);
			// unset($params['app']);			
			ksort($params); // Sort params lexographically
			$computed_hmac = hash_hmac('sha256', http_build_query($params), $this->authentify_get_install_data('secret'));

			// Use hmac data to check that the response is from Shopify or not
			if (hash_equals($hmac, $computed_hmac)) {
				// Set variables for our request
				$query = array(
					"client_id" => $this->authentify_get_install_data('api_key'), // Your API key
					"client_secret" => $this->authentify_get_install_data('secret'), // Your app credentials (secret key)
					"code" => $param->query_vars['code'] // Grab the access key from the URL
				);
				echo '<pre>';
				print_r($query);
				echo '</pre>';
				echo __FILE__ . ' : ' . __LINE__;
				// Generate access token URL
				$access_token_url = "https://" . $params['shop'] . "/admin/oauth/access_token";
				echo '<pre>';
				print_r($access_token_url);
				echo '</pre>';
				echo __FILE__ . ' : ' . __LINE__;
				// Configure curl client and execute request
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $access_token_url);
				curl_setopt($ch, CURLOPT_POST, count($query));
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
				$result = curl_exec($ch);
				echo '<pre>';
				print_r($ch);
				echo '</pre>';
				echo __FILE__ . ' : ' . __LINE__;
				echo '<pre>';
				print_r($result);
				echo '</pre>';
				echo __FILE__ . ' : ' . __LINE__;
				curl_close($ch);
				
				// Store the access token
				$result = json_decode($result, true);
				
				if(isset($result['access_token'])){
					$access_token = $result['access_token'];
					echo '<pre>';
					print_r($access_token);
					echo '</pre>';
					echo __FILE__ . ' : ' . __LINE__;
					$this->authentify_set_acc_token($access_token);
				}
				echo '<pre>';
				print_r($this->authentify_get_access_token());
				echo '</pre>';
				echo __FILE__ . ' : ' . __LINE__;
				// die(__FILE__ . ' : ' . __LINE__);
				$hostt = $this->db_instance->authentify_add_host($host, $this->user, $this->shop);
				$token = $this->db_instance->authentify_add_token($this->app_key, $this->authentify_get_access_token(), $hostt);
				$this->authentify_register_uninstallation($host);
				$this->authentify_do_login($host);
			} else {
				// Someone is trying to be shady!
				die('This request is NOT from Shopify!');
			}
		}
		return;
	}

	public function authentify_get_access_token(){
		return $this->authentify__get_access_token();
	}

	private function authentify_do_login($h){
		$loginizer = new Authentify_Loginizer();
		$loginizer->authentify_do_login($this->user, $h);
	}

	private function authentify_register_uninstallation($h){
		$array = array(
			'webhook' => array(
				'topic' => 'app/uninstalled', 
				'address' => 'https://essential-grid/wp-json/authentify_api/v1/uninstall-app/?app=' . $this->app_key . '&host=' . $h,
				'format' => 'json',
			)
		);

		$webhook = $this->authenti_shopify_call("/admin/api/2020-07/webhooks.json", $array, 'POST');
		$webhook = json_decode($webhook['response'], true);
		echo '<pre>';
		print_r($webhook);
		echo '</pre>';
		echo __FILE__ . ' : ' . __LINE__;
		die(__FILE__ . ' : ' . __LINE__);
	}

	private function authenti_shopify_call($api_endpoint, $query = array(), $method = 'GET', $request_headers = array()) {
    
		// Build URL
		$url = $this->shop . $api_endpoint;
		if (!is_null($query) && in_array($method, array('GET', 	'DELETE'))) {
			$url = $url . "?" . http_build_query($query);
		}
		// Configure cURL
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 3);
		// curl_setopt($curl, CURLOPT_SSLVERSION, 3);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Authentify 1.0.0');
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	
		// Setup headers
		$request_headers[] = "";
		if (!is_null($this->authentify_get_access_token())) $request_headers[] = "X-Shopify-Access-Token: " . $this->authentify_get_access_token();
		curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
	
		if ($method != 'GET' && in_array($method, array('POST', 'PUT'))) {
			if (is_array($query)) $query = http_build_query($query);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $query);
		}
		
		// Send request to Shopify and capture any errors
		$response = curl_exec($curl);
		$error_number = curl_errno($curl);
		$error_message = curl_error($curl);
	
		// Close cURL to be nice
		curl_close($curl);
	
		// Return an error is cURL has a problem
		if ($error_number) {
			return $error_message;
		} else {

			// No error, return Shopify's response by parsing out the body and the headers
			$response = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
			// Convert headers into an array
			$headers = array();
			$header_data = explode("\n",$response[0]);
			$headers['status'] = $header_data[0]; // Does not contain a key, have to explicitly set
			array_shift($header_data); // Remove status, we've already set it above
			foreach($header_data as $part) {
				$h = explode(":", $part);
				$headers[trim($h[0])] = trim($h[1]);
			}
			// Return headers and Shopify's response
			return array('headers' => $headers, 'response' => $response[1]);
		}
	}
}