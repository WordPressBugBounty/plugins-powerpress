<?php
// ============
//  ACTIVE TAB
// ============
$tab = 'programs';
if (!empty($_GET['tab'])) {
    $tab = $_GET['tab'];
}
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

<!-- ===============
      TAB CONTAINER
     =============== -->
<div class="tabs-container">
    <div class="tab">
        <button class="<?php echo $tab == 'programs' ? 'tabActive' : 'tabInactive' ?>" id="programsTab" data-ppn-action="showPPNTab" data-tab="programs">Shows</button>
        <button class="<?php echo $tab == 'groups' ? 'tabActive' : 'tabInactive' ?>" id="groupsTab" data-ppn-action="showPPNTab" data-tab="groups">Groups</button>
        <button class="<?php echo $tab == 'requests' ? 'tabActive' : 'tabInactive' ?>" id="requestsTab" data-ppn-action="showPPNTab" data-tab="requests">Requests</button>
    </div>

    <div class="tabContent" style="<?php echo $tab == 'programs' ? 'display:block' : 'display:none' ?>" id="programs">
        <?php echo $shows_html; ?>
    </div>
    <div class="tabContent" style="<?php echo $tab == 'groups' ? 'display:block' : 'display:none' ?>" id="groups">
        <?php echo $groups_html; ?>
    </div>
    <div class="tabContent" style="<?php echo $tab == 'requests' ? 'display:block' : 'display:none' ?>" id="requests">
        <?php echo $requests_html; ?>
    </div>
</div>

