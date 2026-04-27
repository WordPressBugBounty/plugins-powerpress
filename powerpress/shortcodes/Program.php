<?php

class PowerPressNetworkProgram extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-program');
    }

    function ppn_shortcode($attr, $contents)
    {
        require_once(WP_PLUGIN_DIR . '/powerpress/powerpressadmin.php');

        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) return $ctx;

        $program_id = $attr['id'];
        $props = [];
        $props['program_id'] = $program_id;
        $props['style'] = $attr['style'] ?? 'full';
        $props['ssb-style'] = $attr['ssb-style'] ?? 'modern';
        $props['ssb-shape'] = $attr['ssb-shape'] ?? 'circle';
        $props['limit'] = $attr['limit'] ?? '20';
        $props['network_general'] = $ctx['general'];

        $networkInfo = $ctx['networkInfo'];
        $networkInfo['program_id'] = $program_id;

        $results = PowerpressNetworkDataBus::getSpecificProgramInNetwork($ctx['apiUrl'], false, $networkInfo, false);
        
        if ( !$results || !is_array($results) ) {
            return '<p>' . esc_html__('This show is currently unavailable.', 'powerpress') . '</p>';
        }


        if (isset($results['error'])) {
            if ($results['error'] == 'Fail To Find Your Program In This Network') {
                return PowerPressNetwork::getHTML('no-program-results.php', $props, null, null);
            } else if ($results['error'] == 'Your account does not have the network with specified id') {
                return PowerPressNetwork::getHTML('invalid-network.php', $props, null, null);
            }
            return $results['error'];
        }

        $props['episodes'] = $results['episodes'];
        $props['artwork_url'] = $results['program_info']['artwork_url'];
        $props['program_title'] = $results['program_info']['program_title'];
        $props['program_desc'] = $results['program_info']['program_desc'];
        $props['talent_name'] = $results['program_info']['talent_name'];
        $props['program_url'] = $results['program_info']['program_htmlurl'];
        $props['program_rssurl'] = $results['program_info']['program_rssurl'];
        $props['program_itunesurl'] = $results['program_info']['program_itunesurl'] ?? false;

        // subscribe links
        $subscribeKeys = [
            'subscribe_googleplay', 'subscribe_html', 'subscribe_tunein',
            'subscribe_itunes', 'subscribe_deezer', 'subscribe_iheart',
            'subscribe_pandora', 'subscribe_radio_com', 'subscribe_spotify',
            'subscribe_blubrry', 'subscribe_podchaser', 'subscribe_jiosaavn',
            'subscribe_gaana', 'subscribe_pcindex', 'subscribe_amazon_music',
            'subscribe_anghami',
        ];
        foreach ($subscribeKeys as $key) {
            $props[$key] = $results['program_info'][$key] ?? false;
        }

        return PowerPressNetwork::getHTML('program-result.php', $props, null, null);
    }
}
$GLOBALS['ppn_program'] = new PowerPressNetworkProgram();