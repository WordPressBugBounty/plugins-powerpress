<?php
// ======
//  INIT
// ======
if (empty($props) || !empty($props['error']) || !empty($props['danger']))
    $props = [];

// ===============
//  ORGANIZE LIST
// ===============

// pre-group by status bucket
$groups = ['pending' => [], 'completed' => []];
foreach ($props as $app) {
    if (!is_array($app) || empty($app['applicant_id'])) continue;
    $bucket = ((int)($app['app_status'] ?? 0)) === 0 ? 'pending' : 'completed';
    $groups[$bucket][] = $app;
}

// ====================
//  APPLICATION BUTTON
// ====================
$networkMap = get_option('powerpress_network_map', []);
$applicationPageExists = false;
$applicationPageId = null;
if (!empty($networkMap['Application'])) {
    $pageId = $networkMap['Application'];
    if (get_post_status($pageId) == 'publish') {
        $applicationPageExists = true;
        $applicationPageId = $pageId;
    }
}

// =================
//  TOS URL SETTING
// =================
$ppn_settings = get_option('powerpress_network', []);
$tos_url = $ppn_settings['tos_url'] ?? '';
?>

<!-- =============
      PAGE HEADER
     ============= -->
<div class="ppn-section-header">
    <h2 class="ppn-manage__section-title"><?php echo esc_html(get_option('powerpress_network_title'));?></h2>
    <?php if ($applicationPageExists): ?>
        <a class="button" href="<?php echo esc_url(get_edit_post_link($applicationPageId)); ?>" target="_blank">
            <?php esc_html_e('Edit Application Page', 'powerpress'); ?>
        </a>
    <?php else: ?>
        <button type="button" class="button"
                data-ppn-action="ppnPageAction"
                data-mode="singleton"
                data-target="Application"
                data-title="<?php echo esc_attr(get_option('powerpress_network_title') . ' Application'); ?>"
                data-edit-label="<?php esc_attr_e('Edit Application Page', 'powerpress'); ?>">
            <?php esc_html_e('Create Application', 'powerpress'); ?>
        </button>
    <?php endif; ?>
</div>
<h4 class="ppn-manage__section-desc"><?php esc_html_e('Review and manage applications from shows wanting to join your network.', 'powerpress'); ?></h4>

<!-- ====================
      TOS COLLAPSIBLE
     ==================== -->
<div class="ppn-tos">
    <div class="ppn-toggle ppn-toggle--collapsed" data-ppn-section="tos">
        <label class="ppn-manage__field-label" style="margin: 0; cursor: pointer;"><?php esc_html_e('Terms & Conditions', 'powerpress'); ?></label>
        <i class="material-icons-outlined ppn-toggle__chevron">expand_more</i>
    </div>
    <div class="ppn-toggle__body" data-ppn-section="tos">
        <div class="ppn-toggle__inner">
        <form method="post" id="tosUrlForm">
            <input type="hidden" name="ppn-action" value="save-tos-url" />
            <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>
            <p class="ppn-tos__hint"><?php esc_html_e('Applicants are required to agree to your Terms before submitting. Leave blank if you do not have Terms.', 'powerpress'); ?></p>
            <div class="d-flex gap-sm" style="align-items: center;">
                <input type="text" id="ppn_tos_url" name="ppn_tos_url" class="pp-flex-1 ppn-manage__page-link" value="<?php echo esc_attr($tos_url); ?>" placeholder="Terms of Service URL" />
                <button type="button" class="button" data-ppn-action="saveTosUrl" data-form-id="tosUrlForm"><?php esc_html_e('Save', 'powerpress'); ?></button>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- ===============
      SEARCH + LIST
     =============== -->
<div class="ppn-search">
    <input type="text" class="ppn-search__input" placeholder="<?php esc_attr_e('Filter requests...', 'powerpress'); ?>"
           data-ppn-action="filterList" data-ppn-target="ppn-requests-list">
    <i class="material-icons-outlined ppn-search__icon">search</i>
</div>

