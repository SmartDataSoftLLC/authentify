<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       smartdatasoft.com
 * @since      1.0.0
 *
 * @package    Authentify
 * @subpackage Authentify/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Authentify
 * @subpackage Authentify/includes
 * @author     SmartDataSoft <support@smartdatasoft.com>
 */

class Authentify{


	private static $singleton = false;
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public $plugin_admin;
	public $plugin_public;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'AUTHENTIFY_VERSION' ) ) {
			$this->version = AUTHENTIFY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'authentify';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}
	private function load_dependencies() {
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once AUTHENTIFY_INCLUDES_DIR . '/authentify-i18n.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/authentify-provider.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/authentify-loginizer.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/authentify-admin.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/authentify-db-core.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/authentify-installer-core.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/authentify-installer.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/authentify-public.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Authentify_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Authentify_i18n();

		add_action( 'plugins_loaded', [$plugin_i18n ,'load_plugin_textdomain'] );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$this->plugin_admin = new Authentify_Admin( $this->get_plugin_name(), $this->get_version() );

		add_action( 'admin_init', array( $this->plugin_admin, 'authentify_admin_init' ) );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$this->plugin_public = new Authentify_Public( $this->get_plugin_name(), $this->get_version() );

		add_action( 'init', array( $this->plugin_public, 'authentify_init' ) );
		add_action( 'wp_enqueue_scripts', [$this->plugin_public, 'enqueue_scripts'] );
		// add_action( 'rest_api_init', array( $this->plugin_public, 'authentify_api_callback' ) );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public static function getInstance()
    {
        if (self::$singleton === false) {
            self::$singleton = new self();
        }
        return self::$singleton;
    }
}
