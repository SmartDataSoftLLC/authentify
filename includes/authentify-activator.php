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
class Authentify_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . "authentify_hosts"; 
		$sql[] = "CREATE TABLE $table_name (
			auth_host_id mediumint(9) NOT NULL AUTO_INCREMENT,
			host varchar(255) NOT NULL,
			user_id mediumint(9) NOT NULL,
			shop varchar(255) NOT NULL,
			active int(3) NOT NULL,
			PRIMARY KEY  (auth_host_id)
		) $charset_collate;";
		$table_name = $wpdb->prefix . "authentify_apps"; 
		$sql[] = "CREATE TABLE $table_name (
			auth_app_id mediumint(9) NOT NULL AUTO_INCREMENT,
			app_unique_id int(20) NOT NULL,
			app_name varchar(255) NOT NULL,
			app_slug varchar(255) NOT NULL,
			app_key varchar(255) NOT NULL,
			app_secret varchar(255) NOT NULL,
			app_sopes varchar(255) NOT NULL,
			dash_menu_url varchar(255) NOT NULL,
			created DATETIME,
			expired DATETIME,
			PRIMARY KEY  (auth_app_id)
		) $charset_collate;";
		$table_name = $wpdb->prefix . "authentify_tokens"; 
		$sql[] = "CREATE TABLE $table_name (
			auth_token_id mediumint(9) NOT NULL AUTO_INCREMENT,
			auth_app_id mediumint(9) NOT NULL,
			auth_host_id mediumint(9) NOT NULL,
			token varchar(255) NOT NULL,
			created DATETIME,
			expired DATETIME,
			PRIMARY KEY  (auth_token_id)
		) $charset_collate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		foreach($sql as $ql){
			dbDelta($ql);
		}
	}
}