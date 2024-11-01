<?php
/**
 * Single-Image-Widget
 *
 * @package   single-image-widget
 * @author    Monjurul Hoque
 * @copyright Copyright (c) 2016 Saucer Web Solution
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: single image widget
 * Plugin URI: https://wordpress.org/plugins/single-image-widget/
 * Description: Single Image Widget to add any images to your sidebars.
 * Version: 1.0.1
 * Author: monjurulhoque
 * Author URI: https://www.monjurulhoque.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: single-image-widget
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin instance.
 *
 * @since 4.0.0
 * @type single-image-widgett $single_image_widget
 */
global $single_image_widget;

if ( ! defined( 'SIW_DIR' ) ) {
	/**
	 * Plugin directory path.
	 *
	 * @since 4.0.0
	 * @type string SIW_DIR
	 */
	define( 'SIW_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * Check if the installed version of WordPress supports the new media manager.
 *
 * @since 3.0.0
 */
function is_single_image_widget_legacy() {
	/**
	 * Whether the installed version of WordPress supports the new media manager.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $is_legacy
	 */
	return apply_filters( 'is_single_image_widget_legacy', version_compare( get_bloginfo( 'version' ), '3.4.2', '<=' ) );
}

/**
 * Include functions and libraries.
 */
require_once( SIW_DIR . 'includes/class-single-image-widget.php' );
require_once( SIW_DIR . 'includes/class-single-image-widget-legacy.php' );
require_once( SIW_DIR . 'includes/class-single-image-widget-plugin.php' );
require_once( SIW_DIR . 'includes/class-single-image-widget-template-loader.php' );

/**
 * Deprecated main plugin class.
 *
 * @since      3.0.0
 * @deprecated 4.0.0
 */
class single_image_widget_Loader extends single_image_widget_Plugin {}

// Initialize and load the plugin.
$single_image_widget = new single_image_widget_Plugin();
add_action( 'plugins_loaded', array( $single_image_widget, 'load' ) );
