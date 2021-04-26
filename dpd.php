<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://dpd.com
 * @since             1.0.0
 * @package           Dpd
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Shipping - DPD baltic
 * Description:       DPD baltic shipping plugin for WooCommerce.
 * Version:           1.1.1
 * Author:            DPD
 * Author URI:        https://dpd.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-shipping-dpd-baltic
 * Domain Path:       /languages
 * Requires at least: 4.4
 * Tested up to: 5.6
 * WC requires at least: 3.0
 * WC tested up to: 4.6.1
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('DPD_NAME_VERSION', '1.1.2');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-dpd-activator.php
 */
function activate_dpd()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-dpd-activator.php';
	Dpd_Baltic_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-dpd-deactivator.php
 */
function deactivate_dpd()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-dpd-deactivator.php';
	Dpd_Baltic_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_dpd');
register_deactivation_hook(__FILE__, 'deactivate_dpd');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-dpd.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dpd()
{

	require_once plugin_dir_path(__FILE__) . 'includes/class-dpd-activator.php';
	Dpd_Baltic_Activator::update();

	$plugin = new Dpd();
	$plugin->run();
}

run_dpd();
