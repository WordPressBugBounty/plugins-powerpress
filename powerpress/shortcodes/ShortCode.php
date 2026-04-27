<?php

class PowerPressNetworkShortCode
{
    var $tag;
    function __construct($tag)
    {
        $this->tag = $tag;
        add_action('init', [$this, 'ppn_shortcode_init']);
        add_action('wp_enqueue_scripts', [$this, 'ppn_enqueue_frontend_styles']);
    }

    /** enqueue network frontend css when shortcode is registered */
    function ppn_enqueue_frontend_styles()
    {
        powerpress_enqueue_assets([
            'ppn-frontend'            => ['path' => 'css/ppn-frontend'],
            'powerpress-blueprint'    => ['path' => 'css/blueprint'],
            'powerpress-subscribe-widget' => ['path' => 'css/subscribe-widget'],
            'ppn-montserrat'          => ['type' => 'style', 'url' => 'https://fonts.googleapis.com/css?family=Montserrat&display=swap'],
        ]);
    }

    function ppn_shortcode_init()
    {
        add_shortcode($this->tag, [$this, 'ppn_shortcode']);
    }

    /**
     * load network context: options, api url, network info
     *
     * @return array|string context array on success, error HTML on failure
     */
    protected function getNetworkContext()
    {
        $networkInfo = get_option('powerpress_network', []);
        $networkInfo['network_id'] = get_option('powerpress_network_id');
        $networkInfo['network_title'] = get_option('powerpress_network_title');

        if (empty($networkInfo['network_id'])) {
            return PowerPressNetwork::getHTML('invalid-network.php', [], null, null);
        }

        $apiArray = powerpress_get_api_array();
        return [
            'networkInfo' => $networkInfo,
            'apiUrl'      => $apiArray[0],
            'creds'       => ['post' => false],
            'map'         => get_option('powerpress_network_map', []),
            'general'     => get_option('network_general'),
        ];
    }

    /**
     * resolve page links for programs using the network map
     */
    protected function resolvePageLinks(array &$programs, array $map): void
    {
        foreach ($programs as &$program) {
            $mapKey = "p-{$program['program_id']}";
            $program['link'] = '#';
            if (isset($map[$mapKey])) {
                $postId = $map[$mapKey];
                if (get_post_status($postId) === 'publish') {
                    $program['link'] = get_permalink($postId);
                }
            }
        }
    }
}
