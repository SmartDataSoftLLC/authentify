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

	public function authentify_add_shop($u_id, $shop){
		//chech the init nonce of this class.
		$this->authentifydb->insert(
			$this->authentifydb->prefix . 'authentify_shops', 
			array( 
				'shop' => $shop,
				'user_id' => $u_id,
				'active' => 1,
			), 
			array(
				'%s',
				'%d',
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
				'auth_app_id' => $appid,
				'auth_shop_id' => $hid,
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

	public function authentify_update_shop($u_id, $shop){
		//chech the init nonce of this class.
		$this->authentifydb->update( 
			$this->authentifydb->prefix . 'authentify_shops',
			array(
				'user_id' => $u_id,
			), 
			array( 
				'shop' => $shop,
			) 
		);
	}

	public function authentify_update_token($appid, $token, $hid){
		//chech the init nonce of this class.
		$created = date('Y-m-d');
		$expired = date('Y-m-d', strtotime('+7 day', strtotime($created)));
		$this->authentifydb->update( 
			$this->authentifydb->prefix . 'authentify_tokens',
			array(
				'token' => $token,
				'created' => $created,
				'expired' => $expired,
			), 
			array( 
				'auth_shop_id' => $hid,
				'auth_app_id' => $appid,
			) 
		);
	}

	public function authentify_get_db(){
		return $this->authentifydb;
	}

	public function authentify_create_user($s){

		//check_nonce
		$user_unique = str_replace('.myshopify', '@myshopify', $s);
		$user_id        = username_exists( $user_unique );
		$user_email = email_exists($user_unique);

		if(!$user_id && !$user_email){
			$user_password = wp_generate_password( 12, false );
			$user_id       = wp_create_user( $user_unique, $user_password, $user_email );
		}

		return (int) $user_id;
	}
}