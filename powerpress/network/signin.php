<?php
$signinUrl = admin_url('admin.php?page=powerpressadmin_onboarding.php&step=blubrrySignin&from=network-plugin');
if (function_exists('powerpress_login_create_nonce')) {
    $signinUrl = add_query_arg('_wpnonce', powerpress_login_create_nonce(), $signinUrl);
}
$signupUrl = admin_url('admin.php?page=powerpressadmin_onboarding.php&step=wantStats');
?>
<div class="container">
    <h1 class="pageTitle"><?php esc_html_e('Podcast Network', 'powerpress'); ?></h1>
    <p>
        <?php esc_html_e('A Blubrry account is required to use this feature.', 'powerpress'); ?>
        <a href="<?php echo esc_url($signupUrl); ?>"><?php esc_html_e('Create a free account', 'powerpress'); ?></a>
        <?php esc_html_e('or link your existing account', 'powerpress'); ?>
        <a href="<?php echo esc_url($signinUrl); ?>"><?php esc_html_e('here', 'powerpress'); ?></a>.
    </p>
</div>
