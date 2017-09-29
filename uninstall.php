<?php
/**
 * Uninstall Plugin
 *
 * @package Turn Off REST API
 * @since 1.0.2
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) die;

$option_name = 'tora_allowed_route_list';

delete_option( $option_name );
delete_site_option( $option_name );