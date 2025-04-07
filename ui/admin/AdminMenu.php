<?php

namespace UI\Admin;

use \Netpeak\PluginRepository;

class AdminMenu {
    public static function register() {
        add_action('admin_menu', [self::class, 'addMenuItems']);
        add_action('admin_head', [self::class,'style']);
        
    }
    public static function style() {
        echo '<style>
                .toplevel_page_netpeak_dashboard .wp-submenu li a[href*="netpeak_plugins_label"] {
                    pointer-events: none;
                    color: #888;
                    font-weight: bold;
                }
            </style>';
    }

    private static function registerDynamicPluginPages() {
        if (!class_exists('\Netpeak\PluginRepository')) {
            return;
        }
    
        $plugins = PluginRepository::getAvailablePlugins();
        foreach ($plugins as $plugin) {
            $slug = sanitize_title($plugin['slug']);
            $title = $plugin['name'] ?? ucfirst($slug);
    
            if (is_plugin_active("$slug/$slug.php")) {
                add_submenu_page(
                    'netpeak_dashboard',
                    esc_html($title),
                    esc_html($title),
                    'manage_options',
                    $slug,
                    function () use ($slug) {
                        do_action("netpeak_render_plugin_page_$slug");
                    }
                );
            }
        }
    }

    public static function addMenuItems() {
        add_menu_page(
            __('Netpeak Plugins', 'netpeak-seo'),
            'Netpeak',
            'manage_options',
            'netpeak_dashboard',
            [LicensePage::class, 'render'],
            NETPEAK_PLUGIN_URL. 'assets/img/netpeak-icon.svg',
            2
        );

        add_submenu_page(
            'netpeak_dashboard',
            __('Plugins Repository', 'netpeak-seo'),
            __('Plugins', 'netpeak-seo'),
            'manage_options',
            'netpeak_plugins',
            [PluginRepositoryPage::class, 'render']
        );
        add_submenu_page(
            'netpeak_dashboard',
            '', // page_title
            '<span class="netpeak-menu-label">— Plugins —</span>',
            'manage_options',
            'netpeak_plugins_label',
            '__return_false'
        );

        self::registerDynamicPluginPages();
    }

    

    
    

}
