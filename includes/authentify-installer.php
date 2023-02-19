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

	private $new_inst = true;
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
	public function __construct( $key, $shop) {
		// this key will be used to get the api key dynamiccaly saved at database.
		$this->app_key = $key;
		$this->shop = $shop;
		try {
			parent::__construct( $this->app_key );
		} catch (Exception $e) {
			echo $e->getMessage();
			die();
		}

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

		if (isset($param->query_vars['app']) && $param->query_vars['app'] == $this->app_key && $param->query_vars['name'] == $this->slug) {
			
			// Need to get this url from a function
			$param_qs = [
				'app' => $this->app_key,
			];
			$redirect_url = get_home_url() . '/' . $this->slug . '_redirect/?' . http_build_query($param_qs);
			// Build install/approval URL to redirect to
			$install_url = 'https://' . $this->shop . '/admin/oauth/authorize?client_id=' . $this->authentify_get_install_data('api_key') . '&scope=' . $this->authentify_get_install_data('scopes') . '&redirect_uri=' . $redirect_url;

			// Redirect
			header('Location: ' . $install_url);
			die();
		}elseif(isset($param->query_vars['app']) && $param->query_vars['app'] == $this->app_key && $param->query_vars['name'] == $this->slug_redirect){
			
			$params = $param->query_vars;
			$hmac = $params['hmac'];
			$host = $params['host'];
			$params['shop'] = $this->shop;
			unset($params['page']);
			unset($params['name']);
			unset($params['hmac']);
			// unset($params['app']);			
			ksort($params); // Sort params lexographically
			$computed_hmac = hash_hmac('sha256', http_build_query($params), $this->authentify_get_install_data('secret'));

			// Use hmac data to check that the response is from Shopify or not
			if (hash_equals($hmac, $computed_hmac)) {
				// Set variables for our request
				$query = array(
					'client_id' => $this->authentify_get_install_data('api_key'), // Your API key
					'client_secret' => $this->authentify_get_install_data('secret'), // Your app credentials (secret key)
					'code' => $param->query_vars['code'] // Grab the access key from the URL
				);
				// Generate access token URL
				$access_token_url = 'https://' . $params['shop'] . '/admin/oauth/access_token';
				// Configure curl client and execute request
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $access_token_url);
				curl_setopt($ch, CURLOPT_POST, count($query));
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
				$result = curl_exec($ch);
				curl_close($ch);

				// Store the access token
				$result = json_decode($result, true);

				if(isset($result['access_token'])){
					$access_token = $result['access_token'];
					$this->authentify_set_acc_token($access_token);
				}
				// else{
				// 	die("Token Generation Falied. Please Try Again");
				// 	die(__FILE__ . ' : ' . __LINE__);
				// }

				if($this->new_inst === true){
					$this->user = $this->db_instance->authentify_create_user($this->shop);
					$hostt = $this->db_instance->authentify_add_shop($this->user, $this->shop);
					$token = $this->db_instance->authentify_add_token($this->authentify_get_install_data('app_id'), $this->authentify_get_access_token(), $hostt);	
				}else{
					// update host and token when reinstalling
					$this->user = $this->db_instance->authentify_create_user($this->shop);
					$this->db_instance->authentify_update_shop($this->user, $this->shop);
					$this->db_instance->authentify_update_token($this->authentify_get_install_data('app_id'), $this->authentify_get_access_token(), $this->new_inst);
				}
				$this->authentify_register_uninstallation($this->shop);
				$this->authentify_do_login($this->shop);
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

	private function authentify_register_uninstallation($s){
		$array = array(
			'webhook' => array(
				'topic' => 'app/uninstalled', 
				'address' => 'https://essgrid.shopidevs.com/authentify/api/v1/uninstaller.php?app=' . $this->app_key . '&ushop=' . $s,
				'format' => 'json',
			)
		);
		$webhook = $this->authenti_shopify_call("/admin/api/2020-07/webhooks.json", $array, 'POST');
		$webhook = json_decode($webhook['response'], true);
	}

	private function authenti_shopify_call($api_endpoint, $query = array(), $method = 'GET', $request_headers = array()) {
    
		// Build URL
		$url = 'https://' . $this->shop . $api_endpoint;

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
		// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		// curl_setopt($curl, CURLOPT_SSLVERSION, 3);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Authentify 1.0.0');
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	
		// Setup headers
		$request_headers[] = '';
		if (!is_null($this->authentify_get_access_token())) $request_headers[] = 'X-Shopify-Access-Token: ' . $this->authentify_get_access_token();
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

	public function authentify_set_inst($inst){
		if($inst && is_int($inst)){
			$this->new_inst = $inst;
		}
	}
}