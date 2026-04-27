<?php
// ======
//  INIT
// ======

$networkID = get_option('powerpress_network_id');
$networkObj = $GLOBALS['ppn_object'];

$networkShowSearchResults = false;
$networkShowSearchValue = false;
$networkShowSearchCount = 0;
if(!empty($_POST) && isset($_POST['search'])){
    if($networkID){
        $requestUrl = '/2/powerpress/network/' . $networkID . '/programs/search/';
        $searchValue = htmlspecialchars($_POST['search']);
        $result = $networkObj->requestAPI($requestUrl, true, $_POST);

        // check result before access
        if (!empty($result['error'])) {
            powerpress_page_message_add_error(__('Error: ', 'powerpress') . $result['error']);
        } else if ($result) {
            $networkShowSearchResults = $result['programs'];
            $networkShowSearchValue = $searchValue;
            $networkShowSearchCount = $result['num_results'];
        }
    }
}

$accountShowsNotInNetwork = false;
$accountShowsNotInNetworkCount = 0;
if($networkID){
    // find internal shows not in network
    $requestUrl = '/2/powerpress/network/' . $networkID . '/not-in-network/';
    $result = $networkObj->requestAPI($requestUrl, true, $_POST);

    // check result before access
    if (!empty($result['error'])) {
        powerpress_page_message_add_error(__('Error: ', 'powerpress') . $result['error']);
    } else if ($result){
        $accountShowsNotInNetwork = $result['programs'];
        $accountShowsNotInNetworkCount = $result['num_results'];
    }

    // refresh shows in network
    $requestUrl = '/2/powerpress/network/' . $networkID . '/programs/';
    $props = $networkObj->requestAPI($requestUrl, true, $_POST);

    // check result before access
    if (!empty($props['error']) || !is_array($props)) {
        $props = [];
    }
}

if (empty($props))
    $props = [];

// pre-group by internal/external
$groups = ['internal' => [], 'external' => []];
$inNetworkIds = [];
foreach ($props as $program) {
    if (empty($program['program_id'])) continue; // skip entries w/o valid program_id

    $bucket = !empty($program['internal']) ? 'internal' : 'external';
    $groups[$bucket][] = $program;

    $inNetworkIds[$program['program_id']] = true;
}

// =================
//  HOMEPAGE BUTTON
// =================

// toggle between create and edit based on page existence
$homepageMap = get_option('powerpress_network_map', []);
$homepageExists = false;
$homepageId = null;
if (!empty($homepageMap['Homepage'])) {
    $hpId = $homepageMap['Homepage'];
    if (get_post_status($hpId) == 'publish') {
        $homepageExists = true;
        $homepageId = $hpId;
    }
}
?>

<!-- =============
      PAGE HEADER
     ============= -->

<div class="ppn-section-header">
    <h2 class="ppn-manage__section-title">
        <?php echo esc_html(get_option('powerpress_network_title')); ?>
        <span id="return-to-shows-list" style="display: <?php echo ($networkShowSearchResults ? 'inline-block' : 'none'); ?>;">
            | <a href="<?php echo esc_url(admin_url("admin.php?page=" . urlencode(powerpress_admin_get_page()))); ?>" style="color: #1976D2; font-size: 14px;"><?php esc_html_e('Return to shows list', 'powerpress'); ?></a>
        </span>
    </h2>
    <div class="d-flex gap-sm">
        <form method="POST" action="<?php echo esc_url(admin_url("admin.php?page=" . urlencode(powerpress_admin_get_page()))); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf(__('Are you sure you want to unlink %s?', 'powerpress'), get_option('powerpress_network_title'))); ?>')">
            <input type="hidden" name="ppn-action" value="unset-network-id" />
            <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>
            <button type="submit" class="button ppn-button-danger"><?php esc_html_e('Unlink Network', 'powerpress');?></button>
        </form>

        <!-- CREATE/EDIT HOMEPAGE BUTTON-->
        <?php if ($homepageExists): ?>
            <a id="ppn-homepage-btn" class="button" href="<?php echo esc_url(get_edit_post_link($homepageId)); ?>" target="_blank"
                    style="display: <?php echo ($networkShowSearchResults ? 'none' : ''); ?>;">
                <?php esc_html_e('Edit Home Page', 'powerpress'); ?>
            </a>
        <?php else: ?>
            <button id="ppn-homepage-btn" type="button" class="button"
                    style="display: <?php echo ($networkShowSearchResults ? 'none' : ''); ?>;"
                    data-ppn-action="ppnPageAction" data-mode="singleton"
                    data-target="Homepage"
                    data-title="<?php echo esc_attr(get_option('powerpress_network_title')); ?>"
                    data-edit-label="<?php esc_attr_e('Edit Home Page', 'powerpress'); ?>">
                <?php esc_html_e('Add Home Page', 'powerpress'); ?>
            </button>
        <?php endif; ?>

        <button id="display-network-search" type="button" class="button" onclick="displayNetworkSearch();"
                style="display: <?php echo ($networkShowSearchResults ? 'none' : 'block'); ?>;" >
            <?php esc_html_e('Add Shows to Network', 'powerpress-network');?>
        </button>
    </div>
