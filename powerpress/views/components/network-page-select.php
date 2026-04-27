<?php
/**
 * Network Page Select Modal
 *
 * Shared "link or create page" dialog used by network programs and lists tabs.
 *
 * @param array $args {
 *     @type string $form_id        Form element id (e.g. 'changeForm', 'pageForm')
 *     @type string $select_id      Page dropdown id (e.g. 'page-select-ppn')
 *     @type string $shortcode_id   Shortcode input id (e.g. 'ppn-program-shortcode')
 *     @type string $shortcode      Pre-filled shortcode value (empty if set via JS)
 *     @type string $target         PAGE_TYPES key: 'Program', 'List', etc.
 *     @type string $target_id      Entity id for hidden input (empty if set via JS)
 *     @type string $target_id_attr HTML id attr for targetId input (optional)
 *     @type string $create_id      data-id for create button (empty if set via JS)
 *     @type string $create_title   data-title for create button (empty if set via JS)
 *     @type int    $selected_page  Currently linked page ID (pre-selects dropdown)
 *     @type array  $pages          Array of WP_Post page objects for dropdown
 * }
 */
function powerpress_render_network_page_select($args) {
    $defaults = [
        'form_id'        => 'pageForm',
        'select_id'      => 'pageSelectDropdown',
        'shortcode_id'   => 'ppn-shortcode',
        'shortcode'      => '',
        'target'         => '',
        'target_id'      => '',
        'target_id_attr' => '',
        'create_id'      => '',
        'create_title'   => '',
        'selected_page'  => 0,
        'edit_url'       => '',
        'pages'          => [],
    ];
    $a = array_merge($defaults, $args);
    ?>
<!-- ====================
      SELECT PAGE DIALOG
     ==================== -->
<dialog id="selectPageBox" class="ppn-dialog ppn-dialog--md">
    <button type="button" class="ppn-dialog__close" data-ppn-action="ppnDialogClose" aria-label="<?php esc_attr_e('Close', 'powerpress'); ?>"><i class="material-icons-outlined">close</i></button>
    <form method="POST" id="<?php echo esc_attr($a['form_id']); ?>">
        <div class="flex-row mb-3">
            <p class="ppn-shortcode__label"><?php esc_html_e('Remember to put this shortcode on the page:', 'powerpress'); ?></p>
        </div>
        <div class="flex-row mb-3">
            <div class="ppn-shortcode">
                <input readonly id="<?php echo esc_attr($a['shortcode_id']); ?>" class="ppn-shortcode__input" value="<?php echo esc_attr($a['shortcode']); ?>">
                <button type="button" class="button button-small" data-ppn-action="ppCopyText" data-input-id="<?php echo esc_attr($a['shortcode_id']); ?>">Copy</button>
            </div>
        </div>

        <div class="flex-row mb-3">
            <p class="ppn-shortcode__label"><?php esc_html_e('Link an existing page or create a new one.', 'powerpress'); ?></p>
        </div>
        <div class="flex-row mb-3">
            <!-- PAGE SELECT -->
            <select id="<?php echo esc_attr($a['select_id']); ?>" class="dropdownChoice" name="pageID">
                <?php foreach ($a['pages'] as $page) : ?>
                    <option value="<?php echo esc_attr($page->ID); ?>"<?php if ((int)$page->ID === (int)$a['selected_page']) echo ' selected'; ?>><?php echo esc_html($page->post_title); ?></option>
                <?php endforeach; ?>
            </select>

            <!-- LINK BUTTON -->
            <button type="button" class="button" data-ppn-action="ppnPageAction" data-mode="link" data-form="<?php echo esc_attr($a['form_id']); ?>">
                <?php esc_html_e('Link Page', 'powerpress') ?>
            </button>

            <!-- EDIT PAGE -->
            <a id="ppn-edit-page-link" href="<?php echo esc_url($a['edit_url']); ?>" target="_blank" class="button" <?php if (!$a['edit_url']) echo 'style="display:none;"'; ?>>
                <?php esc_html_e('Edit Page', 'powerpress'); ?>
            </a>
        </div>
        <div class="flex-row mb-3">
            <label>
                <input type="checkbox" name="updateShortcode" value="1" checked>
                <?php esc_html_e('Update shortcode on page to match this link', 'powerpress'); ?>
            </label>
        </div>
        <input name="target" value="<?php echo esc_attr($a['target']); ?>" hidden>
        <input <?php if ($a['target_id_attr']) echo 'id="' . esc_attr($a['target_id_attr']) . '" '; ?>name="targetId" value="<?php echo esc_attr($a['target_id']); ?>" hidden>
    </form>
    <div class="flex-row">
        <div class="ppn-dialog__actions">
            <button id="ppn-create-page-btn" type="button" class="button" data-ppn-action="ppnPageAction" data-mode="dialog" data-target="<?php echo esc_attr($a['target']); ?>" data-id="<?php echo esc_attr($a['create_id']); ?>" data-title="<?php echo esc_attr($a['create_title']); ?>"><?php esc_html_e('Create New Page', 'powerpress'); ?></button>
            <button type="button" class="button" data-ppn-action="ppnDialogClose" style="margin-left: auto;"><?php esc_html_e('Cancel', 'powerpress'); ?></button>
        </div>
    </div>
</dialog>
    <?php
}
