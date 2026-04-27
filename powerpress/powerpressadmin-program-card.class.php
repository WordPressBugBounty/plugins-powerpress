<?php
/**
 * PowerPress Program Card
 * unified component that renders stats widget and show info card together
 * handles program data fetching, stats api calls, and rendering
 */

class PowerPressProgramCard
{
    // CONFIGURATION
    private array $general_settings;
    private array $feed_settings;
    private string $feed_slug;
    private bool $deferred = false;
    private bool $has_blubrry_auth = false;

    // PROGRAM DATA
    private bool $network_mode = false;
    private string $program_keyword = '';
    private string $default_program_keyword = '';
    private array $programs = [];
    private array $programs_full = [];
    private array $program_info = [];

    // STATS DATA
    private array $stats_content = [];
    // URL CONFIG
    private string $stats_base_url = 'https://stats.blubrry.com/';

    /* =========================================
                     INITIALIZATION
       ========================================= */

    public function __construct(string $feed_slug = 'podcast', string $override_program = '', bool $deferred = false) {
        // 1. LOAD SETTINGS
        $this->feed_slug = $feed_slug;
        $general = get_option('powerpress_general');
        $this->general_settings = is_array($general) ? $general : [];

        if (defined('POWERPRESS_BLUBRRY_STATS_URL')) $this->stats_base_url = rtrim(POWERPRESS_BLUBRRY_STATS_URL, '/') . '/';

        $this->network_mode = !empty($this->general_settings['network_mode']);
        $this->default_program_keyword = !empty($this->general_settings['blubrry_program_keyword'])
            ? $this->general_settings['blubrry_program_keyword']
            : '';

        // 2. CHECK AUTH
        $creds = get_option('powerpress_creds');
        $userpass = !empty($this->general_settings['blubrry_auth']) ? $this->general_settings['blubrry_auth'] : '';
        $this->has_blubrry_auth = !empty($creds) || !empty($userpass);

        // 3. CHECK PERMISSIONS
        $stats_enabled = $this->should_show_stats();
        $this->deferred = $deferred && $this->has_blubrry_auth && $stats_enabled;
        $this->load_feed_settings();

        // 4. LOAD DATA
        if (!$this->deferred) {
            $this->load_stats_data($override_program);
        } else {
            $this->program_keyword = $override_program ?: $this->default_program_keyword;
        }
        $this->program_info = $this->build_program_info();
    }

    /** checks permission flags and user capabilities */
    private function should_show_stats(): bool {
        if (!empty($this->general_settings['disable_dashboard_stats'])) return false;
        if (!empty($this->general_settings['use_caps']) && !current_user_can('view_podcast_stats')) return false;
        return true;
    }

    /** loads feed settings for current slug with defaults applied */
    private function load_feed_settings(): void {
        $feed = get_option($this->feed_slug === 'podcast' ? 'powerpress_feed' : "powerpress_feed_{$this->feed_slug}");
        $this->feed_settings = is_array($feed) ? $feed : [];

        if (function_exists('powerpress_default_settings')) {
            $this->feed_settings = powerpress_default_settings($this->feed_settings, 'editfeed');
        }
    }

    public function get_program_keyword(): string {
        return $this->program_keyword;
    }

    /* =========================================
                      PROGRAM DATA
       ========================================= */

    /** fetches program list from api w/ transient caching */
    private function fetch_programs(): array {
        $settings = $this->general_settings;
        $creds = get_option('powerpress_creds');

        // 1. CHECK CACHE
        $cached_programs = get_transient('powerpress_programs_list');
        if ($cached_programs !== false) {
            return $cached_programs;
        }

        // 2. FETCH API
        $programs = [];
        $api_error = null;

        require_once POWERPRESS_ABSPATH . '/powerpressadmin-auth.class.php';
        $auth = new PowerPressAuth();
        $api_url_array = powerpress_get_api_array();

        $results = powerpress_api_request('/2/service/index.json', [], [], $settings, $creds, $auth, $api_url_array, 15);

        if ($results && is_array($results)) {
            if (isset($results['error'])) {
                $api_error = $results['message'] ?? $results['error'];
            } else {
                foreach ($results as $row) {
                    if (isset($row['program_keyword']) && isset($row['program_title'])) {
                        $programs[$row['program_keyword']] = $row['program_title'];
                        // store basic data for program_id lookup
                        $this->programs_full[$row['program_keyword']] = $row;
                    }
                }
            }
        }

        // 3. SAVE CACHE
        if (!empty($programs)) {
            set_transient('powerpress_programs_list', $programs, HOUR_IN_SECONDS);
        } else if ($api_error) {
            set_transient('powerpress_programs_api_error', $api_error, 5 * MINUTE_IN_SECONDS);
        }

        return $programs;
    }

    /**
     * fetches detailed program info from API (lazy-loaded per program)
     */
    private function fetch_program_info(string $keyword): ?array {
        if (empty($keyword)) return null;

        // 1. CHECK CACHE
        $cache_key = 'powerpress_program_info_' . md5($keyword);
        $cached_info = get_transient($cache_key);
        if ($cached_info !== false) {
            return $cached_info;
        }

        // 2. FETCH API
        $settings = $this->general_settings;
        $creds = get_option('powerpress_creds');

        require_once POWERPRESS_ABSPATH . '/powerpressadmin-auth.class.php';
        $auth = new PowerPressAuth();
        $api_url_array = powerpress_get_api_array();

        $enc_keyword = urlencode($keyword);
        $results = powerpress_api_request("/2/program/{$enc_keyword}/info.json", [], [], $settings, $creds, $auth, $api_url_array, 5);

        if (!$results || !is_array($results) || isset($results['error'])) {
            return null;
        }

        // 3. SAVE CACHE (30 min for program info)
        set_transient($cache_key, $results, 30 * MINUTE_IN_SECONDS);

        return $results;
    }

