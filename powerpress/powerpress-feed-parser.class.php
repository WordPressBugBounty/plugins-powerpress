<?php

require_once( ABSPATH . WPINC . '/class-simplepie.php' );

use SimplePie\SimplePie;

class PowerPress_FeedParser {

    private const NS_ITUNES = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
    private const NS_PODCAST = 'https://podcastindex.org/namespace/1.0';
    private const NS_RAWVOICE = 'https://blubrry.com/developer/rawvoice-rss/';
    private const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';

    private const MAX_PER_COLLECTION = 100;

    private $rss;

    private function __construct(SimplePie $rss) {
        $this->rss = $rss;
    }

    /** parse raw xml, returns WP_Error on failure */
    public static function from_raw(string $xml) {
        // normalize podcast namespace url
        $xml = str_replace(
                'https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md',
                self::NS_PODCAST,
                $xml
        );

        libxml_use_internal_errors(true);

        $rss = new SimplePie();

        // xss sanitizer
        if (class_exists('WP_SimplePie_Sanitize_KSES')) {
            $rss->sanitize = new WP_SimplePie_Sanitize_KSES();
        }

        $rss->set_raw_data($xml);
        $rss->enable_order_by_date(false);
        $rss->enable_cache(false);
        $rss->init();

        if ($rss->error()) {
            error_log('PowerPress feed parse error: ' . $rss->error());
            return new WP_Error('powerpress_feed_parse', __('The feed could not be parsed.', 'powerpress'));
        }

        $rss->set_output_encoding(get_option('blog_charset'));
        $rss->handle_content_type();

        return new self($rss);
    }


    // ------------------------------------------------------------------------

    // ===============
    // CHANNEL HELPERS
    // ===============

    /** read all occurrences of a channel tag */
    private function channel_tags_all(string $namespace, string $tag_name): array {
        $tags = $this->rss->get_channel_tags($namespace, $tag_name);
        return is_array($tags) ? $tags : [];
    }

    /** read text content of a channel tag */
    private function channel_tag_text(string $namespace, string $tag_name): ?string {
        $tags = $this->rss->get_channel_tags($namespace, $tag_name);
        if (empty($tags)) return null;

        $data = trim((string) ($tags[0]['data'] ?? ''));
        return $data !== '' ? $data : null;
    }

    /** read a specific attribute from a channel tag */
    private function channel_tag_attr(string $namespace, string $tag_name, string $attr_name): ?string {
        $tags = $this->rss->get_channel_tags($namespace, $tag_name);
        if (empty($tags)) return null;

        $value = trim((string) ($tags[0]['attribs'][''][$attr_name] ?? ''));
        return $value !== '' ? $value : null;
    }

    /** read text content of a tag nested in a channel parent tag */
    private function channel_nested_tag_text(string $parent_namespace, string $parent_name, string $child_namespace, string $child_name): ?string {
        $parents = $this->rss->get_channel_tags($parent_namespace, $parent_name);
        if (empty($parents)) return null;

        $children = $parents[0]['child'][$child_namespace][$child_name] ?? null;
        if (empty($children)) return null;

        $data = trim((string) ($children[0]['data'] ?? ''));
        return $data !== '' ? $data : null;
    }

    /** read an attribute from a tag */
    private function tag_attr_value(array $tag, string $attr_name): ?string {
        $value = trim((string) ($tag['attribs'][''][$attr_name] ?? ''));
        return $value !== '' ? $value : null;
    }

    /** function for sanitizing urls */
    private function clean_url(?string $url): ?string {
        if ($url === null) return null;
        $clean = esc_url_raw(trim($url));
        return $clean !== '' ? $clean : null;
    }

    private function parse_split(?string $value): ?int {
        if ($value === null || !preg_match('/^\d+$/', trim($value))) return null;
        return (int) trim($value);
    }

    // ======================
    // BASIC CHANNEL HANDLERS
    // ======================
    
    // <title>
    public function get_channel_title(): ?string {
        $value = trim((string) $this->rss->get_title());
        return $value !== '' ? $value : null;
    }

    // <link>
    public function get_channel_link(): ?string {
        $value = trim((string) $this->rss->get_link());
        return $value !== '' ? $value : null;
    }

    // <description>
    public function get_channel_description(): ?string {
        $value = trim((string) $this->rss->get_description());
        return $value !== '' ? $value : null;
    }

    // <language>
    public function get_channel_language(): ?string {
        $value = trim((string) $this->rss->get_language());
        return $value !== '' ? $value : null;
    }

    // <copyright>
    public function get_channel_copyright(): ?string {
        $value = trim((string) $this->rss->get_copyright());
        return $value !== '' ? $value : null;
    }

    // =======================
    // ITUNES CHANNEL HANDLERS
    // =======================

    // <itunes:author>
    public function get_channel_author(): ?string {
        return $this->channel_tag_text(self::NS_ITUNES, 'author');
    }

    // <itunes:explicit>
    public function get_channel_explicit(): ?int {
        $value = $this->channel_tag_text(self::NS_ITUNES, 'explicit');
        if ($value === null) return null;

        $map = ['true' => 1, 'yes' => 1, 'no' => 2, 'false' => 2, 'clean' => 2];
        return $map[strtolower($value)] ?? null;
    }

