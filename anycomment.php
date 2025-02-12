<?php
/**
 * Plugin Name: AnyComment
 * Plugin URI: https://anycomment.io
 * Description: AnyComment is an advanced commenting system for WordPress.
 * Version: 0.0.93
 * Author: Bologer
 * Author URI: http://bologer.ru
 * Requires at least: 4.4
 * Requires PHP: 5.4
 * Tested up to: 5.0
 * Text Domain: anycomment
 * Domain Path: /languages
 *
 * @package AnyComment
 * @author bologer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

function anycomment() {
	defined( 'ANYCOMMENT_PLUGIN_FILE' ) or define( 'ANYCOMMENT_PLUGIN_FILE', __FILE__ );
	defined( 'ANYCOMMENT_LANG' ) or define( 'ANYCOMMENT_LANG', __FILE__ );
	defined( 'ANYCOMMENT_ABSPATH' ) or define( 'ANYCOMMENT_ABSPATH', dirname( __FILE__ ) );
	defined( 'ANYCOMMENT_PLUGIN_BASENAME' ) or define( 'ANYCOMMENT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	defined( 'ANYCOMMENT_DEBUG' ) or define( 'ANYCOMMENT_DEBUG', false );

	return \AnyComment\AnyCommentCore::instance();
}

anycomment();

/**
 * Fires after AnyComment was loaded.
 *
 * @since 0.0.3
 */
do_action( 'anycomment/loaded' );