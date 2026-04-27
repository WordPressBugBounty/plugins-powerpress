<?php
/**
 * Sidenav Components
 *
 * Renders the sidenav toggle, container, and links for settings tabs.
 * Use powerpress_render_sidenav_container() for all settings tabs.
 */

/**
 * Renders the sidenav toggle bar (hamburger + icons)
 *
 * @param string $tab_id    Tab identifier (welcome, feeds, website, advanced, etc.)
 * @param string $title     Tooltip title text
 * @param bool   $collapsed Whether to start collapsed (default: false)
 */
function powerpress_render_sidenav_toggle($tab_id, $title = 'More Options', $collapsed = false) {
    $id = esc_attr($tab_id) . '-toggle-sidenav';
    $title = esc_attr($title);
    $class = 'pp-sidenav__toggle' . ($collapsed ? ' pp-sidenav__toggle--collapsed' : '');

    // get default podcast feed url
    $feed_url = get_feed_link('podcast');

    // check for blubrry hosting connection
    $General = powerpress_get_settings('powerpress_general');
    $has_hosting = !empty($General['blubrry_program_keyword']);

    $hosting_url = powerpress_get_publish_url();
    ?>
    <div id="<?php echo $id; ?>" class="<?php echo $class; ?>" title="<?php echo $title; ?>" aria-label="<?php esc_attr_e('Toggle menu', 'powerpress'); ?>">
        <span class="pp-sidenav__toggle-menu" onclick="powerpress_displaySideNav(this.parentElement);">
            <span class="pp-sidenav__toggle-icon dashicons dashicons-menu"></span>
            <span class="pp-sidenav__toggle-text"><?php esc_html_e('More Options', 'powerpress'); ?></span>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </span>
        <span class="pp-sidenav__toggle-actions">
            <?php if ($has_hosting) : ?>
            <a class="pp-sidenav__toggle-hosting" href="<?php echo esc_url($hosting_url); ?>" target="_blank" title="<?php esc_attr_e('Blubrry Dashboard', 'powerpress'); ?>" onclick="event.stopPropagation();">
                <img src="<?php echo esc_url(powerpress_get_root_url() . 'images/settings_nav_icons/blubrry.svg'); ?>" alt="Blubrry" width="20" height="20">
            </a>
            <?php endif; ?>
            <a class="pp-sidenav__toggle-feed" href="<?php echo esc_url($feed_url); ?>" target="_blank" title="<?php esc_attr_e('View RSS Feed', 'powerpress'); ?>" onclick="event.stopPropagation();">
                <span class="dashicons dashicons-rss"></span>
            </a>
        </span>
    </div>
    <?php
}

/**
 * Renders the full sidenav container with toggle, panel, and standard links
 *
 * @param string   $tab_id         Tab identifier (welcome, feeds, website, etc.)
 * @param string   $title          Toggle tooltip text
 * @param array    $General        General settings array (for blubrry services)
 * @param callable $custom_content Optional callback to render custom sidenav content
 */
function powerpress_render_sidenav_container($tab_id, $title, $General, $custom_content = null) {
    ?>
    <div class="pp-sidenav-toggle-container">
        <?php powerpress_render_sidenav_toggle($tab_id, $title); ?>
        <div class="pp-sidenav">
            <?php
            // render custom content if provided (buttons, headings, etc.)
            if (is_callable($custom_content)) {
                $custom_content();
            }

            // blubrry services section
            powerpressadmin_edit_blubrry_services($General);

            // standard links
            powerpress_render_sidenav_links();
            ?>
        </div>
    </div>
    <?php
}

/**
 * Renders the standard sidenav footer links
 */
function powerpress_render_sidenav_links() {
    ?>
    <div class="pp-sidenav-extra" style="margin-top: 10%;"><a href="https://www.blubrry.com/support/" class="pp-sidenav-extra-text"><?php esc_html_e('POWERPRESS DOCUMENTATION', 'powerpress'); ?></a></div>
    <div class="pp-sidenav-extra"><a href="https://www.blubrry.com/podcast-insider/" class="pp-sidenav-extra-text"><?php esc_html_e('PODCAST INSIDER BLOG', 'powerpress'); ?></a></div>
    <div class="pp-sidenav-extra"><a href="https://blubrry.com/manual/" class="pp-sidenav-extra-text"><?php esc_html_e('PODCAST MANUAL', 'powerpress'); ?></a></div>
    <div class="pp-sidenav-extra"><a href="https://blubrry.com/services/" class="pp-sidenav-extra-text"><?php esc_html_e('BLUBRRY RESOURCES', 'powerpress'); ?></a></div>
    <div class="pp-sidenav-extra"><a href="https://blubrry.com/support/" class="pp-sidenav-extra-text"><?php esc_html_e('BLUBRRY SUPPORT', 'powerpress'); ?></a></div>
    <div class="pp-sidenav-extra"><a href="https://wordpress.org/support/plugin/powerpress/" class="pp-sidenav-extra-text"><?php esc_html_e('BLUBRRY POWERPRESS FORUM', 'powerpress'); ?></a></div>
    <?php
}