    /**
     * builds program info for display
     * default program: WP settings first, API as fallback
     * non-default programs: API only (no fallback to default's WP data)
     */
    private function build_program_info(): array {
        // 1. FETCH API DATA (skip when deferred - will load via AJAX later)
        $api = [];
        if (!$this->deferred && !empty($this->program_keyword)) {
            $program = $this->fetch_program_info($this->program_keyword);
            if ($program) {
                $api = $program;
                // store in programs_full for program_id lookups elsewhere
                $this->programs_full[$this->program_keyword] = array_merge(
                    $this->programs_full[$this->program_keyword] ?? [],
                    $program
                );
            }
        }

        // 2. BASE: start with API values
        $default_image = powerpress_get_root_url() . 'images/pts_cover.jpg';

        $title = $api['program_title'] ?? '';
        $author = $api['author'] ?? '';
        $description = $api['description'] ?? '';
        $category = $api['category'] ?? '';
        $feed_url = $api['feed_url'] ?? '';
        $episode_count = isset($api['episode_count']) ? intval($api['episode_count']) : 0;
        $created_date = $api['created_date'] ?? '';
        $last_update = $api['last_update'] ?? '';

        $api_image = $api['artwork_url'] ?? '';
        $has_real_api_image = !$this->is_placeholder_image($api_image);
        $image = $has_real_api_image ? $api_image : $default_image;

        // 3. OVERLAY: default program overrides with WP settings
        $is_default = ($this->program_keyword === $this->default_program_keyword);
        if ($is_default) {
            $wp_settings = $this->feed_settings;

            if (!empty($wp_settings['title'])) $title = $wp_settings['title'];
            if (!empty($wp_settings['itunes_talent_name'])) $author = $wp_settings['itunes_talent_name'];
            if (!empty($wp_settings['description'])) $description = $wp_settings['description'];

            // wp image if real -> api image if real -> wp default
            $wp_image = !empty($wp_settings['itunes_image']) ? $wp_settings['itunes_image'] : ($wp_settings['rss2_image'] ?? '');
            if (!$this->is_placeholder_image($wp_image)) {
                $image = $wp_image;
            } elseif ($has_real_api_image) {
                $image = $api_image;
            }

            if (!empty($wp_settings['apple_cat_1'])) {
                $categories = powerpress_apple_categories(true);
                $category = $categories[$wp_settings['apple_cat_1']] ?? $wp_settings['apple_cat_1'];
            }

            $feed_url = $this->network_mode ? '' : get_feed_link($this->feed_slug);
        }

        if (empty($episode_count)) {
            $episode_count = powerpress_admin_episodes_per_feed($this->feed_slug);
        }

        return [
            'title' => $title,
            'author' => $author,
            'description' => $description,
            'image' => $image,
            'category' => $category,
            'feed_url' => $feed_url,
            'episode_count' => $episode_count,
            'created_date' => $created_date,
            'last_update' => $last_update
        ];
    }

    /* =========================================
                       STATS DATA
       ========================================= */

