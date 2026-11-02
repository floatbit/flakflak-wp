<?php
/**
 * Plugin Name: FlakFlak
 * Description: Add your FlakFlak embed to all frontend pages or selected post IDs.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: FlakFlak
 * License: GPL-2.0-or-later
 * Text Domain: flakflak-wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLAKFLAK_WP_VERSION', '1.0.0' );
define( 'FLAKFLAK_WP_FILE', __FILE__ );
require_once __DIR__ . '/includes/class-flakflak-wp.php';
FlakFlak_WP_Plugin::init();
