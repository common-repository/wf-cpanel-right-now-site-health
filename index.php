<?php
declare( strict_types=1 );
namespace WebFacing\cPanel;

/**
 * Plugin Name:     	WebFacing™ - Storage, resource usage and errors in cPanel®
 * Description:     	🕸️ By WebFacing. Shows your storage information, used and max allowed, errors and alerts, in your Dashboard At a Glance widget, other resource usages as gauges in a custom dashboard widget, and storage information as a special test and shown Site Health Info. Also adds a HTTPS only test to Site Health Status. Made with some help from PRO ISP AS, Norway.
 * Plugin URI:      	https://webfacing.eu/
 * Version:         	3.8
 * Author:          	Knut Sparhell
 * Author URI:      	https://profiles.wordpress.org/knutsp/
 * License:         	GPL v2
 * License URI:     	https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins:	wf-cpanel-email-accounts
 * Requires PHP:    	7.4
 * Tested up to PHP:	8.2
 * Requires at least:   5.7
 * Tested up to:    	6.5.3
 * Text Domain:     	wf-cpanel-right-now-site-health
 */

/**
 * Exit if accessed directly
 */
\defined( 'ABSPATH' ) || exit;

/**
 * Define a non-magic constant inside the namespace pointing to this main plugin file
 */
const PLUGIN_DIR  = __DIR__;

const PLUGIN_FILE = __FILE__;

\define( __NAMESPACE__ . '\PLUGIN_BASENAME', \basename( PLUGIN_DIR ) . \DIRECTORY_SEPARATOR . \basename( PLUGIN_FILE ) );

require_once 'compat-functions.php';
require_once 'includes/Main.php';
require_once 'includes/Glance.php';
require_once 'includes/Charts.php';
require_once 'includes/Health.php';
require_once 'lib/i18n.php';
require_once 'lib/utils.php';

Main::load();

if ( \is_admin() ) {
	Main::admin();
}
