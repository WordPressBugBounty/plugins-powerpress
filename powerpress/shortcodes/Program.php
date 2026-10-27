<?php

class PowerPressNetworkProgram extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-show');
        add_action('wp_ajax_ppn_episodes', [$this, 'ppn_episodes_ajax']);
        add_action('wp_ajax_nopriv_ppn_episodes', [$this, 'ppn_episodes_ajax']);
        add_action('wp_ajax_ppn_episode_panel', [$this, 'ppn_episode_panel_ajax']);
        add_action('wp_ajax_nopriv_ppn_episode_panel', [$this, 'ppn_episode_panel_ajax']);
    }

    /** LEGACY: tags this shortcode replaced, still registered */
    static function legacyTags(): array {
        return [
            'ppn-program',
        ];
    }

    /** accepted attributes and their defaults, also the source for the editor legend */
    static function attributeDefaults(): array {
        return [
            'id' => '',
            'style' => 'full',
            'ssb-shape' => 'circle',
            'limit' => 20,
        ];
    }

    /** cast episode fields the api can return null for, null into wp_kses_post deprecates on 8.1 */
    static function normalizeEpisodes($episodes): array {
        $episodes = self::arrayRowsOnly($episodes);

        foreach ($episodes as $index => $episode) {
            $episodes[$index]['podcast_title'] = (string) ($episode['podcast_title'] ?? '');
            $episodes[$index]['podcast_desc'] = (string) ($episode['podcast_desc'] ?? '');
            $episodes[$index]['media_url'] = (string) ($episode['media_url'] ?? '');
            $episodes[$index]['podcast_post_date'] = (int) ($episode['podcast_post_date'] ?? 0);
        }

        return $episodes;
    }

    /** styles the program view renders, anything outside this set has no branch to land on */
    static function styleChoices(): array {
        return [
            'older-full',
            'artwork',
            'playlist',
            'description',
            'full',
        ];
    }

    /** subscribe button shapes the program view renders */
    static function shapeChoices(): array {
        return [
            'circle',
            'square',
        ];
    }

    /** one line on what this renders, shown above its attributes in the editor legend */
    static function shortcodeDescription(): string {
        return __('One show, with its episodes.', 'powerpress');
    }

    /** a realistic use of this shortcode, shown under its attributes */
    static function shortcodeExample(): string {
        $id = self::anyMappedId('p-', '123');

        return '[ppn-show id="' . $id . '" style="playlist"]';
    }

    /** what each attribute accepts, keyed the same as attributeDefaults() */
    static function attributeLegend(): array {
        return [
            'id' => 'a program id, from the Shows tab',
            'style' => self::styleChoices(),
            'ssb-shape' => self::shapeChoices(),
            'limit' => '1 to 200',
        ];
    }

    /** attributes declared but wired to nothing, kept out of the legend */
    static function inertAttributes(): array {
        return [];
    }

    /** clamp a shortcode style to one the view can render */
    static function normalizeStyle($value): string {
        $style = (string) $value;

        return in_array($style, self::styleChoices(), true) ? $style : self::attributeDefaults()['style'];
    }

    /** clamp a shortcode limit to the api's episode paging bounds */
    static function normalizeLimit($value): int {
        $limit = (int) $value;
        if ($limit < 1) {
            return 20;
        }

        return min($limit, 200);
    }

    /** slug of the episode the page is showing a player for, empty means fall back to newest */
    static function selectedEpisode(): string {
        if (empty($_GET['ppn_episode'])) {
            return '';
        }

        return sanitize_title(wp_unslash($_GET['ppn_episode']));
    }

    /** the playlist's player panel for one episode, swapped in without moving the player */
    function ppn_episode_panel_ajax() {
        $programId = (int) ($_POST['program_id'] ?? 0);
        $limit = self::normalizeLimit($_POST['limit'] ?? 20);
        $start = max(0, (int) ($_POST['start'] ?? 0));
        $selected = isset($_POST['selected']) ? sanitize_title(wp_unslash($_POST['selected'])) : '';
        $media = isset($_POST['media']) ? esc_url_raw(wp_unslash($_POST['media'])) : '';

        if ($programId < 1 || $selected === '') {
            wp_send_json_error('bad request');
        }

        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) {
            wp_send_json_error('no network');
        }

        $networkInfo = $ctx['networkInfo'];
        $networkInfo['program_id'] = $programId;
        $results = PowerpressNetworkDataBus::getSpecificProgramInNetwork($ctx['apiUrl'], false, $networkInfo, $start > 0, $limit, $start);

        if (!is_array($results) || !empty($results['error']) || empty($results['episodes'])) {
            wp_send_json_error('no episodes');
        }

        $props = [
            'episodes' => self::normalizeEpisodes($results['episodes']),
            'selected' => $selected,
            'media' => $media,
            'program_id' => $programId,
            'artwork_url' => $results['program_info']['artwork_url'] ?? [],
            'program_title' => (string) ($results['program_info']['program_title'] ?? ''),
        ];

        ob_start();
        include(dirname(__FILE__) . '/views/program-playlist-panel.php');
        $html = ob_get_clean();

        if ($html === '') {
            wp_send_json_error('not found');
        }

        wp_send_json_success([
            'html' => $html,
            'selected' => $selected,
        ]);
    }

    /** one more page of episode rows, for the Load more button */
    function ppn_episodes_ajax() {
        $programId = (int) ($_POST['program_id'] ?? 0);
        $start = (int) ($_POST['start'] ?? 0);
        $limit = self::normalizeLimit($_POST['limit'] ?? 20);
        $style = (($_POST['style'] ?? 'full') === 'playlist') ? 'playlist' : 'full';

        if ($programId < 1 || $start < 0) {
            wp_send_json_error('bad request');
        }

        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) {
            wp_send_json_error('no network');
        }

        $networkInfo = $ctx['networkInfo'];
        $networkInfo['program_id'] = $programId;
        $results = PowerpressNetworkDataBus::getSpecificProgramInNetwork($ctx['apiUrl'], false, $networkInfo, true, $limit, $start);

        if (!is_array($results) || !empty($results['error'])) {
            wp_send_json_error('unavailable');
        }

        if (empty($results['episodes'])) {
            wp_send_json_success([
                'html' => '',
                'has_more' => false,
                'count' => 0,
            ]);
        }

        $props = [
            'episodes' => self::normalizeEpisodes($results['episodes']),
            'style' => $style,
            'program_id' => $programId,
            'artwork_url' => $results['program_info']['artwork_url'] ?? [],
            'word_break' => $style !== 'playlist',
            'row_spacer' => $style === 'playlist',
            'selected' => isset($_POST['selected']) ? sanitize_title(wp_unslash($_POST['selected'])) : '',
            'offset' => $start,
        ];

        ob_start();
        include(dirname(__FILE__) . '/views/program-episodes.php');
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'has_more' => !empty($results['has_more']),
            'count' => count($results['episodes']),
        ]);
    }

    function ppn_shortcode($attr, $contents)
    {
        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) return $ctx;

        $attr = shortcode_atts(self::attributeDefaults(), $attr, $this->tag);

        $program_id = $attr['id'];

        $props = [
            'program_id' => $program_id,
            'style' => self::normalizeStyle($attr['style']),
            'ssb-shape' => $attr['ssb-shape'],
            'limit' => self::normalizeLimit($attr['limit']),
        ];

        $networkInfo = $ctx['networkInfo'];
        $networkInfo['program_id'] = $program_id;

        $results = PowerpressNetworkDataBus::getSpecificProgramInNetwork($ctx['apiUrl'], false, $networkInfo, false, $props['limit'], 0);
        $unavailable = esc_html__('This show is currently unavailable.', 'powerpress');

        if (!$results || !is_array($results)) {
            return "<p>{$unavailable}</p>";
        }

        if (isset($results['error'])) {
            if ($results['error'] == 'Fail To Find Your Program In This Network') {
                return PowerPressNetwork::getHTML('no-program-results.php', $props, null);
            } else if ($results['error'] == 'Your account does not have the network with specified id') {
                return PowerPressNetwork::getHTML('invalid-network.php', $props, null);
            }
            $detail = is_scalar($results['error']) ? (string) $results['error'] : wp_json_encode($results['error']);
            error_log("PowerPress network shortcode error: {$detail}");

            return "<p>{$unavailable}</p>";
        }

        $props['episodes'] = self::normalizeEpisodes($results['episodes'] ?? []);
        $props['has_more'] = !empty($results['has_more']);
        $props['selected'] = self::selectedEpisode();
        $props['offset'] = 0;
        $props['artwork_url'] = $results['program_info']['artwork_url'] ?? [];
        $props['program_title'] = (string) ($results['program_info']['program_title'] ?? '');
        $props['program_desc'] = (string) ($results['program_info']['program_desc'] ?? '');
        $props['talent_name'] = (string) ($results['program_info']['talent_name'] ?? '');
        $props['program_url'] = (string) ($results['program_info']['program_htmlurl'] ?? '');
        $props['program_rssurl'] = (string) ($results['program_info']['program_rssurl'] ?? '');
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

        return PowerPressNetwork::getHTML('program-result.php', $props, null);
    }
}
$GLOBALS['ppn_program'] = new PowerPressNetworkProgram();