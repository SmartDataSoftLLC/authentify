<?php

final class Authentify_Db_Core {
    
	private $authentifydb;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
		// Check nonce hear so that this class can not be initialized from outside.
        // Also set and init nonce here so that functions of this class can be called.
		global $wpdb;
		$this->authentifydb = $wpdb;

	}

	public function authentify_app_by_key($key){
		//chech the init nonce of this class.
        
        $query = $this->authentifydb->prepare("SELECT * FROM `{$this->authentifydb->prefix}authentify_apps` AS aapps WHERE aapps.app_unique_id = %d", $key );
        $results = $this->authentifydb->get_results( $query, ARRAY_A);
        if(isset($results) && !empty($results)){
			return array_pop($results);
		}else{
			return false;
		}
	}

	public function authentify_add_host($host, $u_id, $shop){
		//chech the init nonce of this class.
		$this->authentifydb->insert(
			$this->authentifydb->prefix . 'authentify_hosts', 
			array( 
				'host' => $host, 
				'user_id' => $u_id,
				'shop' => $shop,
				'active' => 1,
			), 
			array( 
				'%s', 
				'%d',
				'%s',
				'%d',
			) 
		);

		return $this->authentifydb->insert_id;
	}

	public function authentify_add_token($appid, $token, $hid){
		//chech the init nonce of this class.
		$created = date('Y-m-d');
		$expired = date('Y-m-d', strtotime('+7 day', strtotime($created)));
		$this->authentifydb->insert(
			$this->authentifydb->prefix . 'authentify_tokens', 
			array( 
				'app_unique_id' => $appid,
				'auth_host_id' => $hid,
				'token' => $token,
				'created' => $created,
				'expired' => $expired,
			), 
			array( 
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			) 
	   	);

		return $this->authentifydb->insert_id;
	}

	public function authentify_get_db(){
		return $this->authentifydb;
	}
}