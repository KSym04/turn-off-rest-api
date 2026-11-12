<?php
/*
Plugin Name: Turn Off REST API
Plugin URI: https://www.dopethemes.com/downloads/turn-off-rest-api/
Description: Prevents unauthorized requests from using the WP REST API.
Author: DopeThemes
Author URI: https://www.dopethemes.com/
Text Domain: turn-off-rest-api
Version: 1.1.6
Requires at least: 4.7
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Domain Path: /lang
*/

/*
    Copyright DopeThemes

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1335, USA
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

if( ! class_exists( 'turn_off_rest_api' ) ) :

class turn_off_rest_api {

	/**
	 * Plugin version. Single source of truth for the settings array and for the
	 * cache-busting version passed to wp_enqueue_style() / wp_enqueue_script(),
	 * so a release bump cannot leave browsers serving stale assets.
	 *
	 * @var string
	 */
	var $version = '1.1.6';

	/**
	 * Plugin paths and URLs, populated in initialize().
	 *
	 * Declared explicitly: PHP 8.2 deprecates creating a property dynamically, and
	 * PHP 9 makes it a fatal error.
	 *
	 * @var array
	 */
	var $settings = array();

	/**
	 * Whether the outer REST request of this HTTP request has been answered.
	 *
	 * Set by mark_outer_request_dispatched(). Everything WordPress dispatches after that
	 * point (the linked resources of a ?_embed request) is checked against the allow list
	 * by embedded_request_checkpoint().
	 *
	 * @since 1.1.6
	 *
	 * @var bool
	 */
	private $outer_request_dispatched = false;

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
		// Do nothing here.
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
		// Variables.
		// 'name' is deliberately NOT translated here. initialize() runs at plugin load,
		// long before the init action, so a translation call would force WordPress to load
		// the text domain just in time. Since 6.7 that emits a _load_textdomain_just_in_time
		// "called incorrectly" notice on every debug-enabled site. The plugin name is a
		// proper noun anyway; translate at the point of output if it is ever displayed.
		$this->settings = array(
			'name'		 => 'Turn Off REST API',
			'version'	 => $this->version,
			'menu_slug'	 => 'turnoff_rest_api_settings',
			'permission' => 'manage_options',
			'basename'	 => plugin_basename( __FILE__ ),
			'path'		 => plugin_dir_path( __FILE__ ),
			'dir'		 => plugin_dir_url( __FILE__ )
		);

		// Actions.
		add_action( 'init', array( $this, 'disable_api_request') );
		add_action( 'admin_menu', array( $this, 'admin_page_url') );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_page_styles_scripts') );

		// Site Health transparency: explains the REST restriction so it never looks like a fault.
		add_filter( 'site_status_tests', array( $this, 'add_site_health_test') );

		// One-click allow for routes that guest checkout and contact forms depend on.
		add_action( 'admin_post_tora_allow_integration', array( $this, 'admin_allow_integration_routes' ) );

		// One-time notice on the Plugins screen right after activation.
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		add_action( 'load-plugins.php', array( $this, 'maybe_queue_activation_notice' ) );
	}

	/**
	 * Activation callback.
	 *
	 * Flags the one-time "REST API is now blocked" notice for the Plugins screen.
	 * Skipped for WP-CLI (nobody is looking at the Plugins screen) and for
	 * network-wide activation (the network Plugins screen is a different screen).
	 *
	 * @since 1.1.5
	 *
	 * @param bool $network_wide Whether the plugin is being network activated.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if( ( defined( 'WP_CLI' ) && WP_CLI ) || $network_wide ) {
			return;
		}
		update_option( 'tora_activation_notice', '1', false );
	}

	/**
	 * Queue the activation notice. Hooked to load-plugins.php, so the notice can
	 * only ever print on the Plugins screen.
	 *
	 * The flag is left in place for users without manage_options, so the first
	 * administrator who opens the Plugins screen still sees it once.
	 *
	 * @since 1.1.5
	 *
	 * @return void
	 */
	public function maybe_queue_activation_notice() {
		if( is_network_admin() || ! current_user_can( $this->settings['permission'] ) ) {
			return;
		}
		if( '1' !== get_option( 'tora_activation_notice' ) ) {
			return;
		}
		add_action( 'admin_notices', array( $this, 'render_activation_notice' ) );
	}

	/**
	 * Print the activation notice and consume the flag, so it never repeats.
	 *
	 * @since 1.1.5
	 *
	 * @return void
	 */
	public function render_activation_notice() {
		delete_option( 'tora_activation_notice' );

		printf(
			'<div class="notice notice-info is-dismissible"><p><strong>%1$s</strong> %2$s</p><p><a class="button button-primary" href="%3$s">%4$s</a> <a class="button" href="%5$s" target="_blank" rel="noopener noreferrer">%6$s</a> <span class="description">%7$s</span></p></div>',
			esc_html__( 'Turn Off REST API is on.', 'turn-off-rest-api' ),
			esc_html__( 'The REST API is now blocked for logged out visitors. Logged in users and the block editor are unaffected.', 'turn-off-rest-api' ),
			esc_url( admin_url( 'options-general.php?page=' . $this->settings['menu_slug'] ) ),
			esc_html__( 'Review settings', 'turn-off-rest-api' ),
			esc_url( rest_url() ),
			esc_html__( 'Test it', 'turn-off-rest-api' ),
			esc_html__( 'Open the test link in a private window to see what logged out visitors get. In this window you are logged in, so it stays open.', 'turn-off-rest-api' )
		);
	}

	/**
	 * Plugins whose front end needs specific REST routes for logged out visitors.
	 *
	 * Each pattern selects the minimum registered route keys the plugin's guest
	 * features call: the WooCommerce Store API cart, checkout, order (pay for
	 * order) and batch routes used by the Cart and Checkout blocks, and the
	 * Contact Form 7 submit, validation schema and captcha refill routes.
	 *
	 * @since 1.1.5
	 *
	 * @return array Map of integration key => array( 'active' => bool, 'pattern' => regex ).
	 */
	public function get_guest_integrations() {
		return array(
			'woocommerce'    => array(
				'active'  => class_exists( 'WooCommerce' ),
				'pattern' => '#^/wc/store/v1/(?:batch|cart|checkout|order)(?:/|$)#',
			),
			'contact-form-7' => array(
				'active'  => defined( 'WPCF7_VERSION' ),
				'pattern' => '#^/contact-form-7/v1/contact-forms/[^/]+/(?:feedback|feedback/schema|refill)$#',
			),
		);
	}

	/**
	 * WooCommerce Store API route keys that the REST server does not list on this request.
	 *
	 * WooCommerce only registers its Store API (/wc/store/v1) outside wp-admin, so
	 * the settings screen cannot see those routes through rest_get_server(). This
	 * asks WooCommerce for the same route paths from its own route map, without
	 * registering anything, so the screen can show them and keep them on save.
	 *
	 * @since 1.1.5
	 *
	 * @return string[] Raw route keys, empty when WooCommerce is absent, the routes
	 *                  are already registered, or the route map cannot be read.
	 */
	public function get_unlisted_store_api_routes() {
		if( ! class_exists( 'WooCommerce' )
			|| ! class_exists( '\Automattic\WooCommerce\StoreApi\StoreApi' )
			|| ! class_exists( '\Automattic\WooCommerce\StoreApi\RoutesController' ) ) {
			return array();
		}

		$registered = array_keys( rest_get_server()->get_routes() );
		if( ! empty( preg_grep( '#^/wc/store/v1/#', $registered ) ) ) {
			return array();
		}

		try {
			$controller = \Automattic\WooCommerce\StoreApi\StoreApi::container()->get( \Automattic\WooCommerce\StoreApi\RoutesController::class );
			$routes     = array_keys( (array) $controller->get_all_routes( 'v1' ) );
		} catch ( \Throwable $e ) {
			// The route map is WooCommerce internals; if it changes shape, show nothing extra.
			return array();
		}

		return array_values( array_filter( $routes, function ( $route ) {
			return is_string( $route ) && 0 === strpos( $route, '/wc/store/v1/' );
		} ) );
	}

	/**
	 * REST route keys an active integration needs.
	 *
	 * @since 1.1.5
	 *
	 * @param string $key Integration key from get_guest_integrations().
	 * @return string[] Raw route keys, as rest_get_server()->get_routes() returns them,
	 *                  plus the Store API keys WooCommerce does not register in wp-admin.
	 */
	public function get_integration_routes( $key ) {
		$integrations = $this->get_guest_integrations();
		if( ! isset( $integrations[ $key ] ) || ! $integrations[ $key ]['active'] ) {
			return array();
		}
		$routes = array_unique( array_merge(
			array_keys( rest_get_server()->get_routes() ),
			$this->get_unlisted_store_api_routes()
		) );
		return array_values( preg_grep( $integrations[ $key ]['pattern'], $routes ) );
	}

	/**
	 * Integration routes that are not on the allow list yet.
	 *
	 * Uses the same stored form as the settings checkboxes (esc_html() of the
	 * route key), so a route ticked by hand counts as allowed here too.
	 *
	 * @since 1.1.5
	 *
	 * @param string $key Integration key from get_guest_integrations().
	 * @return string[] Raw route keys still blocked for logged out visitors.
	 */
	public function get_blocked_integration_routes( $key ) {
		$allowed = $this->get_route_whitelist_option();
		$blocked = array();
		foreach( $this->get_integration_routes( $key ) as $route ) {
			if( ! in_array( esc_html( $route ), $allowed, true ) ) {
				$blocked[] = $route;
			}
		}
		return $blocked;
	}

	/**
	 * admin-post handler: add one integration's blocked routes to the allow list.
	 *
	 * @since 1.1.5
	 *
	 * @return void Redirects back to the settings screen.
	 */
	public function admin_allow_integration_routes() {
		if( ! current_user_can( $this->settings['permission'] ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to change these settings.', 'turn-off-rest-api' ), '', array( 'response' => 403 ) );
		}

		$key          = isset( $_POST['tora_integration'] ) ? sanitize_key( wp_unslash( $_POST['tora_integration'] ) ) : '';
		$integrations = $this->get_guest_integrations();
		if( ! isset( $integrations[ $key ] ) ) {
			wp_die( esc_html__( 'Unknown request.', 'turn-off-rest-api' ), '', array( 'response' => 400 ) );
		}

		check_admin_referer( 'tora_allow_integration_' . $key, '_tora_nonce_' . $key );

		$blocked = $this->get_blocked_integration_routes( $key );
		if( ! empty( $blocked ) ) {
			$allowed = $this->get_route_whitelist_option();
			foreach( $blocked as $route ) {
				$allowed[] = esc_html( $route );
			}
			update_option( 'tora_allowed_route_list', array_values( array_unique( $allowed ) ) );
		}

		wp_safe_redirect( add_query_arg(
			array(
				'page'         => $this->settings['menu_slug'],
				'tora-allowed' => $key,
			),
			admin_url( 'options-general.php' )
		) );
		exit;
	}

	/**
	 * Whether the REST API is open to logged out visitors because the
	 * tora_grant_rest_api filter says so. Runs the same filter the blocker runs,
	 * with the logged out value, so admin screens report the real visitor state.
	 *
	 * @since 1.1.5
	 *
	 * @return bool True when logged out visitors are granted full REST access.
	 */
	public function is_rest_granted_for_guests() {
		return $this->grant_rest_api( false );
	}

	/*
	*  disable_api_request
	*
	*  @type	function
	*  @date	03/23/17
	*  @since	1.0.0
	*/
	public function disable_api_request() {
		// Hide REST API discovery links and headers from the page source (optional, on by default).
		// remove_action() only works when it names the priority core used in add_action():
		// core sends the REST Link header on template_redirect at priority 11, and prints the
		// oEmbed discovery links on wp_head at priority 4 (the priority 10 copy is back-compat).
		// The default priority removals are kept so nothing that already worked changes.
		if( $this->get_settings( 'hide_discovery' ) ) {
			remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
			remove_action( 'wp_head', 'rest_output_link_wp_head' );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 4 );
			remove_action( 'template_redirect', 'rest_output_link_header' );
			remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		}

		// Allowed routes checkpoint. The plugin requires WordPress 4.7 or newer, where
		// rest_authentication_errors is the supported way to refuse a REST API request.
		add_filter( 'rest_authentication_errors', array( $this, 'allowed_routes_checkpoint') );

		// A batch request runs each of its sub-requests without the authentication check
		// above, so an allowed batch route could reach routes that are not allowed. The
		// whole batch is refused before it runs when any of its paths is not allowed, and
		// every sub-request is checked again as it is dispatched.
		add_filter( 'rest_request_before_callbacks', array( $this, 'batch_request_checkpoint' ), 10, 3 );
		add_filter( 'rest_pre_dispatch', array( $this, 'batch_subrequest_checkpoint' ), 10, 3 );

		// With ?_embed, WordPress dispatches the linked resources (author, featured media,
		// terms) after the outer request has been answered, again without the authentication
		// check above. Those embedded requests are checked against the allow list too. Both
		// run at the latest priority so callbacks added earlier on these filters do not
		// change the result.
		add_filter( 'rest_post_dispatch', array( $this, 'mark_outer_request_dispatched' ), PHP_INT_MAX, 3 );
		add_filter( 'rest_pre_dispatch', array( $this, 'embedded_request_checkpoint' ), PHP_INT_MAX, 3 );
	}

	/**
	 * Note that the outer REST request of this HTTP request has been answered.
	 *
	 * WordPress applies rest_post_dispatch to the outer request after its callback has
	 * run and before it builds the embedded resources for ?_embed. REST requests that an
	 * allowed route's callback makes internally run before this point, so they keep
	 * working as before. REST requests made after this point, for example by a callback
	 * on rest_pre_serve_request, are checked against the allow list for logged out
	 * visitors. So are internal requests that follow a preload of the very same route
	 * inside a callback, since rest_preload_api_request() also applies this filter.
	 *
	 * @since 1.1.6
	 *
	 * @param WP_HTTP_Response|mixed $response Result to send to the client.
	 * @param WP_REST_Server         $server   Server instance.
	 * @param WP_REST_Request        $request  Request used to generate the response.
	 * @return WP_HTTP_Response|mixed The unchanged $response.
	 */
	public function mark_outer_request_dispatched( $response, $server, $request ) {
		if( $this->outer_request_dispatched || ! ( $request instanceof WP_REST_Request ) ) {
			return $response;
		}

		// Only a REST API HTTP request has an outer request. PHP code that calls the REST
		// API while a normal page renders never sets the flag.
		$outer_route = $this->get_current_route();
		if( empty( $outer_route ) || ! is_string( $outer_route ) ) {
			return $response;
		}

		if( 0 === strcasecmp( $this->normalize_sub_route( $request->get_route() ), $this->normalize_sub_route( $outer_route ) ) ) {
			$this->outer_request_dispatched = true;
		}

		return $response;
	}

	/**
	 * Refuse an embedded request whose route is not on the allow list.
	 *
	 * A ?_embed request asks WordPress to include linked resources in the response. Core
	 * dispatches each of them internally through rest_pre_dispatch, never through
	 * rest_authentication_errors, so allowing /wp/v2/posts used to hand logged out
	 * visitors the embedded author, media and term data even when those routes were not
	 * allowed. A refused resource shows up in _embedded as an error object with status
	 * 401, the same shape core uses for any embedded resource the visitor cannot read.
	 *
	 * Batch requests are left to the batch checks, which already cover every request
	 * dispatched inside them.
	 *
	 * @since 1.1.6
	 *
	 * @param mixed           $result  Response to replace the requested one with, or null.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed The unchanged $result, or a 401 WP_REST_Response for a refused request.
	 */
	public function embedded_request_checkpoint( $result, $server, $request ) {
		// Nothing has been embedded yet, an earlier callback already refused the request,
		// or this visitor has full access.
		if( ! $this->outer_request_dispatched || is_wp_error( $result ) || ! ( $request instanceof WP_REST_Request ) || $this->grant_rest_api() ) {
			return $result;
		}

		$outer_route = $this->get_current_route();
		if( empty( $outer_route ) || ! is_string( $outer_route ) || $this->is_batch_route( $outer_route ) ) {
			return $result;
		}

		// An allowed route keeps whatever earlier callbacks returned for it. A route that
		// is not allowed is refused even when another callback supplied a response, the
		// same way the outer request is refused before any callback runs.
		if( $this->is_allowed( $this->normalize_sub_route( $request->get_route() ) ) ) {
			return $result;
		}

		return $this->refusal_response();
	}

	/**
	 * Build the 401 refusal as a response object, for filters that cannot take a WP_Error.
	 *
	 * @since 1.1.6
	 *
	 * @return WP_REST_Response The refusal, with the status from rest_authorization_required_code().
	 */
	private function refusal_response() {
		$error = $this->return_error( null );
		if( function_exists( 'rest_convert_error_to_response' ) ) {
			return rest_convert_error_to_response( $error );
		}

		// WordPress 5.6 and older do not have the helper above (added in 5.7).
		return new WP_REST_Response(
			array(
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
				'data'    => $error->get_error_data(),
			),
			rest_authorization_required_code()
		);
	}

	/**
	 * Whether a route is a batch route (core /batch/v1, or a batch route such as /wc/store/v1/batch).
	 *
	 * @since 1.1.5
	 *
	 * @param string $route Route without a trailing slash.
	 * @return bool True for a batch route.
	 */
	private function is_batch_route( $route ) {
		return '' !== $route && (bool) preg_match( '#(?:^|/)batch(?:/v1)?$#i', $route );
	}

	/**
	 * Normalize a sub-request route for the allow list check.
	 *
	 * The REST index is kept as '/' (an empty route is the index too), so it is checked
	 * against the allow list instead of being skipped. Every other route loses its
	 * trailing slash, matching get_current_route().
	 *
	 * @since 1.1.5
	 *
	 * @param string $route Raw route.
	 * @return string Normalized route, never empty.
	 */
	private function normalize_sub_route( $route ) {
		$route = (string) $route;
		if( '' === $route || '/' === $route ) {
			return '/';
		}
		$route = untrailingslashit( $route );
		return '' === $route ? '/' : $route;
	}

	/**
	 * Refuse a whole batch request when any of its paths is not on the allow list.
	 *
	 * WordPress validates and sanitizes the parameters of every sub-request before any of
	 * them is dispatched, so the per sub-request check in batch_subrequest_checkpoint()
	 * runs too late to keep a logged out visitor from reaching those callbacks on routes
	 * that are not allowed. This runs on the outer batch request, before its callback
	 * starts the batch, and refuses it as a whole.
	 *
	 * @since 1.1.5
	 *
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send, usually null.
	 * @param array                                            $handler  Route handler used for the request.
	 * @param WP_REST_Request                                  $request  Request used to generate the response.
	 * @return mixed The unchanged $response, or a 401 WP_Error for a refused batch.
	 */
	public function batch_request_checkpoint( $response, $handler, $request ) {
		// An earlier step already failed, or this visitor has full access.
		if( is_wp_error( $response ) || ! ( $request instanceof WP_REST_Request ) || $this->grant_rest_api() ) {
			return $response;
		}

		// Only the outer batch request of this HTTP request is checked here.
		$outer_route = $this->get_current_route();
		$route       = untrailingslashit( (string) $request->get_route() );
		if( ! $this->is_batch_route( $outer_route ) || 0 !== strcasecmp( $route, $outer_route ) ) {
			return $response;
		}

		$items = $request->get_param( 'requests' );
		if( ! is_array( $items ) ) {
			return $response;
		}

		foreach( $items as $item ) {
			// Core reads each path with wp_parse_url() and uses only its path part.
			$path  = ( is_array( $item ) && isset( $item['path'] ) && is_string( $item['path'] ) ) ? $item['path'] : '';
			$parts = '' !== $path ? wp_parse_url( $path ) : false;
			$sub   = ( is_array( $parts ) && isset( $parts['path'] ) ) ? $this->normalize_sub_route( $parts['path'] ) : '';

			// An unreadable path, or a route that is not allowed, refuses the whole batch.
			if( '' === $sub || ! $this->is_allowed( $sub ) ) {
				return $this->return_error( null );
			}
		}

		return $response;
	}

	/**
	 * Refuse a batch sub-request whose route is not on the allow list.
	 *
	 * WordPress runs the sub-requests of a batch (core /batch/v1, and batch routes that
	 * call the core batch server, such as the WooCommerce Store API /wc/store/v1/batch)
	 * through rest_pre_dispatch but never through rest_authentication_errors. Without this
	 * check, allowing a batch route would let logged out visitors reach every route that
	 * accepts batching. Only sub-requests of a batch are checked: the outer request was
	 * already checked by allowed_routes_checkpoint(), and REST requests made internally by
	 * PHP while a normal page renders are left alone.
	 *
	 * @since 1.1.5
	 *
	 * @param mixed           $result  Response to replace the requested one with, or null.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed The unchanged $result, or a 401 WP_REST_Response for a refused sub-request.
	 */
	public function batch_subrequest_checkpoint( $result, $server, $request ) {
		// Another callback already answered, or this visitor has full access.
		if( null !== $result || ! ( $request instanceof WP_REST_Request ) || $this->grant_rest_api() ) {
			return $result;
		}

		// Only act inside a batch request: the outer route must be a batch route.
		$outer_route = $this->get_current_route();
		if( ! $this->is_batch_route( $outer_route ) ) {
			return $result;
		}

		// The outer batch request itself passes through; its own check already ran. The
		// REST index stays '/' so it is checked like any other route.
		$route = $this->normalize_sub_route( $request->get_route() );
		if( 0 === strcasecmp( $route, $outer_route ) || $this->is_allowed( $route ) ) {
			return $result;
		}

		// Core expects a response object here: it reads the status of each batch item,
		// so a bare WP_Error would stop the whole batch with a fatal error.
		return $this->refusal_response();
	}

	/*
	*  get_settings
	*
	*  Plugin options merged with defaults. Pass a key for a single value.
	*
	*  @type	function
	*  @since	1.1.0
	*/
	public function get_settings( $key = '' ) {
		$defaults = array(
			'hide_discovery' => '1',
		);
		$settings = wp_parse_args( (array) get_option( 'tora_settings', array() ), $defaults );
		if( '' !== $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		}
		return $settings;
	}

	/*
	*  add_site_health_test
	*
	*  Registers a Site Health test so the REST restriction is explained, not mistaken for a fault.
	*
	*  @type	function
	*  @since	1.1.0
	*/
	public function add_site_health_test( $tests ) {
		$tests['direct']['tora_rest_restricted'] = array(
			'label' => __( 'REST API access', 'turn-off-rest-api' ),
			'test'  => array( $this, 'site_health_rest_test' ),
		);
		return $tests;
	}

	/*
	*  site_health_rest_test
	*
	*  @type	function
	*  @since	1.1.0
	*/
	public function site_health_rest_test() {
		// A tora_grant_rest_api filter can open the REST API to everyone. Report
		// that honestly instead of claiming the restriction is in place.
		if( $this->is_rest_granted_for_guests() ) {
			return array(
				'label'       => __( 'The REST API is open to logged out visitors', 'turn-off-rest-api' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'Security', 'turn-off-rest-api' ),
					'color' => 'orange',
				),
				'description' => '<p>' . esc_html__( 'Turn Off REST API is active, but code on this site uses the tora_grant_rest_api filter to grant REST API access to logged out visitors, so no route is blocked. If that is not intended, remove the filter from your theme or custom plugin.', 'turn-off-rest-api' ) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%1$s">%2$s</a></p>',
					esc_url( admin_url( 'options-general.php?page=' . $this->settings['menu_slug'] ) ),
					esc_html__( 'Review REST API settings', 'turn-off-rest-api' )
				),
				'test'        => 'tora_rest_restricted',
			);
		}

		return array(
			'label'       => __( 'The REST API is restricted to logged in users', 'turn-off-rest-api' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Security', 'turn-off-rest-api' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html__( 'Turn Off REST API is intentionally blocking the WordPress REST API for logged out visitors. Logged in users and the block editor keep full access, so this is expected and your site is working normally. You can allow specific routes under Settings, Turn Off REST API.', 'turn-off-rest-api' ) . '</p>',
			'actions'     => sprintf(
				'<p><a href="%1$s">%2$s</a></p>',
				esc_url( admin_url( 'options-general.php?page=' . $this->settings['menu_slug'] ) ),
				esc_html__( 'Review REST API settings', 'turn-off-rest-api' )
			),
			'test'        => 'tora_rest_restricted',
		);
	}

	/*
	*  allowed_routes_checkpoint
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/
	public function allowed_routes_checkpoint( $access ) {
		// Return current value of $access and skip all plugin functionality.
		if( $this->grant_rest_api() ) {
			return $access;
		}

		$current_route = $this->get_current_route();
		if( !empty( $current_route ) && !$this->is_allowed( $current_route ) ) {
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
	private function grant_rest_api( $is_logged_in = null ) {
		// The blocker passes nothing and gets the current request's login state.
		// Admin screens pass false (since 1.1.5) to ask what a logged out visitor gets.
		if( null === $is_logged_in ) {
			$is_logged_in = is_user_logged_in();
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'tora' is this plugin's established public filter prefix since 1.0.2.
		return (bool) apply_filters( 'tora_grant_rest_api', (bool) $is_logged_in );
	}

	/**
	 * Get the route of the current REST request, normalized the way core serves it.
	 *
	 * Returns an empty string when the request carries no REST route (a normal page
	 * load), so callers can skip their checks there. This follows rest_api_loaded():
	 * core ignores an empty rest_route, trims trailing slashes and backslashes from any
	 * other value, and serves the REST index when nothing is left. Before 1.1.6 a value
	 * such as `//`, `%5C` or `0/` trimmed to an empty route here and skipped the allow
	 * list while core served the index. Such a value is now returned as '/', so the
	 * index is checked like any other route.
	 *
	 * @since 1.0.2
	 * @since 1.1.6 A route that trims to nothing is returned as '/', as core serves it.
	 *
	 * @return string The current route, '/' for the index, or '' when there is none.
	 */
	private function get_current_route() {
		// Guard against an unset global or missing query var (PHP 8+ would emit an
		// "undefined array key" warning when the REST route is not present).
		$rest_route = ( isset( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->query_vars['rest_route'] ) )
			? $GLOBALS['wp']->query_vars['rest_route']
			: '';

		// Core does not serve the REST API for an empty value or a non-string one.
		if( empty( $rest_route ) || ! is_string( $rest_route ) ) {
			return '';
		}

		// Same steps and the same empty() test as rest_api_loaded(), so '0' after
		// trimming is the index here exactly as it is in core.
		$route = untrailingslashit( $rest_route );
		return empty( $route ) ? '/' : $route;
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
		$this->settings['page_hook'] = add_options_page(
			esc_html__( 'Turn Off REST API / Security Settings', 'turn-off-rest-api' ),
			esc_html__( 'Turn Off REST API', 'turn-off-rest-api' ),
			$this->settings['permission'], // capability
			$this->settings['menu_slug'],  // menu slug
			array( $this, 'admin_settings_page')
		);

		add_filter( 'plugin_action_links_' . $this->settings['basename'], array( $this, 'admin_settings_url') );
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

		// Confirmation after the one-click allow redirect. Display only, no state change.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice selector, validated against a fixed list.
		$allowed_key = isset( $_GET['tora-allowed'] ) ? sanitize_key( wp_unslash( $_GET['tora-allowed'] ) ) : '';
		// Only confirm what is true right now: a bookmarked or hand-typed URL must not claim
		// routes were allowed while some of them are still blocked.
		$integrations = $this->get_guest_integrations();
		if( '' !== $allowed_key && ( empty( $integrations[ $allowed_key ]['active'] ) || ! empty( $this->get_blocked_integration_routes( $allowed_key ) ) ) ) {
			$allowed_key = '';
		}
		if( 'woocommerce' === $allowed_key ) {
			add_settings_error( 'turn-off-rest-api-notices', 'tora_allowed_woocommerce', esc_html__( 'WooCommerce cart and checkout routes are now allowed for logged out visitors. All other routes stay blocked.', 'turn-off-rest-api' ), 'updated' );
		} elseif( 'contact-form-7' === $allowed_key ) {
			add_settings_error( 'turn-off-rest-api-notices', 'tora_allowed_contact_form_7', esc_html__( 'Contact Form 7 form routes are now allowed for logged out visitors. All other routes stay blocked.', 'turn-off-rest-api' ), 'updated' );
		}

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
		// Check user capability.
		if( !current_user_can( $this->settings['permission'] ) ) {
			return;
		}

		// Security token.
		if( !( isset( $_POST['_wpnonce'] ) && check_admin_referer( 'turn_off_rest_api_admin_nonce' ) ) ) {
			return;
		}

		// Save plugin options.
		$settings = $this->get_settings();
		$settings['hide_discovery'] = isset( $_POST['tora_hide_discovery'] ) ? '1' : '0';
		update_option( 'tora_settings', $settings );

		// Get all routes.
		// Routes are stored html-encoded on purpose: is_allowed() htmlspecialchars_decode()s
		// each pattern before matching, and the regex syntax (?P<id>...) must survive intact.
		// The form always posts rest_api_routes[] as a list of strings. Anything else (a scalar,
		// or nested arrays inside the list) can only come from a crafted request and is treated
		// as not submitted, so it can never reach array_map() / esc_html() with the wrong type.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- type-checked below; esc_html() is the intended sanitizer for these route patterns.
		$posted_routes   = isset( $_POST['rest_api_routes'] ) ? wp_unslash( $_POST['rest_api_routes'] ) : null;
		$rest_api_routes = is_array( $posted_routes ) ? array_map( 'esc_html', array_values( array_filter( $posted_routes, 'is_string' ) ) ) : null;

		// Restore default - reset.
		if( isset( $_POST['reset'] ) ) {
			delete_option( 'tora_allowed_route_list' );
			add_settings_error( 'turn-off-rest-api-notices', esc_attr( 'settings_updated' ), esc_html__( 'Default settings restored.', 'turn-off-rest-api' ), 'updated' );
			return;
		}

		// Saved with no route ticked: an ordinary save that blocks every route.
		if( empty( $rest_api_routes ) ) {
			delete_option( 'tora_allowed_route_list' );
			add_settings_error( 'turn-off-rest-api-notices', esc_attr( 'settings_updated' ), esc_html__( 'Settings saved.', 'turn-off-rest-api' ), 'updated' );
			return;
		}

		// Save.
		update_option( 'tora_allowed_route_list', $rest_api_routes );
		add_settings_error( 'turn-off-rest-api-notices', esc_attr( 'settings_updated' ), esc_html__( 'Settings saved.', 'turn-off-rest-api' ), 'updated' );
	}

	/*
	*  admin_page_styles_scripts
	*
	*  @type	function
	*  @date	09/27/17
	*  @since	1.0.2
	*/
	public function admin_page_styles_scripts( $hook ) {
		// Only load on this plugin's own settings screen.
		if( ! isset( $this->settings['page_hook'] ) || $hook !== $this->settings['page_hook'] ) {
			return;
		}

		// Style (depends on dashicons for the header and status glyphs).
		wp_enqueue_style( 'tora-admin', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array( 'dashicons' ), $this->settings['version'] );

		// Script.
		wp_enqueue_script( 'tora-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array( 'jquery' ), $this->settings['version'], true );
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
		$error_message = sprintf(
			/* translators: %s: site name */
			esc_html__( 'Only authenticated users are allowed to access %s WP REST API.', 'turn-off-rest-api' ),
			esc_html( $site_name )
		);
		if( is_wp_error( $access ) ) {
			// An earlier check already refused this request. Keep that refusal and add this
			// plugin's reason to it. WP_Error::add() returns nothing, so the error object itself
			// must be returned: returning the add() result would hand core null and let the
			// request through.
			$access->add( 'disabled', $error_message, array( 'status' => rest_authorization_required_code() ) );
			return $access;
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
	if( ! isset($turn_off_rest_api) ) {
		$turn_off_rest_api = new turn_off_rest_api();
		$turn_off_rest_api->initialize();
	}

	return $turn_off_rest_api;
}

// initialize.
turn_off_rest_api();


endif; // class_exists check.