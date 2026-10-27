<?php

class PowerPressNetworkGrid extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-show-grid');
    }

    /** LEGACY: tags this shortcode replaced, still registered */
    static function legacyTags(): array {
        return [
            'ppn-gridview',
        ];
    }

    /** accepted attributes and their defaults, also the source for the editor legend */
    static function attributeDefaults(): array {
        return [
            'id' => 'all',
            'rows' => 0,
            'cols' => 4,
            'title' => 'false',
            'description' => 'false',
            'hover' => 'true',
            'group-description' => null,
        ];
    }

    /** column counts the grid can lay out, anything else falls back to the default */
    static function colChoices(): array {
        return [1, 2, 3, 4, 6];
    }

    /** one line on what this renders, shown above its attributes in the editor legend */
    static function shortcodeDescription(): string {
        return __('Shows as a grid of artwork. Use a group id, or all for the whole network.', 'powerpress');
    }

    /** a realistic use of this shortcode, shown under its attributes */
    static function shortcodeExample(): string {
        return '[ppn-show-grid id="all" cols="3"]';
    }

    /** what each attribute accepts, keyed the same as attributeDefaults() */
    static function attributeLegend(): array {
        return [
            'id' => 'a group id, from the Groups tab, or all',
            'rows' => '1 or more, 0 for all',
            'cols' => self::colChoices(),
            'title' => ['true', 'false'],
            'description' => ['true', 'false'],
            'hover' => ['true', 'false'],
            'group-description' => ['true', 'false'],
        ];
    }

    /** attributes declared but wired to nothing, kept out of the legend */
    static function inertAttributes(): array {
        return [];
    }

    function ppn_shortcode($attr, $contents)
    {
        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) return $ctx;

        $attr = shortcode_atts(self::attributeDefaults(), $attr, $this->tag);

        $rows = max(0, (int) $attr['rows']);
        $cols = in_array((int) $attr['cols'], self::colChoices(), true) ? (int) $attr['cols'] : self::attributeDefaults()['cols'];
        $disp = self::flagIsTrue($attr['title'], false);
        $blurb = self::flagIsTrue($attr['description'], false);
        $listId = $attr['id'];

        $props = [
            'hover' => self::flagIsTrue($attr['hover'], true),
            'show_description' => self::flagIsTrue($attr['group-description'], true),
            'network_map' => $ctx['map'],
        ];

        $networkInfo = $ctx['networkInfo'];
        $networkInfo['list_id'] = $listId;
        $networkId = $networkInfo['network_id'];
        $networkTitle = $networkInfo['network_title'];

        $checkNetwork = PowerpressNetworkDataBus::getSpecificNetworkInAccount($ctx['apiUrl'], $ctx['creds'], $networkInfo, false);
        if (!empty($checkNetwork['error'])) {
            return PowerPressNetwork::getHTML('invalid-network.php', $props, null);
        }

        if ($listId != 'all') {
            $results = PowerpressNetworkDataBus::getSpecificListInNetwork($ctx['apiUrl'], $ctx['creds'], $networkInfo, false);
            if (!is_array($results) || empty($results['programs'])) {
                return PowerPressNetwork::getHTML('no-list-results.php', $props, null);
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
        $props['title'] = $disp;
        $props['description'] = $blurb;
        $props['apiUrl'] = $ctx['apiUrl'];

        // a specific list carries every network program, only the checked ones belong to it
        $temp = null;
        if (!empty($props['results'])) {
            foreach ($props['results'] as $program) {
                if (($listId == 'all') || (isset($program['checked']) && $program['checked'] == true)) {
                    $temp[] = $program;
                }
            }
        }

        if (!empty($temp)) {
            $this->normalizePrograms($temp);
            $this->resolvePageLinks($temp, $ctx['map']);
        }

        $props['results'] = $temp;

        if (empty($props['list_title'])) {
            return PowerPressNetwork::getHTML('no-list-results.php', $props, null);
        }

        if (empty($props['results'])) {
            return PowerPressNetwork::getHTML('list-no-programs.php', $props, null);
        }

        return PowerPressNetwork::getHTML('grid-results.php', $props, null);
    }
}

$GLOBALS['ppn-grid'] = new PowerPressNetworkGrid();
