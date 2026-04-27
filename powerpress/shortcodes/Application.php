<?php

class PowerPressNetworkApplication extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-application');
    }

    function ppn_shortcode($attr, $contents)
    {
        require_once(WP_PLUGIN_DIR . '/powerpress/powerpressadmin.php');

        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) return $ctx;

        $props = [];

        if (!empty($attr['auto-active'])) {
            $props['auto-active'] = true;
        }

        // read tos url from db, allow shortcode attribute as override
        $tosFromDb = $ctx['networkInfo']['tos_url'] ?? '';
        $props['tos_url'] = !empty($attr['terms-url']) ? (string) $attr['terms-url'] : $tosFromDb;

        if (isset($attr['default-list'])) {
            $props['default-list'] = (int) $attr['default-list'];
        }

        $props['network_general'] = $ctx['general'];
        $props['powerpress_network'] = $ctx['networkInfo'];
        $props['post'] = false;

        return PowerPressNetwork::getHTML('forms.php', $props, null, null);
    }
}

$GLOBALS['ppn_application'] = new PowerPressNetworkApplication();

