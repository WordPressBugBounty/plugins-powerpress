<?php

// matches the 255 the network description and group description columns hold
if (!defined('PPN_EPISODE_EXCERPT_CHARS')) {
    define('PPN_EPISODE_EXCERPT_CHARS', 255);
}

class PowerPressNetworkShortCode
{
    const PREVIEW_MEDIA = 'https://media.blubrry.com/blubrry/content.blubrry.com/blubrry/MediaElement_audio.mp3';

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
            'ppn-frontend' => ['path' => 'css/ppn-frontend'],
            'ppn-frontend-js' => [
                'type' => 'script',
                'path' => 'js/ppn-frontend',
            ],
            'powerpress-blueprint' => ['path' => 'css/blueprint'],
            'powerpress-subscribe-widget' => ['path' => 'css/subscribe-widget'],
        ]);
    }

    /** tag allowlist for show notes, wp_kses_post minus what does not belong inside a list row */
    static function showNotesAllowedHtml(): array {
        $allowed = wp_kses_allowed_html('post');

        $reject = [
            'h1', 'main', 'title', 'article', 'section', 'nav', 'header', 'footer', 'aside', 'hgroup',
            'dialog', 'button', 'textarea', 'fieldset', 'legend', 'label', 'meter', 'progress', 'search',
            'object', 'map', 'area', 'menu',
        ];

        foreach ($reject as $tag) {
            unset($allowed[$tag]);
        }

        return $allowed;
    }

    /** the network's player, falling back to the default when unset or unrecognised */
    static function networkPlayer(): string {
        $networkInfo = get_option('powerpress_network', []);
        $player = is_array($networkInfo) ? ($networkInfo['player_style'] ?? '') : '';

        return PowerPressNetwork::resolvePlayer($player);
    }

    /** drop rows the api sent as scalars, the loops below all index into every member */
    static function arrayRowsOnly($rows): array {
        if (!is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /** the published page a group or the network itself is mapped to, empty when there is none */
    static function mappedPageUrl(string $mapKey, array $map): string {
        if (!isset($map[$mapKey])) {
            return '';
        }

        return get_post_status($map[$mapKey]) === 'publish' ? (string) get_permalink($map[$mapKey]) : '';
    }

    /** an id the reader actually owns, taken from the page map, else an obvious placeholder */
    static function anyMappedId(string $prefix, string $fallback): string {
        foreach (array_keys(get_option('powerpress_network_map', [])) as $key) {
            if (strpos($key, $prefix) === 0) {
                return substr($key, strlen($prefix));
            }
        }

        return $fallback;
    }

    /** read a shortcode flag, accepting the spellings a customer actually types */
    static function flagIsTrue($value, bool $default): bool {
        if ($value === null || $value === '') {
            return $default;
        }

        $flag = strtolower(trim((string) $value));

        if (in_array($flag, ['true', '1', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($flag, ['false', '0', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    /** the video counterpart of a network choice, neither blubrry player renders video */
    static function networkVideoPlayer(): string {
        return 'mediaelement-video';
    }

    /** build a player, letting the network's choice win over the site wide setting */
    static function buildPlayer(string $mediaUrl, array $episodeData): string {
        return self::buildPlayerAs(self::networkPlayer(), $mediaUrl, $episodeData);
    }

    /** build a player as a named choice, pinning video since neither choice renders it */
    static function buildPlayerAs(string $player, string $mediaUrl, array $episodeData): string {
        $video = self::networkVideoPlayer();

        $override = function ($value) use ($player, $video) {
            if (is_array($value)) {
                $value['player'] = $player;
                $value['video_player'] = $video;
            }

            return $value;
        };

        add_filter('option_powerpress_general', $override);
        $html = apply_filters('powerpress_player', '', esc_url($mediaUrl), $episodeData);
        remove_filter('option_powerpress_general', $override);

        return $html;
    }

    /** a sample of one player choice, for the manage tab's preview */
    static function previewPlayer(string $player): string {
        do_action('wp_powerpress_player_scripts');

        return self::buildPlayerAs($player, self::PREVIEW_MEDIA, []);
    }

    /** episode data the blubrry player needs to resolve a show, which is what gives it artwork */
    static function playerEpisodeData(array $props): array {
        $artwork = (isset($props['artwork_url']) && is_array($props['artwork_url'])) ? $props['artwork_url'] : [];

        return [
            'podcast_id' => (int) ($props['program_id'] ?? 0),
            'itunes_image' => (string) ($artwork['300'] ?? ''),
        ];
    }

    /** LEGACY: tags this shortcode replaced, still registered */
    static function legacyTags(): array {
        return [];
    }

    function ppn_shortcode_init()
    {
        add_shortcode($this->tag, [$this, 'ppn_shortcode']);

        foreach (static::legacyTags() as $legacy) {
            add_shortcode($legacy, [$this, 'ppn_shortcode']);
        }
    }

    /** network context, or the error html when there is no network to read */
    protected function getNetworkContext()
    {
        $networkInfo = get_option('powerpress_network', []);
        $networkInfo['network_id'] = get_option('powerpress_network_id');
        $networkInfo['network_title'] = get_option('powerpress_network_title');

        if (empty($networkInfo['network_id'])) {
            return PowerPressNetwork::getHTML('invalid-network.php', [], null);
        }

        $apiArray = powerpress_get_api_array();

        return [
            'networkInfo' => $networkInfo,
            'apiUrl' => $apiArray[0],
            'creds' => ['post' => false],
            'map' => get_option('powerpress_network_map', []),
        ];
    }

    /** cast program fields the api can return null for, null into esc_html/strlen deprecates on 8.1 */
    protected function normalizePrograms(array &$programs): void {
        $programs = self::arrayRowsOnly($programs);

        foreach ($programs as $index => $program) {
            $programs[$index]['program_title'] = (string) ($program['program_title'] ?? '');
            $programs[$index]['program_desc'] = (string) ($program['program_desc'] ?? '');
            $programs[$index]['talent_name'] = (string) ($program['talent_name'] ?? '');

            $artwork = (isset($program['artwork_url']) && is_array($program['artwork_url'])) ? $program['artwork_url'] : [];
            $artwork['300'] = (string) ($artwork['300'] ?? '');
            $programs[$index]['artwork_url'] = $artwork;
        }
    }

    /** resolve each program's page link through the network map */
    protected function resolvePageLinks(array &$programs, array $map): void
    {
        $mapped = [];
        foreach ($programs as $program) {
            $mapKey = 'p-' . ($program['program_id'] ?? '');
            if (isset($map[$mapKey])) {
                $mapped[] = (int) $map[$mapKey];
            }
        }

        if (!empty($mapped) && function_exists('_prime_post_caches')) {
            _prime_post_caches($mapped, false, false);
        }

        foreach ($programs as &$program) {
            $mapKey = 'p-' . ($program['program_id'] ?? '');
            $postId = $map[$mapKey] ?? null;
            $program['link'] = ($postId !== null && get_post_status($postId) === 'publish') ? get_permalink($postId) : '#';
        }
    }
}
