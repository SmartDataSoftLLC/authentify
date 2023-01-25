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
		parent::__construct();
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function authentify_init(){

		// Also need to check more security with host and purchasing data.
		if(isset($_GET['host'])){
			$host = $_GET['host'];
			$shop = $_GET['shop'];
			$app = $_GET['app'];
			$existing = $this->authentify_hosts_exists($host, $shop, $app);

			if($existing && is_array($existing)){
				extract($existing);
				$user_id = $this->authentify_get_user($shop, $user_id);
				$loginizer = new Authentify_Loginizer();
				$loginizer->authentify_do_login((int) $user_id, $host);
			}else{
				$app = $_GET['app'];
				$installer = new Authentify_Installer($app, $shop);
				$installer->authentify_set_inst($existing);
				add_action( 'parse_request', [$installer, 'authentify_install_app'] );
			}
		}
	}

	public function enqueue_scripts() {

		if(isset($_GET['host'])){
			$host = $_GET['host'];
			$shop = $_GET['shop'];
			$app = $_GET['app'];
			$existing = $this->authentify_hosts_exists($host, $shop, $app);

			if($existing){
				$app = $_GET['app'];
				$dash_url = $this->authentify_get_dash_url($app) . '&app=' . $app;
				wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . '/js/authentify-public.js', array( 'jquery' ), $this->version, false);
				wp_localize_script(
					'authentify',
					'authentify_object',
					array(
						'dash_url' => $dash_url ,
					)
				);
			}
		}
	}

	public function authentify_api_callback(){
		register_rest_route( 'authentify_api/v1', '/uninstall-app/', 
			array(
				'method' => 'POST',
				'callback' => [$this, 'authentify_app_uninstall'],
			) 
		);
	}

	public function authentify_app_uninstall($request){

		$app = $request->get_param( 'app' );
		$uhost = $request->get_param( 'uhost' );
		
		// add shopify checking for uninstallation
		// $this->authentify_uninstall__app__($app, $uhost);
	}
}

if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}
