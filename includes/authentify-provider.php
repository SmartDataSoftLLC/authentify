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
class Authentify_Provider {

	private $db_instance;

	protected function __construct() {
		$this->db_instance = new Authentify_Db_Core();
	}

	protected function authentify_shop_exists($host, $shop, $app){
		if(!$host || !$shop){

			return false;
		}

		$tables = "`{$this->db_instance->authentify_get_db()->prefix}authentify_apps` AS aa RIGHT JOIN `{$this->db_instance->authentify_get_db()->prefix}authentify_tokens` as at ON aa.auth_app_id = at.auth_app_id LEFT JOIN `{$this->db_instance->authentify_get_db()->prefix}authentify_shops` as ah ON ah.auth_shop_id = at.auth_shop_id";
		$prep_args = array(
			$shop,
			$app,
		);
		$query = $this->db_instance->authentify_get_db()->prepare("SELECT aa.`app_secret`, ah.`auth_shop_id`, aa.`auth_app_id`, at.`token`, ah.`user_id` FROM $tables WHERE ah.shop = %s AND aa.app_unique_id = %d", $prep_args );
		$results = $this->db_instance->authentify_get_db()->get_results($query ,ARRAY_A);

		if(isset($results) && !empty($results)){
			$results = array_pop($results);

			if(isset($results['token'])){

				if($results['token'] === '0' || $results['token'] == ''){

					return (int) $results['auth_shop_id'];
				}
			}

			return $results;
		}

		return false;
	}

	protected function authentify_get_user($shop_name, $secret, $uid = 0){

		$user = new WP_User( $uid );

		if ( ! $user->exists()) {
			// This means that access token is available but user does not exists. So create user or open support ticket. Give form to put shop name and tohers and create user and login.
			die('We could not authenticate you as our user. Please authenticate yourself!!!!');
		}else{

			$params = $_GET;
			$hmac = $params['hmac'];
			$host = $params['host'];
			unset($params['hmac']);
			// unset($params['app']);			
			ksort($params); // Sort params lexographically
			$computed_hmac = hash_hmac('sha256', http_build_query($params), $secret);

			// Use hmac data to check that the response is from Shopify or not
			if (hash_equals($hmac, $computed_hmac)) {
				// Set variables for our request
				return $user->ID;
			} else {
				// Someone is trying to be shady!
				die('This request is NOT from Shopify!');
			}
		}
	}

	protected function authentify_get_token($host, $hmac){

		if(!$hmac || !$host){

			return false;
		}
		$prep_args = array(
			$hmac,
			$host,
		);
		$query = $this->db_instance->authentify_get_db()->prepare("SELECT * FROM `{$this->db_instance->authentify_get_db()->prefix}authentify_tokens` AS at WHERE at.token = %s AND at.auth_shop_id = %d", $prep_args );
		$results = $this->db_instance->authentify_get_db()->get_results($query, ARRAY_A);

		if(isset($results) && !empty($results)){

			return array_pop($results);
		}

		return false;
	}

	protected function authentify_get_dash_url($app_id){

		$prep_args = array(
			$app_id,
		);
		$dash_url = $this->db_instance->authentify_get_db()->get_var($this->db_instance->authentify_get_db()->prepare("SELECT aa.dash_menu_url FROM `{$this->db_instance->authentify_get_db()->prefix}authentify_apps` AS aa WHERE aa.app_unique_id = %d", $prep_args ));

		return $dash_url;
	}
}