<?php


class PowerPressNetworkList extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-list');
    }

    function ppn_shortcode($attr, $contents)
    {
        require_once(WP_PLUGIN_DIR . '/powerpress/powerpressadmin.php');

        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) return $ctx;

        $list_id = $attr['id'];

        $props = [];
        $props['style'] = (isset($attr['style']) && $attr['style'] == 'detailed') ? 'detailed' : 'simple';

        $page = get_query_var('paged');
        if ($page > 1) {
            $props['paged'] = $page;
        } else {
            $props['start'] = 1;
            $props['paged'] = 1;
        }

        $props['show-paging'] = true;

        $pathParts = explode('/', htmlspecialchars($_SERVER['REQUEST_URI']));
        $props['link-to'] = "featured/" . $pathParts[1];

        $networkInfo = $ctx['networkInfo'];
        $networkId = $networkInfo['network_id'];
        $networkTitle = $networkInfo['network_title'];

        $props['network_general'] = $ctx['general'];
        $props['network_map'] = $ctx['map'];

        if (!empty($list_id) && $list_id != 'all') {
            $networkInfo['list_id'] = $list_id;
            $results = PowerpressNetworkDataBus::getSpecificListInNetwork($ctx['apiUrl'], $ctx['creds'], $networkInfo, false);
            if (!is_array($results) || empty($results['programs'])) {
                return PowerPressNetwork::getHTML('no-list-results.php', $props, null, null);
            }
            $props['list_title'] = $results['list_info']['list_title'] ?? '';
            $props['list_desc'] = $results['list_info']['list_description'] ?? '';
            $props['results'] = $results;
        } else if (!empty($list_id) && $list_id == 'all') {
            $results = PowerpressNetworkDataBus::getProgramsInNetwork($ctx['apiUrl'], $ctx['creds'], $networkInfo, true);
            $props['results']['programs'] = $results;
            $props['list_title'] = $networkTitle;
        }

        $props['network_title'] = $networkTitle;
        $props['network_id'] = $networkId;
        $props['post'] = false;
        $props['apiUrl'] = $ctx['apiUrl'];

        // filter to checked programs and resolve page links
        $temp = null;
        if (!empty($props['results']['programs'])) {
            foreach ($props['results']['programs'] as $program) {
                if (($list_id == 'all') || (isset($program['checked']) && $program['checked'] == true)) {
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
            return PowerPressNetwork::getHTML('lists-results.php', $props, null, null);
        }
    }
}

$GLOBALS['ppn-list'] = new PowerPressNetworkList();
