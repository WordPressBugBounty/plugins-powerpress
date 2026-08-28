<?php
// =============
//  FIELD VALUES
// =============

$networkTitleValue = esc_attr(stripslashes((string) get_option('powerpress_network_title', '')));
$networkOption = get_option('powerpress_network', []);
$networkDescValue = esc_html(stripslashes((string) ($networkOption['network_description'] ?? '')));

// one shot save notice, read and cleared here so it survives the post then tab refresh
$manageNotice = get_option('ppn_manage_notice');
if (!empty($manageNotice)) {
    delete_option('ppn_manage_notice');
}

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

<!-- =======================
      NETWORK DETAILS FORM
     ======================= -->

<div class="row">
    <div class="col-md-6">
        <?php if (!empty($manageNotice['text'])) { ?>
            <div class="notice <?php echo !empty($manageNotice['error']) ? 'notice-error' : 'notice-success'; ?> inline">
                <p><?php echo esc_html($manageNotice['text']); ?></p>
            </div>
        <?php } ?>
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

        <div class="flex-row d-flex justify-content-end gap-md p-3 align-items-center">
            <button type="button" class="button" data-ppn-action="ppnAction" data-form="networkForm" data-tab="manage"><?php esc_html_e('Save Changes', 'powerpress'); ?></button>
        </div>
    </div>
</div>
