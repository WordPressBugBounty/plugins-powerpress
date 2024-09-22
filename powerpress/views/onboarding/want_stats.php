<?php
$pp_nonce = powerpress_login_create_nonce();
$page = htmlspecialchars($_GET['page']);
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
    img.pp-stats {
        display: inline-block;
        height: 6em;
        margin-right: 1em;
        margin-top: -4px;
    }
    .iab-img-container {
        text-align: left;
    }
    .stats-screenshot {
        height: 30vh;
        margin-top: 5vh;
    }
</style>
<div class="wrap">
    <div class="pp_container">
        <img class="pp-stats" src="<?php echo powerpress_get_root_url(); ?>images/onboarding/blubrry_stats.png" style="margin-bottom: -1em;" />
        <div style="display: inline-block; width: 90%; float: right;">
            <h3><?php echo __('Podcast Statistics with Blubrry', 'powerpress'); ?></h3>
            <h5 style="margin-bottom: 0;">
                <span>
                <?php echo __('Why do you need Podcast Statistics?', 'powerpress'); ?>
                <a href="https://blubrry.com/manual/statistics-analytics-measuring-performance/">
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
                                <h3 style="text-align: left;"><?php echo __('Statistics Features', 'powerpress'); ?></h3>
                                <ul class="ul-disc">
                                    <li><?php echo __('60,000 podcasters trust Blubrry', 'powerpress'); ?></li>
                                    <li><?php echo __('Measure your audience', 'powerpress'); ?></li>
                                    <li><?php echo __('Geographic data', 'powerpress'); ?></li>
                                    <li><?php echo __('Apps and device comparison', 'powerpress'); ?></li>
                                    <li><?php echo __('Show and episode numbers', 'powerpress'); ?></li>
                                    <li><?php echo __('Date range analysis', 'powerpress'); ?></li>
                                    <li><?php echo __('IAB Certified', 'powerpress'); ?></li>
                                </ul>
                                <div style="text-align: left;">
                                    <a href="<?php echo esc_attr(add_query_arg( '_wpnonce', $pp_nonce, admin_url("admin.php?page={$page}&step=blubrrySignup&onboarding_type=stats"))); ?>">
                                        <button type="button" class="pp_button" style="padding-left: 4px; padding-right: 4px;">
                                            <span><?php echo __('Sign up for Stats', 'powerpress');?></span>
                                        </button>
                                    </a>
                                    <a style="margin-left: 3ch;font-size: 14px; color: #747474;" href="<?php echo admin_url("admin.php?page=powerpressadmin_basic"); ?>">
                                        <span><?php echo __('No Thanks', 'powerpress'); ?></span>
                                    </a>
                                </div>
                                <div style="text-align: left; margin-top: 1ch;">
                                    <p>
                                        <?php echo __('Already have Blubrry Statistics?', 'powerpress'); ?>
                                        <a href="<?php echo esc_attr(add_query_arg( '_wpnonce', $pp_nonce, admin_url("admin.php?page={$page}&step=blubrrySignin&onboarding_type=hosting"))); ?>">
                                            <?php echo __('Sign In', 'powerpress'); ?></a>
                                    </p>
                                </div>
                                <!--</div>-->
                            </div>
                        </div>
                    </div>
                    <div class="pp_col">
                        <div class="pp_box" style="border: none; padding-top: 2em; padding-left: 0;">
                            <div class="pp_fit center">
                                <img src="<?php echo powerpress_get_root_url(); ?>images/onboarding/stats_screenshot.jpg" alt="" class="stats-screenshot" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>