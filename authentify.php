<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              smartdatasoft.com
 * @since             1.0.5
 * @package           Authentify
 *
 * @wordpress-plugin
 * Plugin Name:       Authentify
 * Plugin URI:        
 * Description:       Give your envato customers a special discount on your Easy Digital Downloads powered by site.
 * Version:           1.0.0
 * Author:            SmartDataSoft
 * Author URI:        smartdatasoft.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       authentify
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AUTHENTIFY_VERSION', '1.0.0' );
define( 'AUTHENTIFY_INCLUDES_DIR', plugin_dir_path( __FILE__ ) . '/includes');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-edd-discount-for-envato-customers-activator.php
 */
function activate_authentify() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/authentify-activator.php';
	Authentify_Activator::activate();
}

if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php'; 
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-edd-discount-for-envato-customers-deactivator.php
 */
function deactivate_authentify() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/authentify-deactivator.php';
	Authentify_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_authentify' );
register_deactivation_hook( __FILE__, 'deactivate_authentify' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/authentify.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_authentify() {
	return authentify_get_instance();
}
run_authentify();

function authentify_get_instance(){
	return Authentify::getInstance();
}

