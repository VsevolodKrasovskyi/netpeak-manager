<?php

if(!defined('ABSPATH')) {
    exit;
}

function netpeak_settings_manager() {
    register_setting( 'netpeak_seo_license', 'netpeak_seo_license_email' );
    register_setting( 'netpeak_seo_license', 'netpeak_seo_license_password' );
    register_setting( 'netpeak_seo_license', 'netpeak_seo_license_auth_token' );
    register_setting( 'netpeak_seo_license', 'netpeak_seo_license_key' );
}
add_action('admin_init', 'netpeak_settings_manager');

function install_netpeak_plugins() {
        if (!current_user_can('install_plugins')) {
            wp_die('Permission denied');
        }
    
        $plugin_url = esc_url_raw($_GET['plugin_url'] ?? '');
        $slug = sanitize_key($_GET['slug'] ?? '');
        $nonce = $_GET['_wpnonce'] ?? '';
    
        if (!$plugin_url || !$slug) {
            wp_die('Missing plugin_url or slug');
        }
    
        if (!wp_verify_nonce($nonce, 'install-plugin_' . $slug)) {
            wp_die('Invalid nonce');
        }
    
        // Step 1: Download ZIP
        $tmp_dir = WP_CONTENT_DIR . '/uploads/netpeak-temp/';
        if (!file_exists($tmp_dir)) {
            wp_mkdir_p($tmp_dir);
        }
    
        $tmp_file = $tmp_dir . $slug . '.zip';
        $response = wp_remote_get($plugin_url, ['timeout' => 60]);
    
        if (is_wp_error($response)) {
            wp_die('Failed to download plugin: ' . $response->get_error_message());
        }
    
        file_put_contents($tmp_file, wp_remote_retrieve_body($response));
    
        // Step 2: Install ZIP
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    
        WP_Filesystem();
    
        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->install($tmp_file);
    
        // Step 3: Cleanup
        unlink($tmp_file);
    
        if (is_wp_error($result)) {
            wp_die('Installation failed: ' . $result->get_error_message());
        }
    
        // Optional auto-activation
        $plugin_path = $upgrader->plugin_info();
        if ($plugin_path && !is_plugin_active($plugin_path)) {
            activate_plugin($plugin_path);
        }
    
        wp_safe_redirect(admin_url('plugins.php?installed=' . $slug));
        exit;
    
}

add_action('admin_post_netpeak_install_plugin', 'install_netpeak_plugins');