<?php

namespace Netpeak;

class PluginRepository {
    public static function getAvailablePlugins() {
        $transient_key = 'netpeak_plugin_repo_cache';

        $cached = get_transient($transient_key);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        $token = get_option('netpeak_seo_license_auth_token');
        if (!$token) {
            return 'Required authentication token is missing.';
        }

        $base_url = CDN::getInstance()->getBaseApi();

        $response = wp_remote_get($base_url . 'plugins', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $plugins = json_decode($body, true);

        if (!is_array($plugins)) {
            return [];
        }
        $plugins = array_values($plugins);

        set_transient($transient_key, $plugins, 6 * HOUR_IN_SECONDS);

        return $plugins;
    }
}