    private function load_stats_data(string $override_program = ''): void {
        $settings = $this->general_settings;
        $creds = get_option('powerpress_creds');

        // 1. VALIDATE PERMISSIONS
        if (!$this->should_show_stats()) return;

        $userpass = !empty($settings['blubrry_auth']) ? $settings['blubrry_auth'] : '';

        // 2. CHECK AUTH
        if (!$userpass && !$creds) {
            $this->program_keyword = $this->default_program_keyword;
            return;
        }

        // 3. LOAD PROGRAMS
        $this->programs = $this->fetch_programs();

        // 4. RESOLVE KEYWORD
        $keyword = '';

        if ($this->network_mode) {
            $program_keys = array_map('strval', array_keys($this->programs));

            if ($override_program && in_array((string)$override_program, $program_keys, true)) {
                $keyword = (string)$override_program;
            } else {
                $default_kw = $this->default_program_keyword ? (string)$this->default_program_keyword : '';
                if ($default_kw && in_array($default_kw, $program_keys, true)) {
                    $keyword = $default_kw;
                }
            }

            $this->program_keyword = $keyword;

            if (empty($this->programs)) {
                $api_error = get_transient('powerpress_programs_api_error');
                $stats_url = esc_url($this->stats_base_url);
                if ($api_error) {
                    $safe_error = esc_html($api_error);
                    $error_msg = "{$safe_error}";
                } else {
                    $visit = __('Multi-program mode is enabled but no programs were found. Please visit', 'powerpress');
                    $suffix = __('to see your statistics.', 'powerpress');
                    $error_msg = "{$visit} <a href=\"{$stats_url}\" target=\"_blank\">Blubrry Stats</a> {$suffix}";
                }
                $this->stats_content = ['error' => $error_msg];
                return;
            }
        } else {
            $keyword = $this->default_program_keyword;
            $this->program_keyword = $keyword;
        }

        if (empty($keyword)) {
            $stats_url = esc_url($this->stats_base_url);
            $visit = __('No program selected. Please visit', 'powerpress');
            $suffix = __('to see your statistics.', 'powerpress');
            $this->stats_content = ['error' => "{$visit} <a href=\"{$stats_url}\" target=\"_blank\">Blubrry Stats</a> {$suffix}"];
            return;
        }

        // 5. LOAD CACHE
        $days_key = "{$keyword}_days";
        $meta_key = "{$keyword}_meta";

        $stats_cached = get_option('powerpress_stats');
        if (!is_array($stats_cached)) $stats_cached = [];

        $stats_cached = $this->migrate_stats_cache($stats_cached, $keyword);

        $cached_days = isset($stats_cached[$days_key]) && is_array($stats_cached[$days_key])
            ? $stats_cached[$days_key]
            : [];
        $cached_meta = isset($stats_cached[$meta_key]) && is_array($stats_cached[$meta_key])
            ? $stats_cached[$meta_key]
            : [];

        // fetch window
        $max_retention = 60;

        foreach (array_keys($cached_days) as $date) {
            if (!$this->is_valid_cache_date($date)) {
                unset($cached_days[$date]);
            }
        }

        // 6. CALCULATE DELTA
        $today = date('Y-m-d');
        $today_ttl = 30 * 60; // 30 min ttl for today's data
        $last_fetch = isset($cached_meta['last_fetch']) ? (int)$cached_meta['last_fetch'] : 0;
        $today_needs_refresh = (time() - $last_fetch) > $today_ttl;

        $delta = $this->calculate_stats_delta($cached_days, $today, $max_retention);
        $fetch_days = $delta['fetch_days'];
        $is_full_fetch = $delta['is_full_fetch'];

        // use cached data if fresh enough
        if (!empty($cached_days) && !$today_needs_refresh && !$is_full_fetch) {
            $this->stats_content = $this->build_stats_content_from_cache($cached_days, $cached_meta, $max_retention);
            return;
        }

        // 7. FETCH API
        require_once(POWERPRESS_ABSPATH . '/powerpressadmin-auth.class.php');
        $auth = new PowerPressAuth();
        $api_url_array = powerpress_get_api_array();

        $enc_keyword = urlencode($keyword);
        $new_content = powerpress_api_request(
            "/2/stats/{$enc_keyword}/data.json?days={$fetch_days}&include=daily,totals,averages,trends",
            [],
            [],
            $settings,
            $creds,
            $auth,
            $api_url_array,
            2
        );

        // 8. PROCESS RESPONSE
        if (!$new_content) {
            if (!empty($cached_days)) {
                $this->stats_content = $this->build_stats_content_from_cache($cached_days, $cached_meta, $max_retention);
            } else {
                $this->stats_content = ['error' => 'Unable to retrieve statistics'];
            }
            return;
        }

        if (isset($new_content['error'])) {
            if (strpos($new_content['error'], 'Unable to locate program') !== false ||
                strpos($new_content['error'], 'No statistics') !== false) {
                $this->stats_content = $this->get_empty_stats_content();
                $no_stats_programs = get_transient('powerpress_no_stats_programs');
                if (!is_array($no_stats_programs)) $no_stats_programs = [];
                $no_stats_programs[$keyword] = true;
                set_transient('powerpress_no_stats_programs', $no_stats_programs, DAY_IN_SECONDS);
            } else {
                $this->stats_content = ['error' => $new_content['error']];
            }
            return;
        }

        // 9. UPDATE CACHE (map data API response to internal format)
        $new_day_data = isset($new_content['daily']) ? $new_content['daily'] : [];
        $merged_days = $this->merge_stats_days_from_data_api($cached_days, $new_day_data);
        $merged_days = $this->prune_stats_days($merged_days, $today, $max_retention);

        $stats_cached[$days_key] = $merged_days;
        $stats_cached[$meta_key] = [
            'last_fetch' => time(),
            'last_date' => $today,
            'stats_tier' => $new_content['stats_tier'] ?? 'basic',
            'program_total' => $new_content['totals']['all_time'] ?? 0,
            'month_average' => $new_content['averages']['thirty_day'] ?? 0,
            'month_average_change' => $new_content['trends']['thirty_day_vs_prior'] ?? 'same',
        ];
        update_option('powerpress_stats', $stats_cached);

        // clear no-stats flag if we got data
        $no_stats_programs = get_transient('powerpress_no_stats_programs');
        if (is_array($no_stats_programs) && isset($no_stats_programs[$keyword])) {
            unset($no_stats_programs[$keyword]);
            set_transient('powerpress_no_stats_programs', $no_stats_programs, DAY_IN_SECONDS);
        }

        $this->stats_content = $this->build_stats_content_from_cache($merged_days, $stats_cached[$meta_key], $max_retention);
    }

    /** converts cached days data to stats_content format for rendering */
    private function build_stats_content_from_cache(array $days_data, array $meta, int $window = 60): array {
        $today = date('Y-m-d');
        $days_data = $this->fill_missing_dates($days_data, $today, $window);

        return [
            'day_total_data' => $this->days_to_api_format($days_data),
            'stats_tier' => $meta['stats_tier'] ?? 'basic',
            'program_total' => $meta['program_total'] ?? 0,
            'month_average' => $meta['month_average'] ?? 0,
            'month_average_change' => $meta['month_average_change'] ?? 'same',
        ];
    }

