<?php
/**
 * Admin Page Settings
 *
 * @package Turn Off REST API
 * @since 1.0.2
 */

if( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/* create route list with checkbox */
function turn_off_rest_api_list_route_checkboxes() {

	// rest server
	$wp_rest_server = rest_get_server();
	$all_server_namespaces = $wp_rest_server->get_namespaces();
	$all_server_routes = array_keys( $wp_rest_server->get_routes() );
	$allowed_list_routes = is_array( get_option( 'tora_allowed_route_list' ) ) ? get_option( 'tora_allowed_route_list' ) : array();

	// vars
	$loop_counter = 0;
	$current_namespace = '';

	foreach( $all_server_routes as $route ) {

		$is_route_label = in_array( ltrim( $route, "/" ), $all_server_namespaces );
		$checked_prop = turn_off_rest_api_get_route_checkbox_prop( $route, $allowed_list_routes );

		if( $is_route_label || "/" == $route ) {

			$current_namespace = $route;

			if( 0 != $loop_counter ) {
				echo "</ul>";
			}

			$route_for_display = ( "/" == $route ) ? sprintf( '<strong>%s</strong>', __( 'WP REST API root', 'turn-off-rest-api' ) ) : esc_html( $route );

			// build parent checkbox
			echo "
				<h3>
					<label for='parent-check-$loop_counter'>
						<input name='rest_api_routes[]'
							value='$route'
							type='checkbox'
							id='parent-check-$loop_counter'
							data-counter='$loop_counter'
							$checked_prop />
								{$route_for_display}
					</label>
				</h3>
					<ul id='child-check-$loop_counter'>
				";

			// main WP API Root
			if( "/" == $route ) {
				printf( '<li>%1$s <strong>%2$s</strong></li>', __( 'Root URL: ', 'turn-off-rest-api' ), rest_url() );
			}

		} else {
			// build child checkbox
			echo "
				<li>
					<label for='child-checkbox-{$loop_counter}'>
						<input name='rest_api_routes[]'
							value='$route'
							type='checkbox'
							class='child-checkbox-{$loop_counter}'
							id='child-checkbox-{$loop_counter}'
							$checked_prop />"
								. esc_html( $route ) . "
					</label>
				</li>";
		}

		$loop_counter++;
	}

	echo "</ul>";
}

/* checkbox properties checkings */
function turn_off_rest_api_get_route_checkbox_prop( $route, $allowed_list_routes ) {

	$is_route_checked = in_array( esc_html( $route ), $allowed_list_routes, true );
	return checked( $is_route_checked, true, false );

} ?>

<div class="tora-settings-page wrap">
	<h1><?php _e( 'Turn Off REST API', 'turn-off-rest-api' ); ?></h1>
	<?php settings_errors( 'turn-off-rest-api-notices' ); ?>
	<p>
		<strong>
		<?php _e( 'Unauthorized access to WP REST API endpoints are disabled by default.', 'turn-off-rest-api' ); ?>
		</strong><br />
		<?php _e( 'To restore default functionality and permit an access on REST API endpoints, you may check the box.', 'turn-off-rest-api' ); ?>
	</p>

	<form method="post" action="" id="tora-form">
	<?php wp_nonce_field( 'turn_off_rest_api_admin_nonce' ); ?>
		<div id="tora-checkbox-list"><?php turn_off_rest_api_list_route_checkboxes(); ?></div>

		<?php $reset_message = __( "Are you sure you want to restore default settings?", 'turn-off-rest-api' ); ?>
		<div class="tora-action-box__row">
			<?php submit_button( __( 'Save', 'turn-off-rest-api' ), 'primary', 'submit', false ); ?>
			<?php submit_button( __( 'Reset', 'turn-off-rest-api' ), 'secondary', 'reset', false, array( 'onclick' => "return confirm('{$reset_message}');" ) ); ?>
		</div>
	</form>
</div>