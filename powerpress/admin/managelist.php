<?php
// ======
//  INIT
// ======

$availablePages = get_pages();
$new_group = false;
if (empty($props['list_info'])) {
    $props['programs'] = [];
    $networkInfo['link_page_list'] = '';
    $new_group = true;
}

// =============
//  FORM FIELDS
// =============

$formId = $new_group ? 'createForm' : 'editForm';
$titleName = $new_group ? 'newListTitle' : 'editListTitle';
$descName = $new_group ? 'newListDescription' : 'editListDescription';
$titleValue = $new_group ? '' : esc_attr(stripslashes($props['list_info']['list_title']));
$descValue = $new_group ? '' : esc_html(stripslashes($props['list_info']['list_description']));

// resolve page link from map
$networkMap = get_option('powerpress_network_map', []);
$listMapKey = 'l-' . ($networkInfo['list_id'] ?? '');
$linkedPageId = $networkMap[$listMapKey] ?? 0;
$pageLink = '(not set)';
$hasPageLink = false;
if ($linkedPageId && get_post_status($linkedPageId) === 'publish') {
    $pageLink = get_permalink($linkedPageId);
    $hasPageLink = true;
}

// ==========
//  BACK URL
// ==========

$backPage = urlencode(powerpress_admin_get_page());
$backUrl = admin_url("admin.php?page={$backPage}&status=Select+Choice&tab=groups");

// ================
//  SHOWS IN GROUP
// ================

$checkedShows = [];
foreach ($props['programs'] as $i => $program) {
    $mapKey = "p-{$program['program_id']}";
    $link = null;
    $pageMissing = false;
    if (isset($networkMap[$mapKey])) {
        if (get_post_status($networkMap[$mapKey]) === 'publish') {
            $link = get_permalink($networkMap[$mapKey]);
        } else {
            $pageMissing = true;
        }
    }
    $props['programs'][$i]['link'] = $link;

    if (!empty($program['checked'])) {
        $checkedShows[] = [
            'title'        => $program['program_title'],
            'program_id'   => $program['program_id'],
            'has_page'     => !empty($link),
            'page_missing' => $pageMissing,
            'link'         => $link,
        ];
    }
}

// sort programs w/o page to top
usort($checkedShows, function($a, $b) {
    return $a['has_page'] - $b['has_page'];
});

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

<!-- =====================
      GROUP SETTINGS FORM
     ===================== -->

