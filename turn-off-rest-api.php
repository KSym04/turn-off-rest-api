<?php
/*
Plugin Name: Turn Off REST API
Plugin URI: http://www.dopethemes.com/
Description: Turn off JSON REST API on your website to anonymous users and prevent unauthorized requests from using the REST API.
Author: DopeThemes
Author URI: http://www.dopethemes.com/
Text Domain: turn-off-rest-api
Version: 1.0.0
License: GPL2+
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

	function __construct() {

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

	function initialize() {

		// vars
		$this->settings = array(

			// basic
			'name'				=> __( 'Turn Off REST API', 'log_in' ),
			'version'			=> '1.0.0'

		);

		// actions
		add_action( 'init',	array($this, 'disable_api_request') );

	}


	/*
	*  disable_api_request
	*
	*  @type	function
	*  @date	03/23/17
	*  @since	1.0.0
	*/

	function disable_api_request() {

 			add_filter( 'rest_authentication_errors', array($this, 'return_error') );
 			add_filter( 'json_enabled', '__return_false' );
    	add_filter( 'json_jsonp_enabled', '__return_false' );
    	add_filter( 'rest_enabled', '__return_false' );
    	add_filter( 'rest_jsonp_enabled', '__return_false' );
    	remove_action( 'wp_head', 'rest_output_link_wp_head' );
    	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    	remove_action( 'template_redirect', 'rest_output_link_header' );
    	remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );

	}


	/*
	*  return_error
	*
	*  @type	function
	*  @date	03/23/17
	*  @since	1.0.0
	*/

	function return_error() {
		if( !is_user_logged_in() ) {
    		return new WP_Error( 'disable', __( 'Only authenticated users allowed access to REST API.', 'turn-off-rest-api' ), array( 'status' => rest_authorization_required_code() ) );
    }
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