<?php

/**
 * Intelligence bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              getlevelten.com/blog/tom
 * @since             1.0.0
 * @package           Intelligence
 *
 * @wordpress-plugin
 * Plugin Name:       Intelligence
 * Plugin URI:        intl.getlevelten.com
 * Description:       Provides behavior and visitor intelligence.
 * Version:           1.0.0
 * Author:            Tom McCracken
 * Author URI:        getlevelten.com/blog/tom
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       intel
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-intel-activator.php
 */
function activate_intel() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-intel-activator.php';
	Intel_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-intel-deactivator.php
 */
function deactivate_intel() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-intel-deactivator.php';
	Intel_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_intel' );
register_deactivation_hook( __FILE__, 'deactivate_intel' );

/**
 * required shims
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-intel-df.php';
function intel_df() {
	return Intel_Df::getInstance();
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-intel.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function intel() {
	return Intel::getInstance();
}

/*
 * Start GADWP
 */
global $intel;
$intel = intel();