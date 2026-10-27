<?php if (!empty($props['list_desc']) && ($props['show_group_description'] ?? true)): ?>
    <p class="ppn-list-description"><?php echo esc_html($props['list_desc']); ?></p>
<?php endif; ?>

<?php
$ppnFallback = esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg');
$ppnRailId = wp_unique_id('ppn-carousel-');
$ppnShows = [];

foreach ($props['results'] as $ppnProgram) {
    if (!$ppnProgram['link'] || $ppnProgram['link'] == "#") {
        continue;
    }

    $ppnShows[] = $ppnProgram;
}

$ppnLoops = count($ppnShows) > 1;
$ppnCaption = ($props['show_title'] ?? true) || ($props['show_description'] ?? true);
$ppnPasses = $ppnLoops ? 2 : 1;
?>
<div class="ppn-carousel">
    <div class="ppn-carousel__nav">
        <button type="button" class="ppn-carousel__step" data-ppn-carousel="<?php echo esc_attr($ppnRailId); ?>" data-ppn-carousel-step="prev" aria-label="<?php esc_attr_e('Previous shows', 'powerpress'); ?>" hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5 8 12l7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="ppn-carousel__step" data-ppn-carousel="<?php echo esc_attr($ppnRailId); ?>" data-ppn-carousel-step="next" aria-label="<?php esc_attr_e('More shows', 'powerpress'); ?>" hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>

    <ul class="ppn-carousel__rail<?php echo $ppnCaption ? ' ppn-carousel__rail--captioned' : ''; ?>" data-ppn-carousel-rail="<?php echo esc_attr($ppnRailId); ?>"<?php echo $ppnLoops ? ' data-ppn-carousel-loop="1"' : ''; ?> aria-label="<?php echo esc_attr($props['list_title']); ?>">
        <?php for ($ppnPass = 0; $ppnPass < $ppnPasses; $ppnPass++) {
            $ppnEcho = $ppnPass > 0;

            foreach ($ppnShows as $program) {
                $ppnLink = esc_url($program['link']);
                $ppnArt = ($program['artwork_url']['300'] ?? '') !== '' ? esc_url($program['artwork_url']['300']) : $ppnFallback;
                ?>
                <li class="ppn-carousel__item"<?php echo $ppnEcho ? ' aria-hidden="true"' : ''; ?>>
                    <a class="ppn-carousel__card" href="<?php echo $ppnLink; ?>"<?php echo $ppnEcho ? ' tabindex="-1"' : ''; ?>>
                        <span class="ppn-carousel__art">
                            <img src="<?php echo $ppnArt; ?>" alt="" loading="lazy" onerror="this.onerror=null; this.src='<?php echo $ppnFallback; ?>';">
                        </span>
                        <?php if ($props['show_title'] ?? true) { ?>
                            <span class="ppn-carousel__title"><?php echo esc_html($program['program_title']); ?></span>
                        <?php } ?>
                        <?php if (($props['show_description'] ?? true) && $program['program_desc'] !== '') { ?>
                            <span class="ppn-carousel__description"><?php echo esc_html($program['program_desc']); ?></span>
                        <?php } ?>
                    </a>
                </li>
            <?php }
        } ?>
    </ul>
</div>
