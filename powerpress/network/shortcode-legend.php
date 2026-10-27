<?php
if (!class_exists('PowerPressNetworkShortCode')) {
    require_once(dirname(__DIR__) . '/shortcodes/ShortCode.php');
}

foreach (PowerPressNetwork::shortcodeClasses() as $ppnLegendClass) {
    require_once(dirname(__DIR__) . '/shortcodes/' . str_replace('PowerPressNetwork', '', $ppnLegendClass) . '.php');
}

$ppnLegend = PowerPressNetwork::shortcodeLegend();
$ppnFirst = true;
?>
<div class="ppn-legend-shell">
<div class="ppn-legend">
    <?php
    // radios before rail + panes, :checked only looks forward
    foreach (array_keys($ppnLegend) as $ppnTag) { ?>
        <input type="radio" name="ppn-legend-pick" id="ppn-legend-<?php echo esc_attr($ppnTag); ?>"
               class="ppn-legend__radio"<?php checked($ppnFirst); $ppnFirst = false; ?>>
    <?php } ?>

    <div class="ppn-legend__rail">
        <?php foreach (array_keys($ppnLegend) as $ppnTag) { ?>
            <label class="ppn-legend__pick" for="ppn-legend-<?php echo esc_attr($ppnTag); ?>">
                <code><?php echo esc_html($ppnTag); ?></code>
            </label>
        <?php } ?>
    </div>

    <div class="ppn-legend__panes">
        <?php foreach ($ppnLegend as $ppnTag => $ppnEntry) { ?>
            <section class="ppn-legend__pane" data-ppn-legend-pane="<?php echo esc_attr($ppnTag); ?>">
                <p class="ppn-legend__tag"><code>[<?php echo esc_html($ppnTag); ?>]</code></p>
                <p class="ppn-legend__desc"><?php echo esc_html($ppnEntry['description']); ?></p>

                <?php if (!empty($ppnEntry['attributes'])) { ?>
                    <table class="ppn-legend__table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Attribute', 'powerpress'); ?></th>
                                <th><?php esc_html_e('Accepts', 'powerpress'); ?></th>
                                <th><?php esc_html_e('Default', 'powerpress'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ppnEntry['attributes'] as $ppnName => $ppnRow) { ?>
                                <tr>
                                    <td data-label="<?php esc_attr_e('Attribute', 'powerpress'); ?>"><code><?php echo esc_html($ppnName); ?></code></td>
                                    <td data-label="<?php esc_attr_e('Accepts', 'powerpress'); ?>">
                                        <?php if ($ppnRow['enumerated']) { ?>
                                            <?php foreach ($ppnRow['accepts'] as $ppnValue) { ?>
                                                <code class="ppn-legend__value"><?php echo esc_html($ppnValue); ?></code>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="ppn-legend__accepts"><?php echo esc_html($ppnRow['accepts']); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td<?php echo $ppnRow['default'] !== '-' ? ' data-label="' . esc_attr__('Default', 'powerpress') . '"' : ''; ?>>
                                        <?php if ($ppnRow['default'] !== '-') { ?>
                                            <code class="ppn-legend__value"><?php echo esc_html($ppnRow['default']); ?></code>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>

                <p class="ppn-legend__label"><?php esc_html_e('Example', 'powerpress'); ?></p>
                <pre class="ppn-legend__example"><code><?php echo esc_html($ppnEntry['example']); ?></code></pre>
            </section>
        <?php } ?>
    </div>
</div>
</div>
