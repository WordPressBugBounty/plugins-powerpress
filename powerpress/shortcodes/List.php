<?php

class PowerPressNetworkList extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-show-list');
    }

    /** LEGACY: tags this shortcode replaced, still registered */
    static function legacyTags(): array {
        return [
            'ppn-list',
        ];
    }

    /** accepted attributes and their defaults, also the source for the editor legend */
    static function attributeDefaults(): array {
        return [
            'id' => 'all',
            'style' => 'simple',
            'description' => 'true',
            'group-description' => null,
            'limit' => 0,
        ];
    }

    /** layouts the list view renders */
    static function styleChoices(): array {
        return [
            'simple',
            'detailed',
        ];
    }

    /** one line on what this renders, shown above its attributes in the editor legend */
    static function shortcodeDescription(): string {
        return __('Shows as a list. Use a group id, or all for the whole network.', 'powerpress');
    }

    /** a realistic use of this shortcode, shown under its attributes */
    static function shortcodeExample(): string {
        return '[ppn-show-list id="all" style="detailed" limit="5"]';
    }

    /** what each attribute accepts, keyed the same as attributeDefaults() */
    static function attributeLegend(): array {
        return [
            'id' => 'a group id, from the Groups tab, or all',
            'style' => self::styleChoices(),
            'description' => ['true', 'false'],
            'group-description' => ['true', 'false'],
            'limit' => '1 or more, 0 for all',
        ];
    }

    /** cap the shows rendered, zero renders every one of them */
    static function normalizeCap($value): int {
        $cap = (int) $value;

        return $cap > 0 ? $cap : 0;
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

        $list_id = $attr['id'];

        $props = [
            'style' => in_array($attr['style'], self::styleChoices(), true) ? $attr['style'] : self::attributeDefaults()['style'],
            'show_description' => self::flagIsTrue($attr['description'], true),
            'show_group_description' => self::flagIsTrue($attr['group-description'], true),
        ];

        $networkInfo = $ctx['networkInfo'];
        $networkId = $networkInfo['network_id'];
        $networkTitle = $networkInfo['network_title'];

        $props['network_map'] = $ctx['map'];

        if (!empty($list_id) && $list_id != 'all') {
            $networkInfo['list_id'] = $list_id;
            $results = PowerpressNetworkDataBus::getSpecificListInNetwork($ctx['apiUrl'], $ctx['creds'], $networkInfo, false);
            if (!is_array($results) || empty($results['programs'])) {
                return PowerPressNetwork::getHTML('no-list-results.php', $props, null);
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

        // a specific list carries every network program, only the checked ones belong to it
        $temp = null;
        if (!empty($props['results']['programs'])) {
            foreach ($props['results']['programs'] as $program) {
                if (($list_id == 'all') || (isset($program['checked']) && $program['checked'] == true)) {
                    $temp[] = $program;
                }
            }
        }

        if (!empty($temp)) {
            $this->normalizePrograms($temp);
            $this->resolvePageLinks($temp, $ctx['map']);

            $temp = array_values(array_filter($temp, function ($program) {
                return !empty($program['link']) && $program['link'] !== '#';
            }));
        }

        $cap = self::normalizeCap($attr['limit']);
        $props['more_url'] = '';
        $props['total'] = is_array($temp) ? count($temp) : 0;

        if ($cap > 0 && $props['total'] > $cap) {
            $temp = array_slice($temp, 0, $cap);
            $mapKey = $list_id === 'all' ? 'Homepage' : "l-{$list_id}";

            if ((int) ($ctx['map'][$mapKey] ?? 0) !== (int) get_the_ID()) {
                $props['more_url'] = self::mappedPageUrl($mapKey, $ctx['map']);
            }
        }

        $props['results'] = $temp;

        if (empty($props['list_title'])) {
            return PowerPressNetwork::getHTML('no-list-results.php', $props, null);
        }

        if (empty($props['results'])) {
            return PowerPressNetwork::getHTML('list-no-programs.php', $props, null);
        }

        return PowerPressNetwork::getHTML('lists-results.php', $props, null);
    }
}

$GLOBALS['ppn-list'] = new PowerPressNetworkList();