    // <itunes:type>
    public function get_channel_type(): ?string {
        return $this->channel_tag_text(self::NS_ITUNES, 'type');
    }

    // <itunes:image href>
    public function get_channel_artwork(): ?string {
        return $this->channel_tag_attr(self::NS_ITUNES, 'image', 'href');
    }

    // <itunes:owner><itunes:name>
    public function get_channel_owner_name(): ?string {
        return $this->channel_nested_tag_text(self::NS_ITUNES, 'owner', self::NS_ITUNES, 'name');
    }

    // <itunes:owner><itunes:email>
    public function get_channel_owner_email(): ?string {
        return $this->channel_nested_tag_text(self::NS_ITUNES, 'owner', self::NS_ITUNES, 'email');
    }

    // <itunes:category>
    // multiple occurences + optional nested subcategory
    public function get_channel_categories(): array {
        // 1. COLLECT CATEGORIES
        $parents = $this->channel_tags_all(self::NS_ITUNES, 'category');
        if (empty($parents)) return [];

        $texts = [];
        foreach ($parents as $parent) {
            $parent_text = trim((string) ($parent['attribs']['']['text'] ?? ''));
            if ($parent_text !== '') {
                $texts[] = $parent_text;
            }

            $subcats = $parent['child'][self::NS_ITUNES]['category'] ?? [];
            foreach ($subcats as $subcat) {
                $subcat_text = trim((string) ($subcat['attribs']['']['text'] ?? ''));
                if ($subcat_text !== '') {
                    $texts[] = $subcat_text;
                }
            }
        }
        if (empty($texts)) return [];

        // 2. MAP EACH NAME TO XX-YY APPLE CODE
        $apple_cats = powerpress_apple_categories();
        $apple_cats = array_map('strtolower', $apple_cats);
        $codes_by_name = array_flip($apple_cats);

        $found = [];
        foreach ($texts as $text) {
            $key = strtolower($text);
            if (!empty($codes_by_name[$key])) {
                $found[] = $codes_by_name[$key];
            }
        }
        if (empty($found)) return [];

        // 3. STACK CODES INTO 3 SLOTS, SUBCATS OVERRIDE PARENT CAT, MAX 3
        $slots = ['', '', ''];
        $idx = 0;
        foreach ($found as $code) {
            if (!empty($slots[$idx])) {
                // advance if subcast assigned
                if (intval(substr($slots[$idx], 3)) > 0) {
                    $idx++;
                } 
                // advance if subcat has different cat family
                elseif (intval(substr($slots[$idx], 0, 2)) !== intval(substr($code, 0, 2))) {
                    $idx++;
                }
            }
            if ($idx > 2) break;
            $slots[$idx] = $code;
        }

        // 4. DROP EMPTY, REINDEX, RETURN
        return array_values(array_filter($slots));
    }

    // <itunes:complete>
    public function get_channel_complete(): ?int {
        $text = $this->channel_tag_text(self::NS_ITUNES, 'complete');
        if ($text === null) return null;
        
        return in_array(strtolower($text), ['yes', 'true'], true) ? 1 : null;
    }

    // <itunes:block> + <podcast:block>
    public function get_channel_block(): ?array {
        // EXTRACT ITUNES BLOCK
        $itunes_text = $this->channel_tag_text(self::NS_ITUNES, 'block');
        $itunes_block = ($itunes_text !== null && in_array(strtolower($itunes_text),['yes', 'true'], true)) ? 1: null;

        // EXTRACT PODCAST INDEX BLOCK
        $block_all = null;
        foreach ($this->rss->get_channel_tags(self::NS_PODCAST, 'block') ?? [] as $tag) {
            if (isset($tag['attribs']['']['id']) && trim((string) $tag['attribs']['']['id']) !== '') continue;
            if (in_array(strtolower(trim((string) ($tag['data'] ?? ''))), ['yes', 'true'], true)) {
                $block_all = 1;
                break;
            }
        }

        if ($itunes_block === null && $block_all === null) return null;
        
        return [
            'itunes_block' => $itunes_block,
            'block_all' => $block_all,
        ];
    }

    // ==============================
    // PODCAST INDEX CHANNEL HANDLERS
    // ==============================

    // <podcast:locked>
    public function get_channel_locked(): bool {
        $value = $this->channel_tag_text(self::NS_PODCAST, 'locked');
        return $value !== null && strtolower($value) === 'yes';
    }

    // <podcast:medium>
    public function get_channel_medium(): ?string {
        return $this->channel_tag_text(self::NS_PODCAST, 'medium');
    }

    // <podcast:guid>
    public function get_channel_guid(): ?string {
        return $this->channel_tag_text(self::NS_PODCAST, 'guid');
    }