</div>

<!-- ============
      SHOWS LIST
     ============ -->

<div id="network-shows-list" style="display: <?php echo ($networkShowSearchResults ? 'none' : 'block'); ?>;">
    <?php if(!empty($groups['internal']) || !empty($groups['external'])){ ?>
        <div class="ppn-search">
            <input type="text" class="ppn-search__input" placeholder="<?php esc_attr_e('Filter shows...', 'powerpress'); ?>"
                   data-ppn-action="filterList" data-ppn-target="ppn-programs-list">
            <i class="material-icons-outlined ppn-search__icon">search</i>
        </div>
    <?php } ?>

    <div class="ppn-list" id="ppn-programs-list">
        <div class="ppn-list__header row">
            <div class="col-8"><?php esc_html_e('Show', 'powerpress'); ?></div>
            <div class="col-2"><?php esc_html_e('Show Page', 'powerpress'); ?></div>
            <div class="col-2"><?php esc_html_e('Manage', 'powerpress'); ?></div>
        </div>
        <?php
        $option = get_option('powerpress_network_map');
        $list_props = $secondary_props;

        $render_program = function($program) use ($option) {
            $title      = $program['program_title'] ?? 'n/a';
            $programId  = $program['program_id'] ?? 0;
            $key        = "p-{$programId}";
            $link       = (isset($option[$key]) && get_post_status($option[$key]) === 'publish') ? get_permalink($option[$key]) : null;
            $pageMissing = isset($option[$key]) && !$link;
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
                                    data-target="Program"
                                    data-id="<?php echo esc_attr($programId); ?>"
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
                                    data-target="Program"
                                    data-id="<?php echo esc_attr($programId); ?>"
                                    data-title="<?php echo esc_attr($title); ?>">
                                        <?php esc_html_e('Create Page', 'powerpress');?>
                            </button>
                        </span>
                    <?php } else { ?>
                        <span class="ppn-page-status"><a class="ppn-page-status--linked" target="_blank" href="<?php echo esc_url($link);?>"><?php esc_html_e('View Page', 'powerpress');?></a></span>
                    <?php } ?>
                </div>
                <div class="col-2">
                    <?php if ($link !== null){ ?>
                        <button type="button" class="ppn-icon-btn" title="<?php esc_html_e('Change Page', 'powerpress');?>" data-ppn-action="editPageForProgram" data-program-id="<?php echo esc_attr($programId); ?>" data-program-title="<?php echo esc_attr($title); ?>" data-link-page="<?php echo esc_attr($option[$key]); ?>" data-ppn-dialog="selectPageBox"><i class="material-icons-outlined">edit</i></button>
                        <form method="POST" style="display:contents">
                            <input type="hidden" name="target" value="Program" />
                            <input type="hidden" name="targetId" value="<?php echo esc_attr($programId); ?>" />
                            <input type="hidden" name="redirectUrl" value="false" />
                            <input type="hidden" name="pageAction" value="unlink" />
                            <button type="button" class="ppn-icon-btn" title="<?php esc_html_e('Unlink Page', 'powerpress');?>" data-ppn-action="ppnAction" data-tab="programs" data-confirm="<?php echo esc_attr("Are you sure you want to unlink this page from {$title}?"); ?>"><i class="material-icons-outlined">link_off</i></button>
                        </form>
                    <?php } else { ?>
                        <button type="button" class="ppn-icon-btn" title="<?php esc_html_e('Link to Existing Page', 'powerpress');?>" data-ppn-action="editPageForProgram" data-program-id="<?php echo esc_attr($programId); ?>" data-program-title="<?php echo esc_attr($title); ?>" data-link-page="" data-ppn-dialog="selectPageBox"><i class="material-icons-outlined">edit</i></button>
                    <?php } ?>
                    <button type="button" class="ppn-icon-btn" title="<?php esc_html_e('Add to Group', 'powerpress');?>" data-ppn-action="addToGroup" data-program-id="<?php echo esc_attr($programId); ?>" data-ppn-dialog="addToGroup"><i class="material-icons-outlined">library_add</i></button>
                    <form method="POST" style="display:contents">
                        <input type="hidden" name="target" value="program" />
                        <input type="hidden" name="targetId" value="<?php echo esc_attr($programId); ?>" />
                        <input type="hidden" name="requestAction" value="delete" />
                        <input type="hidden" name="redirectUrl" value="false" />
                        <input type="hidden" name="changeOrCreate" value="true" />
                        <input type="hidden" name="pageAction" value="clearSiteCache" />
                        <input type="hidden" name="clearSiteCache" value="true" />
                        <button type="button" class="ppn-icon-btn" title="<?php esc_html_e('Delete Program', 'powerpress');?>" data-ppn-action="ppnAction" data-tab="programs" data-confirm="<?php echo esc_attr("Are you sure you want to remove '{$title}'?"); ?>"><i class="material-icons-outlined">delete</i></button>
                    </form>
                </div>
            </div>
            <?php
        };

        if (!empty($groups['internal']) || !empty($groups['external'])) {
            powerpress_render_list_section('internal', __('Internal Shows', 'powerpress'), $groups['internal'], $render_program);
            powerpress_render_list_section('external', __('External Shows', 'powerpress'), $groups['external'], $render_program, false, __('No external shows yet.', 'powerpress'));
        } else { ?>
            <div class="row justify-content-center mt-4">
                <h1 class="ppn-page-title"><?php esc_html_e('This Network is Empty', 'powerpress'); ?></h1>
            </div>

            <div class="row justify-content-center" style="margin-left: 20%; margin-right: 20%;">
                <p><?php esc_html_e('A podcast network is a collection of shows managed under one umbrella, with shared branding, purpose, and often a unified strategy. While many organizations are starting to dip their toes into podcasting with a single show, networks offer flexibility, growth, and audience segmentation. With PowerPress you can host multiple shows under one account. Centralized control makes it simple to oversee multiple podcasts from one dashboard.', 'powerpress'); ?></p>
            </div>

            <div class="row justify-content-center mb-4">
                <button id="display-network-search" type="button" class="button" onclick="displayNetworkSearch();">
                    <?php esc_html_e('Add Shows to Network', 'powerpress');?>
                </button>
            </div>
        <?php } ?>
    </div>

    <!-- form lives outside dialog so values persist -->
    <form method="POST" id="addForm">
        <input id="add-program-to-group" name="program" type="hidden" />
        <input id="group-for-program-add" name="list_id" type="hidden" />
        <input name="requestAction" value="add" type="hidden">
    </form>
