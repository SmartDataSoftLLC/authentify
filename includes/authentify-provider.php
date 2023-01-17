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

	protected function authentify_hosts_exists($host, $shop){
		if(!$host || !$shop){
			return false;
		}
		$prep_args = array(
			$host,
			$shop,
		);
		$query = $this->db_instance->authentify_get_db()->prepare("SELECT * FROM `{$this->db_instance->authentify_get_db()->prefix}authentify_hosts` AS ah WHERE ah.host = %s AND ah.shop = %s", $prep_args );
		$results = $this->db_instance->authentify_get_db()->get_results($query ,ARRAY_A);

		if(isset($results) && !empty($results)){
			return array_pop($results);
		}

		return false;
	}

	protected function authentify_get_user($token, $shop_name){
		$user_id        = username_exists( $token );
		$user_email = email_exists(str_replace('.myshopify', '@myshopify', $shop_name));
		
		if(!$user_id && !$user_email){
			$user_password = wp_generate_password( 12, false );
			$user_id       = wp_create_user( $token, $user_password, $user_email );
		}
		return (int) $user_id;
	}

	protected function authentify_get_token($host, $hmac){
		if(!$hmac || !$host){
			return false;
		}
		$prep_args = array(
			$hmac,
			$host,
		);
		$query = $this->db_instance->authentify_get_db()->prepare("SELECT * FROM `{$this->db_instance->authentify_get_db()->prefix}authentify_tokens` AS at WHERE at.token = %s AND at.auth_host_id = %d", $prep_args );
		$results = $this->db_instance->authentify_get_db()->get_results($query ,ARRAY_A);

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