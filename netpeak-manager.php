<?php
/**
 * Plugin Name: Netpeak Plugin Manager
 * Plugin URI: https://cdn.netpeak.dev/
 * Description: Manages Netpeak plugins
 * Author: Netpeak Dev Team
 * Author URI: https://netpeak.dev/
 * Text Domain: netpeak
 * Domain Path: /languages
 * Requires at least: 5.7
 * Requires PHP: 7.0
 * License: Subscription-based License
 * License URI: https://cdn.netpeak.dev/license-information
 * Version: 1.0.0
 * ███╗   ██╗███████╗████████╗██████╗ ███████╗ █████╗ ██╗  ██╗
 * ████╗  ██║██╔════╝╚══██╔══╝██╔══██╗██╔════╝██╔══██╗██║ ██╔╝
 * ██╔██╗ ██║█████╗     ██║   ██████╔╝█████╗  ███████║█████╔╝ 
 * ██║╚██╗██║██╔══╝     ██║   ██╔═══╝ ██╔══╝  ██╔══██║██╔═██╗ 
 * ██║ ╚████║███████╗   ██║   ██║     ███████╗██║  ██║██║  ██╗
 * ╚═╝  ╚═══╝╚══════╝   ╚═╝   ╚═╝     ╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants for plugin paths and URLs
if ( ! defined( 'NETPEAK_PLUGIN_DIR' ) ) {
    define( 'NETPEAK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'NETPEAK_PLUGIN_URL' ) ) {
    define( 'NETPEAK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if (file_exists(NETPEAK_PLUGIN_DIR . '/vendor/autoload.php')) {
    require_once NETPEAK_PLUGIN_DIR . '/vendor/autoload.php';
}

require_once NETPEAK_PLUGIN_DIR . '/init.php';
use Netpeak\LicenseManager;
use Netpeak\CDN;
use UI\Admin\AdminMenu;

add_action('admin_init', [LicenseManager::class, 'init']);
add_action('init',[AdminMenu::class, 'register']);


function netpeak_load_assets() {
    $cdn = CDN::getInstance();
    wp_enqueue_script('netpeak-license', NETPEAK_PLUGIN_URL . 'assets/js/license.js', array(), null, true);
    wp_localize_script('netpeak-license', 'NetpeakData', [
        'ajax_url'      => admin_url('admin-ajax.php'),
        'site_domain'   => parse_url(home_url(), PHP_URL_HOST),
        'license_text'  => __('License is active and valid.', 'netpeak-seo'),
        'login_api'     => $cdn->getBaseApi() . 'login',
        'license_api'   => $cdn->getBaseApi() . 'check-license-status',
        'activate_api'  => $cdn->getBaseApi() . 'activate-license',
        'auth_token' => get_option('netpeak_seo_license_auth_token'),
        'license_key' => get_option('netpeak_seo_license_key'),
        'messages'      => [
            'expires_on'      => __('Expires on:', 'netpeak-seo'),
            'lifetime'        => __('Lifetime license.', 'netpeak-seo'),
            'auth_required'   => __('Authorization required. Please log in again.', 'netpeak-seo'),
            'invalid_license' => __('License is invalid. Please contact support.', 'netpeak-seo'),
            'not_found'       => __('License not found. Please check your license key.', 'netpeak-seo'),
            'generic_error'   => __('An error occurred while checking the license status.', 'netpeak-seo'),
            'error_token'     =>__('Error retrieving tokens','netpeak-seo'),
            'license_required' => __('License is required to use this feature.', 'netpeak-seo'),
        ],
    ]);
    wp_enqueue_style( 'netpeak-license-page', NETPEAK_PLUGIN_URL . 'assets/css/license-page.css' );
    wp_enqueue_style('netpeak-license-switch-css', NETPEAK_PLUGIN_URL . 'assets/css/license-switch.css');
    wp_enqueue_style('netpeak-repo-css', NETPEAK_PLUGIN_URL . 'assets/css/license-repo.css');
};
add_action('admin_enqueue_scripts', 'netpeak_load_assets');


