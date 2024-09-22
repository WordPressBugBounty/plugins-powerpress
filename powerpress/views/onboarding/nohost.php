<?php
$GeneralSettings = powerpress_get_settings('powerpress_general');
$creds = get_option('powerpress_creds');
$page = htmlspecialchars($_GET['page']);
if ((isset($GeneralSettings['blubrry_auth']) && $GeneralSettings['blubrry_auth'] != null) || $creds) {
    $next_page = admin_url("admin.php?page=powerpressadmin_basic");
} else {
    $next_page = admin_url("admin.php?page={$page}&step=wantStats");
}
if (isset($_GET['from']) && $_GET['from'] == 'import') {
    $querystring_import = "&from=import";
} else {
    $querystring_import = "";
}
$pp_nonce = powerpress_login_create_nonce();
?>
<style>
    li {
        font-size: 14px;
    }
    li::marker {
        color: #1976d2;
    }
    ul {
        text-align: left;
    }
    a {
        text-decoration: none;
        color:#1976d2;
    }
    img {
        vertical-align: text-bottom;
    }
</style>
<div class="wrap">
    <div class="pp_container">
        <img style="display: inline-block; height: 5em; margin-right: 1em;" src="<?php echo powerpress_get_root_url(); ?>images/onboarding/hosting_icon.png" />
        <div style="display: inline-block; width: 90%; float: right;">
            <h3><?php echo __('Host with Blubrry', 'powerpress'); ?></h3>
            <h5>
                <span>
                <?php echo __('Don’t know what a podcast host is?', 'powerpress'); ?>
                <a href="https://blubrry.com/manual/internet-media-hosting/">
                    <?php echo __('Learn more', 'powerpress'); ?></a>
                </span>
            </h5>
        </div>
        <hr  class="pp_align-center" />

        <section id="one" class="pp_wrapper" style="margin-top:25px;">
            <div class="pp_inner">

                <div class="pp_flex-grid">
                    <div class="pp_col" style="margin-top: -1px;">
                        <div class="pp_box pp_service-container" style="border: none;">
                            <div class="center" style="padding-left: 3em; padding-top: 1em;">
                                <img src="<?php echo powerpress_get_root_url(); ?>images/onboarding/blubrry_logo_blue.png" alt="" />
                            </div>
                            <div class="pp_content" style="padding-top: 3ch;">
                                <h3 style="text-align: left;"><?php echo __('Hosting Features', 'powerpress'); ?></h3>
                                <ul class="ul-disc">
                                    <li><?php echo __('Integrate with the PowerPress plugin', 'powerpress'); ?></li>
                                    <li><?php echo __('Upload audio directly in your episode', 'powerpress'); ?></li>
                                    <li><?php echo __('Publish your show directly on this website', 'powerpress'); ?></li>
                                    <li><?php echo __('Accessible world-class tech support', 'powerpress'); ?></li>
                                    <li><?php echo __('Standard Statistics included', 'powerpress'); ?></li>
                                    <li><?php echo __('Mobile-ready audio/video player', 'powerpress'); ?></li>
                                    <li><?php echo __('Free file migration', 'powerpress'); ?></li>
                                </ul>
                                <div style="text-align: left;">
                                    <a href="<?php echo esc_attr(add_query_arg( '_wpnonce', $pp_nonce, admin_url("admin.php?page={$page}&step=blubrrySignup&onboarding_type=hosting$querystring_import"))); ?>">
                                        <button type="button" class="pp_button"><span><?php echo __('Start Free Trial', 'powerpress'); ?></span></button>
                                    </a>
                                    <a style="margin-left: 3ch;font-size: 14px; color: #747474;" href="<?php echo $next_page; ?>">
                                        <span><?php echo __('No Thanks', 'powerpress'); ?></span>
                                    </a>
                                </div>
                                <div style="text-align: left; margin-top: 1ch;">
                                    <p>
                                        <?php echo __('Already have Blubrry Hosting?', 'powerpress'); ?>
                                        <a href="<?php echo esc_attr(add_query_arg( '_wpnonce', $pp_nonce, admin_url("admin.php?page={$page}&step=blubrrySignin&onboarding_type=hosting$querystring_import"))); ?>">
                                            <?php echo __('Sign In', 'powerpress'); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pp_col">
                        <div class="pp_box" style="border: none; padding-top: 2em;">
                            <div class="pp_fit center">
                                <img src="<?php echo powerpress_get_root_url(); ?>images/onboarding/hero_circle_1.webp" alt="" class="" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>