<div class="ppn-list" id="ppn-requests-list">
    <div class="ppn-list__header row">
        <div class="col-4"><?php esc_html_e('Shows', 'powerpress'); ?></div>
        <div class="col-3"><?php esc_html_e('Application', 'powerpress'); ?></div>
        <div class="col-3"><?php esc_html_e('Status', 'powerpress'); ?></div>
        <div class="col-2"><?php esc_html_e('Manage', 'powerpress'); ?></div>
    </div>
    <?php if (empty($groups['pending']) && empty($groups['completed'])): ?>
        <div class="ppn-list__row ppn-list__empty">
            <span class="pp-text-muted"><?php esc_html_e('No applications yet.', 'powerpress'); ?></span>
        </div>
    <?php elseif (!isset($props['danger'])):
        // shared row renderer
        $render_app = function($app) {
            $showName  = $app['show_name'] ?? $app['program_title'] ?? '';
            $appId     = $app['applicant_id'] ?? 0;
            $status    = (int)($app['app_status'] ?? 0);
            $statusMap = [1 => 'approved', -1 => 'rejected'];
            $statusMod = $statusMap[$status] ?? 'pending';
            ?>
            <div class="ppn-list__row row" id="app-row-<?php echo esc_attr($appId); ?>" data-title="<?php echo esc_attr(strtolower($showName)); ?>">
                <div class="col-4">
                    <?php echo esc_html($showName); ?>
                </div>
                <div class="col-3">
                    <span class="ppn-list__label"><?php esc_html_e('Application', 'powerpress'); ?></span>
                    <a href="#" class="ppn-page-status--linked" data-ppn-action="ppnDialog" data-ppn-dialog="ppn-app-<?php echo esc_attr($appId); ?>"><?php esc_html_e('View Application', 'powerpress'); ?></a>
                </div>
                <div id="app-status-<?php echo esc_attr($appId); ?>" class="col-3">
                    <span class="ppn-list__label"><?php esc_html_e('Status', 'powerpress'); ?></span>
                    <span class="ppn-app-status-label ppn-app-status-label--<?php echo esc_attr($statusMod); ?>">
                        <?php
                        if ($status === 1) esc_html_e('Approved', 'powerpress');
                        elseif ($status === -1) esc_html_e('Rejected', 'powerpress');
                        else esc_html_e('Pending', 'powerpress');
                        ?>
                    </span>
                </div>
                <div class="col-2">
                    <?php if ($status !== 1): ?>
                    <button type="button" class="ppn-icon-btn" data-ppn-action="approveProgram" data-applicant-id="<?php echo esc_attr($appId); ?>" data-delete="true"><i class="material-icons-outlined">delete</i></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        };

        powerpress_render_list_section('pending', __('Pending', 'powerpress'), $groups['pending'], $render_app, false, __('No pending applications.', 'powerpress'));
        powerpress_render_list_section('completed', __('Completed', 'powerpress'), $groups['completed'], $render_app, true, __('No completed applications yet.', 'powerpress'));
    endif; ?>
</div>

<!-- ====================
      APPLICATION DIALOGS
     ==================== -->
<?php
if (!isset($props['danger'])) {
    $lbl_not_provided = __('(not provided)', 'powerpress');
    $all_apps = array_merge($groups['pending'], $groups['completed']);

    foreach ($all_apps as $app) {
        $showName = $app['show_name'] ?? $app['program_title'] ?? '';
        $appId    = $app['applicant_id'] ?? 0;
        $status   = (int)($app['app_status'] ?? 0);

        // detail field data
        $detailRows = [
            ['label' => __('Podcaster', 'powerpress'),    'value' => $app['podcaster_name'] ?? ''],
            ['label' => __('Feed URL', 'powerpress'),     'value' => $app['feed_url'] ?? $app['program_rssurl'] ?? '', 'link' => true],
            ['label' => __('Website', 'powerpress'),      'value' => $app['website_url'] ?? '',    'link' => true],
            ['label' => __('Show Listing', 'powerpress'), 'value' => $app['listing_url'] ?? '',    'link' => true, 'hr' => true],
            ['label' => __('Note', 'powerpress'),         'value' => stripslashes($app['application_note']) ?? '', 'style' => 'white-space: pre-line;', 'hr' => true],
        ];
        ?>
        <dialog id="ppn-app-<?php echo esc_attr($appId); ?>" class="ppn-dialog ppn-dialog--md">
        <button type="button" class="ppn-dialog__close" data-ppn-action="ppnDialogClose" aria-label="<?php esc_attr_e('Close', 'powerpress'); ?>"><i class="material-icons-outlined">close</i></button>
        <div class="container-fluid p-3">
            <div class="row"><div class="col-12"><h3><?php echo esc_html($showName); ?></h3></div></div>
            <?php foreach ($detailRows as $row): ?>
                <div class="row">
                    <div class="col-4"><strong><?php echo esc_html($row['label']); ?></strong></div>
                    <div class="col-8"<?php if (!empty($row['style']) && !empty($row['value'])) echo " style=\"" . esc_attr($row['style']) . "\""; ?>>
                        <?php if (empty($row['value'])): ?>
                            <span class="pp-text-muted" style="font-style: italic;"><?php echo esc_html($lbl_not_provided); ?></span>
                        <?php elseif (!empty($row['link'])): ?>
                            <a href="<?php echo esc_url($row['value']); ?>" target="_blank"><?php echo esc_url($row['value']); ?></a>
                        <?php else: ?>
                            <?php echo esc_html($row['value']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($row['hr'])): ?><hr><?php endif; ?>
            <?php endforeach; ?>
            <div class="row">
                <div class="col-4"><strong><?php esc_html_e('Decision', 'powerpress'); ?></strong></div>
                <div id="app-modal-status-<?php echo esc_attr($appId); ?>" class="col-8">
                    <select data-ppn-action="approveProgram" data-applicant-id="<?php echo esc_attr($appId); ?>" class="application-dropdown">
                        <option value="0"<?php selected($status, 0); ?>><?php esc_html_e('Pending', 'powerpress'); ?></option>
                        <option value="1"<?php selected($status, 1); ?>><?php esc_html_e('Approve', 'powerpress'); ?></option>
                        <option value="-1"<?php selected($status, -1); ?>><?php esc_html_e('Reject', 'powerpress'); ?></option>
                    </select>
                </div>
            </div>
            <?php if ($status !== 1): ?>
            <div class="row mt-2">
                <div class="col-4"></div>
                <div class="col-8">
                    <button type="button" class="ppn-icon-btn" style="display: inline-flex; align-items: center; gap: 4px;"
                            data-ppn-action="approveProgram" 
                            data-applicant-id="<?php echo esc_attr($appId); ?>" 
                            data-delete="true">
                        <i class="material-icons-outlined">delete</i>
                        <?php esc_html_e('Delete Application', 'powerpress'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        </dialog>
        <?php
    }
}
?>
