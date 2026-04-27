<?php


class PowerPressNetworkGrid extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-gridview');
    }

    function ppn_shortcode($attr, $contents)
    {
        require_once(WP_PLUGIN_DIR . '/powerpress/powerpressadmin.php');

        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) return $ctx;

        $rows = $attr['rows'] ?? 1;
        $cols = (isset($attr['cols']) && in_array($attr['cols'], [1, 2, 3, 4, 6])) ? $attr['cols'] : 4;
        $disp = isset($attr['display-title']) && $attr['display-title'] == 'true';
        $listId = $attr['id'];

        $props = [];
        $props['hover'] = !isset($attr['hover']) || $attr['hover'] != 'false';
        $props['network_general'] = $ctx['general'];
        $props['network_map'] = $ctx['map'];

        $networkInfo = $ctx['networkInfo'];
        $networkInfo['list_id'] = $listId;
        $networkId = $networkInfo['network_id'];
        $networkTitle = $networkInfo['network_title'];

        $checkNetwork = PowerpressNetworkDataBus::getSpecificNetworkInAccount($ctx['apiUrl'], $ctx['creds'], $networkInfo, false);
        if (!empty($checkNetwork['error'])) {
            return PowerPressNetwork::getHTML('invalid-network.php', $props, null, null);
        }

        if ($listId != 'all') {
            $results = PowerpressNetworkDataBus::getSpecificListInNetwork($ctx['apiUrl'], $ctx['creds'], $networkInfo, false);
            if (!is_array($results) || empty($results['programs'])) {
                return PowerPressNetwork::getHTML('no-list-results.php', $props, null, null);
            }
            $props['results'] = $results['programs'];
            $props['list_title'] = $results['list_info']['list_title'] ?? '';
            $props['list_desc'] = $results['list_info']['list_description'] ?? '';
        } else {
            $results = PowerpressNetworkDataBus::getProgramsInNetwork($ctx['apiUrl'], $ctx['creds'], $networkInfo, false);
            $props['results'] = $results;
            $props['list_title'] = $networkTitle;
            $props['network_description'] = $networkInfo['network_description'] ?? '';
        }

        $props['network_title'] = $networkTitle;
        $props['network_id'] = $networkId;
        $props['post'] = false;
        $props['cols'] = $cols;
        $props['rows'] = $rows;
        $props['display-title'] = $disp;
        $props['apiUrl'] = $ctx['apiUrl'];

        // filter to checked programs (for specific lists) and resolve page links
        $temp = null;
        if (!empty($props['results'])) {
            foreach ($props['results'] as $program) {
                if ($listId == 'all' || isset($program['checked']) && $program['checked'] == true) {
                    $temp[] = $program;
                }
            }
        }
        if (!empty($temp)) {
            $this->resolvePageLinks($temp, $ctx['map']);
        }
        $props['results'] = $temp;

        if (empty($props['list_title'])) {
            return PowerPressNetwork::getHTML('no-list-results.php', $props, null, null);
        } else if (empty($props['results'])) {
            return PowerPressNetwork::getHTML('list-no-programs.php', $props, null, null);
        } else {
            return PowerPressNetwork::getHTML('grid-results.php', $props, null, null);
        }
    }
}

$GLOBALS['ppn-grid'] = new PowerPressNetworkGrid();
