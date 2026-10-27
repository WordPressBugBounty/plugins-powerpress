<?php

class PowerPressNetworkCarousel extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-show-carousel');
    }

    /** LEGACY: tags this shortcode replaced, still registered */
    static function legacyTags(): array {
        return [];
    }

    /** accepted attributes and their defaults, also the source for the editor legend */
    static function attributeDefaults(): array {
        return [
            'id' => 'all',
            'title' => 'true',
            'description' => 'true',
            'group-description' => null,
            'limit' => 0,
        ];
    }

    /** one line on what this renders, shown above its attributes in the editor legend */
    static function shortcodeDescription(): string {
        return __('Shows as a scrolling row of artwork. Use a group id, or all for the whole network.', 'powerpress');
    }

    /** a realistic use of this shortcode, shown under its attributes */
    static function shortcodeExample(): string {
        $id = self::anyMappedId('l-', 'all');

        return '[ppn-show-carousel id="' . $id . '" limit="12"]';
    }

    /** what each attribute accepts, keyed the same as attributeDefaults() */
    static function attributeLegend(): array {
        return [
            'id' => 'a group id, from the Groups tab, or all',
            'title' => ['true', 'false'],
            'description' => ['true', 'false'],
            'group-description' => ['true', 'false'],
            'limit' => '1 or more, 0 for all',
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

        $listId = $attr['id'];

        $props = [
            'show_title' => self::flagIsTrue($attr['title'], true),
            'show_description' => self::flagIsTrue($attr['description'], true),
            'show_group_description' => self::flagIsTrue($attr['group-description'], true),
            'network_map' => $ctx['map'],
        ];

        $networkInfo = $ctx['networkInfo'];
        $networkInfo['list_id'] = $listId;
        $networkTitle = $networkInfo['network_title'];

        if (!empty($listId) && $listId != 'all') {
            $results = PowerpressNetworkDataBus::getSpecificListInNetwork($ctx['apiUrl'], $ctx['creds'], $networkInfo, false);
            if (!is_array($results) || empty($results['programs'])) {
                return PowerPressNetwork::getHTML('no-list-results.php', $props, null);
            }
            $props['results'] = $results['programs'];
            $props['list_title'] = $results['list_info']['list_title'] ?? '';
            $props['list_desc'] = $results['list_info']['list_description'] ?? '';
        } else {
            $results = PowerpressNetworkDataBus::getProgramsInNetwork($ctx['apiUrl'], $ctx['creds'], $networkInfo, true);
            $props['results']['programs'] = $results;
            $props['list_title'] = $networkTitle;
        }

        $props['network_title'] = $networkTitle;
        $props['post'] = false;
        $props['apiUrl'] = $ctx['apiUrl'];

        // a specific list carries every network program, only the checked ones belong to it
        $temp = null;
        if (!empty($props['results']['programs'])) {
            foreach ($props['results']['programs'] as $program) {
                if (($listId == 'all') || (isset($program['checked']) && $program['checked'] == true)) {
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

        $cap = max(0, (int) $attr['limit']);
        if ($cap > 0 && is_array($temp) && count($temp) > $cap) {
            $temp = array_slice($temp, 0, $cap);
        }

        $props['results'] = $temp;

        if (empty($props['list_title'])) {
            return PowerPressNetwork::getHTML('no-list-results.php', $props, null);
        }

        if (empty($props['results'])) {
            return PowerPressNetwork::getHTML('list-no-programs.php', $props, null);
        }

        return PowerPressNetwork::getHTML('carousel-results.php', $props, null);
    }
}

$GLOBALS['ppn-carousel'] = new PowerPressNetworkCarousel();