    // <podcast:license url>
    public function get_channel_license(): ?array {
        $tags = $this->rss->get_channel_tags(self::NS_PODCAST, 'license');
        if (empty($tags)) return null;

        // EXTRACT TEXT
        $text = trim((string) ($tags[0]['data'] ?? ''));

        // EXTRACT URL
        $url = trim((string) ($tags[0]['attribs']['']['url'] ?? ''));
        $url = $url !== '' ? $url : null;

        if ($text === '' && $url === null) return null;
        return [
            'url' => $url,
            'copyright' => $text !== '' ? $text : null,
        ];
    }

    // <podcast:funding url>
    public function get_channel_funding(): ?array {
        $tags = $this->rss->get_channel_tags(self::NS_PODCAST, 'funding');
        if (empty($tags)) return null;

        // EXTRACT TEXT
        $text = trim((string) ($tags[0]['data'] ?? ''));

        // EXTRACT URL
        $url = trim((string) ($tags[0]['attribs']['']['url'] ?? ''));
        $url = $url !== '' ? $url : null;

        if ($text === '' && $url === null) return null;
        return [
            'url' => $url,
            'label' => $text !== '' ? $text : null,
        ];
    }

    // <podcast:person>
    // multiple occurences
    public function get_channel_persons(): array {
        $tags = $this->channel_tags_all(self::NS_PODCAST, 'person');
        if (empty($tags)) return [];

        $persons = [];
        foreach ($tags as $tag) {
            $name = trim((string) ($tag['data'] ?? ''));
            if ($name === '') continue;

            $persons[] = [
                'name' => $name,
                'role' => $this->tag_attr_value($tag, 'role'),
                'person_url' => $this->tag_attr_value($tag, 'img'),
                'link_url' => $this->tag_attr_value($tag, 'href'),
            ];
        }
        return $persons;
    }

    // <podcast:location>
    // multiple occurences
    public function get_channel_locations(): array {
        $tags = $this->channel_tags_all(self::NS_PODCAST, 'location');
        if (empty($tags)) return [];

        $locations = [];
        foreach ($tags as $tag) {
            $address = trim((string) ($tag['data'] ?? ''));
            if ($address === '') continue;

            $locations[] = [
                'address' => $address,
                'pci_geo' => $this->tag_attr_value($tag, 'geo'),
                'pci_osm' => $this->tag_attr_value($tag, 'osm'),
                'pci_country' => $this->tag_attr_value($tag, 'country'),
            ];
        }
        return $locations;
    }

    // <podcast:podroll>
    // parent tag w/ nested remoteItems
    public function get_channel_podroll(): array {
        $podrolls = $this->channel_tags_all(self::NS_PODCAST, 'podroll');
        if (empty($podrolls)) return [];

        $items = [];
        foreach ($podrolls as $podroll) {
            $remotes = $podroll['child'][self::NS_PODCAST]['remoteItem'] ?? [];
            foreach ($remotes as $remote) {
                $feed_guid = $this->tag_attr_value($remote, 'feedGuid');
                if ($feed_guid === null) continue;

                $items[] = [
                    'podroll' => 1,
                    'feed_guid' => $feed_guid,
                    'item_guid' => $this->tag_attr_value($remote, 'itemGuid'),
                    'item_link' => $this->tag_attr_value($remote, 'feedUrl'),
                    'medium' => $this->tag_attr_value($remote, 'medium'),
                    'item_title' => $this->tag_attr_value($remote, 'title'),
                ];
            }
        }
        return $items;
    }

    // <podcast:remoteItem>
    // remote items outside of podroll
    public function get_channel_remote_items(): array {
        $remotes = $this->channel_tags_all(self::NS_PODCAST, 'remoteItem');
        if (empty($remotes)) return [];

        $items = [];
        foreach ($remotes as $remote) {
            $feed_guid = $this->tag_attr_value($remote, 'feedGuid');
            if ($feed_guid === null) continue;

            $items[] = [
                'podroll' => 0,
                'feed_guid' => $feed_guid,
                'item_guid' => $this->tag_attr_value($remote, 'itemGuid'),
                'item_link' => $this->tag_attr_value($remote, 'feedUrl'),
                'medium' => $this->tag_attr_value($remote, 'medium'),
                'item_title' => $this->tag_attr_value($remote, 'title'),
            ];
        }
        return $items;
    }

    // <podcast:value> -> <podcast:valueRecipient>
    // can have multiple nested valueRecipients
    public function get_channel_value_recipients(): array {
        $values = $this->channel_tags_all(self::NS_PODCAST, 'value');
        if (empty($values)) return [];

        $recipients = [];
        foreach ($values as $value) {
            $vrs = $value['child'][self::NS_PODCAST]['valueRecipient'] ?? [];
            foreach ($vrs as $vr) {
                $address = $this->tag_attr_value($vr, 'address');
                $split = $this->tag_attr_value($vr, 'split');
                if ($address === null || $split === null || !is_numeric($split)) continue;

                $fee_raw = $this->tag_attr_value($vr, 'fee');
                $fee = ($fee_raw !== null && in_array(strtolower($fee_raw), ['true', 'yes', '1'], true)) ? 'true' : null;

                $recipients[] = [
                    'pubkey' => $address,
                    'split' => (int) $split,
                    'name' => $this->tag_attr_value($vr, 'name'),
                    'customKey' => $this->tag_attr_value($vr, 'customKey'),
                    'customValue' => $this->tag_attr_value($vr, 'customValue'),
                    'fee' => $fee,
                ];
            }
        }
        return $recipients;
    }