<div class="row">
    <div class="col-md-6">
        <div class="flex-row p-2 d-block">
            <h2 class="ppn-manage__section-title m-0 p-2">Edit Group</h2>
            <h4 class="ppn-manage__section-desc p-2"><?php esc_html_e('Group shows together and showcase them on one page.', 'powerpress');?></h4>
        </div>
        <div class="settingBox w-100">
            <form method="POST" action="#/" id="<?php echo $formId; ?>">

                <h4 class="ppn-manage__field-label"><?php esc_html_e('Group Name', 'powerpress');?></h4>
                <input id="editListTitle" name="<?php echo $titleName; ?>" type="text" value="<?php echo $titleValue; ?>" class="ppn-manage__page-link">

                <h4 class="ppn-manage__field-label"><?php esc_html_e('Group Description', 'powerpress');?></h4>
                <textarea id="editListDescription" name="<?php echo $descName; ?>" rows="4" maxlength="500" class="ppn-manage__page-link"><?php echo $descValue; ?></textarea>
                <p class="description" style="color: #999;"><?php esc_html_e('500 character limit', 'powerpress'); ?></p>

                <?php if (!$new_group) { ?>
                    <h4 class="ppn-manage__field-label"><?php esc_html_e('Group Page Link', 'powerpress'); ?></h4>
                    <div id="ppn-page-link-row" class="ppn-page-link-row">
                        <input id="ppn-page-link-input" class="ppn-manage__page-link" type="text" value="<?php echo $pageLink; ?>" readonly>
                        <?php if ($hasPageLink) { ?>
                            <a href="<?php echo esc_url($pageLink); ?>" target="_blank" class="ppn-page-link-view" title="<?php esc_attr_e('View page', 'powerpress'); ?>"><i class="material-icons-outlined">open_in_new</i></a>
                            <a href="<?php echo esc_url(get_edit_post_link($linkedPageId)); ?>" target="_blank" class="ppn-page-link-view" title="<?php esc_attr_e('Edit page', 'powerpress'); ?>"><i class="material-icons-outlined">edit</i></a>
                        <?php } ?>
                    </div>
                    <div class="d-flex gap-sm" style="margin-top: 8px;">
                        <?php if ($hasPageLink) { ?>
                            <button id="ppn-link-page-btn" type="button" class="button" data-ppn-action="ppnDialog" data-ppn-dialog="selectPageBox"><?php esc_html_e('Change Page', 'powerpress'); ?></button>
                            <button type="submit" form="unlinkPageForm" class="button ppn-button-danger" onclick="return confirm('<?php $listTitle = esc_js($props['list_info']['list_title']); echo esc_js("Are you sure you want to unlink this page from {$listTitle}?"); ?>')"><?php esc_html_e('Unlink Page', 'powerpress'); ?></button>
                        <?php } else { ?>
                            <button id="ppn-link-page-btn" type="button" class="button" data-ppn-action="ppnDialog" data-ppn-dialog="selectPageBox"><?php esc_html_e('Add Page', 'powerpress'); ?></button>
                        <?php } ?>
                    </div>
                    <input id="requestAction" name="requestAction" value="save" hidden>
                <?php } ?>

            <!-- =================
                  ADD SHOWS MODAL
                 ================= -->

            <dialog id="programBox" class="ppn-dialog ppn-dialog--lg">
                <button type="button" class="ppn-dialog__close" data-ppn-action="ppnDialogClose" aria-label="<?php esc_attr_e('Close', 'powerpress'); ?>"><i class="material-icons-outlined">close</i></button>
                <h4 class="ppn-manage__field-label"><?php esc_html_e('Add Shows', 'powerpress'); ?></h4>

                <input type="text" id="showSearchInput" class="ppn-manage__page-link mb-2" placeholder="Search shows..." data-ppn-action="filterShows">

                <div class="well-z-depth-1 ppn-manage__scroll-box ppn-manage__scroll-box--modal">
                    <div id="showsTable">
                        <?php for ($i = 0; $i < count($props['programs']); ++$i) { ?>
                            <div class="show-row" data-title="<?php echo esc_attr(strtolower($props['programs'][$i]['program_title'])); ?>">
                                <label class="ppn-show-row-label">
                                    <input name="program[<?php echo $i; ?>]" class="program" type="checkbox"
                                           value="<?php echo esc_attr($props['programs'][$i]['program_id']); ?>"
                                           <?php if ($props['programs'][$i]['checked']) echo ' checked'; ?>
                                           data-ppn-action="updateListOfShows"
                                           data-title="<?php echo esc_attr($props['programs'][$i]['program_title']); ?>"
                                           data-program-id="<?php echo esc_attr($props['programs'][$i]['program_id']); ?>"
                                           data-has-page="<?php echo !empty($props['programs'][$i]['link']) ? '1' : '0'; ?>">
                                    <span class="ppn-manage__show-name" title="<?php echo esc_attr($props['programs'][$i]['program_title']); ?>"><?php echo esc_html($props['programs'][$i]['program_title']); ?></span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="text-right mt-2">
                    <button type="button" class="button button-primary" data-ppn-action="ppnDialogClose"><?php esc_html_e('Done', 'powerpress');?></button>
                </div>
            </dialog>

            </form>
            <?php if (!$new_group) { ?>
                <form method="POST" id="unlinkPageForm" action="<?php echo esc_url("?page=" . urlencode(powerpress_admin_get_page()) . "&status=Manage+List&tab=groups"); ?>">
                    <input type="hidden" name="target" value="List" />
                    <input type="hidden" name="targetId" value="<?php echo esc_attr($networkInfo['list_id']); ?>" />
                    <input type="hidden" name="redirectUrl" value="false" />
                    <input type="hidden" name="pageAction" value="unlink" />
                    <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>
                </form>
            <?php } ?>
        </div>
    </div>

    <div class="col-md-6">
        <?php if (!$new_group) { ?>
        <div class="settingBox w-100 d-flex pp-flex-col ppn-manage__shows-panel">
            <h4 class="ppn-manage__field-label"><?php esc_html_e('Shows in Group', 'powerpress');?></h4>
            <p class="description"><?php esc_html_e('Shows need a linked page to appear in the grid. If a show is missing from the front end, make sure it has a published page assigned.', 'powerpress'); ?></p>
            <div class="well-z-depth-1 pp-flex-1 ppn-manage__scroll-box" style="overflow-y: auto;">
                <ul id="shows-in-group" class="ppn-manage__show-list">
                <?php if (empty($checkedShows)) { ?>
                    <li class="ppn-manage__show-item--empty"><?php esc_html_e('No shows in this group yet', 'powerpress'); ?></li>
                <?php } else { ?>
                    <?php foreach ($checkedShows as $show) { ?>
                        <li class="ppn-manage__show-item" data-title="<?php echo esc_attr($show['title']); ?>">
                            <?php echo esc_html($show['title']); ?>

                            <?php if ($show['page_missing']): // linked page no longer published (draft/trash) ?>
                                <span id="ppn-show-status-<?php echo esc_attr($show['program_id']); ?>" class="ppn-page-status" style="margin-left: auto;">
                                    <button type="button"
                                            class="ppn-page-status--action"
                                            data-ppn-action="ppnPageAction"
                                            data-mode="group"
                                            data-target="Program"
                                            data-id="<?php echo esc_attr($show['program_id']); ?>"
                                            data-title="<?php echo esc_attr($show['title']); ?>">
                                                <?php esc_html_e('Create Page', 'powerpress'); ?>
                                    </button>
                                </span>
                            <?php elseif (!$show['has_page']): // no page ever created ?>
                                <span id="ppn-show-status-<?php echo esc_attr($show['program_id']); ?>" class="ppn-page-status" style="margin-left: auto;">
                                    <button type="button" 
                                            class="ppn-page-status--action" 
                                            data-ppn-action="ppnPageAction" 
                                            data-mode="group" 
                                            data-target="Program" 
                                            data-id="<?php echo esc_attr($show['program_id']); ?>" 
                                            data-title="<?php echo esc_attr($show['title']); ?>">
                                                <?php esc_html_e('Create Page', 'powerpress'); ?>
                                    </button>
                                </span>
                            <?php elseif ($show['link']): ?>
                                <span class="ppn-page-status" style="margin-left: auto;">
                                    <a class="ppn-page-status--linked" target="_blank" href="<?php echo esc_url($show['link']); ?>">
                                        <?php esc_html_e('View Page', 'powerpress'); ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php } ?>
                <?php } ?>
                </ul>
            </div>
        </div>
        <div class="pl-4">
            <button type="button" class="button" data-ppn-action="ppnDialog" data-ppn-dialog="programBox">
                <?php esc_html_e('Manage Shows', 'powerpress'); ?>
            </button>
        </div>

        <?php } else { ?>
        <div class="settingBox w-100">
            <h4 class="ppn-manage__field-label"><?php esc_html_e('About Groups', 'powerpress');?></h4>
            <p class="description"><?php esc_html_e('Groups let you organize shows for showcasing and display on your network site. After creating a group you can add shows and link it to a page. The group page will display your show(s) podcast artwork grid.', 'powerpress'); ?></p>
            <h4 class="ppn-manage__field-label" style="margin-top: 16px;"><?php esc_html_e('Available Shortcodes', 'powerpress');?></h4>
            <p class="description"><code>[ppn-gridview id="{group_id}" rows="100" cols="3"]</code><br><?php esc_html_e('Show artwork in a grid layout.', 'powerpress'); ?></p>
            <p class="description"><code>[ppn-list id="{group_id}" style="detailed"]</code><br><?php esc_html_e('Show a detailed list with artwork and descriptions.', 'powerpress'); ?></p>
            <p class="description"><code>[ppn-list id="{group_id}"]</code><br><?php esc_html_e('Show a simple list of show titles.', 'powerpress'); ?></p>
        </div>
        <?php } ?>
    </div>
</div>

<!-- ============
      ACTION BAR
     ============ -->

<div class="flex-row d-flex justify-content-end gap-md p-3 align-items-center">
    <span id="ppn-unsaved-indicator" class="ppn-unsaved-indicator" style="display: none;"><?php esc_html_e('Unsaved changes', 'powerpress'); ?></span>
    <a href="<?php echo esc_url($backUrl); ?>" class="button">
        <?php esc_html_e('Back', 'powerpress');?>
    </a>
    <button type="button" class="button button-primary" data-ppn-action="ppnAction" data-form="<?php echo $formId; ?>" data-navigate="Manage List" data-change="true">
        <?php esc_html_e('Save Group', 'powerpress');?>
    </button>
</div>

<!-- ====================
      PAGE SELECT DIALOG
     ==================== -->

<?php if ( !$new_group ) : ?>
<?php
powerpress_render_network_page_select([
    'form_id'       => 'pageForm',
    'shortcode_id'  => 'ppn-list-shortcode',
    'shortcode'     => "[ppn-gridview id=\"{$networkInfo['list_id']}\" rows=\"100\" cols=\"3\"]",
    'target'        => 'List',
    'target_id'     => $networkInfo['list_id'],
    'create_id'     => $networkInfo['list_id'],
    'create_title'  => $props['list_info']['list_title'],
    'selected_page' => $linkedPageId,
    'edit_url'      => $hasPageLink ? get_edit_post_link($linkedPageId) : '',
    'pages'         => $availablePages,
]);
?>

<?php endif; ?>

<div class="clear"></div>
<form method="POST" action="#/" id="manageForm"> <!-- Make sure to keep back slash there for WordPress -->
</form>
</div>
