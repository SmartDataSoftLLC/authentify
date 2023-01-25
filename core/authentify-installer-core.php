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
class Authentify_Installer_Core {

	protected $db_instance;
	private $app_id = 0;
	private $app_name = '';
	protected $slug = '';
	private $api_key = '';
	private $secret = '';
	private $scopes = '';
	private $access_token = '';
	protected $slug_redirect = '';
	protected $dash_menu_url = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	protected function __construct( $key ) {
		$this->db_instance = new Authentify_Db_Core();
		// $coreinit = $this->authentify_installer_set_app_data($key);
		// if(!$coreinit){
		// 	throw new Exception("Not our app!!!! Who are you bro????");
		// }
		$this->authentify_installer_set_app_data($key);
	}

	private function authentify_installer_set_app_data($key){
		//Set and check nonce year so the out side calls can not be done on db
		$app_data = $this->db_instance->authentify_app_by_key($key);
		if(isset($app_data) && !empty($app_data)){
			extract($app_data);
			$this->app_id = $auth_app_id;
			$this->app_name = $app_name;
			$this->slug = $app_slug;
			$this->api_key = $app_key;
			$this->secret = $app_secret;
			$this->scopes = implode(",",json_decode($app_sopes, true));
			$this->slug_redirect = $app_slug . '_redirect';
			$this->dash_menu_url = $dash_menu_url;
			// return true;
		}
		// else{
		// 	// return false;
		// }
	}

	protected function authentify_get_install_data($name){
		return $this->{$name};
	}

	protected function authentify_set_acc_token($token){
		$this->access_token = $token;
	}

	protected function authentify__get_access_token(){
		return $this->access_token;
	}
}