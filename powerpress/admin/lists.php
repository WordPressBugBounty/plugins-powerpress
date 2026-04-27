<!-- =============
      PAGE HEADER
     ============= -->

<div class="ppn-section-header">
    <h2 class="ppn-manage__section-title"><?php echo esc_html(get_option('powerpress_network_title')); ?></h2>
    <button data-ppn-action="ppnAction" data-form="manageForm" data-navigate="Create List" data-tab="groups" class="button"><?php esc_html_e('Create New Group', 'powerpress');?></button>
</div>
<h4 class="ppn-manage__section-desc"><?php esc_html_e('Manage the groups in your network by adding/removing podcasts.', 'powerpress');?></h4>

<!-- =============
      GROUPS LIST
     ============= -->

<div class="ppn-search">
    <input type="text" class="ppn-search__input" placeholder="<?php esc_attr_e('Filter groups...', 'powerpress'); ?>"
           data-ppn-action="filterList" data-ppn-target="ppn-groups-list">
    <i class="material-icons-outlined ppn-search__icon">search</i>
</div>
<div class="ppn-list" id="ppn-groups-list">
    <div class="ppn-list__header row">
        <div class="col-8"><?php esc_html_e('Group', 'powerpress');?></div>
        <div class="col-2"><?php esc_html_e('Group Page', 'powerpress');?></div>
        <div class="col-2"><?php esc_html_e('Manage', 'powerpress');?></div>
    </div>
    <?php
    $map = get_option('powerpress_network_map');
    if (empty($props))
        $props = [];

    // filter out entries w/o valid list_id (api status keys also filtered)
    $props = array_filter($props, function($item) {
        return is_array($item) && !empty($item['list_id']);
    });

    $render_group = function($list) use ($map) {
            $title       = $list['list_title'] ?? '';
            $listId      = $list['list_id'] ?? 0;
            $key         = "l-{$listId}";
            $link        = (isset($map[$key]) && get_post_status($map[$key]) === 'publish') ? get_permalink($map[$key]) : null;
            $pageMissing = isset($map[$key]) && !$link;
            ?>
            <div class="ppn-list__row row" data-title="<?php echo esc_attr(strtolower($title)); ?>">
                <div class="col-8">
                    <?php echo esc_html($title); ?>
                </div>
                <div class="col-2">
                    <span class="ppn-list__label"><?php esc_html_e('Page', 'powerpress'); ?></span>
                    <?php if ($pageMissing){ // linked page no longer published (draft/trash) ?>
                        <span class="ppn-page-status">
                            <button type="button"
                                    class="ppn-page-status--action"
                                    data-ppn-action="ppnPageAction"
                                    data-mode="list"
                                    data-target="List"
                                    data-id="<?php echo esc_attr($listId); ?>"
                                    data-title="<?php echo esc_attr($title); ?>">
                                        <?php esc_html_e('Create Page', 'powerpress');?>
                            </button>
                        </span>
                    <?php } else if ($link === null){ // no page ever created ?>
                        <span class="ppn-page-status">
                            <button type="button"
                                    class="ppn-page-status--action"
                                    data-ppn-action="ppnPageAction"
                                    data-mode="list"
                                    data-target="List"
                                    data-id="<?php echo esc_attr($listId); ?>"
                                    data-title="<?php echo esc_attr($title); ?>">
                                        <?php esc_html_e('Create Page', 'powerpress');?>
                            </button>
                        </span>
                    <?php } else { ?>
                        <span class="ppn-page-status"><a class="ppn-page-status--linked" target="_blank" href="<?php echo esc_url($link);?>"><?php esc_html_e('View Page', 'powerpress');?></a></span>
                    <?php } ?>
                </div>
                <div class="col-2">
                    <button type="button" class="ppn-icon-btn" title="<?php esc_html_e('Edit List', 'powerpress');?>" data-ppn-action="ppnAction" data-form="manageList" data-navigate="Manage List" data-tab="groups" data-set-field="listId" data-set-value="<?php echo esc_attr($listId);?>"><i class="material-icons-outlined">edit</i></button>
                    <button type="button" class="ppn-icon-btn" title="<?php esc_html_e('Delete List', 'powerpress');?>" data-ppn-action="ppnAction" data-form="manageList" data-tab="groups" data-change="true" data-confirm="<?php echo esc_attr(__('Are you sure you want to delete this list?', 'powerpress')); ?>" data-set-field="listId" data-set-value="<?php echo esc_attr($listId);?>" data-fields="requestAction:delete"><i class="material-icons-outlined">delete</i></button>
                </div>
            </div>
            <?php
        };

    powerpress_render_list_section('groups', false, $props, $render_group, false, __('No groups yet.', 'powerpress'));
    ?>
</div>

<!-- ==============
      HIDDEN FORMS
     ============== -->

<form id="manageForm" action="#/" method="POST" hidden> <!-- Make sure to keep back slash there for WordPress -->
</form>

<form id="manageList" action="#" method="POST" hidden> <!-- Make sure to keep back slash there for WordPress -->
    <input class="requestAction" name="requestAction">
    <input id="listId" name="listId" value="">
    <input id="linkPageList" name="linkPageList" value="">
</form>
