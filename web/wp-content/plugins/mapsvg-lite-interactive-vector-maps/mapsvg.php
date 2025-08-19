<?php
/*
Plugin Name: MapSVG Lite
Plugin URI: https://mapsvg.com
Description: Any maps with database integration, filters and search. Use included maps or draw your own. Create vector maps, Google maps, image maps, floor plans, store locators.
Version: 8.5.34
Requires at least: 5.0
Requires PHP: 7.4
Author: Northern Lights Production
Author URI: https://mapsvg.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: mapsvg-lite
Domain Path: /languages
*/

namespace MapSVG;

include 'php/Autoloader.php';


if (!defined('ABSPATH')) exit; // Exit if accessed directly

/** MapSVG plan */
define('MAPSVG_API_URL', 'https://mapsvg.com/dashboard/api');
define('MAPSVG_PLAN', 'mapsvg-lite');
/** MapSVG version number */
define('MAPSVG_VERSION', '8.5.34');
/** Prefix for MapSVG tables in the database */
define('MAPSVG_PREFIX',  'mapsvg6_');

/**
 * Checks if should start Clockwork logging.
 * When true, clockwork will log all requests and responses to the database, 
 * but they will be inaccessible without the proper access token.
 */
function checkDebugMode()
{
    // By default, debug mode is disabled
    $logToClockWork = false;

        

    if (!defined('MAPSVG_DEBUG')) {
        define('MAPSVG_DEBUG', $logToClockWork);
    }
    if (MAPSVG_DEBUG) {
        if (WP_DEBUG) {
            wp_debug_mode();
        }
        if (!defined('CLOCKWORK_ENABLE')) {
            define('CLOCKWORK_ENABLE', true);
        }
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
        if (!defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', false);
        }

        $logToFile = WP_DEBUG ? true : false;


        Logger::init(["logToFile" => $logToFile, "logToClockwork" => $logToClockWork]);
    }
}
checkDebugMode();

/**
 * The MAPSVG_DEBUG constant toggles Development / Production mode.
 * To enable the Dev mode, add the following line to wp_config.php:
 * define('MAPSVG_DEBUG', true);
 * Also, debugging can be enabled by adding ?mapsvg_debug=SECRET_KEY to the URL
 */

/**
 * Include the class that renders shortcode on an empty page.
 * Used in MapSVG templates as shown below:
 * {{shortcode '[apple id="123"]'}}
 */

if (isset($_GET['mapsvg_shortcode']) || isset($_GET['mapsvg_shortcode_inline']) || isset($_GET['mapsvg_embed_post'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    include(__DIR__ . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'Domain' . DIRECTORY_SEPARATOR . 'ShortcodeRender' . DIRECTORY_SEPARATOR . 'shortcodes.php');
}

/**
 * If MAPSVG_RAND == true && MAPSVG_DEBUG == true
 * then a random number is added to js/css file URLs to disable cache
 */
define('MAPSVG_RAND', isset($_GET['norand']) ? false : true); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$upload_dir = wp_upload_dir();
$upload_dir['path'] = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $upload_dir['path']);
$upload_dir['basedir'] = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $upload_dir['basedir']);

$plugin_dir_url = plugin_dir_url(__FILE__);
if (is_ssl()) {
    $upload_dir['baseurl'] = str_replace('http:', 'https:', $upload_dir['baseurl']);
    $plugin_dir_url = str_replace('http:', 'https:', $plugin_dir_url);
}

/** MapSVG plugin URL */
define('MAPSVG_PLUGIN_URL', $plugin_dir_url);

/** MapSVG plugin relative URL without domain */
$parts = wp_parse_url(MAPSVG_PLUGIN_URL);
define('MAPSVG_PLUGIN_RELATIVE_URL', $parts['path']);

/** MapSVG plugin dir */
define('MAPSVG_PLUGIN_DIR', str_replace(['\\', '/'], DIRECTORY_SEPARATOR, realpath(plugin_dir_path(__FILE__))));

/** Maps dir */
define('MAPSVG_MAPS_DIR', realpath(MAPSVG_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'maps'));

/** Maps uploads dir */
define('MAPSVG_UPLOADS_DIR', $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'mapsvg');

/** Maps uploads URL */
define('MAPSVG_UPLOADS_URL', $upload_dir['baseurl'] . '/mapsvg/');

define('MAPSVG_MAPS_URL', MAPSVG_PLUGIN_URL . 'maps/');

define('MAPSVG_PINS_DIR', realpath(MAPSVG_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'markers'));

define('MAPSVG_PINS_URL', MAPSVG_PLUGIN_URL . 'markers/');

// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand -- wp_rand() isn't available this early in plugin initialization
define('MAPSVG_ASSET_VERSION', MAPSVG_VERSION . (MAPSVG_DEBUG ? (MAPSVG_RAND ? rand() : '') : ''));

// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand -- wp_rand() isn't available this early in plugin initialization
define('MAPSVG_JQUERY_VERSION', MAPSVG_VERSION . (MAPSVG_DEBUG ? (MAPSVG_RAND ? rand() : '') : ''));

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});


/**
 * Class MapSVG
 * @package MapSVG
 */
class MapSVG
{

    private $mapsvgPurchaseCode;

    public function __construct() {}

    


    public function run()
    {

        if (defined("PHP_VERSION_ERROR")) {
            add_action('admin_menu', array($this, 'addErrorPage'));
            return;
        }


        $upgrader = new Upgrade();
        $upgrader->run();

        


        $router = new Router();

        if (is_admin()) {
            /** Load Admin controller */
            try {
                $admin = new Admin();
            } catch (\Exception $e) {
                status_header($e->getCode());
                $response = ['error' => $e->getMessage()];
            }
        } else {
            /** Load Front-end controller */
            $front = new Front();
        }
    }


    function addErrorPage()
    {
        add_menu_page('MapSVG', 'MapSVG', 'edit_posts', 'mapsvg-config', array($this, 'renderErrorPage'), '', 66);
    }

    /**
     * Checks PHP Version
     * @return bool
     */
    function isPhpVersionOk()
    {
        $match = array();
        preg_match("#^\d+(\.\d+)*#", PHP_VERSION, $match);
        $php_version = $match[0];
        if (version_compare($php_version, '7.0.0', '<')) {
            define('PHP_VERSION_ERROR', 'Your PHP version is ' . $php_version . '. MapSVG requires version 7.0.0 or higher.');
            return false;
        } else {
            return true;
        }
    }

    function renderErrorPage()
    {
        echo "<div style='padding: 30px;'>" . esc_html(PHP_VERSION_ERROR) . "</div>";
    }
}

$mapsvg = new MapSVG();
$mapsvg->run();
