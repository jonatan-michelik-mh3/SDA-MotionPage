<?php

use motionpage\Scaffold;
use motionpage\Common\Functions;
use motionpage\Common\UpdaterFunctions;

/**
 * @package     motionpage
 * @author    	David Babinec
 * @copyright 	2025 CoreWeb OÜ
 * @license   	EULA + GPLv2
 * @link      	https://motion.page
 * @version  		3.2.0
 *
 * @wordpress-plugin
 *
 * Plugin Name:   Motion.page
 * Plugin URI:    https://motion.page
 * Description:   Move it like it's HOT!
 * Version:       3.2.0
 * Author:        David Babinec
 * Author URI:    https://motion.page
 * Text Domain:   motionpage
 * Domain Path:   /languages
 * License:       EULA + GPLv2
 * License URI:   https://motion.page/eula + https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:  8.0
 * Requires WP:   5.9.0
 * Namespace:     motionpage
 *
 * ███╗   ███╗ ██████╗ ████████╗██╗ ██████╗ ███╗   ██╗   ██████╗  █████╗  ██████╗ ███████╗
 * ████╗ ████║██╔═══██╗╚══██╔══╝██║██╔═══██╗████╗  ██║   ██╔══██╗██╔══██╗██╔════╝ ██╔════╝
 * ██╔████╔██║██║   ██║   ██║   ██║██║   ██║██╔██╗ ██║   ██████╔╝███████║██║  ███╗█████╗
 * ██║╚██╔╝██║██║   ██║   ██║   ██║██║   ██║██║╚██╗██║   ██╔═══╝ ██╔══██║██║   ██║██╔══╝
 * ██║ ╚═╝ ██║╚██████╔╝   ██║   ██║╚██████╔╝██║ ╚████║██╗██║     ██║  ██║╚██████╔╝███████╗
 * ╚═╝     ╚═╝ ╚═════╝    ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚═╝╚═╝     ╚═╝  ╚═╝ ╚═════╝ ╚══════╝
 *
 */

if (!defined('ABSPATH')) {
	exit();
}

define('MOTIONPAGE_NAME', 'motionpage');
define('MOTIONPAGE_VERSION', '3.2.0');
define('MOTIONPAGE_FILE', __FILE__);
define('MOTIONPAGE_BASENAME', plugin_basename(MOTIONPAGE_FILE));
define('MOTIONPAGE_DIR_PATH', plugin_dir_path(MOTIONPAGE_FILE));

// Fix URL when using symlinks
$plugin_url = plugin_dir_url(MOTIONPAGE_FILE);
// Also check if the URL looks correct but might have issues
if (strpos($plugin_url, '/Users/') === 0 || strpos($plugin_url, '/home/') === 0 || strpos($plugin_url, '%20') !== false) {
    // This is a filesystem path or has URL encoding issues - fix it
    $plugin_folder = basename(dirname(MOTIONPAGE_FILE));
    // Ensure proper URL encoding
    $plugin_folder = rawurlencode($plugin_folder);
    $plugin_url = content_url('plugins/' . $plugin_folder . '/');
}
define('MOTIONPAGE_DIR_URL', $plugin_url);

$motionpage_autoloader = require MOTIONPAGE_DIR_PATH . 'vendor/autoload.php';

if (!wp_installing()) {
	register_activation_hook(__FILE__, ['motionpage\Config\Setup', 'activation']);
	register_deactivation_hook(__FILE__, ['motionpage\Config\Setup', 'deactivation']);
	register_uninstall_hook(__FILE__, ['motionpage\Config\Setup', 'uninstall']);
}

if (!class_exists('\motionpage\Scaffold')) {
	wp_die(__('Motion.page is unable to find the Scaffold class.', MOTIONPAGE_NAME));
}

add_action('plugins_loaded', static function () use ($motionpage_autoloader): void {
	try {
		new Scaffold($motionpage_autoloader);
	} catch (Exception $e) {
		wp_die(__('Motion.page is unable to run the Scaffold class.', MOTIONPAGE_NAME));
	}
});

add_action('wp_initialize_site', ['motionpage\Config\Setup', 'newMultisiteBlog'], 999, 2);

/**
 * Create a main function for external uses
 * @since 2.0.0
 */
function motionpage(): Functions {
	return new Functions();
}

/**
 * Backward-compatible stub for the legacy EDD updater.
 * Sites upgrading from v2.x still have old hooks in memory that call this.
 * @since 2.0.0
 * @deprecated 3.0.0
 */
function motionpageUpdater(): UpdaterFunctions {
	return new UpdaterFunctions();
}