    // <podcast:updateFrequency rrule>
    public function get_channel_update_frequency(): ?array {
        $rrule = $this->channel_tag_attr(self::NS_PODCAST, 'updateFrequency', 'rrule');
        $complete = $this->channel_tag_attr(self::NS_PODCAST, 'updateFrequency', 'complete');
        $dtstart = $this->channel_tag_attr(self::NS_PODCAST, 'updateFrequency', 'dtstart');
        if ($rrule === null && $complete === null && $dtstart === null) return null;

        $result = [];
        
        // 1. PARSE RRULE
        if ($rrule !== null) {
            $parts = [];
            foreach (explode(';', $rrule) as $segment) {
                if (strpos($segment, '=') === false) continue;
                [$k, $v] = explode('=', $segment, 2);
                $parts[strtolower(trim($k))] = trim($v);
            }

            // MAP MODIFIERS
            if (isset($parts['freq']))
                $result['freq'] = strtoupper($parts['freq']);

            if (isset($parts['byday']))
                $result['byday'] = $parts['byday'];

            if (isset($parts['bymonth']))
                $result['bymonth'] = $parts['bymonth'];

            if (isset($parts['bymonthday']))
                $result['bymonthday'] = $parts['bymonthday'];

            if (isset($parts['count']) && is_numeric($parts['count']))
                $result['count'] = (int) $parts['count'];

            if (isset($parts['interval']) && is_numeric($parts['interval']))
                $result['interval'] = (int) $parts['interval'];

            if (isset($parts['until']))
                $result['until'] = $parts['until'];
        }

        // 2. PARSE COMPLETE
        if ($complete !== null)
            $result['complete'] = (strtolower($complete) === 'true');

        // 3. PARSE DTSTART
        if ($dtstart !== null)
            $result['dtstart'] = $dtstart;

        return !empty($result) ? $result : null;
    }

    // <podcast:txt>
    public function get_channel_txt_tags(): array {
        $tags = $this->rss->get_channel_tags(self::NS_PODCAST, 'txt');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            $text = trim((string) ($tag['data'] ?? ''));
            if ($text === '') continue;

            $purpose = $tag['attribs']['']['purpose'] ?? null;
            $result[] = [
                'tag' => $text,
                'purpose' => $purpose,
            ];
        }

