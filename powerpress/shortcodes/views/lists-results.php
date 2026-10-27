<?php if (!empty($props['list_desc']) && ($props['show_group_description'] ?? true)): ?>
    <p class="ppn-list-description"><?php echo esc_html($props['list_desc']); ?></p>
<?php endif; ?>

<?php
if ($props['style'] == 'simple') {
?>
    <div class="ppn-program-list ppn-program-list--simple">
        <?php foreach ($props['results'] as $program) {
            if (!$program['link'] || $program['link'] == "#") {
                continue;
            }

            $ppnLink = esc_url($program['link']);
            ?>
            <div class="ppn-program-row">
                <span class="ppn-program-tick"></span>
                <div class="ppn-program-main">
                    <h3 class="ppn-program-title"><a href="<?php echo $ppnLink; ?>"><?php echo esc_html($program['program_title']); ?></a></h3>
                </div>
            </div>
        <?php } ?>
    </div>
<?php
} else if ($props['style'] == 'detailed') {
    $ppnFallback = esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg');
    ?>
    <div class="ppn-program-list">
        <?php foreach ($props['results'] as $program) {
            if (!$program['link'] || $program['link'] == "#") {
                continue;
            }

            $ppnLink = esc_url($program['link']);
            $ppnArt = ($program['artwork_url']['300'] ?? '') !== '' ? esc_url($program['artwork_url']['300']) : $ppnFallback;
            ?>
            <div class="ppn-program-row">
                <span class="ppn-program-tick"></span>
                <a class="ppn-program-thumb" href="<?php echo $ppnLink; ?>" tabindex="-1" aria-hidden="true">
                    <img src="<?php echo $ppnArt; ?>" alt="" onerror="this.onerror=null; this.src='<?php echo $ppnFallback; ?>';">
                </a>
                <div class="ppn-program-main">
                    <h3 class="ppn-program-title"><a href="<?php echo $ppnLink; ?>"><?php echo esc_html($program['program_title']); ?></a></h3>
                    <?php if (($props['show_description'] ?? true) && $program['program_desc'] !== '') { ?>
                        <p class="ppn-program-description"><?php echo esc_html($program['program_desc']); ?></p>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
    <?php
}

if (!empty($props['more_url'])) { ?>
    <div class="ppn-loadmore-row" bp="12">
        <a class="ppn-loadmore" href="<?php echo esc_url($props['more_url']); ?>">
            <?php printf(
                esc_html(_n('See all %s show', 'See all %s shows', $props['total'], 'powerpress')),
                esc_html(number_format_i18n($props['total']))
            ); ?>
        </a>
    </div>
<?php } ?>
