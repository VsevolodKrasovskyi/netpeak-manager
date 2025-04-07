<?php

namespace UI\Admin;

class LicensePage {
    public static function render() {
        $saved_email = get_option('netpeak_seo_license_email', '');
        $saved_password = get_option('netpeak_seo_license_password', '');
        $saved_license_key = get_option( 'netpeak_seo_license_key', '' );
        ?>
        <div class="form-license">
            <h2 class="license-title"><?php _e('License Verification', 'netpeak-seo'); ?></h2>

            <div id="loader" style="display:none; text-align: center;">
                <img width="50" src="<?php echo esc_url(NETPEAK_PLUGIN_URL . 'assets/img/loading.gif');?>" alt="Loading...">
            </div>
            <div id="netpeak-license-response"></div>

            <form id="auth-form" style="display:none;">
                <input type="email" name="email" placeholder="<?php esc_attr_e('Enter your email', 'netpeak-seo'); ?>" required value="<?php echo esc_attr($saved_email); ?>">
                <input type="password" name="password" placeholder="<?php esc_attr_e('Enter your password', 'netpeak-seo'); ?>" required autocomplete="current-password" value="<?php echo esc_attr($saved_password); ?>">
                <button type="button" id="auth-submit"><?php esc_attr_e('Login', 'netpeak-seo'); ?></button>
            </form>

            <!-- Form for entering the licence key -->
            <form id="license-form" style="display:none;">
                <input type="text" name="license-key" placeholder="<?php esc_attr_e('Enter license key', 'netpeak-seo'); ?>" value="<?php echo esc_attr($saved_license_key); ?>" required>
                <button type="button" id="license-submit"><?php esc_attr_e('Save License Key', 'netpeak-seo'); ?></button>
            </form>
            <div class="license-footer-buttons" style="margin-top: 30px; text-align: center;">
                <a href="https://cdn.netpeak.dev/support" target="_blank" class="button button-secondary" style="margin-right: 10px;">
                    <?php esc_html_e('Support', 'netpeak-seo'); ?>
                </a>
                <a href="https://cdn.netpeak.dev/login" target="_blank" class="button button-secondary">
                    <?php esc_html_e('Account', 'netpeak-seo'); ?>
                </a>
            </div>
        </div>
    <?php

    }
}
