<?php
$Settings = get_option('powerpress_general');
if (!isset($Settings['pp_onboarding_incomplete'])) {
    powerpress_save_settings(array('pp_onboarding_incomplete' => 1, 'powerpress_general'));
}
$pp_nonce = powerpress_login_create_nonce();
?>
<div class="wrap">
    <div class="pp_container">
        <div class="onboarding_header">
            <div class="onboarding-logo-container">
                <img id="blubrry-logo-onboarding" src="<?php echo powerpress_get_root_url(); ?>images/PowerPress_white.svg" alt="" />
            </div>
            <div style="display: inline-block;">
                <h4 style="margin: 0;"><?php echo __('Welcome to PowerPress','powerpress'); ?></h4>
                <h5 style="margin: 0;"><?php echo __('Let\'s get started by connecting your podcast. ','powerpress'); ?> <a href="<?php echo esc_attr(add_query_arg( '_wpnonce', $pp_nonce, admin_url("admin.php?page=powerpressadmin_onboarding.php&step=blubrrySignin&from=new_post")));?>">Connect Blubrry Account</a></h5>
            </div>
        </div>
            <hr  class="pp_align-center" />
        <h4 style="margin: 0;" class="pp_align-center"><b><?php echo __('Have you started your podcast?','powerpress'); ?></b></h4>
        <br />
        <section id="one" class="pp_wrapper">
            <div class="pp_inner">

                <div class="pp_flex-grid">
                    <div class="pp_col" style="margin-left: 0;">
                        <div class="pp_box" style="padding-top: 1em; padding-bottom: 0;">
                            <div class="pp_image pp_fit center">
                                <img src="<?php echo powerpress_get_root_url(); ?>images/onboarding/no_start.png" alt="" class="" />
                            </div>
                            <div class="pp_content">
                                <div class="btn-caption-container pp_align-center" style="margin-top:3ch; height: 25%;">
                                    <p style="font-size: 110%;"><?php echo __('No I don\'t have a podcast.','powerpress'); ?></p>
                                </div>
                                <!--<footer class="pp_align-center">-->
                                    <div class="pp_button-container">
                                        <a href="<?php echo admin_url("admin.php?page=" . htmlspecialchars($_GET['page']) . "&step=showBasics"); ?>">
                                            <button type="button" class="pp_button"><span style="font-size: 90%;"><?php echo __('Create Podcast','powerpress'); ?></span></button>
                                        </a>
                                    </div>
                                <!--</footer>-->
                            </div>
                        </div>
                    </div>

                    <div class="pp_col" style="margin-right: 0;">
                        <div class="pp_box" style="padding-top: 1em; padding-bottom: 0;">
                            <div class="pp_image pp_fit center">
                                <img src="<?php echo powerpress_get_root_url(); ?>images/onboarding/yes_start.png" alt="" />
                            </div>
                            <div class="pp_content">
                                <div class="btn-caption-container pp_align-center" style="margin-top:3ch; height: 25%;">
                                    <p style="font-size: 110%;"><?php echo __('Yes, I have a podcast.','powerpress'); ?></p>
                                </div>
                                    <!--<footer class="pp_align-center">-->
                                    <div class="pp_button-container">
                                        <a href="<?php echo $_GET['page'] == 'powerpressadmin_basic' ? admin_url("admin.php?import=powerpress-rss-podcast&from=onboarding") : admin_url("admin.php?import=powerpress-rss-podcast&from=gs"); ?>">
                                            <button type="button" class="pp_button" style="background-color: white; border: 1px #1976d2 solid;"><span style="color: #1976d2; font-size: 90%;"><?php echo __('Import RSS Feed','powerpress'); ?></span></button>
                                        </a>
                                    </div>
                                <!--</footer>-->
                            </div>
                        </div>
                    </div>
            </div>
                    <div class="pp_button-container" style="float: right;margin-top: 1em;">
                        <a href="<?php echo admin_url("admin.php?page=powerpressadmin_basic&skip_onboarding=true"); ?>">
                            <?php echo __('Skip &rarr;','powerpress'); ?>
                        </a>
                    </div>
    </div>
</div>

