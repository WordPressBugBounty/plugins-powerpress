<?php
// episode rows, shared by first paint and the ppn_episodes handler
$ppnBreak = !empty($props['word_break']) ? ' word-break' : '';
$ppnSpacer = !empty($props['row_spacer']);
$ppnPlaylist = (isset($props['style']) && $props['style'] === 'playlist');
$ppnSelected = $props['selected'] ?? '';
$ppnOffset = (int) ($props['offset'] ?? 0);
$ppnIndex = 0;

$ppnGlyph = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
    . '<path fill="currentColor" d="M12 3v10.6l3.3-3.3 1.4 1.4L11 18l-5.7-6.3 1.4-1.4L10 13.6V3h2zM4 19h16v2H4z"/>'
    . '</svg>';

foreach ($props['episodes'] as $episode) {
    $title = $episode['podcast_title'];
    $slug = sanitize_title($title);
    $isSelected = ($ppnSelected === '') ? ($ppnOffset === 0 && $ppnIndex === 0) : ($ppnSelected === $slug);
    $ppnAbsolute = $ppnOffset + $ppnIndex;
    $ppnIndex++;

    $downloadLabel = sprintf(__('Download %s', 'powerpress'), $title);
    $notes = $episode['podcast_desc'];
    $plain = wp_strip_all_tags($notes, true);
    $collapsible = (mb_strlen($plain) > PPN_EPISODE_EXCERPT_CHARS);
    // content derived, an appended row must not collide with an inline one
    $notesKey = substr(md5($episode['media_url']), 0, 10);
    $notesId = "ppn-notes-{$notesKey}";

    if ($ppnPlaylist) {
        $current = $isSelected ? ' ppn-episode-row--current' : '';
        ?>
        <div class="ppn-episode-row ppn-episode-row--band<?php echo $current; ?>"<?php echo $isSelected ? ' aria-current="true"' : ''; ?>>
            <span class="ppn-episode-tick"></span>
            <div class="ppn-episode-main">
                <h3 class="ppn-episode-title<?php echo $ppnBreak; ?>">
                    <a class="ppn-episode-hit" href="<?php echo esc_url(add_query_arg('ppn_episode', $slug)); ?>" data-ppn-select="<?php echo esc_attr($slug); ?>" data-ppn-index="<?php echo esc_attr($ppnAbsolute); ?>" data-ppn-media="<?php echo esc_attr($episode['media_url']); ?>"><?php echo esc_html($title); ?></a>
                </h3>
                <?php if ($plain !== '') { ?>
                    <p class="ppn-episode-excerpt<?php echo $ppnBreak; ?>"><?php echo wp_html_excerpt($notes, PPN_EPISODE_EXCERPT_CHARS, '...'); ?></p>
                <?php } ?>
            </div>
            <span class="ppn-episode-date"><?php echo wp_date('M d, Y', $episode['podcast_post_date']); ?></span>
            <a class="ppn-download-link" href="<?php echo esc_url($episode['media_url']); ?>" aria-label="<?php echo esc_attr($downloadLabel); ?>"><?php echo $ppnGlyph; ?></a>
            <?php if ($plain !== '') { ?>
                <div class="ppn-episode-notes ppn-episode-notes--dormant">
                    <div class="ppn-episode-description<?php echo $ppnBreak; ?>"><?php echo wp_kses($notes, PowerPressNetworkShortCode::showNotesAllowedHtml()); ?></div>
                </div>
            <?php } ?>
        </div>
        <?php

        if ($ppnSpacer) {
            echo '<br>';
        }

        continue;
    }

    do_action('wp_powerpress_player_scripts');
    $player = PowerPressNetworkShortCode::buildPlayer($episode['media_url'], PowerPressNetworkShortCode::playerEpisodeData($props));
    ?>
    <div class="ppn-episode-row ppn-episode-row--open">
        <span class="ppn-episode-tick"></span>
        <div class="ppn-episode-main">
            <h3 class="ppn-episode-title<?php echo $ppnBreak; ?>"><?php echo esc_html($title); ?></h3>
        </div>
        <span class="ppn-episode-date"><?php echo wp_date('M d, Y', $episode['podcast_post_date']); ?></span>
        <a class="ppn-download-link" href="<?php echo esc_url($episode['media_url']); ?>" aria-label="<?php echo esc_attr($downloadLabel); ?>"><?php echo $ppnGlyph; ?></a>
        <div class="ppn-episode-well"><?php echo $player; ?></div>

        <?php if ($plain !== '') { ?>
            <div class="ppn-episode-notes">
                <?php if ($collapsible) { ?>
                    <input type="checkbox" id="<?php echo esc_attr($notesId); ?>" class="ppn-episode-notes-check">
                    <p class="ppn-episode-excerpt<?php echo $ppnBreak; ?>"><?php echo wp_html_excerpt($notes, PPN_EPISODE_EXCERPT_CHARS, '...'); ?></p>
                <?php } ?>
                <div class="ppn-episode-description<?php echo $ppnBreak; echo $collapsible ? ' ppn-episode-description--collapsible' : ''; ?>"><?php echo wp_kses($notes, PowerPressNetworkShortCode::showNotesAllowedHtml()); ?></div>
                <?php if ($collapsible) { ?>
                    <label for="<?php echo esc_attr($notesId); ?>" class="ppn-episode-notes-more">
                        <span class="ppn-notes-more-label"><?php esc_html_e('More', 'powerpress'); ?></span>
                        <span class="ppn-notes-less-label"><?php esc_html_e('Less', 'powerpress'); ?></span>
                    </label>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <?php

    if ($ppnSpacer) {
        echo '<br>';
    }
}
