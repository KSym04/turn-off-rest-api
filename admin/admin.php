<?php
/**
 * Admin Page Settings
 *
 * @package Turn Off REST API
 * @since 1.0.2
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * turn_off_rest_api_list_route_checkboxes
 * 
 * @package Turn Off REST API
 * @since 1.0.0
 */
function turn_off_rest_api_list_route_checkboxes() {
	// Rest server.
	$wp_rest_server        = rest_get_server();
	$all_server_namespaces = $wp_rest_server->get_namespaces();
	$all_server_routes     = array_keys( $wp_rest_server->get_routes() );
	$allowed_list_routes   = is_array( get_option( 'tora_allowed_route_list' ) ) ? get_option( 'tora_allowed_route_list' ) : array();

	// Markup allowed through wp_kses at output time.
	$allowed_html = array(
		'h3'     => array(),
		'ul'     => array( 'id' => array() ),
		'li'     => array(),
		'label'  => array( 'for' => array() ),
		'strong' => array(),
		'input'  => array(
			'name'         => array(),
			'value'        => array(),
			'type'         => array(),
			'id'           => array(),
			'class'        => array(),
			'data-counter' => array(),
			'checked'      => array(),
		),
	);

	$loop_counter = 0;
	$output       = '';

	foreach( $all_server_routes as $route ) {

		$is_route_label = in_array( ltrim( $route, '/' ), $all_server_namespaces, true );
		$checked_prop   = turn_off_rest_api_get_route_checkbox_prop( $route, $allowed_list_routes );
		$counter        = (int) $loop_counter;
		$route_attr     = esc_attr( $route );

		if( $is_route_label || '/' === $route ) {

			if( 0 !== $loop_counter ) {
				$output .= '</ul>';
			}

			$route_for_display = ( '/' === $route )
				? '<strong>' . esc_html__( 'WP REST API root', 'turn-off-rest-api' ) . '</strong>'
				: esc_html( $route );

			$output .= "<h3><label for='parent-check-{$counter}'>"
				. "<input name='rest_api_routes[]' value='{$route_attr}' type='checkbox' id='parent-check-{$counter}' data-counter='{$counter}' {$checked_prop} /> "
				. "{$route_for_display}</label></h3><ul id='child-check-{$counter}'>";

			// Main WP API Root.
			if( '/' === $route ) {
				$output .= '<li>' . esc_html__( 'Root URL:', 'turn-off-rest-api' ) . ' <strong>' . esc_url( rest_url() ) . '</strong></li>';
			}
		} else {
			$output .= "<li><label for='child-checkbox-{$counter}'>"
				. "<input name='rest_api_routes[]' value='{$route_attr}' type='checkbox' class='child-checkbox-{$counter}' id='child-checkbox-{$counter}' {$checked_prop} /> "
				. esc_html( $route ) . '</label></li>';
		}

		$loop_counter++;
	}

	$output .= '</ul>';

	echo wp_kses( $output, $allowed_html );
}

/**
 * turn_off_rest_api_get_route_checkbox_prop
 * 
 * @package Turn Off REST API
 * @since 1.0.0
 */
function turn_off_rest_api_get_route_checkbox_prop( $route, $allowed_list_routes ) {

	$is_route_checked = in_array( esc_html( $route ), $allowed_list_routes, true );
	return checked( $is_route_checked, true, false );

} ?>

<div class="wrap tora-wrap">
	<h1 class="tora-page-title">
		<span class="dashicons dashicons-shield-alt tora-page-icon" aria-hidden="true"></span>
		<span class="tora-wordmark"><?php esc_html_e( 'Turn Off REST API', 'turn-off-rest-api' ); ?></span>
		<span class="tora-page-subtitle"><?php esc_html_e( 'Security Settings', 'turn-off-rest-api' ); ?></span>
	</h1>

	<?php settings_errors( 'turn-off-rest-api-notices' ); ?>

	<div class="tora-layout">
	<div class="tora-main">

	<div class="tora-callout">
		<p>
			<strong><?php esc_html_e( 'Unauthenticated access to the WP REST API is disabled by default.', 'turn-off-rest-api' ); ?></strong><br />
			<?php esc_html_e( 'Logged in users are unaffected. To keep a specific route or namespace public, check it below and save.', 'turn-off-rest-api' ); ?>
		</p>
	</div>

	<form method="post" action="" id="tora-form">
		<?php wp_nonce_field( 'turn_off_rest_api_admin_nonce' ); ?>

		<div class="tora-settings-section">
			<h2><?php esc_html_e( 'Options', 'turn-off-rest-api' ); ?></h2>
			<p class="tora-status">
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<?php esc_html_e( 'The REST API is restricted to logged in users. Your block editor and logged in users are unaffected.', 'turn-off-rest-api' ); ?>
			</p>
			<p>
				<label>
					<input type="checkbox" name="tora_hide_discovery" value="1" <?php checked( turn_off_rest_api()->get_settings( 'hide_discovery' ), '1' ); ?> />
					<?php esc_html_e( 'Hide REST API discovery links and headers from your page source.', 'turn-off-rest-api' ); ?>
				</label>
			</p>
		</div>

		<div class="tora-settings-section">
			<h2><?php esc_html_e( 'Allowed REST API Routes', 'turn-off-rest-api' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Everything is blocked for logged out visitors by default. Check a route or namespace to keep it public.', 'turn-off-rest-api' ); ?></p>
			<div id="tora-checkbox-list"><?php turn_off_rest_api_list_route_checkboxes(); ?></div>
		</div>

		<div class="tora-action-box__row">
			<?php submit_button( esc_html__( 'Save Changes', 'turn-off-rest-api' ), 'primary', 'submit', false ); ?>
			<?php submit_button( esc_html__( 'Reset', 'turn-off-rest-api' ), 'secondary', 'reset', false, array( 'onclick' => "return confirm('" . esc_js( __( 'Are you sure you want to restore default settings?', 'turn-off-rest-api' ) ) . "');" ) ); ?>
		</div>
	</form>
	</div><!-- .tora-main -->

	<aside class="tora-aside">
		<div class="tora-box tora-more">
			<h2><?php esc_html_e( 'More on DopeThemes', 'turn-off-rest-api' ); ?></h2>
			<p><?php esc_html_e( 'Tutorials, code snippets, and more free plugins for WordPress and WooCommerce.', 'turn-off-rest-api' ); ?></p>
			<a class="button" href="https://www.dopethemes.com/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Visit DopeThemes', 'turn-off-rest-api' ); ?>
				<span class="dashicons dashicons-external" aria-hidden="true"></span>
			</a>
		</div>
	</aside>
	</div><!-- .tora-layout -->
</div>