    /** transforms stats_content into chart-ready data with scale and labels */
    private function get_chart_data(int $day_count = 7): ?array {
        // 1. VALIDATE
        if (empty($this->stats_content) || isset($this->stats_content['error']))
            return null;

        // 2. BUILD DAYS
        $days = [];
        $max_val = 0;
        $day_total_data = $this->stats_content['day_total_data'] ?? [];

        $total_entries = count($day_total_data);
        $start_index = max(0, $total_entries - $day_count);
        $is_month_view = $day_count > 7;
        $comparison_offset = $is_month_view ? 30 : 7;

        for ($i = $start_index; $i < $total_entries; $i++) {
            $day_data = $day_total_data[$i] ?? [];
            $total = (int)($day_data['trending_day_total'] ?? 0);
            $day_date = $day_data['day_date'] ?? date('Y-m-d');

            // period-over-period comparison for tooltip
            $comparison_index = $i - $comparison_offset;
            $comparison_total = null;
            if ($comparison_index >= 0 && isset($day_total_data[$comparison_index]['trending_day_total'])) {
                $comparison_total = (int)$day_total_data[$comparison_index]['trending_day_total'];
            }

            $day_entry = [
                'total' => $total,
                'date' => $is_month_view ? date("M d", strtotime($day_date)) : date("D d", strtotime($day_date)),
                'full_date' => date("l M d", strtotime($day_date)),
            ];

            if ($is_month_view) {
                $day_entry['last_month_total'] = $comparison_total;
            } else {
                $day_entry['last_week_total'] = $comparison_total;
            }

            $days[] = $day_entry;
            if ($total > $max_val) $max_val = $total;
        }

        // 3. CALCULATE SCALE
        $total_sum = array_sum(array_column($days, 'total'));
        $display_average = count($days) > 0 ? (int)round($total_sum / count($days)) : 0;

        $scale_min = 0;
        $nice_steps = [1, 2, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000];

        $target_max = $max_val * 1.2; // 20% headroom
        if ($target_max < 5) $target_max = 5;

        $best_step = 1;
        $best_max = 5;
        foreach ($nice_steps as $step) {
            $potential_max = ceil($target_max / $step) * $step;
            $num_steps = $potential_max / $step;
            if ($num_steps >= 3 && $num_steps <= 6) {
                $best_step = $step;
                $best_max = $potential_max;
                break;
            } elseif ($num_steps < 3) {
                // step too big, use previous
                break;
            }
            $best_step = $step;
            $best_max = $potential_max;
        }

        $scale_max = $best_max;
        $scale_step = $best_step;

        $scale_range = $scale_max - $scale_min;
        if ($scale_range <= 0) $scale_range = 5;

        $num_lines = (int)($scale_range / $scale_step);
        if ($num_lines <= 0) $num_lines = 5;

        // 4. BUILD LABELS
        $scale_labels = [];
        for ($x = 0; $x < $num_lines; $x++) {
            $scale_labels[] = $scale_min + ($x + 1) * $scale_step;
        }

        $title_suffix = $day_count > 7 ? ' (30 Days)' : ' (7 Days)';
        $base_title = !empty($this->stats_content['widget_title']) ? $this->stats_content['widget_title'] : 'Podcast Statistics';

        // 5. RESOLVE URLS
        $tier = $this->get_stats_tier();
        if (!$this->has_blubrry_auth) {
            $upgrade_url = admin_url('admin.php?page=powerpressadmin_basic&step=blubrrySignup&onboarding_type=stats');
        } else if ($tier === 'basic') {
            $upgrade_url = 'https://secure.blubrry.com/checkout/manage-subscriptions/';
        } else {
            // /s-{program_id}/ for specific show
            $program_id = !empty($this->programs_full[$this->program_keyword]['program_id'])
                ? $this->programs_full[$this->program_keyword]['program_id']
                : '';

            $upgrade_url = $program_id
                ? rtrim($this->stats_base_url, '/') . '/s-' . $program_id . '/'
                : $this->stats_base_url;
        }

        return [
            'scale_min' => $scale_min,
            'scale_max' => $scale_max,
            'scale_range' => $scale_range,
            'scale_step' => $scale_step,
            'num_lines' => $num_lines,
            'scale_labels' => $scale_labels,
            'days' => $days,
            'widget_title' => "{$base_title}{$title_suffix}",
            'tier' => $this->get_stats_tier(),
            'has_auth' => $this->has_blubrry_auth,
            'upgrade_url' => $upgrade_url,
            'display_average' => $display_average
        ];
    }

    public function get_week_chart_data(): ?array {
        return $this->get_chart_data(7);
    }

    public function get_month_chart_data(): ?array {
        return $this->get_chart_data(30);
    }

    public function get_week_average(): int {
        $chart_data = $this->get_week_chart_data();
        return $chart_data['display_average'] ?? 0;
    }

    public function get_month_average(): int {
        $chart_data = $this->get_month_chart_data();
        return $chart_data['display_average'] ?? 0;
    }

    /**
     * empty stats placeholder for new programs
     */
    private function get_empty_stats_content(): array {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $days[] = [
                'day_date' => $date,
                'trending_day_total' => 0
            ];
        }

