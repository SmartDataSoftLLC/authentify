<?php

final class Authentify_Db_Core {
    
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
	}

	public function authentify_app_by_key($key){
		//chech the init nonce of this class.
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}authentify_apps` AS aapps WHERE aapps.app_unique_id = %d", $key );
        $results = $wpdb->get_results( $query, ARRAY_A);
        if(isset($results) && !empty($results)){
			return array_pop($results);
		}else{
			return false;
		}
	}
}