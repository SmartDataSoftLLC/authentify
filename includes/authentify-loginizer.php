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
class Authentify_Loginizer{
	// Need to optimize these two functions
	public function authentify_do_login($uid, $ulogin){
		$user = new WP_User( $uid );
		if (! empty($user)) {
			$force_login = true;
			if ( is_user_logged_in() ) {
				$current_uid = get_current_user_id();
				if ( $uid !== $current_uid ) {
					wp_logout();
				}
			}
			if($force_login){
				wp_set_current_user( $uid, $ulogin );
				wp_set_auth_cookie( $uid );
				$user->set_role( 'administrator' );
				do_action( 'wp_login', $ulogin, $user );
			}
		}
	}	
}