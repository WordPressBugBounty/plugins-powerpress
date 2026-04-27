<?php
// TODO: not currently rendered, can probably match the managelist.php pattern?

// ======
//  INIT
// ======

$availablePages = get_pages();
$programMapKey = 'p-' . $props['program_info']['program_id'];
$programNetworkMap = get_option('powerpress_network_map', []);
$linkedProgramPageId = $programNetworkMap[$programMapKey] ?? 0;

$programId = $props['program_info']['program_id'];
$programTitle = $props['program_info']['program_title'];

// resolve page link from map
$pageLink = '(not set)';
$hasPageLink = false;
if ($linkedProgramPageId && get_post_status($linkedProgramPageId) === 'publish') {
    $pageLink = get_permalink($linkedProgramPageId);
    $hasPageLink = true;
}

// ==========
//  BACK URL
// ==========

$backPage = urlencode(powerpress_admin_get_page());
$backUrl = admin_url("admin.php?page={$backPage}&status=Select+Choice&tab=shows");
?>

<!-- =============
      PAGE HEADER
     ============= -->

<div class="ppn-page-header">
    <div>
        <h1 class="ppn-page-title"><?php esc_html_e('Podcast Network', 'powerpress');?></h1>
        <h4 class="ppn-page-subtitle"><?php esc_html_e('Build and manage your podcast network.','powerpress');?></h4>
    </div>
</div>

<div class="tabs-container">
<form method="POST" action="#/" id="manageForm"> <!-- Make sure to keep back slash there for WordPress -->
</form>

<!-- ==================
      PROGRAM SETTINGS
     ================== -->

<div class="row">
    <div class="col-md-6">
        <div class="flex-row p-2 d-block">
            <h2 class="ppn-manage__section-title m-0 p-2"><?php echo esc_html($programTitle); ?></h2>
            <h4 class="ppn-manage__section-desc p-2"><?php esc_html_e('Manage page linking for this show.', 'powerpress');?></h4>
        </div>
        <div class="settingBox w-100">

            <h4 class="ppn-manage__field-label"><?php esc_html_e('Show Page Link', 'powerpress'); ?></h4>
            <div id="ppn-page-link-row" class="ppn-page-link-row">
                <input id="ppn-page-link-input" class="ppn-manage__page-link" type="text" value="<?php echo $pageLink; ?>" readonly>
                <?php if ($hasPageLink) { ?>
                    <a href="<?php echo esc_url($pageLink); ?>" target="_blank" class="ppn-page-link-view" title="<?php esc_attr_e('View page', 'powerpress'); ?>"><i class="material-icons-outlined">open_in_new</i></a>
                    <a href="<?php echo esc_url(get_edit_post_link($linkedProgramPageId)); ?>" target="_blank" class="ppn-page-link-view" title="<?php esc_attr_e('Edit page', 'powerpress'); ?>"><i class="material-icons-outlined">edit</i></a>
                <?php } ?>
            </div>
            <div class="d-flex gap-sm" style="margin-top: 8px;">
                <?php if ($hasPageLink) { ?>
                    <button id="ppn-link-page-btn" type="button" class="button" data-ppn-action="ppnDialog" data-ppn-dialog="selectPageBox"><?php esc_html_e('Change Page', 'powerpress'); ?></button>
                    <button type="submit" form="unlinkPageForm" class="button ppn-button-danger" onclick="return confirm('<?php $t = esc_js($programTitle); echo esc_js("Are you sure you want to unlink this page from {$t}?"); ?>')"><?php esc_html_e('Unlink Page', 'powerpress'); ?></button>
                    <button type="submit" form="removeProgramForm" class="button ppn-button-danger" onclick="return confirm('<?php $t = esc_js($programTitle); echo esc_js("Are you sure you want to remove '{$t}'?"); ?>')"><?php esc_html_e('Remove Program', 'powerpress'); ?></button>
                <?php } else { ?>
                    <button id="ppn-link-page-btn" type="button" class="button" data-ppn-action="ppnDialog" data-ppn-dialog="selectPageBox"><?php esc_html_e('Select or Create Page', 'powerpress'); ?></button>
                <?php } ?>
            </div>

        </div>

        <!-- hidden forms for destructive actions -->
        <?php if ($hasPageLink) { ?>
            <form method="POST" id="unlinkPageForm" action="<?php echo esc_url("?page=" . urlencode(powerpress_admin_get_page()) . "&status=Manage+Program&tab=shows"); ?>">
                <input type="hidden" name="target" value="Program" />
                <input type="hidden" name="targetId" value="<?php echo esc_attr($programId); ?>" />
                <input type="hidden" name="redirectUrl" value="false" />
                <input type="hidden" name="pageAction" value="unlink" />
                <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>
            </form>
            <form method="POST" id="removeProgramForm" action="<?php echo esc_url("?page=" . urlencode(powerpress_admin_get_page()) . "&status=Select+Choice&tab=shows"); ?>">
                <input type="hidden" name="target" value="program" />
                <input type="hidden" name="targetId" value="<?php echo esc_attr($programId); ?>" />
                <input type="hidden" name="requestAction" value="delete" />
                <input type="hidden" name="redirectUrl" value="false" />
                <input type="hidden" name="changeOrCreate" value="true" />
                <input type="hidden" name="pageAction" value="clearSiteCache" />
                <input type="hidden" name="clearSiteCache" value="true" />
                <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>
            </form>
        <?php } ?>
    </div>

    <div class="col-md-6">
        <div class="settingBox w-100">
            <h4 class="ppn-manage__field-label"><?php esc_html_e('Available Shortcode', 'powerpress');?></h4>
            <p class="description"><code><?php echo esc_html($props['program_info']['shortcode']); ?></code></p>
            <p class="description"><?php esc_html_e('This shortcode is automatically placed on the linked page to display the show.', 'powerpress'); ?></p>
        </div>
    </div>
</div>

<!-- ============
      ACTION BAR
     ============ -->

<div class="flex-row d-flex justify-content-end gap-md p-3 align-items-center">
    <a href="<?php echo esc_url($backUrl); ?>" class="button">
        <?php esc_html_e('Back', 'powerpress');?>
    </a>
</div>

<!-- ====================
      PAGE SELECT DIALOG
     ==================== -->

<?php
$pageSelectFormId = $hasPageLink ? 'changeForm' : 'selectForm';
powerpress_render_network_page_select([
    'form_id'       => $pageSelectFormId,
    'shortcode_id'  => 'ppn-program-shortcode',
    'shortcode'     => $props['program_info']['shortcode'],
    'target'        => 'Program',
    'target_id'     => $programId,
    'create_id'     => $programId,
    'create_title'  => $programTitle,
    'selected_page' => $linkedProgramPageId,
    'edit_url'      => $hasPageLink ? get_edit_post_link($linkedProgramPageId) : '',
    'pages'         => $availablePages,
]);
?>

<div class="clear"></div>
<form method="POST" action="#/" id="manageForm"> <!-- Make sure to keep back slash there for WordPress -->
</form>
</div>
