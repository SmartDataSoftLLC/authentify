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

	protected function authentify_hosts_exists($host, $shop){
		if(!$host || !$shop){
			return false;
		}
		global $wpdb;
		$prep_args = array(
			$host,
			$shop,
		);
		$query = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}authentify_hosts` AS ah WHERE ah.host = %s AND ah.shop = %s", $prep_args );
		$results = $wpdb->get_results($query ,ARRAY_A);

		if(isset($results) && !empty($results)){
			return array_pop($results);
		}

		return false;
	}

	protected function authentify_get_user($token, $shop_name){
		$user_id        = username_exists( $token );
		$user_email = str_replace('.myshopify', '@myshopify', $shop_name);
		
		if(!$user_id){
			$user_password = wp_generate_password( 12, false );
			$user_id       = wp_create_user( $token, $user_password, $user_email );
		}

		return $user_id;
	}

	protected function authentify_get_token($host, $hmac){
		if(!$hmac || !$host){
			return false;
		}
		global $wpdb;
		$prep_args = array(
			$hmac,
			$host,
		);
		$query = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}authentify_tokens` AS at WHERE at.token = %s AND at.auth_host_id = %d", $prep_args );
		$results = $wpdb->get_results($query ,ARRAY_A);

		if(isset($results) && !empty($results)){
			return array_pop($results);
		}

		return false;
	}
}