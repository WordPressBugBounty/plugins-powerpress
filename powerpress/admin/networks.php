<?php
// ======
//  INIT
// ======

$backPage = urlencode(powerpress_admin_get_page());
?>

<!-- =============
      PAGE HEADER
     ============= -->

<div class="ppn-page-header">
    <div>
        <h1 class="ppn-page-title"><?php esc_html_e('Your Networks', 'powerpress');?></h1>
        <h4 class="ppn-page-subtitle"><?php esc_html_e('Choose a network you want to edit.', 'powerpress');?></h4>
    </div>
</div>

<!-- ===================
      NETWORK SELECTION
     =================== -->

<div class="tabs-container pl-3 pr-3">
<?php if (!empty($props)) { ?>
    <div class="ppn-list">
        <div class="ppn-list__header row">
            <div class="col-12"><?php esc_html_e('Network Title', 'powerpress');?></div>
        </div>
        <?php for ($i = 0; $i < count($props); ++$i) { ?>
            <div class="ppn-list__row row">
                <div class="col-12">
                    <form action="<?php echo admin_url("admin.php?page={$backPage}"); ?>" method="post">
                        <input type="hidden" name="networkId" value="<?php echo esc_attr($props[$i]['network_id']); ?>" />
                        <input type="hidden" name="ppn-action" value="set-network-id" />
                        <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>
                        <label><input type="radio" name="networkChoice" onclick="this.form.submit();"> <?php echo esc_html($props[$i]['network_title']); ?></label>
                    </form>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } else { ?>
    <div class="settingBox w-100">
        <form method="POST" action="<?php echo esc_url(admin_url("admin.php?page=" . urlencode(powerpress_admin_get_page()))); ?>">

            <input type="hidden" name="ppn-action" value="create-network"/>
            <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>

            <div class="row">
                <div class="col-12 col-lg-6">
                    <h2 style="margin-top: 0;"><?php esc_html_e('Building a Podcast Network', 'powerpress');?></h2>

                    <p>
                        A podcast network is a collection of shows managed under one umbrella, with shared branding, purpose, and often a unified strategy.
                        While many organizations are starting to dip their toes into podcasting with a single show, networks offer flexibility, growth, and audience segmentation.
                    </p>

                    <p>With PowerPress you can host multiple shows under one account. Centralized control makes it simple to oversee multiple podcasts from one dashboard.</p>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="row mr-4">
                        <label class="mt-4" for="network-title-input"><?php esc_html_e('Title of the Network', 'powerpress'); ?></label>
                        <input class="mb-4" required type="text" name="network_title" id="network-title-input">
                    </div>

                    <div class="row mr-4">
                        <label for="network-description-input"><?php esc_html_e('Description', 'powerpress'); ?></label>
                        <textarea required name="network_description" id="network-description-input" rows="5"></textarea>
                    </div>

                    <div class="row mt-3">
                        <button type="submit" class="button"><?php esc_html_e('Create Podcast Network', 'powerpress');?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
<?php } ?>

<!-- ============
      ACTION BAR
     ============ -->
<?php if(!empty($props)){ ?>
    <div class="flex-row d-flex justify-content-end gap-md p-3 align-items-center">
        <button type="submit" form="unlinkForm" class="button ppn-button-danger" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to unlink this account?', 'powerpress')); ?>')"><?php esc_html_e('Unlink Account', 'powerpress');?></button>
    </div>
<?php } ?>

<!-- hidden forms -->
<form method="POST" action="<?php echo esc_url("?page={$backPage}&status=Signin"); ?>" id="unlinkForm">
    <input type="hidden" name="unlinkAccount" value="1" />
    <?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>
</form>
</div>
