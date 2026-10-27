<?php
$ppnFallback = esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg');
$ppnArtwork = ($props['artwork_url']['300'] ?? '') !== '' ? esc_url($props['artwork_url']['300']) : $ppnFallback;
if ($props['style'] == 'older-full') { ?>
    <div class="ppn-container" id="ppn-program">
        <div class="ppn-header" id="ppn-header">
            <img alt="<?php echo esc_html($props['program_title']) ?>" src="<?php echo $ppnArtwork; ?>" onerror="this.onerror=null; this.src='<?php echo $ppnFallback; ?>';">
            <p><?php echo wp_kses_post($props['program_desc']) ?></p>
        </div>
        <br>
        <?php for ($i = 0; ($i < $props['limit'] && $i < count($props['episodes'])); $i++) {
            do_action('wp_powerpress_player_scripts');
            $player = PowerPressNetworkShortCode::buildPlayer($props['episodes'][$i]['media_url'], PowerPressNetworkShortCode::playerEpisodeData($props));
         ?>
            <div class="entry well z-depth-1">
                <div id="ppn-episode">
                    <?php echo $player;?>
                    <div>
                        <h3><?php echo esc_html($props['episodes'][$i]['podcast_title']); ?></h3>
                    </div>
                    <div>
                        <p><?php echo wp_kses_post($props['episodes'][$i]['podcast_desc']); ?></p>
                    </div>
                    <div>
                        <i><?php echo wp_date('F d, Y', $props['episodes'][$i]['podcast_post_date']); ?></i>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } elseif ($props['style'] == 'artwork') { ?>
    <div class="ppn-container" id="ppn-program">
        <div class="ppn-header" id="ppn-header">
            <img alt="<?php echo esc_html($props['program_title']) ?>" src="<?php echo $ppnArtwork; ?>" onerror="this.onerror=null; this.src='<?php echo $ppnFallback; ?>';">
        </div>
    </div>
<?php } elseif ($props['style'] == 'playlist') { ?>
    <div class="ppn-grid-header" bp="grid">
        <?php include(dirname(__FILE__) . '/program-playlist-panel.php'); ?>
        <div data-ppn-episodes class="<?php echo $props['style'] === 'playlist' ? 'ppn-playlist' : ''; ?>" bp="12" data-program="<?php echo esc_attr($props['program_id']); ?>" data-style="<?php echo esc_attr($props['style']); ?>" data-limit="<?php echo esc_attr($props['limit']); ?>" data-loaded="<?php echo esc_attr(count($props['episodes'])); ?>" data-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
            <?php
            $props['word_break'] = false;
            $props['row_spacer'] = false;
            include(dirname(__FILE__) . '/program-episodes.php');
            ?>
        </div>
        <?php include(dirname(__FILE__) . '/program-loadmore.php'); ?>
    </div>
<?php } elseif ($props['style'] == 'description') { ?>
    <div class="ppn-container" id="ppn-program">
        <div class="ppn-header" id="ppn-header">
            <p><?php echo wp_kses_post($props['program_desc']) ?></p>
        </div>
    </div>
<?php } elseif ($props['style'] == 'full') {
    ?>
    <div class="ppn-grid-header" bp="grid">
        <div bp="4@lg 4@md 12@sm">
            <span class="ppn-program-artwork-cell"><img class="ppn-program-artwork" src="<?php echo $ppnArtwork; ?>" onerror="this.onerror=null; this.src='<?php echo $ppnFallback; ?>';"></span>
        </div>
        <div class="ppn-program-info-cell" bp="8@lg 8@md 12@sm">
            <p class="ppn-program-description"><?php echo wp_kses_post($props['program_desc']);?></p>
            <br />
            <b class="ppn-program-talent-name">By <?php echo esc_html($props['talent_name']); ?></b>
            <p class="ppn-program-url word-break"><a href="<?php echo esc_url($props['program_url']);?>"><?php echo esc_url($props['program_url']);?></a></p>
        </div>
        <div class="ppn-program-sub-title" bp="12">
            <h2 style="margin: 0; ">Subscribe</h2>
        </div>
        <div class="ppn-program-sub-btns" bp="12">
            <?php echo powerpress_do_subscribe_sidebar_widget([
                'feed_url' => esc_url($props['program_rssurl']),
                'modern_style' => $props['ssb-shape'] == "square" ? "-sq" : "",
                'modern_direction' => 'horizontal',
                'itunes_url' => esc_url($props['subscribe_itunes'] ? $props['subscribe_itunes'] : $props['program_itunesurl']),
                'subscribe_feature_email_sidebar' => true,
                'subscribe_feature_apple_sidebar' => true,
                'subscribe_feature_android_sidebar' => true,
                'subscribe_feature_gp_sidebar' => true,
                'subscribe_feature_tunein_sidebar' => true,
                'tunein_url' => esc_url($props['subscribe_tunein']),
                'subscribe_feature_spotify_sidebar' => true,
                'spotify_url' => esc_url($props['subscribe_spotify']), //TODO make program meta have this value
                'subscribe_feature_iheart_sidebar' => false,
                'iheart_url' => esc_url($props['subscribe_iheart']),
                'subscribe_feature_deezer_sidebar' => false,
                'deezer_url' => esc_url($props['subscribe_deezer']),
                'subscribe_feature_podchaser_sidebar' => false,
                'podchaser_url' => esc_url($props['subscribe_podchaser']),
                'subscribe_feature_jiosaavn_sidebar' => false,
                'jiosaavn_url' => esc_url($props['subscribe_jiosaavn']),
                'subscribe_feature_gaana_sidebar' => false,
                'gaana_url' => esc_url($props['subscribe_gaana']),
                'subscribe_feature_pcindex_sidebar' => false,
                'pcindex_url' => esc_url($props['subscribe_pcindex']),
                'subscribe_feature_amazon_sidebar' => false,
                'amazon_url' => esc_url($props['subscribe_amazon_music']),
                'subscribe_feature_pandora_sidebar' => false,
                'pandora_url' => esc_url($props['subscribe_pandora']),
                'subscribe_feature_anghami_sidebar' => false,
                'anghami_url' => esc_url($props['subscribe_anghami']),
                'subscribe_feature_rss_sidebar' => true,
                'subscribe_page_url' => esc_url($props['subscribe_html']),
            ]) ?>
        </div>
        <div data-ppn-episodes class="<?php echo $props['style'] === 'playlist' ? 'ppn-playlist' : ''; ?>" bp="12" data-program="<?php echo esc_attr($props['program_id']); ?>" data-style="<?php echo esc_attr($props['style']); ?>" data-limit="<?php echo esc_attr($props['limit']); ?>" data-loaded="<?php echo esc_attr(count($props['episodes'])); ?>" data-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
            <?php
            $props['word_break'] = true;
            $props['row_spacer'] = false;
            include(dirname(__FILE__) . '/program-episodes.php');
            ?>
        </div>
        <?php include(dirname(__FILE__) . '/program-loadmore.php'); ?>
    </div>
    <?php
}

