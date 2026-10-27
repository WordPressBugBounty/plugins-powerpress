<?php
// the playlist's fixed player panel, the only place a playlist builds a player
$ppnPanel = null;
$ppnPanelSelected = $props['selected'] ?? '';
$ppnPanelMedia = (string) ($props['media'] ?? '');

// media_url is the only per-episode unique field, titles collide and can slug to ''
foreach ($props['episodes'] as $episode) {
    if ($ppnPanelMedia !== '') {
        if ($episode['media_url'] === $ppnPanelMedia) {
            $ppnPanel = $episode;
            break;
        }

        continue;
    }

    if ($ppnPanelSelected === '' || sanitize_title($episode['podcast_title']) === $ppnPanelSelected) {
        $ppnPanel = $episode;
        break;
    }
}

// fall back to newest only when nothing was asked for by media, a named miss must surface
if ($ppnPanel === null && $ppnPanelMedia === '' && !empty($props['episodes'])) {
    $ppnPanel = $props['episodes'][0];
}

if ($ppnPanel !== null) {
    $panelTitle = $ppnPanel['podcast_title'];
    $panelNotes = $ppnPanel['podcast_desc'];
    $panelPlain = wp_strip_all_tags($panelNotes, true);
    $panelCollapsible = (mb_strlen($panelPlain) > PPN_EPISODE_EXCERPT_CHARS);
    $panelNotesKey = substr(md5($ppnPanel['media_url']), 0, 10);
    $panelNotesId = "ppn-panel-notes-{$panelNotesKey}";
    $panelKicker = (string) ($props['program_title'] ?? '');
    $panelMark = (string) ($props['artwork_url']['300'] ?? '');

    do_action('wp_powerpress_player_scripts');
    $panelPlayer = PowerPressNetworkShortCode::buildPlayer($ppnPanel['media_url'], PowerPressNetworkShortCode::playerEpisodeData($props));
    ?>
    <div class="ppn-panel" data-ppn-panel data-ppn-panel-for="<?php echo esc_attr($props['program_id'] ?? ''); ?>" aria-live="polite">
        <div class="ppn-panel-head" data-ppn-panel-head>
            <?php if ($panelMark !== '') { ?>
                <span class="ppn-panel-mark"><img src="<?php echo esc_url($panelMark); ?>" alt="" onerror="this.onerror=null; this.src='<?php echo esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg'); ?>';"></span>
            <?php } ?>
            <div class="ppn-panel-id">
                <?php if ($panelKicker !== '') { ?>
                    <span class="ppn-panel-kicker"><?php echo esc_html($panelKicker); ?></span>
                <?php } ?>
                <h3 class="ppn-panel-title"><?php echo esc_html($panelTitle); ?></h3>
                <span class="ppn-episode-date"><?php echo wp_date('M d, Y', $ppnPanel['podcast_post_date']); ?></span>
            </div>
            <a class="ppn-download-link" href="<?php echo esc_url($ppnPanel['media_url']); ?>" aria-label="<?php echo esc_attr(sprintf(__('Download %s', 'powerpress'), $panelTitle)); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 3v10.6l3.3-3.3 1.4 1.4L11 18l-5.7-6.3 1.4-1.4L10 13.6V3h2zM4 19h16v2H4z"/></svg>
            </a>
        </div>

        <div class="ppn-episode-well" data-ppn-panel-player><?php echo $panelPlayer; ?></div>

        <?php if ($panelPlain !== '') { ?>
            <div class="ppn-episode-notes" data-ppn-panel-notes>
                <?php if ($panelCollapsible) { ?>
                    <input type="checkbox" id="<?php echo esc_attr($panelNotesId); ?>" class="ppn-episode-notes-check">
                    <p class="ppn-episode-excerpt"><?php echo wp_html_excerpt($panelNotes, PPN_EPISODE_EXCERPT_CHARS, '...'); ?></p>
                <?php } ?>
                <div class="ppn-episode-description<?php echo $panelCollapsible ? ' ppn-episode-description--collapsible' : ''; ?>"><?php echo wp_kses($panelNotes, PowerPressNetworkShortCode::showNotesAllowedHtml()); ?></div>
                <?php if ($panelCollapsible) { ?>
                    <label for="<?php echo esc_attr($panelNotesId); ?>" class="ppn-episode-notes-more">
                        <span class="ppn-notes-more-label"><?php esc_html_e('More', 'powerpress'); ?></span>
                        <span class="ppn-notes-less-label"><?php esc_html_e('Less', 'powerpress'); ?></span>
                    </label>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <?php
}
