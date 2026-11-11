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

	// WooCommerce does not register its Store API in wp-admin. List those routes
	// anyway (as their own group) so they can be ticked here and an allowed route
	// is posted back on save instead of being dropped from the allow list.
	$store_api_routes = turn_off_rest_api()->get_unlisted_store_api_routes();
	if( ! empty( $store_api_routes ) ) {
		$all_server_namespaces[] = 'wc/store/v1';
		$all_server_routes[]     = '/wc/store/v1';
		$all_server_routes       = array_merge( $all_server_routes, $store_api_routes );
	}

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

	<?php
	// Real visitor state: the tora_grant_rest_api filter can open everything.
	$tora_guest_granted = turn_off_rest_api()->is_rest_granted_for_guests();

	// Guest features of other plugins that break while their routes are blocked.
	// Nothing is blocked when the filter grants access, so no callout then.
	$tora_integration_callouts = array();
	if( ! $tora_guest_granted ) {
		$tora_integration_copy = array(
			'woocommerce'    => array(
				'title'  => __( 'WooCommerce checkout is blocked for logged out shoppers.', 'turn-off-rest-api' ),
				'body'   => __( 'The Cart and Checkout blocks use the WooCommerce Store API (/wc/store/v1). While its cart and checkout routes are blocked, guests cannot add to cart, change the cart, or place an order. Allowing them keeps every other route blocked.', 'turn-off-rest-api' ),
				'button' => __( 'Allow WooCommerce checkout routes', 'turn-off-rest-api' ),
			),
			'contact-form-7' => array(
				'title'  => __( 'Contact Form 7 forms cannot be sent by logged out visitors.', 'turn-off-rest-api' ),
				'body'   => __( 'Contact Form 7 sends each form through its REST API feedback route. While those routes are blocked, visitors get an error when they submit a form. Allowing them keeps every other route blocked.', 'turn-off-rest-api' ),
				'button' => __( 'Allow Contact Form 7 form routes', 'turn-off-rest-api' ),
			),
		);
		foreach( $tora_integration_copy as $tora_key => $tora_copy ) {
			$tora_blocked = turn_off_rest_api()->get_blocked_integration_routes( $tora_key );
			if( ! empty( $tora_blocked ) ) {
				$tora_integration_callouts[ $tora_key ] = array_merge( $tora_copy, array( 'routes' => $tora_blocked ) );
			}
		}
	}
	?>

	<div class="tora-layout">
	<div class="tora-main">

	<div class="tora-callout">
		<p>
			<strong><?php esc_html_e( 'Unauthenticated access to the WP REST API is disabled by default.', 'turn-off-rest-api' ); ?></strong><br />
			<?php esc_html_e( 'Logged in users are unaffected. To keep a specific route or namespace public, check it below and save.', 'turn-off-rest-api' ); ?>
		</p>
	</div>

	<?php foreach( $tora_integration_callouts as $tora_key => $tora_callout ) : ?>
		<div class="tora-callout tora-callout--warning">
			<p>
				<strong><?php echo esc_html( $tora_callout['title'] ); ?></strong><br />
				<?php echo esc_html( $tora_callout['body'] ); ?>
			</p>
			<details class="tora-callout__routes">
				<summary>
					<?php
					/* translators: %d: number of REST API routes. */
					echo esc_html( sprintf( _n( 'Show the %d route this adds', 'Show the %d routes this adds', count( $tora_callout['routes'] ), 'turn-off-rest-api' ), count( $tora_callout['routes'] ) ) );
					?>
				</summary>
				<ul>
					<?php foreach( $tora_callout['routes'] as $tora_route ) : ?>
						<li><code><?php echo esc_html( $tora_route ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			</details>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tora-callout__form">
				<input type="hidden" name="action" value="tora_allow_integration" />
				<input type="hidden" name="tora_integration" value="<?php echo esc_attr( $tora_key ); ?>" />
				<?php wp_nonce_field( 'tora_allow_integration_' . $tora_key, '_tora_nonce_' . $tora_key ); ?>
				<?php submit_button( $tora_callout['button'], 'primary', 'tora_allow_submit', false, array( 'id' => 'tora-allow-' . $tora_key ) ); ?>
			</form>
		</div>
	<?php endforeach; ?>

	<form method="post" action="" id="tora-form">
		<?php wp_nonce_field( 'turn_off_rest_api_admin_nonce' ); ?>

		<div class="tora-settings-section">
			<h2><?php esc_html_e( 'Options', 'turn-off-rest-api' ); ?></h2>
			<?php if( $tora_guest_granted ) : ?>
				<p class="tora-status tora-status--warning">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<?php esc_html_e( 'The REST API is open to logged out visitors. Code on this site uses the tora_grant_rest_api filter to grant access, so no route below is blocked.', 'turn-off-rest-api' ); ?>
				</p>
			<?php else : ?>
				<p class="tora-status">
					<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'The REST API is restricted to logged in users. Your block editor and logged in users are unaffected.', 'turn-off-rest-api' ); ?>
				</p>
			<?php endif; ?>
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