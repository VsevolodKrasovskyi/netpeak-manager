<?php

namespace UI\Admin;

use Netpeak\PluginRepository;

class PluginRepositoryPage {
    public static function render() {
        $plugins = PluginRepository::getAvailablePlugins();
        if (is_string($plugins)) {
            echo '<div class="error"><p>' . esc_html($plugins) . '</p></div>';
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Available Netpeak Plugins', 'netpeak-seo'); ?></h1>
            <div class="netpeak-plugin-list">
                <?php foreach ($plugins as $plugin): 
                    $slug = esc_attr($plugin['slug']);
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    $all_plugins = get_plugins();
                    $installed = false;
                    $isActive = false;
                    $plugin_path = '';

                    foreach ($all_plugins as $path => $data) {
                        if (strpos($path, $slug . '/') === 0 || strpos($path, '/' . $slug . '.php') !== false) {
                            $installed = true;
                            $plugin_path = $path;
                            $isActive = is_plugin_active($path);
                            break;
                        }
                    }
                    $meets_wp_version = version_compare(get_bloginfo('version'), $plugin['requires_wp'], '>=');
                    $meets_php_version = version_compare(PHP_VERSION, $plugin['requires_php'], '>=');

                    $missing_dependencies = array_filter($plugin['dependencies'], function ($dep_slug) {
                        return !file_exists(WP_PLUGIN_DIR . "/$dep_slug/$dep_slug.php");
                    });

                    $can_install = $meets_wp_version && $meets_php_version && empty($missing_dependencies);
                    ?>
                    <div class="plugin-card">
                        <div class="plugin-header">
                            <img src="<?php echo esc_url($plugin['icon']); ?>" alt="icon" />
                            <div style="flex: 1;">
                                <h2 class="plugin-name"><?php echo esc_html($plugin['name']); ?></h2>
                                <span class="plugin-version">v<?php echo esc_html($plugin['version']); ?></span>
                            </div>
                        </div>

                        <div class="plugin-meta">
                            <p><?php echo esc_html($plugin['description']); ?></p>
                            <p>
                                <strong><img src="<?php echo esc_url(NETPEAK_PLUGIN_URL . 'assets/img/wordpress.png'); ?>" class="inline-icon" alt="WP" /> WP:</strong>
                                <?php echo esc_html($plugin['requires_wp'] ?? '—'); ?>
                            </p>
                            <p>
                                <strong><img src="<?php echo esc_url(NETPEAK_PLUGIN_URL . 'assets/img/php.png'); ?>" class="inline-icon" alt="PHP" /> PHP:</strong>
                                <?php echo esc_html($plugin['requires_php'] ?? '—'); ?>
                            </p>
                            <?php if (!empty($plugin['dependencies'])): ?>
                                <p><strong>Requires Plugins:</strong> <?php echo implode(', ', $plugin['dependencies']); ?></p>
                            <?php endif; ?>
                            <?php
                            $reason = [];

                            if (!$meets_wp_version) {
                                $reason[] = 'Requires WordPress ≥ ' . esc_html($plugin['requires_wp']);
                            }

                            if (!$meets_php_version) {
                                $reason[] = 'Requires PHP ≥ ' . esc_html($plugin['requires_php']);
                            }

                            if (!empty($missing_dependencies)) {
                                $reason[] = 'Missing plugins: ' . implode(', ', array_map('esc_html', $missing_dependencies));
                            }

                            if (!$can_install && !empty($reason)) {
                                echo '<div class="plugin-reason">';
                                foreach ($reason as $r) {
                                    echo '<p class="reason-item">⚠️ ' . $r . '</p>';
                                }
                                echo '</div>';
                            }
                            ?>

                        </div>

                        <div class="plugin-footer">
                            <?php
                            if ($isActive) {
                                echo '<span class="button disabled">Activated</span>';
                            } elseif ($installed && !$isActive) {
                                $plugin_file = "$slug/$slug.php";
                                $activate_url = wp_nonce_url(
                                    add_query_arg([
                                        'action' => 'activate',
                                        'plugin' => $plugin_file
                                    ], admin_url('plugins.php')),
                                    'activate-plugin_' . $plugin_file
                                );

                                echo '<a href="' . esc_url($activate_url) . '" class="button">Activate</a>';
                            } elseif (!$can_install) {
                                echo '<span class="button disabled" title="Check system requirements or missing dependencies">Unavailable</span>';
                            } else {
                                $zip_url = esc_url_raw($plugin['repository']);
                                $nonce = wp_create_nonce('install-plugin_' . $slug);

                                $install_url = add_query_arg([
                                    'action'     => 'netpeak_install_plugin',
                                    'plugin_url' => urlencode($zip_url),
                                    'slug'       => $slug,
                                    '_wpnonce'   => $nonce,
                                ], admin_url('admin-post.php'));       

                                echo '<a href="' . esc_url($install_url) . '" class="button">Install & Activate</a>';

                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
