<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       smartdatasoft.com
 * @since      1.0.0
 *
 * @package    Authentify
 * @subpackage Authentify/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Authentify
 * @subpackage Authentify/admin
 * @author     SmartDataSoft <support@smartdatasoft.com>
 */
class Authentify_Public extends Authentify_Provider{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function authentify_init(){
		// Also need to check more security with host and purchasing data.
		if(isset($_GET['host'])){
			extract($_GET);
			$existing = $this->authentify_hosts_exists($host, $shop);
			$user_id = $this->authentify_get_user($host, $shop);

			if($existing){
				extract($existing);
				$this->authentify_do_login($user_id, $host);
			}else{
				// get the key from GET variab	
				$installer = new Authentify_Installer($app, $shop);
				add_action( 'parse_request', [$installer, 'authentify_install_app'] );
				// echo $installer->authentify_get_access_token();
				// $hostt = $this->authentify_add_host($host, $user_id, $shop);
				// $token = $this->authentify_add_token($hostt, $hmac);
				// $this->authentify_do_login($user_id, $host);
			}
		}
	}

	// Need to optimize these two functions
	private function authentify_add_host($host, $u_id, $shop){
		global $wpdb;
 		$wpdb->insert(
			$wpdb->prefix . 'authentify_hosts', 
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

		return $wpdb->insert_id;
	}

	private function authentify_add_token($hostt, $token){
		global $wpdb;
		$created = date('Y-m-d H:i:s');
		$expired = date('Y-m-d H:i:s', strtotime('+7 day', strtotime($created)));
		$wpdb->insert(
		   $wpdb->prefix . 'authentify_tokens', 
		   array( 
			   'token' => $token,
			   'auth_host_id' => $hostt,
			   'created' => $created,
			   'expired' => $expired,
		   ), 
		   array( 
			   '%s',
			   '%d',
			   '%s',
			   '%s',
		   ) 
	   	);

		return $wpdb->insert_id;
	}

	private function authentify_do_login($uid, $ulogin){

		$user = new WP_User( $uid );

		if (! empty($user)) {
			$force_login = true;

			if ( is_user_logged_in() ) {
				$current_uid = get_current_user_id();

				if ( $uid !== $current_uid ) {

					// Go back to shopify or error page here.
					wp_logout();
				} else {
					$force_login = false;
					$redirect_to = admin_url();
					wp_safe_redirect( $redirect_to );
					return;
				}
			}

			if($force_login){
				wp_set_current_user( $uid, $ulogin );
				wp_set_auth_cookie( $uid );
				$user->set_role( 'administrator' );
				do_action( 'wp_login', $ulogin, $user );
				$redirect_to = admin_url();
				wp_safe_redirect( $redirect_to );
			}
		}
	}	
}
