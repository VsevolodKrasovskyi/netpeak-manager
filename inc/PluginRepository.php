<?php

namespace Netpeak;

class PluginRepository {
    public static function getAvailablePlugins() {
        $json = file_get_contents(NETPEAK_PLUGIN_DIR . 'plugin.json');
        return json_decode($json, true);
    }
}
