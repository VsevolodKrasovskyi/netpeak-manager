<?php


namespace NetpeakManager;
class LicenseManager
{
    public static function init()
    {
        add_action('wp_ajax_save_license_tokens', [self::class, 'handleSaveTokens']);
        add_action('wp_ajax_get_license_tokens', [self::class, 'handleGetTokens']);
    }

    public static function handleSaveTokens()
    {
        $updated = false; 

        if (!empty($_POST['authToken']) && !empty($_POST['licenseKey'])) {
            update_option('netpeak_seo_license_auth_token', sanitize_text_field($_POST['authToken']));
            update_option('netpeak_seo_license_key', sanitize_text_field($_POST['licenseKey']));
            $updated = true;
        }

        if (!empty($_POST['email']) && !empty($_POST['password'])) {
            update_option('netpeak_seo_license_email', sanitize_email($_POST['email']));
            update_option('netpeak_seo_license_password', sanitize_text_field($_POST['password']));
            $updated = true;
        }

        if ($updated) {
            wp_send_json_success('Data saved successfully.');
        } else {
            wp_send_json_error('No valid data provided.');
        }
    }
    public static function handleGetTokens()
    {
        $auth_token = get_option('netpeak_seo_license_auth_token');
        $license_key = get_option('netpeak_seo_license_key');
        if ($auth_token && $license_key) {
            wp_send_json_success([
                'token' => $auth_token,
                'licenseKey' => $license_key
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to retrieve license tokens.']);
        }
    }
}