        return [
            'widget_title' => __('Podcast Statistics', 'powerpress'),
            'day_total_data' => $days,
            'scale_min' => 0,
            'scale_max' => 5,
            'scale_step' => 1,
            'month_average' => 0,
            'month_average_change' => '',
            'program_total' => 0,
            'is_empty' => true
        ];
    }

    /** tier comes directly from api response, default to basic */
    public function get_stats_tier(): string {
        return $this->stats_content['stats_tier'] ?? 'basic';
    }

    /* =========================================
                      MAIN RENDER
       ========================================= */

    public function render(string $new_post_query_string = ''): void {
        ?>
        <div class="pp-program-summary" id="pp-program-card"
             data-feed-slug="<?php echo esc_attr($this->feed_slug); ?>"
             data-program-keyword="<?php echo esc_attr($this->program_keyword); ?>"
             data-default-program="<?php echo esc_attr($this->default_program_keyword); ?>">

            <div class="pp-settings-program-summary">
                <?php $this->render_stats_widget(); ?>

                <div class="pp-program-row row">
                    <div class="col-md-12">
                        <?php $this->render_show_info_card(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /* =========================================
                 STATS WIDGET RENDERING
       ========================================= */

    public function render_stats_widget(bool $stacked = false): void {
        // no widget when not connected
        if (empty($this->stats_content) && !$this->deferred) return;

        // template variables
        $nonce = wp_create_nonce('powerpress_stats_program');
        $ajax_url = admin_url('admin-ajax.php');
        $chart_data = $this->deferred ? null : $this->get_week_chart_data();
        $deferred = $this->deferred;
        $stats_content = $this->stats_content;
        $program_card = $this;

        // load shared template
        include(POWERPRESS_ABSPATH . '/views/stats-widget.php');
    }

    public function render_stats_header(): void {
        $current_program_title = '';
        if ($this->network_mode && !empty($this->programs)) {
            $current_program_title = $this->programs[$this->program_keyword] ?? '';
        }
        ?>
        <div class="pp-stats-header">
            <div class="pp-stats-header-left">
                <h2 class="pp-stats-title"><span class="pp-stats-title-text"><?php _e('Podcast Statistics', 'powerpress'); ?><?php echo ($this->network_mode && !empty($this->programs)) ? ':' : ''; ?></span></h2><?php if ($this->network_mode && !empty($this->programs)) : ?><button type="button" class="pp-program-selector-trigger" id="pp-program-selector-trigger" aria-haspopup="listbox" aria-expanded="false" aria-label="<?php printf(esc_attr__('Select podcast program. Currently showing: %s', 'powerpress'), esc_attr($current_program_title)); ?>">
                    <span class="pp-program-selector-name"><?php echo esc_html($current_program_title); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                </button>
                <div class="pp-program-selector-dropdown" id="pp-program-selector-dropdown" role="listbox" aria-label="<?php esc_attr_e('Podcast programs', 'powerpress'); ?>">
                    <?php $this->render_program_selector_list(); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="pp-stats-header-controls">
                <button type="button" class="pp-stats-btn pp-stats-cog-btn" id="pp-stats-cog-btn" data-tier="<?php echo esc_attr($this->get_stats_tier()); ?>" title="<?php esc_attr_e('Chart options', 'powerpress'); ?>" aria-label="<?php esc_attr_e('Open chart options', 'powerpress'); ?>" aria-expanded="false">
                    <span class="dashicons dashicons-admin-generic pp-cog-icon" aria-hidden="true"></span>
                    <span class="dashicons dashicons-yes-alt pp-save-icon" style="display:none;" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <?php
        if ($this->get_stats_tier() !== 'basic') {
            $this->render_stats_settings_row();
        }
    }

    /**
     * only for non-basic tier users
     */
    private function render_stats_settings_row(): void {
        ?>
        <div class="pp-stats-settings-row" id="pp-stats-settings-row" style="display:none;">
            <div class="pp-stats-settings-line">
                <span class="pp-stats-setting-label"><?php esc_html_e('View:', 'powerpress'); ?></span>
                <div class="pp-stats-toggle-group">
                    <label class="pp-stats-toggle" title="<?php esc_attr_e('Show last 7 days', 'powerpress'); ?>">
                        <input type="radio" name="pp_stats_view" value="week" checked>
                        <span><?php esc_html_e('Week', 'powerpress'); ?></span>
                    </label>
                    <label class="pp-stats-toggle" title="<?php esc_attr_e('Show last 30 days', 'powerpress'); ?>">
                        <input type="radio" name="pp_stats_view" value="month">
                        <span><?php esc_html_e('Month', 'powerpress'); ?></span>
                    </label>
                </div>
            </div>
            <div class="pp-stats-settings-line">
                <span class="pp-stats-setting-label"><?php esc_html_e('Scale:', 'powerpress'); ?></span>
                <div class="pp-stats-toggle-group">
                    <label class="pp-stats-toggle" title="<?php esc_attr_e('Standard view - equal spacing between values', 'powerpress'); ?>">
                        <input type="radio" name="pp_stats_scale" value="linear" checked>
                        <span><?php esc_html_e('Linear', 'powerpress'); ?></span>
                    </label>
                    <label class="pp-stats-toggle" title="<?php esc_attr_e('Better for viral spikes - compresses large values', 'powerpress'); ?>">
                        <input type="radio" name="pp_stats_scale" value="log">
                        <span><?php esc_html_e('Log', 'powerpress'); ?></span>
                    </label>
                </div>
            </div>
            <div class="pp-stats-settings-line">
                <span class="pp-stats-setting-label"><?php esc_html_e('Show:', 'powerpress'); ?></span>
                <div class="pp-stats-toggle-group pp-stats-toggle-group--checkboxes">
                    <label class="pp-stats-toggle" title="<?php esc_attr_e('Growth direction over time', 'powerpress'); ?>">
                        <input type="checkbox" id="pp-stats-opt-trendline" checked>
                        <span><?php esc_html_e('Trend', 'powerpress'); ?></span>
                    </label>
                    <label class="pp-stats-toggle" title="<?php esc_attr_e('Daily average reference line', 'powerpress'); ?>">
                        <input type="checkbox" id="pp-stats-opt-avgline" checked>
                        <span><?php esc_html_e('Avg', 'powerpress'); ?></span>
                    </label>
                    <label class="pp-stats-toggle" id="pp-stats-opt-values-label" title="<?php esc_attr_e('Download counts on bars (week view only)', 'powerpress'); ?>">
                        <input type="checkbox" id="pp-stats-opt-values" checked>
                        <span><?php esc_html_e('Values', 'powerpress'); ?></span>
                    </label>
                </div>
            </div>
            <div class="pp-stats-settings-line">
                <button type="button" class="pp-stats-refresh-btn" id="pp-stats-refresh-btn" title="<?php esc_attr_e('Clear cached data and fetch fresh stats', 'powerpress'); ?>">
                    <span class="dashicons dashicons-update"></span>
                    <span><?php _e('Refresh Stats', 'powerpress'); ?></span>
                </button>
            </div>
        </div>
        <?php
    }

    public function render_stats_chart(): void {
        // build screen reader description from current data
        $day_total_data = $this->stats_content['day_total_data'] ?? [];
        $num_days = count($day_total_data);

        // get todays value
        $today_data = !empty($day_total_data) ? end($day_total_data) : [];
        $today_total = (int)($today_data['trending_day_total'] ?? 0);

        // calculate average
        $total_downloads = 0;
        foreach ($day_total_data as $day) {
            $total_downloads += (int)($day['trending_day_total'] ?? 0);
        }
        $avg_downloads = $num_days > 0 ? round($total_downloads / $num_days) : 0;

        // calculate velocity (trend direction)
        $velocity_pct = 0;
        if ($num_days >= 2 && $avg_downloads > 0) {
            $sum_x = 0; $sum_y = 0; $sum_xy = 0; $sum_x2 = 0;
            $i = 0;
            foreach ($day_total_data as $day) {
                $val = (int)($day['trending_day_total'] ?? 0);
                $sum_x += $i;
                $sum_y += $val;
                $sum_xy += $i * $val;
                $sum_x2 += $i * $i;
                $i++;
            }
            $denom = $num_days * $sum_x2 - $sum_x * $sum_x;
            if ($denom != 0) {
                $slope = ($num_days * $sum_xy - $sum_x * $sum_y) / $denom;
                $velocity_pct = round(($slope / $avg_downloads) * 100 * $num_days);
            }
        }

        // sentiment based on velocity
        if ($velocity_pct > 5) {
            $sentiment = __("Your podcast is growing - keep posting!", 'powerpress');
        } elseif ($velocity_pct < -5) {
            $sentiment = __("Downloads are slowing down. Time for fresh content!", 'powerpress');
        } else {
            $sentiment = __("Your podcast is holding steady.", 'powerpress');
        }

        // build description
        $sr_parts = [];
        if ($today_total == 0) {
            $sr_parts[] = __("No downloads today", 'powerpress');
        } else {
            $sr_parts[] = sprintf(__("You had %s downloads today", 'powerpress'), number_format($today_total));
        }
        $sr_parts[] = sprintf(__("with a daily average of %s", 'powerpress'), number_format($avg_downloads));
        if ($this->get_stats_tier() !== 'basic' && $velocity_pct != 0) {
            $direction = $velocity_pct > 0 ? __('up', 'powerpress') : __('down', 'powerpress');
            $sr_parts[] = sprintf(__("Trend is %s %d percent", 'powerpress'), $direction, abs($velocity_pct));
        }
        $sr_parts[] = $sentiment;

        $sr_description = implode(' ', $sr_parts);
        ?>
        <span id="pp-stats-chart-description" class="pp-screen-reader-text"><?php echo esc_html($sr_description); ?></span>
        <div class="pp-stats-chart" role="img" aria-label="<?php esc_attr_e('Podcast download statistics', 'powerpress'); ?>" aria-describedby="pp-stats-chart-description">
            <canvas id="pp-stats-widget__canvas" aria-hidden="true"></canvas>
        </div>
        <?php
    }

    public function render_stats_summary(): void {
        ?>
        <div class="pp-stats-summary" id="pp-stats-summary">
            <table class="pp-stats-summary__table" role="table" aria-label="<?php esc_attr_e('Download statistics summary', 'powerpress'); ?>">
                <tr class="pp-stats-summary__item--first">
                    <td class="pp-stats-summary__label">Today</td>
                    <?php
                    $day_total_data = $this->stats_content['day_total_data'] ?? [];
                    $today_data = end($day_total_data) ?: [];
                    $yesterday_data = prev($day_total_data) ?: [];
                    $today_total = (int)($today_data['trending_day_total'] ?? 0);
                    $yesterday_total = (int)($yesterday_data['trending_day_total'] ?? 0);

                    if ($today_total < $yesterday_total) {
                        $day_img_src = powerpress_get_root_url() . 'images/down_arrow_pink.svg';
                        $day_change_text = "Decreased from yesterday";
                    } elseif ($today_total > $yesterday_total) {
                        $day_img_src = powerpress_get_root_url() . 'images/up_arrow_pink.svg';
                        $day_change_text = "Increased from yesterday";
                    } else {
                        $day_img_src = powerpress_get_root_url() . 'images/audio_lines_pink.svg';
                        $day_change_text = "Unchanged from yesterday";
                    }
                    ?>
                    <td class="pp-stats-summary__data" aria-label="<?php echo number_format($today_total) . " downloads today. $day_change_text"; ?>">
                        <?php echo number_format($today_total); ?>
                        <div class="pp-stats-summary__icon" title="<?php echo $day_change_text; ?>">
                            <img alt="today" src="<?php echo esc_url($day_img_src); ?>"/>
                        </div>
                    </td>
                </tr>
                <tr class="pp-stats-summary__item">
                    <td class="pp-stats-summary__label" id="pp-avg-label">7 Day Average</td>
                    <?php
                    $week_average = $this->get_week_average();
                    // icon based on week-over-week trend (compare to previous 7 days)
                    $week_change = $this->stats_content['month_average_change'] ?? '';
                    switch ($week_change) {
                        case 'up':
                            $avg_img_src = powerpress_get_root_url() . 'images/up_arrow.svg';
                            $avg_change_text = "Increased from last week";
                            break;
                        case 'down':
                            $avg_img_src = powerpress_get_root_url() . 'images/down_arrow.svg';
                            $avg_change_text = "Decreased from last week";
                            break;
                        default:
                            $avg_img_src = powerpress_get_root_url() . 'images/audio_lines.svg';
                            $avg_change_text = "Unchanged from last week";
                            break;
                    }
                    ?>
                    <td class="pp-stats-summary__data" id="pp-avg-value" aria-label="<?php echo number_format($week_average) . " average downloads for the past week. $avg_change_text"; ?>">
                        <?php echo number_format($week_average); ?>
                        <div class="pp-stats-summary__icon" title="<?php echo $avg_change_text; ?>">
                            <img alt="average change" src="<?php echo esc_url($avg_img_src); ?>"/>
                        </div>
                    </td>
                </tr>
                <tr class="pp-stats-summary__item">
                    <td class="pp-stats-summary__label">Total Downloads</td>
                    <?php
                    if ($today_total > 0) {
                        $total_img_src = powerpress_get_root_url() . 'images/up_arrow.svg';
                        $total_change_text = "Increased from yesterday";
                    } else {
                        $total_img_src = powerpress_get_root_url() . 'images/audio_lines.svg';
                        $total_change_text = "Unchanged from yesterday";
                    }
                    $program_total = $this->stats_content['program_total'] ?? 0;
                    ?>
                    <td class="pp-stats-summary__data" aria-label="<?php echo number_format($program_total) . " total downloads for program. $total_change_text"; ?>">
                        <?php echo number_format($program_total); ?>
                        <div class="pp-stats-summary__icon" title="<?php echo $total_change_text; ?>">
                            <img alt="total" src="<?php echo esc_url($total_img_src); ?>"/>
                        </div>
                    </td>
                </tr>
            </table>
            <?php if (!empty($this->stats_content['is_empty'])) : ?>
            <p class="pp-stats-empty-message">
                <?php _e('Your podcast is just getting started! Downloads will appear here once listeners tune in.', 'powerpress'); ?>
            </p>
            <?php endif; ?>
            <?php
            $program_id = !empty($this->programs_full[$this->program_keyword]['program_id'])
                ? $this->programs_full[$this->program_keyword]['program_id']
                : '';
            $stats_link = $program_id
                ? rtrim($this->stats_base_url, '/') . '/s-' . $program_id . '/'
                : $this->stats_base_url;
            ?>
            <div class="pp-stats-footer-actions">
                <a class="pp-stats-btn pp-stats-btn--primary" href="<?php echo esc_url(admin_url('post-new.php')); ?>">
                    <?php esc_html_e('New Episode', 'powerpress'); ?>
                </a>
                <a id="pp-stats-advanced-link" class="pp-stats-btn pp-stats-btn--secondary" href="<?php echo esc_url($stats_link); ?>" target="_blank">
                    <?php esc_html_e('See all statistics', 'powerpress'); ?>
                </a>
            </div>
        </div>
        <?php
    }


    /** splits programs into with/without stats for selector ordering */
    private function get_sorted_programs(): array {
        $no_stats_programs = get_transient('powerpress_no_stats_programs');
        if (!is_array($no_stats_programs)) $no_stats_programs = [];

        $with_stats = [];
        $without_stats = [];
        foreach ($this->programs as $keyword => $title) {
            if (isset($no_stats_programs[$keyword])) {
                $without_stats[$keyword] = $title;
            } else {
                $with_stats[$keyword] = $title;
            }
        }

        ksort($with_stats);
        ksort($without_stats);

        return ['with_stats' => $with_stats, 'without_stats' => $without_stats];
    }

    /** renders <select> dropdown for program switching */
    private function render_program_selector_options(): void {
        $sorted = $this->get_sorted_programs();
        ?>
        <select id="pp-stats-program-select" name="pp_stats_program">
            <?php foreach ($sorted['with_stats'] as $keyword => $title) : ?>
                <option value="<?php echo esc_attr($keyword); ?>"<?php echo ((string)$this->program_keyword === (string)$keyword) ? ' selected' : ''; ?>><?php echo esc_html($title); ?></option>
            <?php endforeach; ?>
            <?php foreach ($sorted['without_stats'] as $keyword => $title) : ?>
                <option value="<?php esc_attr_e($keyword); ?>" disabled><?php echo esc_html($title); ?> (<?php _e('stats not enabled', 'powerpress'); ?>)</option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /** renders accessible listbox for inline program selector */
    private function render_program_selector_list(): void {
        $sorted = $this->get_sorted_programs();
        ?>
        <ul class="pp-program-selector-list" role="presentation">
            <?php foreach ($sorted['with_stats'] as $keyword => $title) :
                $is_selected = ((string)$this->program_keyword === (string)$keyword);
            ?>
                <li role="option" aria-selected="<?php echo $is_selected ? 'true' : 'false'; ?>">
                    <button type="button" class="pp-program-selector-item<?php echo $is_selected ? ' pp-program-selector-item--active' : ''; ?>" data-keyword="<?php echo esc_attr($keyword); ?>">
                        <?php echo esc_html($title); ?>
                    </button>
                </li>
            <?php endforeach; ?>
            <?php if (!empty($sorted['without_stats'])) : ?>
                <li class="pp-program-selector-divider" role="separator" aria-hidden="true"></li>
                <?php foreach ($sorted['without_stats'] as $keyword => $title) : ?>
                    <li role="option" aria-disabled="true" aria-selected="false">
                        <button type="button" class="pp-program-selector-item pp-program-selector-item--disabled" disabled aria-label="<?php printf(esc_attr__('%s - stats not enabled', 'powerpress'), esc_attr($title)); ?>">
                            <?php echo esc_html($title); ?>
                            <span class="pp-program-selector-item-note"><?php _e('stats not enabled', 'powerpress'); ?></span>
                        </button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <?php
    }

    /* =========================================
                SHOW INFO CARD RENDERING
       ========================================= */

     private function render_show_info_card(): void {
        $podcast_title = $this->program_info['title'] ?: __('Podcast', 'powerpress');
        $episode_count = intval($this->program_info['episode_count']);
        $img_alt = sprintf(__('Cover art for %s', 'powerpress'), $podcast_title);
        ?>
        <div class="prog-sum-head" id="pp-show-info-card" role="region" aria-labelledby="welcome-title">
            <div class="pp-program-card">
                <div class="pp-program-card__header">
                    <h2 class="pp-heading" id="welcome-title"><?php echo esc_html($podcast_title); ?></h2>
                    <div class="pp-program-card__episode-count" aria-label="<?php printf(esc_attr__('%d episodes published', 'powerpress'), $episode_count); ?>">
                        <span class="pp-program-card__label" aria-hidden="true"><?php _e('Episodes', 'powerpress'); ?>:</span>
                        <span class="pp-episode-count" aria-hidden="true"><?php echo $episode_count; ?></span>
                    </div>
                </div>
                <div class="pp-program-card__body">
                    <img id="welcome-preview-image" src="<?php echo esc_url($this->program_info['image']); ?>" alt="<?php echo esc_attr($img_alt); ?>" onerror="this.onerror=null; this.src='<?php echo esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg'); ?>';" />
                    <div class="pp-program-card__content">
                        <p class="pp-program-card__author"><span class="pp-program-card__label"><?php _e('By', 'powerpress'); ?></span> <?php echo esc_html($this->program_info['author']); ?></p>

                        <div class="pp-program-card__main">
                            <p class="pp-program-card__description"><span class="pp-program-card__label"><?php _e('Description', 'powerpress'); ?>:</span> <?php echo esc_html($this->program_info['description']); ?></p>
                        </div>

                        <div class="pp-program-card__footer">
                            <p class="pp-program-card__category"<?php echo empty($this->program_info['category']) ? ' style="display: none;"' : ''; ?>><span class="pp-program-card__label"><?php _e('Category', 'powerpress'); ?>:</span> <?php echo esc_html($this->program_info['category']); ?></p>
                            <div class="pp-program-card__dates">
                                <span class="pp-program-card__last-update"<?php echo empty($this->program_info['last_update']) ? ' style="display: none;"' : ''; ?>><span class="pp-program-card__label"><?php _e('Last Upload', 'powerpress'); ?>:</span> <?php echo esc_html($this->format_date($this->program_info['last_update'])); ?></span>
                                <span class="pp-program-card__date-separator" aria-hidden="true"<?php echo (empty($this->program_info['last_update']) || empty($this->program_info['created_date'])) ? ' style="display: none;"' : ''; ?>>|</span>
                                <span class="pp-program-card__created-date"<?php echo empty($this->program_info['created_date']) ? ' style="display: none;"' : ''; ?>><span class="pp-program-card__label"><?php _e('Started Publishing', 'powerpress'); ?>:</span> <?php echo esc_html($this->format_date($this->program_info['created_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /** checks if url matches any known default/placeholder artwork */
    private function is_placeholder_image(string $url): bool {
        if (empty($url)) return true;
        // api default, pp grey logo, pp blue logo
        return strpos($url, 'default.jpg') !== false
            || strpos($url, 'pts_cover.jpg') !== false
            || strpos($url, 'itunes_default.jpg') !== false;
    }

    /** formats unix timestamp or date string using WP date format */
    private function format_date($timestamp): string {
        if (empty($timestamp)) return '';
        if (is_numeric($timestamp))
            return date_i18n(get_option('date_format'), $timestamp);
        $ts = strtotime($timestamp);
        if ($ts === false || $ts < 0) return '';
        return date_i18n(get_option('date_format'), $ts);
    }

    /* =========================================
                 AJAX RESPONSE METHODS
       ========================================= */

    public function get_stats_widget_html(bool $stacked = false): string {
        ob_start();
        $this->render_stats_widget($stacked);
        return ob_get_clean();
    }

    public function get_show_info_card_html(): string {
        ob_start();
        $this->render_show_info_card();
        return ob_get_clean();
    }

    public function get_program_info_data(): array {
        return $this->program_info;
    }

    /* =========================================
                       ACCESSORS
       ========================================= */

    public function is_network_mode(): bool { return $this->network_mode; }
    public function get_programs(): array { return $this->programs; }
    public function get_current_program_keyword(): string { return $this->program_keyword; }

    /* =========================================
                   DELTA CACHE HELPERS
       ========================================= */

    /** determines how many days to fetch based on cache gap */
    private function calculate_stats_delta(array $cached_days, string $today, int $max_days): array {
        if (empty($cached_days)) {
            return ['fetch_days' => $max_days, 'is_full_fetch' => true];
        }

        $last_date = max(array_keys($cached_days));
        $last_ts = strtotime($last_date);
        $today_ts = strtotime($today);

        $gap_days = (int)(($today_ts - $last_ts) / 86400);

        // same day = still fetch 1 day for today refresh
        if ($gap_days <= 0) {
            return ['fetch_days' => 1, 'is_full_fetch' => false];
        }

        // gap exceeds retention = full fetch
        if ($gap_days >= $max_days) {
            return ['fetch_days' => $max_days, 'is_full_fetch' => true];
        }

        return ['fetch_days' => $gap_days, 'is_full_fetch' => false];
    }

    /**
     * merge daily data from data API format (date/downloads keys)
     */
    private function merge_stats_days_from_data_api(array $existing, array $new_data): array {
        foreach ($new_data as $day) {
            if (isset($day['date']) && isset($day['downloads'])) {
                $existing[$day['date']] = (int)$day['downloads'];
            }
        }
        return $existing;
    }

    /** drops dates older than retention window */
    private function prune_stats_days(array $days_data, string $today, int $retention_days): array {
        $cutoff_ts = strtotime($today) - ($retention_days * 86400);

        return array_filter($days_data, function($date) use ($cutoff_ts) {
            return strtotime($date) >= $cutoff_ts;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * fills gaps with zeros to prevent unnecessary refetches
     */
    private function fill_missing_dates(array $days_data, string $today, int $window): array {
        $today_ts = strtotime($today);
        for ($i = 0; $i < $window; $i++) {
            $date = date('Y-m-d', $today_ts - ($i * 86400));
            if (!isset($days_data[$date])) {
                $days_data[$date] = 0;
            }
        }
        return $days_data;
    }

    /** converts date=>total map to [{day_date, trending_day_total}] */
    private function days_to_api_format(array $days_data): array {
        ksort($days_data);
        $result = [];
        foreach ($days_data as $date => $total) {
            $result[] = ['day_date' => $date, 'trending_day_total' => $total];
        }
        return $result;
    }

    /** validate YYYY-MM-DD format */
    private function is_valid_cache_date($date): bool {
        if (!is_string($date) || empty($date)) {
            return false;
        }
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
    }

    /**
     * clears legacy cache formats (v1/v2/v3)
     */
    private function migrate_stats_cache(array $stats_cached, string $keyword): array {
        $modified = false;

        // clear old v1 format (simple updated/content keys)
        if (isset($stats_cached['updated']) || isset($stats_cached['content'])) {
            unset($stats_cached['updated'], $stats_cached['content'], $stats_cached['retry_count']);
            $modified = true;
        }

        // clear old v2/v3 format keys
        foreach (array_keys($stats_cached) as $key) {
            if (preg_match('/_v[23]$/', $key)) {
                unset($stats_cached[$key]);
                $modified = true;
            }
        }

        if ($modified) {
            update_option('powerpress_stats', $stats_cached);
        }

        return $stats_cached;
    }
}