</div>

<div id="network-search-for-shows" style="display: <?php echo ($networkShowSearchResults ? 'block' : 'none'); ?>;">
    <form id="network-show-search-form" method="POST" action="<?php echo esc_url(admin_url("admin.php?page=" . urlencode(powerpress_admin_get_page()))); ?>">
        <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>

        <input type="hidden" name="networkId" value="<?php echo esc_attr($networkID); ?>"/>
        <div class="ppn-network-search">
            <div class="ppn-search ppn-network-search__bar">
                <label for="network-show-search-input"></label>
                <input id="network-show-search-input" type="text" class="ppn-search__input" name="search" value="<?php echo esc_attr($networkShowSearchValue); ?>"
                       placeholder="<?php esc_attr_e('Search for shows...', 'powerpress'); ?>">
                <i id="network-show-search-submit" class="material-icons-outlined ppn-search__icon" style="cursor: pointer; pointer-events: auto;" onclick="submitNetworkShowSearch();">search</i>
            </div>
            <a href="<?php echo esc_url(admin_url("admin.php?page=" . urlencode(powerpress_admin_get_page()))); ?>" class="ppn-network-search__close">&times;</a>
        </div>
    </form>

    <!-- =================================
          SEARCH RESULTS / SUGGESTED SHOWS
         ================================= -->
    <div class="ppn-list p-4">
        <?php if(empty($networkShowSearchResults) && empty($accountShowsNotInNetwork)){ ?>
            <div class="ppn-empty-state">
                <i class="material-icons-outlined ppn-empty-state__icon">podcasts</i>
                <p class="ppn-empty-state__text"><?php esc_html_e('Search the Blubrry directory to find and add shows to your network.', 'powerpress'); ?></p>
            </div>
        <?php } ?>

        <?php if(!empty($networkShowSearchResults) && !empty($searchValue)){ ?>
            <h4 class="ppn-page-subtitle mb-3">SEARCH RESULTS: <?php echo esc_html($searchValue); ?></h4>

            <div class="row">
                <?php foreach($networkShowSearchResults as $show){
                    $title = htmlspecialchars($show['program_title']);
                    $name = htmlspecialchars($show['talent_name']);
                    $website = htmlspecialchars($show['program_htmlurl']);

                    $artwork = 'https://assets.blubrry.com/coverart/90/' . htmlspecialchars($show['program_itunes_image']);
                    $fallback = esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg');
                    ?>

                    <div class="col-12 col-lg-6 mb-3">
                        <div class="row align-items-center">
                            <div class="col-1">
                                <img data-ppn-action="addProgramToNetwork"
                                     data-program-id="<?php echo $show['program_id']; ?>"
                                     data-network-id="<?php echo $networkID; ?>"
                                     data-updated-src="<?php echo powerpress_get_root_url(); ?>images/circlecheck_blue.png"
                                     data-title="<?php echo esc_attr($show['program_title']); ?>"
                                     id="add-program-<?php echo $show['program_id']; ?>"
                                     class="circle-add-to-network"
                                     alt="Add to Network" src="<?php echo powerpress_get_root_url() . (isset($inNetworkIds[$show['program_id']]) ? 'images/circlecheck_blue.png' : 'images/circleplus.png'); ?>">
                            </div>

                            <div class="col-1 pr-0 pl-0">
                                <img class="results-coverart" src="<?php echo esc_url($artwork); ?>" onerror="this.onerror=null; this.src='<?php echo $fallback; ?>';" alt="Show Coverart">
                            </div>

                            <div class="col-10">
                                <div class="row pl-2">
                                    <h3 class="m-0"><?php echo $title; ?></h3>
                                </div>
                                <div class="row pl-2">
                                    <?php if($name){ ?>
                                        <h4 class="m-0"><?php echo $name; ?></h4>
                                    <?php } ?>
                                    <?php if($website){ ?>
                                        <a class="website-link" href="<?php echo $website; ?>" target="_blank"><?php echo $website; ?></a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>


        <?php if(!empty($accountShowsNotInNetwork)){ ?>
        <h4 class="ppn-page-subtitle mb-3 mt-3">SUGGESTED SHOWS</h4>

            <div class="row">
                <?php foreach($accountShowsNotInNetwork as $show){
                    $title = htmlspecialchars($show['program_title']);
                    $name = htmlspecialchars($show['talent_name']);
                    $website = htmlspecialchars($show['program_htmlurl']);

                    $artwork = 'https://assets.blubrry.com/coverart/90/' . htmlspecialchars($show['program_itunes_image']);
                    $fallback = esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg');
                    ?>

                    <div class="col-12 col-lg-6 mb-3">
                        <div class="row align-items-center">
                            <div class="col-1">
                                <img data-ppn-action="addProgramToNetwork"
                                     data-program-id="<?php echo $show['program_id']; ?>"
                                     data-network-id="<?php echo $networkID; ?>"
                                     data-updated-src="<?php echo powerpress_get_root_url(); ?>images/circlecheck_blue.png"
                                     data-title="<?php echo esc_attr($show['program_title']); ?>"
                                     id="add-program-<?php echo $show['program_id']; ?>"
                                     class="circle-add-to-network"
                                     alt="Add to Network" src="<?php echo powerpress_get_root_url() . (isset($inNetworkIds[$show['program_id']]) ? 'images/circlecheck_blue.png' : 'images/circleplus.png'); ?>">
                            </div>

                            <div class="col-1 pr-0 pl-0">
                                <img class="results-coverart" src="<?php echo esc_url($artwork); ?>" onerror="this.onerror=null; this.src='<?php echo $fallback; ?>';" alt="Show Coverart">
                            </div>

                            <div class="col-10">
                                <div class="row pl-2">
                                    <h3 class="m-0"><?php echo $title; ?></h3>
                                </div>
                                <div class="row pl-2">
                                    <?php if($name){ ?>
                                        <h4 class="m-0"><?php echo $name; ?></h4>
                                    <?php } ?>
                                    <?php if($website){ ?>
                                        <a class="website-link" href="<?php echo $website; ?>" target="_blank"><?php echo $website; ?></a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<!-- ====================
      ADD TO GROUP DIALOG
     ==================== -->

<dialog id="addToGroup" class="ppn-dialog ppn-dialog--sm">
    <button type="button" class="ppn-dialog__close" data-ppn-action="ppnDialogClose" aria-label="<?php esc_attr_e('Close', 'powerpress'); ?>"><i class="material-icons-outlined">close</i></button>
    <h4 class="ppn-manage__field-label" style="margin-top: 0;"><?php esc_html_e('Select a Group', 'powerpress'); ?></h4>
    <?php if (empty($list_props)) { ?>
        <p class="description"><?php esc_html_e('No groups yet. Create a group first.', 'powerpress'); ?></p>
    <?php } else { ?>
        <?php foreach ($list_props as $list) { ?>
            <div class="ppn-group-pick">
                <a href="" data-ppn-action="ppnAction" data-form="addForm" data-tab="programs" data-set-field="group-for-program-add" data-set-value="<?php echo esc_attr($list['list_id']); ?>" data-change="true"><?php echo esc_html($list['list_title']); ?></a>
            </div>
        <?php } ?>
    <?php } ?>
    <div class="ppn-dialog__actions">
        <button type="button" class="button" data-ppn-action="ppnDialogClose"><?php esc_html_e('Cancel', 'powerpress'); ?></button>
    </div>
</dialog>

<script>
    function displayNetworkSearch(){
        let networkShowsList = document.getElementById('network-shows-list');
        let networkSearchForShows = document.getElementById('network-search-for-shows');
        let addShowsToNetworkBtn = document.getElementById('display-network-search');
        let returnLink = document.getElementById('return-to-shows-list');
        let homepageBtn = document.getElementById('ppn-homepage-btn');

        if(networkShowsList.style.display !== 'none'){ // if shows list is displayed
            // hide shows list, and display network search
            networkShowsList.style.display = 'none';
            networkSearchForShows.style.display = 'block';
            addShowsToNetworkBtn.style.display = 'none';
            if (homepageBtn) homepageBtn.style.display = 'none';
            returnLink.style.display = 'inline-block';

        } else { // if network search is displayed
            // hide network search, and display shows list
            networkSearchForShows.style.display = 'none';
            networkShowsList.style.display = 'block';
            addShowsToNetworkBtn.style.display = 'block';
            if (homepageBtn) homepageBtn.style.display = '';
            returnLink.style.display = 'none';
        }
    }

    function submitNetworkShowSearch(){
        let searchForm = document.getElementById('network-show-search-form');
        let searchValue = document.getElementById('network-show-search-input');

        if(searchValue.value !== ''){
            searchForm.submit();
        }
    }
</script>

<!-- ====================
      PAGE SELECT DIALOG
     ==================== -->

<?php
$availablePages = get_pages();
powerpress_render_network_page_select([
    'form_id'        => 'changeForm',
    'select_id'      => 'page-select-ppn',
    'shortcode_id'   => 'ppn-program-shortcode',
    'target'         => 'Program',
    'target_id_attr' => 'select-page-target-id',
    'pages'          => $availablePages,
]);
?>
