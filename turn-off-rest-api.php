<?php
/*
Plugin Name: Turn Off REST API
Plugin URI: http://wordpress.dopethemes.com/turn-off-rest-api/
Description: Turn off JSON REST API on your website to anonymous users and prevent unauthorized requests from using the REST API.
Author: DopeThemes
Author URI: http://www.dopethemes.com/
Text Domain: turn-off-rest-api
Version: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /lang
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists('turn_off_rest_api') ) :


class turn_off_rest_api {

	/*
	*  __construct
	*
	*  A dummy constructor to ensure Turn Off REST API is only initialized once
	*
	*  @type	function
	*  @date	03/23/17
	*  @since	1.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/

	public function __construct() {

		/* Do nothing here */

	}

	/*
	*  initialize
	*
	*  The real constructor to initialize Turn Off REST API
	*
	*  @type	function
	*  @date	03/23/17
	*  @since	1.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/

	public function initialize() {

		// vars
		$this->settings = array(

			// basic
			'name'		=> __( 'Turn Off REST API', 'turn-off-rest-api' ),
			'version'	=> '1.0.2',

			// parameters
			'menu_slug'	=> 'turnoff_rest_api_settings',
			'permission'	=> 'manage_options',

			// path
			'basename'	=> plugin_basename( __FILE__ ),
			'path'	=> plugin_dir_path( __FILE__ ),
			'dir'	=> plugin_dir_url( __FILE__ )

		);

		// actions
		add_action( 'init', array($this, 'disable_api_request') );
		add_action( 'admin_menu', array($this, 'admin_page_url') );	// admin
		add_action( 'admin_enqueue_scripts', array($this, 'admin_page_styles_scripts') ); // admin style and scripts

	}

	/*
	*  disable_api_request
	*
	*  @type	function
	*  @date	03/23/17
	*  @since	1.0.0
	*/

	public function disable_api_request() {

		remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'template_redirect', 'rest_output_link_header' );

		// detect WordPress version
		$wordpress_current_version = get_bloginfo( 'version' );

		if( version_compare( $wordpress_current_version, '4.7', '>=' ) ) {

			// allowed routes checkpoint
			add_filter( 'rest_authentication_errors', array($this, 'allowed_routes_checkpoint') );

		} else {

			// WP REST API v1
			add_filter( 'json_enabled', '__return_false' );
			add_filter( 'json_jsonp_enabled', '__return_false' );

			// WP REST API v2
			add_filter( 'rest_enabled', '__return_false' );
			add_filter( 'rest_jsonp_enabled', '__return_false' );

		}

	}

	/*
	*  allowed_routes_checkpoint
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	public function allowed_routes_checkpoint( $access ) {

		// Return current value of $access and skip all plugin functionality
		if( $this->grant_rest_api() ) {
			return $access;
		}

		$current_route = $this->get_current_route();

		if( empty( $current_route ) && ! $this->is_allowed( $current_route ) ) {
			return $this->return_error( $access );
		}

		return $access;

	}

	/*
	*  grant_rest_api
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	private function grant_rest_api() {

		return (bool) apply_filters( 'tora_grant_rest_api', is_user_logged_in() );

	}

	/*
	*  get_current_route
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	private function get_current_route() {

		$rest_route = $GLOBALS['wp']->query_vars['rest_route'];

		return ( empty( $rest_route ) || '/' == $rest_route ) ?
			$rest_route :
			untrailingslashit( $rest_route );

	}

	/*
	*  is_allowed
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	private function is_allowed( $current_route ) {

		return array_reduce( $this->get_route_whitelist_option(), function ( $check_matched, $pattern ) use ( $current_route ) {
			return $check_matched || (bool) preg_match( '@^' . htmlspecialchars_decode( $pattern ) . '$@i', $current_route );
		}, false );

	}

	/*
	*  get_route_whitelist_option
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	private function get_route_whitelist_option() {

		return (array) get_option( 'tora_allowed_route_list', array() );

	}

	/*
	*  admin_page_url
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	public function admin_page_url() {

		add_options_page(
			esc_html__( 'Turn Off REST API / Security Settings', 'turn-off-rest-api' ),
			esc_html__( 'Turn Off REST API', 'turn-off-rest-api' ),
			$this->settings['permission'], // capability
			$this->settings['menu_slug'],  // menu slug
			array( $this, 'admin_settings_page')
		);

		add_filter( 'plugin_action_links_' . $this->settings['basename'], array($this, 'admin_settings_url') );

	}

	/*
	*  admin_settings_page
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	public function admin_settings_page() {

		$this->admin_save_settings();
		include( $this->settings['path'] . 'admin/admin.php' );

	}

	/*
	*  admin_settings_url
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	public function admin_settings_url( $url ) {

		$settings_url  = menu_page_url( $this->settings['menu_slug'], false );
		$settings_link = "<a href='$settings_url'>" . esc_html__( "Settings", "turn-off-rest-api" ) . "</a>";
		array_unshift( $url, $settings_link );

		return $url;

	}

	/*
	*  admin_save_settings
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	private function admin_save_settings() {

		// check user capability
		if( !current_user_can( $this->settings['permission'] ) ) {
			return;
		}

		// security token
		if( !( isset( $_POST['_wpnonce'] ) && check_admin_referer( 'turn_off_rest_api_admin_nonce' ) ) ) {
			return;
		}

		// get all routes
		$rest_api_routes = ( isset( $_POST['rest_api_routes'] ) ) ? array_map( 'esc_html', wp_unslash( $_POST['rest_api_routes'] ) ) : null;

		// restore default / reset
		if( empty( $rest_api_routes ) || isset( $_POST['reset'] ) ) {
			delete_option( 'tora_allowed_route_list' );
			add_settings_error( 'turn-off-rest-api-notices', esc_attr( 'settings_updated' ), esc_html__( 'Default settings restored.' ), 'updated' );
			return;
		}

		// save
		update_option( 'tora_allowed_route_list', $rest_api_routes );
		add_settings_error( 'turn-off-rest-api-notices', esc_attr( 'settings_updated' ), esc_html__( 'Settings saved.' ), 'updated' );

	}

	/*
	*  admin_page_styles_scripts
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/

	public function admin_page_styles_scripts() {

		// style
		wp_register_style( 'tora-admin-base', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), $this->settings['version'] );
		wp_enqueue_style( 'tora-admin-base' );

		// script
		wp_enqueue_script( 'tora-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array('jquery'), $this->settings['version'] );

	}

	/*
	*  return_error
	*
	*  @type	function
	*  @date	03/23/17
	*  @since	1.0.0
	*/

	private function return_error( $access ) {

		$site_name = get_bloginfo( 'name' );
		$error_message = esc_html__( "Only authenticated users are allowed to access {$site_name} WP REST API.", 'turn-off-rest-api' );
		if( is_wp_error( $access ) ) {
			return $access->add( 'disabled', $error_message, array( 'status' => rest_authorization_required_code() ) );
		}

		return new WP_Error( 'disabled', $error_message, array( 'status' => rest_authorization_required_code() ) );

	}


}

/*
*  turn_off_rest_api
*
*  The main function responsible for returning the one true turn_off_rest_api Instance to functions everywhere.
*  Use this function like you would a global variable, except without needing to declare the global.
*
*  Example: <?php $turn_off_rest_api = turn_off_rest_api(); ?>
*
*  @type	function
*  @date	03/23/17
*  @since	1.0.0
*
*  @param	N/A
*  @return	(object)
*/

function turn_off_rest_api() {

	global $turn_off_rest_api;

	if( !isset($turn_off_rest_api) ) {

		$turn_off_rest_api = new turn_off_rest_api();

		$turn_off_rest_api->initialize();

	}

	return $turn_off_rest_api;

}

// initialize
turn_off_rest_api();


endif; // class_exists check