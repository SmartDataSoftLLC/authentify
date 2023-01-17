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
				// Generate access token URL
				$access_token_url = "https://" . $params['shop'] . "/admin/oauth/access_token";
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

				$hostt = $this->db_instance->authentify_add_host($host, $this->user, $this->shop);
				$token = $this->db_instance->authentify_add_token($this->app_key, $this->authentify_get_access_token());
				$token = $this->db_instance->authentify_add_happ($hostt, $this->app_key);
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
		$loginizer->authentify_do_login($this->user, $h, $this->authentify_get_install_data('dash_menu_url'));
	}
}