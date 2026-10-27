<?php
if (!class_exists('PowerPressNetworkShortCode')) {
    require_once(dirname(__DIR__) . '/shortcodes/ShortCode.php');
}

// ============
// FIELD VALUES
// ============

$networkTitleValue = esc_attr(stripslashes((string) get_option('powerpress_network_title', '')));
$networkOption = get_option('powerpress_network', []);
$networkDescValue = esc_html(stripslashes((string) ($networkOption['network_description'] ?? '')));

$managePlayer = PowerPressNetwork::resolvePlayer($networkOption['player_style'] ?? '');
$manageLegend = PowerPressNetwork::shortcodeLegendEnabled();

// resolve homepage link from map
$manageMap = get_option('powerpress_network_map', []);
$manageHomeId = $manageMap['Homepage'] ?? 0;
$manageHomeLink = '(not set)';
$manageHasHome = false;
if ($manageHomeId && get_post_status($manageHomeId) === 'publish') {
    $manageHomeLink = get_permalink($manageHomeId);
    $manageHasHome = true;
}
?>

<!-- ====================
     NETWORK DETAILS FORM
     ==================== -->

<div class="row">
    <div class="col-md-6">
        <div class="flex-row p-2 d-block">
            <h2 class="ppn-manage__section-title m-0 p-2"><?php esc_html_e('Network Details', 'powerpress'); ?></h2>
        </div>
        <div class="settingBox w-100">
            <form method="POST" action="#/" id="networkForm">

                <h4 class="ppn-manage__field-label"><?php esc_html_e('Network Name', 'powerpress'); ?></h4>
                <input id="editNetworkTitle" name="editNetworkTitle" type="text" maxlength="255" value="<?php echo $networkTitleValue; ?>" class="ppn-manage__page-link">

                <h4 class="ppn-manage__field-label"><?php esc_html_e('Network Description', 'powerpress'); ?></h4>
                <textarea id="editNetworkDescription" name="editNetworkDescription" rows="4" maxlength="255" data-char-counter="editNetworkDescriptionCount" data-char-warn="230" class="ppn-manage__page-link"><?php echo $networkDescValue; ?></textarea>
                <p class="description" style="color: #999;"><span id="editNetworkDescriptionCount">0</span> <?php esc_html_e('of 255 characters', 'powerpress'); ?></p>

                <h4 class="ppn-manage__field-label"><?php esc_html_e('Network Page Link', 'powerpress'); ?></h4>
                <div class="ppn-page-link-row">
                    <input class="ppn-manage__page-link" type="text" value="<?php echo esc_attr($manageHomeLink); ?>" readonly>
                    <?php if ($manageHasHome) { ?>
                        <a href="<?php echo esc_url($manageHomeLink); ?>" target="_blank" class="ppn-page-link-view" title="<?php esc_attr_e('View page', 'powerpress'); ?>"><i class="material-icons-outlined">open_in_new</i></a>
                        <a href="<?php echo esc_url(get_edit_post_link($manageHomeId)); ?>" target="_blank" class="ppn-page-link-view" title="<?php esc_attr_e('Edit page', 'powerpress'); ?>"><i class="material-icons-outlined">edit</i></a>
                    <?php } ?>
                </div>
            </form>
        </div>

        <div class="settingBox w-100">
            <h4 class="ppn-manage__field-label"><?php esc_html_e('Editor', 'powerpress'); ?></h4>

            <input type="hidden" name="shortcodeLegend" value="0" form="networkForm">
            <label class="ppn-manage__radio" for="shortcodeLegend">
                <input type="checkbox"
                       name="shortcodeLegend"
                       id="shortcodeLegend"
                       form="networkForm"
                       value="1"
                       <?php checked($manageLegend, true); ?>>
                <?php esc_html_e('Shortcode reference on post and page editors', 'powerpress'); ?>
            </label>
        </div>

        <div class="settingBox w-100">
            <h4 class="ppn-manage__field-label"><?php esc_html_e('Player', 'powerpress'); ?></h4>

            <div class="ppn-manage__player-preview">
                <?php foreach (array_keys(PowerPressNetwork::playerChoices()) as $ppnPreviewValue) { ?>
                    <div data-ppn-player-preview="<?php echo esc_attr($ppnPreviewValue); ?>"<?php echo $managePlayer === $ppnPreviewValue ? '' : ' hidden'; ?>>
                        <?php echo PowerPressNetworkShortCode::previewPlayer($ppnPreviewValue); ?>
                    </div>
                <?php } ?>
            </div>

            <?php foreach (PowerPressNetwork::playerChoices() as $ppnPlayerValue => $ppnPlayerLabel) { ?>
                <label class="ppn-manage__radio" for="playerStyle-<?php echo esc_attr($ppnPlayerValue); ?>">
                    <input type="radio"
                           name="playerStyle"
                           id="playerStyle-<?php echo esc_attr($ppnPlayerValue); ?>"
                           form="networkForm"
                           value="<?php echo esc_attr($ppnPlayerValue); ?>"
                           data-ppn-action="ppnPlayerPreview"
                           <?php checked($managePlayer, $ppnPlayerValue); ?>>
                    <?php echo esc_html($ppnPlayerLabel); ?>
                </label>
            <?php } ?>
        </div>

        <div class="flex-row d-flex justify-content-end gap-md p-3 align-items-center">
            <button type="button" class="button" data-ppn-action="ppnAction" data-form="networkForm" data-tab="manage"><?php esc_html_e('Save Changes', 'powerpress'); ?></button>
        </div>
    </div>
</div>