        return $result;
    }

    // <podcast:liveItem>
    public function get_channel_live_item(): ?array {
        $tags = $this->rss->get_channel_tags(self::NS_PODCAST, 'liveItem');
        if (empty($tags)) return null;

        $tag = $tags[0];
        $attribs = $tag['attribs'][''] ?? [];
        $children = $tag['child'] ?? [];

        $childText = function (string $ns, string $name) use ($children): ?string {
            $text = $children[$ns][$name][0]['data'] ?? null;
            if ($text === null) return null;

            $text = trim((string) $text);
            return $text !== '' ? $text : null;
        };

        $childAttr = function (string $ns, string $name, string $attr) use ($children): ?string {
            $value = $children[$ns][$name][0]['attribs'][''][$attr] ?? null;
            if ($value === null) return null;

            $value = trim((string) $value);
            return $value !== '' ? $value : null;
        };

        $parseDate = function (?string $iso): ?string {
            if ($iso === null) return null;

            try {
                return (new \DateTime($iso))->format('Y-m-d\TH:i:s');
            } catch (\Exception $e) {
                return null;
            }
        };

        $result = [
            'enabled' => '1',
            'status' => $attribs['status'] ?? null,
            'start_date_time' => $parseDate($attribs['start'] ?? null),
            'end_date_time' => $parseDate($attribs['end'] ?? null),
            'title' => $childText('', 'title'),
            'guid' => $childText('', 'guid'),
            'description' => $childText('', 'description'),
            'episode_link' => $childText('', 'link'),
            'stream_link' => $childAttr('', 'enclosure', 'url'),
            'stream_type' => $childAttr('', 'enclosure', 'type'),
            'fallback_link' => $childAttr(self::NS_PODCAST, 'contentLink', 'href'),
            'timezone' => $childText(self::NS_PODCAST, 'timezone'),
        ];

        $srcset = $childAttr(self::NS_PODCAST, 'images', 'srcset');
        if ($srcset !== null) {
            $url = preg_replace('/\s+\S+$/', '', $srcset);
            if ($url !== null && $url !== '') {
                $result['cover_art'] = $url;
                $result['coverart_link'] = '1';
            }
        }

        return $result;
    }

    // =========================
    // RAWVOICE CHANNEL HANDLERS
    // =========================

    // <rawvoice:rating>
    public function get_channel_rating(): ?string {
        return $this->channel_tag_text(self::NS_RAWVOICE, 'rating');
    }

    // <rawvoice:frequency>
    public function get_channel_frequency(): ?string {
        return $this->channel_tag_text(self::NS_RAWVOICE, 'frequency');
    }

    // <rawvoice:donate href>
    public function get_channel_donate(): ?array {
        $tags = $this->rss->get_channel_tags(self::NS_RAWVOICE, 'donate');
        if (empty($tags)) return null;

        $text = trim((string) ($tags[0]['data'] ?? ''));
        $url = trim((string) ($tags[0]['attribs']['']['href'] ?? ''));
        $url = $url !== '' ? $url : null;
        if ($text === '' && $url === null) return null;

        return [
            'url' => $url,
            'label' => $text !== '' ? $text : null,
        ];
    }

    // <rawvoice:subscribe>
    public function get_channel_subscribe_urls(): array {
        $tags = $this->rss->get_channel_tags(self::NS_RAWVOICE, 'subscribe');
        if (empty($tags)) return [];

        $attr_map = [
            'html' => 'subscribe_page_link_href',
            'itunes' => 'itunes_url',
            'tunein' => 'tunein_url',
            'spotify' => 'spotify_url',
            'amazon_music' => 'amazon_url',
            'pcindex' => 'pcindex_url',
            'iheart' => 'iheart_url',
            'pandora' => 'pandora_url',
            'deezer' => 'deezer_url',
            'jiosaavn' => 'jiosaavn_url',
            'podchaser' => 'podchaser_url',
            'gaana' => 'gaana_url',
            'anghami' => 'anghami_url',
            'youtube' => 'youtube_url',
        ];

        $result = [];
        $attribs = $tags[0]['attribs'][''] ?? [];
        foreach ($attr_map as $tag_attr => $key) {
            if (isset($attribs[$tag_attr]) && trim((string) $attribs[$tag_attr]) !== '') {
                $result[$key] = trim((string) $attribs[$tag_attr]);
            }
        }
        return $result;
    }

    // ------------------------------------------------------------------------

    // ============
    // ITEM HELPERS
    // ============

    // <item>
    public function get_items(): ?array {
        return $this->rss->get_items();
    }

    /** read text content of a single item tag */
    private function item_tag_text($item, string $namespace, string $tag_name): ?string {
        $tags = $item->get_item_tags($namespace, $tag_name);
        if (empty($tags)) return null;

        $data = trim((string) ($tags[0]['data'] ?? ''));
        return $data !== '' ? $data : null;
    }

    /** read a specific attribute from a single item tag */
    private function item_tag_attr($item, string $namespace, string $tag_name, string $attr_name): ?string {
        $tags = $item->get_item_tags($namespace, $tag_name);
        if (empty($tags)) return null;

        $value = trim((string) ($tags[0]['attribs'][''][$attr_name] ?? ''));
        return $value !== '' ? $value : null;
    }

    // ===================
    // BASIC ITEM HANDLERS
    // ===================

    // <enclosure>
    public function get_item_enclosure($item) {
        $enc = $item->get_enclosure();
        return $enc ?: null;
    }

    // <enclosure url>
    public function get_item_enclosure_url($enc): ?string {
        if (!$enc) return null;

        $url = $enc->get_link();
        return $url ? trim($url) : null;
    }

    // <enclosure length>
    public function get_item_enclosure_length($enc): ?int {
        if (!$enc) return null;

        $length = $enc->get_length();
        if ($length === null || $length === '') return null;

        $n = (int) $length;
        return $n > 0 ? $n : null;
    }

    // <enclosure type>
    public function get_item_enclosure_type($enc): ?string {
        if (!$enc) return null;

        $type = $enc->get_type();
        return $type ? trim($type) : null;
    }

    // <title>
    public function get_item_post_title($item): ?string {
        return $this->item_tag_text($item, '', 'title');
    }

    // <guid>
    public function get_item_guid($item): ?string {
        return $this->item_tag_text($item, '', 'guid');
    }

    // <pubDate>
    public function get_item_pubdate($item): ?int {
        $ts = $item->get_date('U');
        return ($ts !== null && $ts !== '') ? (int) $ts : null;
    }

    // <category>
    public function get_item_categories($item): array {
        $cats = $item->get_categories();
        if (empty($cats)) return [];

        $labels = [];
        foreach ($cats as $cat) {
            if (count($labels) >= self::MAX_PER_COLLECTION) break;

            $label = $cat->get_label();
            if ($label !== null && trim($label) !== '') {
                $labels[] = trim($label);
            } 
        }
        return $labels;
    }

    // <content:encoded / <description>
    public function get_item_content($item): ?string {
        $content = $this->item_tag_text($item, self::NS_CONTENT, 'encoded');
        if ($content !== null) return $content;

        return $this->item_tag_text($item, '', 'description');
    }

    // ====================
    // ITUNES ITEM HANDLERS
    // ====================

    // <itunes:duration>
    public function get_item_duration($item): ?string {
        return $this->item_tag_text($item, self::NS_ITUNES, 'duration');
    }

    // <itunes:author>
    public function get_item_author($item): ?string {
        return $this->item_tag_text($item, self::NS_ITUNES, 'author');
    }

    // <itunes:block>
    public function get_item_block($item): ?int {
        $value = $this->item_tag_text($item, self::NS_ITUNES, 'block');
        if (empty($value)) return null;

        $map = ['yes' => 1, 'true' => 1];
        return $map[strtolower($value)] ?? null;
    }

    // <itunes:image href>
    public function get_item_artwork($item): ?string {
        return $this->item_tag_attr($item, self::NS_ITUNES, 'image', 'href');
    }

    // <itunes:explicit>
    public function get_item_explicit($item): ?int {
        $value = $this->item_tag_text($item, self::NS_ITUNES, 'explicit');
        if ($value === null) return null;

        $map = ['true' => 1, 'yes' => 1, 'no' => 2, 'false' => 2, 'clean' => 2];
        return $map[strtolower($value)] ?? null;
    }

    // <itunes:summary>
    public function get_item_summary($item): ?string {
        return $this->item_tag_text($item, self::NS_ITUNES, 'summary');
    }

    // <itunes:title>
    public function get_item_title($item): ?string {
        return $this->item_tag_text($item, self::NS_ITUNES, 'title');
    }

    // <itunes:episodeType>
    public function get_item_episode_type($item): ?string {
        return $this->item_tag_text($item, self::NS_ITUNES, 'episodeType');
    }

    // <itunes:season>
    public function get_item_itunes_season($item): ?string {
        return $this->item_tag_text($item, self::NS_ITUNES, 'season');
    }

    // <itunes:episode>
    public function get_item_itunes_episode($item): ?int {
        $text = $this->item_tag_text($item, self::NS_ITUNES, 'episode');
        if ($text !== null && is_numeric($text))
            return (int) $text;

        return null;
    }

    // ===========================
    // PODCAST INDEX ITEM HANDLERS
    // ===========================

    // <podcast:season>
    public function get_item_pci_season($item): ?string {
        return $this->item_tag_text($item, self::NS_PODCAST, 'season');
    }

    // <podcast:episode>
    public function get_item_pci_episode($item): ?array {
        $text = $this->item_tag_text($item, self::NS_PODCAST, 'episode');
        $display = $this->item_tag_attr($item, self::NS_PODCAST, 'episode', 'display');
        if ($text !== null && is_numeric($text)) {
            return [
                'number' => (int) $text,
                'display' => $display ?? null,
            ];
        }

        return null;
    }

    // <podcast:funding url>
    public function get_item_funding($item): ?array {
        $url = $this->item_tag_attr($item, self::NS_PODCAST, 'funding', 'url');
        $label = $this->item_tag_text($item, self::NS_PODCAST, 'funding');
        if ($url === null && $label === null) return null;

        return ['url' => $url, 'label' => $label];
    }

    // <podcast:license url>
    public function get_item_license($item): ?array {
        $url = $this->item_tag_attr($item, self::NS_PODCAST, 'license', 'url');
        $copyright = $this->item_tag_text($item, self::NS_PODCAST, 'license');
        if ($url === null && $copyright === null) return null;

        return ['url' => $url, 'copyright' => $copyright];
    }

    // <podcast:chapters url>
    public function get_item_chapters($item): ?string {
        return $this->item_tag_attr($item, self::NS_PODCAST, 'chapters', 'url');
    }

    // <podcast:transcript url language>
    public function get_item_transcript($item): ?array {
        $url = $this->item_tag_attr($item, self::NS_PODCAST, 'transcript', 'url');
        $lang = $this->item_tag_attr($item, self::NS_PODCAST, 'transcript', 'language');
        if ($url === null) return null;

        return [
            'url' => $url,
            'language' => $lang,
        ];
    }

    // <podcast:person>
    public function get_item_persons($item): array {
        $tags = $item->get_item_tags(self::NS_PODCAST, 'person');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            if (count($result) >= self::MAX_PER_COLLECTION) break;

            $name = trim((string) ($tag['data'] ?? ''));
            if ($name === '') continue;

            $role = trim((string) ($tag['attribs']['']['role'] ?? ''));
            $img = trim((string) ($tag['attribs']['']['img'] ?? ''));
            $href = trim((string) ($tag['attribs']['']['href'] ?? ''));

            $result[] = [
                'name' => $name,
                'role' => $role !== '' ? $role : null,
                'person_url' => $this->clean_url($img),
                'link_url' => $this->clean_url($href),
            ];
        }
        return $result;
    }

    // <podcast:soundbite>
    public function get_item_soundbites($item): array {
        $tags = $item->get_item_tags(self::NS_PODCAST, 'soundbite');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            if (count($result) >= self::MAX_PER_COLLECTION) break;

            $start = $tag['attribs']['']['startTime'] ?? null;
            $duration = $tag['attribs']['']['duration'] ?? null;
            if ($start === null || $duration === null) continue;
            if (!is_numeric($start) || !is_numeric($duration)) continue;

            $title = trim((string) ($tag['data'] ?? ''));

            $result[] = [
                'start' => trim($start),
                'duration' => trim($duration),
                'title' => $title !== '' ? $title : null,
            ];
        }
        return $result;
    }

    // <podcast:location>
    public function get_item_locations($item): array {
        $tags = $item->get_item_tags(self::NS_PODCAST, 'location');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            if (count($result) >= self::MAX_PER_COLLECTION) break;

            $address = trim((string) ($tag['data'] ?? ''));
            if ($address === '') continue;

            $geo = trim((string) ($tag['attribs']['']['geo'] ?? ''));
            $osm = trim((string) ($tag['attribs']['']['osm'] ?? ''));
            $rel = trim((string) ($tag['attribs']['']['rel'] ?? ''));
            $country = trim((string) ($tag['attribs']['']['country'] ?? ''));

            $rel_storage = null;
            if ($rel !== '') {
                $rel_storage = ($rel === 'creator') ? '0' : '1';
            }

            $result[] = [
                'address' => $address,
                'geo' => $geo !== '' ? $geo : null,
                'osm' => $osm !== '' ? $osm : null,
                'rel' => $rel_storage,
                'country' => $country !== '' ? $country : null,
            ];
        }
        return $result;
    }

    // <podcast:contentLink>
    public function get_item_content_links($item): array {
        $tags = $item->get_item_tags(self::NS_PODCAST, 'contentLink');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            if (count($result) >= self::MAX_PER_COLLECTION) break;

            $href = $this->clean_url($tag['attribs']['']['href'] ?? null);
            if ($href === null) continue;

            $label = trim((string) ($tag['data'] ?? ''));

            $result[] = [
                'url' => $href,
                'label' => $label !== '' ? $label : null,
            ];
        }
        return $result;
    }

    // <podcast:txt>
    public function get_item_txt_tags($item): array {
        $tags = $item->get_item_tags(self::NS_PODCAST, 'txt');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            if (count($result) >= self::MAX_PER_COLLECTION) break;

            $text = trim((string) ($tag['data'] ?? ''));
            if ($text === '') continue;

            $purpose = trim((string) ($tag['attribs']['']['purpose'] ?? ''));
            $purpose = $purpose !== '' ? $purpose : null;

            $result[] = [
                'tag' => $text,
                'purpose' => $purpose,
            ];
        }
        return $result;
    }

    // <podcast:alternateEnclosure>
    public function get_item_alternate_enclosures($item): array {
        $tags = $item->get_item_tags(self::NS_PODCAST, 'alternateEnclosure');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            if (count($result) >= self::MAX_PER_COLLECTION) break;

            $sources = $tag['child'][self::NS_PODCAST]['source'] ?? [];
            if (empty($sources)) continue;

            $primary_uri = '';
            $primary_index = -1;
            $source_count = count($sources);
            for ($i = 0; $i < $source_count; $i++) {
                $candidate = $this->clean_url($sources[$i]['attribs']['']['uri'] ?? '');
                if ($candidate !== '') {
                    $primary_uri = $candidate;
                    $primary_index = $i;
                    break;
                }
            }
            if ($primary_uri === '') continue;

            $attrs = $tag['attribs'][''] ?? [];
            $content_type = trim((string) ($attrs['type'] ?? ''));

            $uris = [];
            for ($i = $primary_index + 1; $i < $source_count; $i++) {
                if (count($uris) >= self::MAX_PER_COLLECTION) break;

                $uri = $this->clean_url($sources[$i]['attribs']['']['uri'] ?? '');
                if ($uri === '') continue;

                $src_ct = trim((string) ($sources[$i]['attribs']['']['contentType'] ?? ''));

                $uris[] = [
                    'uri' => $uri,
                    'contentType' => $src_ct !== '' ? $src_ct : null,
                ];
            }

            $title = trim((string) ($attrs['title'] ?? ''));
            $lang = trim((string) ($attrs['lang'] ?? ''));
            $rel = trim((string) ($attrs['rel'] ?? ''));
            $codecs = trim((string) ($attrs['codecs'] ?? ''));
            $length = trim((string) ($attrs['length'] ?? ''));
            $bitrate = trim((string) ($attrs['bitrate'] ?? ''));
            $height = trim((string) ($attrs['height'] ?? ''));
            $default = strtolower(trim((string) ($attrs['default'] ?? '')));

            $result[] = [
                'url' => $primary_uri,
                'type' => $content_type !== '' ? $content_type : null,
                'length' => ($length !== '' && (int) $length >= 0) ? (int) $length : null,
                'bitrate' => ($bitrate !== '' && (int) $bitrate >= 0) ? (int) $bitrate : null,
                'height' => ($height !== '' && (int) $height >= 0) ? (int) $height : null,
                'title' => $title !== '' ? $title : null,
                'lang' => $lang !== '' ? $lang : null,
                'rel' => $rel !== '' ? $rel : null,
                'codecs' => $codecs !== '' ? $codecs : null,
                'is_default' => $default === 'true',
                'uris' => $uris,
            ];
        }
        return $result;
    }

    // <podcast:value> -> <podcast:valueRecipient>
    public function get_item_value_recipients($item): array {
        $values = $item->get_item_tags(self::NS_PODCAST, 'value');
        if (empty($values)) return [];

        $recipients = [];
        foreach ($values as $value) {
            $vrs = $value['child'][self::NS_PODCAST]['valueRecipient'] ?? [];
            foreach ($vrs as $vr) {
                if (count($recipients) >= self::MAX_PER_COLLECTION) break 2;

                $address = $this->tag_attr_value($vr, 'address');
                $split = $this->parse_split($this->tag_attr_value($vr, 'split'));
                if ($address === null || $split === null || !is_numeric($split)) continue;

                $fee_raw = $this->tag_attr_value($vr, 'fee');
                $fee = ($fee_raw !== null && in_array(strtolower($fee_raw), ['true', 'yes', '1'], true)) ? 'true' : null;

                $recipients[] = [
                    'pubkey' => $address,
                    'split' => $split,
                    'name' => $this->tag_attr_value($vr, 'name'),
                    'customKey' => $this->tag_attr_value($vr, 'customKey'),
                    'customValue' => $this->tag_attr_value($vr, 'customValue'),
                    'fee' => $fee,
                ];
            }
        }
        return $recipients;
    }

    // <podcast:valueTimeSplit>
    public function get_item_value_time_splits($item): array {
        $tags = $item->get_item_tags(self::NS_PODCAST, 'valueTimeSplit');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            if (count($result) >= self::MAX_PER_COLLECTION) break;

            $attrs = $tag['attribs'][''] ?? [];
            if (!isset($attrs['startTime']) || !isset($attrs['duration'])) continue;
            if (!is_numeric($attrs['startTime']) || !is_numeric($attrs['duration'])) continue;

            $entry = [
                'start_time' => (int) $attrs['startTime'],
                'duration' => (int) $attrs['duration'],
            ];

            $remote_items = $tag['child'][self::NS_PODCAST]['remoteItem'] ?? [];
            $value_recipients = $tag['child'][self::NS_PODCAST]['valueRecipient'] ?? [];

            $remote_feed_guid = null;
            if (!empty($remote_items)) {
                $remote_feed_guid = $this->tag_attr_value($remote_items[0], 'feedGuid');
            }

            // EXTRACT REMOTE ITEMS
            if ($remote_feed_guid !== null) {
                $remote = $remote_items[0];

                $entry['recipient'] = 0;
                $entry['remote_percent'] = isset($attrs['remotePercentage']) 
                                            ? max(0, min(100, (int) $attrs['remotePercentage'])) 
                                            : 100;
                $entry['remote_item'] = [
                    'feed_guid' => $remote_feed_guid,
                    'item_guid' => $this->tag_attr_value($remote, 'itemGuid'),
                    'feed_link' => $this->tag_attr_value($remote, 'feedUrl'),
                    'medium' => $this->tag_attr_value($remote, 'medium'),
                    'item_title' => $this->tag_attr_value($remote, 'title'),
                ];
            }
            // EXTRACT VALUE RECIPIENTS
            elseif (!empty($value_recipients)) {
                $entry['recipient'] = 1;
                $recipients = [];
                foreach ($value_recipients as $vr) {
                    if (count($recipients) >= self::MAX_PER_COLLECTION) break;

                    $address = $this->tag_attr_value($vr, 'address');
                    $split = $this->parse_split($this->tag_attr_value($vr, 'split'));
                    if ($address === null || $split === null || !is_numeric($split)) continue;

                    $fee_raw = $this->tag_attr_value($vr, 'fee');
                    $fee = ($fee_raw !== null && in_array(strtolower($fee_raw), ['true', 'yes', '1'], true)) ? 'true' : null;

                    $recipients[] = [
                        'pubkey' => $address,
                        'split' => $split,
                        'lightning' => $this->tag_attr_value($vr, 'name'),
                        'custom_key' => $this->tag_attr_value($vr, 'customKey'),
                        'custom_value' => $this->tag_attr_value($vr, 'customValue'),
                        'value_is_fee' => $fee,
                    ];
                }
                $entry['value_recipients'] = $recipients;
            } else {
                continue;
            }
            $result[] = $entry;
        } 
        return $result;
    }

    // <podcast:socialInteract>
    public function get_item_social_interacts($item): array {
        $tags = $item->get_item_tags(self::NS_PODCAST, 'socialInteract');
        if (empty($tags)) return [];

        $result = [];
        foreach ($tags as $tag) {
            if (count($result) >= self::MAX_PER_COLLECTION) break;

            $uri = $this->clean_url($this->tag_attr_value($tag, 'uri'));
            $protocol = $this->tag_attr_value($tag, 'protocol');
            if ($uri === null || $protocol === null) continue;

            $result[] = [
                'uri' => $uri,
                'protocol' => $protocol,
                'account_id' => $this->tag_attr_value($tag, 'accountId'),
                'accountUrl' => $this->clean_url($this->tag_attr_value($tag, 'accountUrl')),
                'priority' => $this->tag_attr_value($tag, 'priority'),
            ];
        }
        return $result;
    }

    // ======================
    // RAWVOICE ITEM HANDLERS
    // ======================
    
    // <rawvoice:pid>
    public function get_item_pid($item): ?int {
        $value = $this->item_tag_text($item, self::NS_RAWVOICE, 'pid');
        if ($value === null) return null;

        $pid = (int) trim($value);
        return $pid > 0 ? $pid : null;
    }
}
