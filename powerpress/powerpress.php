<?php
/*
Plugin Name: Blubrry PowerPress
Plugin URI: https://blubrry.com/services/powerpress-plugin/
Description: <a href="https://blubrry.com/services/powerpress-plugin/" target="_blank">Blubrry PowerPress</a> is the No. 1 Podcasting plugin for WordPress. Developed by podcasters for podcasters; features include Simple and Advanced modes, multiple audio/video player options, subscribe to podcast tools, podcast SEO features, and more! Fully supports Apple Podcasts (previously iTunes), Google Podcasts, Spotify, and Blubrry Podcasting directories, as well as all podcast applications and clients.
Version: 11.15.14
Author: Blubrry
Author URI: https://blubrry.com/
Requires at least: 3.6
Tested up to: 6.9
Text Domain: powerpress
Change Log:
	Please see readme.txt for detailed change log.

Contributors:
	Angelo Mandato, CIO Blubrry - Plugin founder, architect and lead developer
	See readme.txt for full list of contributors.

Credits:
	getID3(), License: GPL 2.0+ by James Heinrich <info [at] getid3.org> http://www.getid3.org
		Note: getid3.php analyze() function modified to prevent redundant filesize() function call.

Copyright 2008-2019 Blubrry (https://blubrry.com)

License: GPL (http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt)

	This project uses source that is GPL licensed.
*/

use Mpdf\Tag\A;

if( !function_exists('add_action') ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}

/**
 * Updated version of a function originally added by the WordPress.org Plugins Review team in response to an incident with versions 11.9.3 to 11.9.4 where users were auto-created
 * This resets passwords for these auto-created users. Query was updated for efficiency.
 */
function PowerPress_PRT_incidence_response_notice() {
    global $PowerPress_PRT_incidence_response_usernames;
    ?>
    <div class="notice notice-warning">
        <h3><?php esc_html_e( 'Action Required: Please verify user accounts', 'powerpress' ); ?></h3>
        <p><?php esc_html_e( 'On June 28th, an unauthorized update of PowerPress was released using a compromised account. 
        That version (11.9.3 to 11.9.4), contained malicious code that created users with administrative privileges. It was quickly replaced with a fixed version about an hour later. 
        As a security measure, the passwords of all such accounts were invalidated to prevent access. 
        ', 'powerpress' ); ?>
      <?php printf(
                esc_html__( 'To remove this message, please verify all admin users and remove users with login names %s.', 'powerpress' ),
                esc_html(implode(', ', $PowerPress_PRT_incidence_response_usernames))
            ); ?>

      <?php   if(function_exists('add_footer_script')){
       esc_html_e( 'In addition, a function called "add_footer_script" may have been modified/added to the functions.php file in your theme. This will have to be manually checked. Updating to a new version or re-installing your theme will also fix this issue.', 'powerpress' );
            } ?> </p>
        <p><?php esc_html_e( 'We would like to thank the community and the WordPress team for their help in getting this detected and fixed quickly.', 'powerpress' ); ?></p>
        <p><?php _e( 'Our support lines are open. If you need help or have any questions, please reach out via our <a href="https://blubrry.com/contact"> contact form. </a>', 'powerpress' ); ?></p>
    </div>
    <?php
}
function PowerPress_PRT_incidence_response() {
    global $PowerPress_PRT_incidence_response_usernames;
    $check_completed = get_option('powerpress_user_check_completed');
    if ($check_completed) {
        return;
    }
    // They tried to create those users.
    $affectedusernames = ['PluginAUTH', 'PluginGuest', 'Options'];

    $page = 1;
    $showWarning = false;

    do {
        $args = array (
            'role'          => 'administrator',
            'date_query'    => array(
                array(
                    'after'     => '2024-06-27 00:00:00',
                    'inclusive' => true,
                ),
            ),
            'number' => 5000,
            'paged' => $page
        );

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        if (!$users) {
            break;
        }
        foreach ($users as $user) {
            if (7 === strlen($user->user_login)) {
                $affectedusernames[] = $user->user_login;
            }
        }

        if (!empty($affectedusernames)) {
            foreach ($affectedusernames as $affectedusername) {
                $user = get_user_by('login', $affectedusername);
                if ($user) {
                    // Affected users had an email on the form <username>@example.com
                    if ($user->user_email === $affectedusername . '@example.com') {
                        // We set an invalid password hash to invalidate the user login.
                        $temphash = 'PRT_incidence_response_230624';
                        if ($user->user_pass !== $temphash) {
                            global $wpdb;
                            $wpdb->update(
                                $wpdb->users,
                                array(
                                    'user_pass' => $temphash,
                                    'user_activation_key' => '',
                                ),
                                array('ID' => $user->ID)
                            );
                            clean_user_cache($user);
                        }
                        $PowerPress_PRT_incidence_response_usernames[] = $user->user_login;
                        $showWarning = true;
                    }
                }
            }
        }
        $page++;
    } while (!empty($users));
    if($showWarning){
        add_action( 'admin_notices', 'PowerPress_PRT_incidence_response_notice' );
    } else {
        add_option('powerpress_user_check_completed', true);
    }
}
add_action('init', 'PowerPress_PRT_incidence_response');

// WP_PLUGIN_DIR (REMEMBER TO USE THIS DEFINE IF NEEDED)
define('POWERPRESS_VERSION', '11.15.14' );

// Translation support:
if ( !defined('POWERPRESS_ABSPATH') )
    define('POWERPRESS_ABSPATH', dirname(__FILE__) );



/////////////////////////////////////////////////////
// The following define options should be placed in your
// wp-config.php file so the setting is not disrupted when
// you upgrade the plugin.
/////////////////////////////////////////////////////

if( !defined('POWERPRESS_BLUBRRY_API_URL') )
    define('POWERPRESS_BLUBRRY_API_URL', 'http://api.blubrry.com/');

// Replace validator service with one that is more reliable here:
define('POWERPRESS_FEEDVALIDATOR_URL', 'https://castfeedvalidator.com/?url=');

// Display custom play image for quicktime media. Applies to on page player only.
//define('POWERPRESS_PLAY_IMAGE', 'http://www.blubrry.com/themes/blubrry/images/player/PlayerBadge150x50NoBorder.jpg');

if( !defined('POWERPRESS_CONTENT_ACTION_PRIORITY') )
    define('POWERPRESS_CONTENT_ACTION_PRIORITY', 10 );

// Added so administrators can customize what capability is needed for PowerPress
if( !defined('POWERPRESS_CAPABILITY_MANAGE_OPTIONS') )
    define('POWERPRESS_CAPABILITY_MANAGE_OPTIONS', 'manage_options');
if( !defined('POWERPRESS_CAPABILITY_EDIT_PAGES') )
    define('POWERPRESS_CAPABILITY_EDIT_PAGES', 'edit_pages');

// Define variables, advanced users could define these in their own wp-config.php so lets not try to re-define
if( !defined('POWERPRESS_LINK_SEPARATOR') )
    define('POWERPRESS_LINK_SEPARATOR', '|');
if( !defined('POWERPRESS_TEXT_SEPARATOR') )
    define('POWERPRESS_TEXT_SEPARATOR', ':');
if( !defined('POWERPRESS_PLAY_IMAGE') )
    define('POWERPRESS_PLAY_IMAGE', 'play_video_default.jpg');
if( !defined('PHP_EOL') )
    define('PHP_EOL', "\n"); // We need this variable defined for new lines.
if( defined('POWERPRESS_DEBUG') ) {
    if( !defined('PHP_EOL_WEB') ) {
        define('PHP_EOL_WEB', "\n"); // Helps with readability
    }
} else {
    if( !defined('PHP_EOL_WEB') ) {
        define('PHP_EOL_WEB', ''); // We don't necessarily need new lines for web output
    }
}

if( !defined('POWERPRESS_SUBSCRIBE') )
    define('POWERPRESS_SUBSCRIBE', true);
if(!defined('POWERPRESS_NEW_APPLE_CATEGORIES')) {
    define('POWERPRESS_NEW_APPLE_CATEGORIES', true);
}
// Set regular expression values for determining mobile devices
if( !defined('POWERPRESS_MOBILE_REGEX') )
    define('POWERPRESS_MOBILE_REGEX', 'iphone|ipod|ipad|aspen|android|blackberry|opera mini|webos|incognito|webmate|silk');

$powerpress_feed = NULL; // DO NOT CHANGE

function powerpress_content($content)
{
    global $post, $g_powerpress_excerpt_post_id;

    if( defined('PODPRESS_VERSION') || isset($GLOBALS['podcasting_player_id']) || isset($GLOBALS['podcast_channel_active']) || defined('PODCASTING_VERSION') )
        return $content;

    if( empty($post->ID) || !is_object($post) )
        return $content;

    if( defined('POWERPRESS_DO_ENCLOSE_FIX') )
        $content = preg_replace('/\<!--.*added by PowerPress.*-->/im', '', $content );

    if( is_feed() )
        return $content; // We don't want to do anything to the feed

    if( function_exists('post_password_required') )
    {
        if( post_password_required($post) )
            return $content;
    }

    // PowerPress settings:
    $GeneralSettings = get_option('powerpress_general', array());

    // No player or links to add to content...
    if( !empty($GeneralSettings['disable_appearance']) )
        return $content;

    // check for themes/plugins where we know we need to do this...
    if( empty($GeneralSettings['player_aggressive']) )
    {
        if( !empty($GLOBALS['fb_ver']) && version_compare($GLOBALS['fb_ver'], '1.0',  '<=')	) {
            $GeneralSettings['player_aggressive'] = 1;
        }
        if( defined('JETPACK__VERSION') && version_compare(JETPACK__VERSION, '2.0',  '>=')	) {
            $GeneralSettings['player_aggressive'] = 1; // Jet pack still doesn't behave with PowerPress the_content
        }
        if( defined('WPSEO_VERSION') ) {
            $GeneralSettings['player_aggressive'] = 4;
        }
    }

    if( !empty($GeneralSettings['player_aggressive']) )
    {
        if( $GeneralSettings['player_aggressive'] == 4 )
        {
            $in_http_head = powerpress_in_wp_head();
            if( $in_http_head === true )
                return $content;
        }
        else if( $GeneralSettings['player_aggressive'] == 2 ) // If we do not have theme issues then lets keep this logic clean. and only display playes after the wp_head only
        {
            if( empty($GLOBALS['powerpress_wp_head_completed']) )
                return $content;
        }
        else // method 1 or 3...
        {
            if( strstr($content, '<!--powerpress_player-->') !== false )
                return $content; // The players were already added to the content

            if( $GeneralSettings['player_aggressive'] != 3 && $g_powerpress_excerpt_post_id > 0 )
                $g_powerpress_excerpt_post_id = 0; // Hack, set this to zero so it always goes past...

            if( $GeneralSettings['player_aggressive'] == 3 )
                $GeneralSettings['player_aggressive'] = 1; // remainder of the system will function as normal
        }
    }

    // Problem: If the_excerpt is used instead of the_content, both the_exerpt and the_content will be called here.
    // Important to note, get_the_excerpt will be called before the_content is called, so we add a simple little hack
    if( current_filter() == 'get_the_excerpt' )
    {
        $g_powerpress_excerpt_post_id = $post->ID;
        return $content; // We don't want to do anything to this content yet...
    }
    else if( current_filter() == 'the_content' && $g_powerpress_excerpt_post_id == $post->ID )
    {
        return $content; // We don't want to do anything to this excerpt content in this call either...
    }
    else if( class_exists('custom_post_widget') && powerpress_in_custom_post_widget() )
    {
        return $content; // Custom Post Widget compatibility
    }


    if( !isset($GeneralSettings['custom_feeds']) )
        $GeneralSettings['custom_feeds'] = array('podcast'=>'Default Podcast Feed');
    if( empty($GeneralSettings['custom_feeds']['podcast']) )
        $GeneralSettings['custom_feeds']['podcast'] = 'Default Podcast Feed';

    // Re-order so the default podcast episode is the top most...
    $Temp = $GeneralSettings['custom_feeds'];
    $GeneralSettings['custom_feeds'] = array();
    $GeneralSettings['custom_feeds']['podcast'] = 'Default Podcast Feed';
    foreach( $Temp as $feed_slug=> $feed_title )
    {
        if( $feed_slug == 'podcast' )
            continue;
        $GeneralSettings['custom_feeds'][ $feed_slug ] = $feed_title;
    }

    // Handle post type feeds....
    if( !empty($GeneralSettings['posttype_podcasting']) )
    {
        $post_type = get_query_var('post_type');
        if ( is_array( $post_type ) ) {
            $post_type = reset( $post_type ); // get first element in array
        }

        // Get the feed slugs and titles for this post type
        $PostTypeSettingsArray = get_option('powerpress_posttype_'.$post_type, array());
        // Loop through this array of post type settings...
        if( !empty($PostTypeSettingsArray) )
        {
            switch($post_type)
            {
                case 'post':
                case 'page': {
                    // Do nothing!, we want the default podcast to appear in these post types
                }; break;
                default: {
                    if( !empty($post_type) && empty($PostTypeSettingsArray['podcast']) )
                        unset($GeneralSettings['custom_feeds']['podcast']); // special case, we do not want an accidental podcast episode to appear in a custom post type if the feature is enabled
                }; break;
            }

            if (is_array($PostTypeSettingsArray)) {
                foreach ($PostTypeSettingsArray as $feed_slug => $postTypeSettings) {
                    if (!empty($postTypeSettings['title']))
                        $GeneralSettings['custom_feeds'][$feed_slug] = $postTypeSettings['title'];
                    else
                        $GeneralSettings['custom_feeds'][$feed_slug] = $feed_slug;
                }
            }
        }
    }

    if( !isset($GeneralSettings['display_player']) )
        $GeneralSettings['display_player'] = 1;
    if( !isset($GeneralSettings['player_function']) )
        $GeneralSettings['player_function'] = 1;
    if( !isset($GeneralSettings['podcast_link']) )
        $GeneralSettings['podcast_link'] = 1;

    // The blog owner doesn't want anything displayed, so don't bother wasting anymore CPU cycles
    if( $GeneralSettings['display_player'] == 0 )
        return $content;

    if( current_filter() == 'the_excerpt' && empty($GeneralSettings['display_player_excerpt']) )
        return $content; // We didn't want to modify this since the user didn't enable it for excerpts

    if( !empty($GeneralSettings['hide_player_more']) && strstr($content, 'class="more-link"') )
        return $content; // We do not want to add players and links if the read-more class found

    // Figure out which players are alerady in the body of the page...
    $ExcludePlayers = array();
    if( isset($GeneralSettings['disable_player']) )
        $ExcludePlayers = $GeneralSettings['disable_player']; // automatically disable the players configured

    if( !empty($GeneralSettings['process_podpress']) && strstr($content, '[display_podcast]') )
        return $content;

    if( preg_match_all('/(.?)\[(powerpress)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)/s', $content, $matches) )
    {
        if( isset($matches[3]) && is_array($matches[3]) )
        {
            foreach ($matches[3] as $key => $recipientow) {
                $attributes = shortcode_parse_atts($recipientow);
                if (isset($attributes['url'])) {
                    // not a problem...
                } else if (isset($attributes['feed'])) {
                    // we want to exclude this feed from the links aera...
                    $ExcludePlayers[$attributes['feed']] = true;
                } else {
                    // we don't want to include any players below...
                    $ExcludePlayers = $GeneralSettings['custom_feeds'];
                }
            }
        }
    }

    $new_content = '';
    if ( is_array($GeneralSettings['custom_feeds']) ) {
        // LOOP HERE TO DISPLAY EACH MEDIA TYPE
        foreach ($GeneralSettings['custom_feeds'] as $feed_slug => $feed_title) {
            // Get the enclosure data
            $EpisodeData = powerpress_get_enclosure_data($post->ID, $feed_slug);

            if (!$EpisodeData && !empty($GeneralSettings['process_podpress']) && $feed_slug == 'podcast')
                $EpisodeData = powerpress_get_enclosure_data_podpress($post->ID);

            if (!$EpisodeData || !$EpisodeData['url'])
                continue;

            // Just in case, if there's no URL lets escape!
            if (!$EpisodeData['url'])
                continue;

            // If the player is not already inserted in the body of the post using the shortcode...
            //if( preg_match('/\[powerpress(.*)\]/is', $content) == 0 )
            if (!isset($ExcludePlayers[$feed_slug])) // If the player is not in our exclude list because it's already in the post body somewhere...
            {
                if (isset($GeneralSettings['premium_caps']) && $GeneralSettings['premium_caps'] && !powerpress_premium_content_authorized($feed_slug)) {
                    $new_content .= powerpress_premium_content_message($post->ID, $feed_slug, $EpisodeData);
                } else {
                    if ($GeneralSettings['player_function'] != 3 && $GeneralSettings['player_function'] != 0) // Play in new window only or disabled
                    {
                        do_action('wp_powerpress_player_scripts');
                        $AddDefaultPlayer = empty($EpisodeData['no_player']);

                        if ($EpisodeData && !empty($EpisodeData['embed'])) {
                            $new_content .= SanitizeEmbed(trim($EpisodeData['embed']));
                            if (!empty($GeneralSettings['embed_replace_player']))
                                $AddDefaultPlayer = false;
                        }

                        if ($AddDefaultPlayer) {
                            $image = '';
                            if (isset($EpisodeData['image']) && $EpisodeData['image'] != '')
                                $image = $EpisodeData['image'];

                            $new_content .= apply_filters('powerpress_player', '', powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData);
                        }
                    }

                    if (!isset($EpisodeData['no_links'])) {
                        do_action('wp_powerpress_player_scripts');
                        $new_content .= apply_filters('powerpress_player_links', '', powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData);
                        $new_content .= apply_filters('powerpress_player_subscribe_links', '', powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData);
                    }
                }
            }
        }
    }

    if( $new_content == '' )
        return $content;

    switch( $GeneralSettings['display_player'] )
    {
        case 1: { // Below posts
            return $content.$new_content.( !empty($GeneralSettings['player_aggressive']) && $GeneralSettings['player_aggressive'] == 1 ?'<!--powerpress_player-->':'');
        }; break;
        case 2: { // Above posts
            return ( !empty($GeneralSettings['player_aggressive']) && $GeneralSettings['player_aggressive'] == 1 ?'<!--powerpress_player-->':'').$new_content.$content;
        }; break;
    }
    return $content;
}//end function


add_filter('get_the_excerpt', 'powerpress_content', (POWERPRESS_CONTENT_ACTION_PRIORITY - 1) );
add_filter('the_content', 'powerpress_content', POWERPRESS_CONTENT_ACTION_PRIORITY);
if( !defined('POWERPRESS_NO_THE_EXCERPT') )
    add_filter('the_excerpt', 'powerpress_content', POWERPRESS_CONTENT_ACTION_PRIORITY);

/* Specail case fix Yoast bug which messes up the HTML */
function powerpress_yoast_gawp_fix($content)
{
    $content= preg_replace(
        array('/return powerpress\_pinw\(\"/', '/return powerpress\_embed\_winplayer\(\"/', '/return powerpress\_show\_embed\(\"/', '/return powerpress\_embed\_html5v\(\"/', '/return powerpress\_embed\_html5a\(\"/',  ),
        array('return powerpress_pinw(\'', 'return powerpress_embed_winplayer(\'', 'return powerpress_show_embed(\'', 'return powerpress_embed_html5v(\'', 'return powerpress_embed_html5a(\'' ),
        $content);

    return $content;
}

function powerpress_header()
{
    // PowerPress settings:
    $Powerpress = get_option('powerpress_general', array());
    if( !isset($Powerpress['custom_feeds']) )
        $Powerpress['custom_feeds'] = array('podcast'=>'Default Podcast Feed');

    if( empty($Powerpress['disable_appearance']) || $Powerpress['disable_appearance'] == false )
    {
        if( !isset($Powerpress['player_function']) || $Powerpress['player_function'] > 0 ) // Don't include the player in the header if it is not needed...
        {
            $PowerpressPluginURL = powerpress_get_root_url();
            ?>
            <script type="text/javascript"><!--
                <?php
                $new_window_width = 420;
                $new_window_height = 240;

                if( isset($Powerpress['new_window_width']) && $Powerpress['new_window_width'] > 0 )
                    $new_window_width = $Powerpress['new_window_width'];
                else if( isset($Powerpress['new_window_width']) )
                    $new_window_width = 420;

                if( isset($Powerpress['new_window_height']) && $Powerpress['new_window_height'] > 0 )
                    $new_window_height = $Powerpress['new_window_height'];
                else if( isset($Powerpress['new_window_height']) )
                    $new_window_height = 240;

                if( empty($Powerpress['new_window_nofactor']) )
                {
                    $new_window_width  += 40;
                    $new_window_height += 80;
                }

                ?>
                function powerpress_pinw(pinw_url){window.open(pinw_url, 'PowerPressPlayer','toolbar=0,status=0,resizable=1,width=<?php echo ($new_window_width); ?>,height=<?php echo ($new_window_height); ?>');	return false;}
                //-->

                // tabnab protection
                window.addEventListener('load', function () {
                    // make all links have rel="noopener noreferrer"
                    document.querySelectorAll('a[target="_blank"]').forEach(link => {
                        link.setAttribute('rel', 'noopener noreferrer');
                    });
                });
            </script>
            <?php
        }
    }

    if( !empty($Powerpress['feed_links']) )
    {
        if( is_home() ) {
            $feed_slug = 'podcast';
            $href = get_feed_link($feed_slug);
            // Podcast default and channel feed settings
            $Settings = get_option('powerpress_feed_'. $feed_slug, array());

            if( empty($Settings) && $feed_slug == 'podcast' )
                $Settings = get_option('powerpress_feed', array()); // Get the main feed settings

            if( empty($Settings['title']) )
                $Settings['title'] = get_bloginfo_rss('name'); // Get blog title

            // Get the default podcast feed...
            echo '<link rel="alternate" type="' . feed_content_type() . '" title="' . esc_attr( $Settings['title'] ) . '" href="' . esc_url( $href ) . '" />' . "\n";
        } else if( is_category() ) {

            $category_id = get_query_var('cat');
            if( $category_id ) {
                $Settings = get_option('powerpress_cat_feed_'.$category_id, array() );
                if( empty($Settings['title']) ) {
                    $Settings['title'] = get_cat_name( $category_id ); // Get category title
                    $Settings['title'] .= ' '. apply_filters( 'document_title_separator', '-' ) .' ';
                    $Settings['title'] .= get_bloginfo_rss('name');
                }
                if( empty($Settings['title']) )	{
                    $Settings['title'] = get_bloginfo_rss('name'); // Get blog title, best we can do
                }

                if( !empty($Settings['feed_redirect_url']) )
                    $Settings['feed_url'] = $Settings['feed_redirect_url'];
                else if( !empty($Powerpress['cat_casting_podcast_feeds']) )
                    $Settings['feed_url'] = get_category_feed_link($category_id, 'podcast');
                else
                    $Settings['feed_url'] = get_category_feed_link( $category_id ); // Get category feed URL

                // Get the category podcast feed...
                echo '<link rel="alternate" type="' . feed_content_type() . '" title="' . esc_attr( $Settings['title'] ) . '" href="' . esc_url( $Settings['feed_url'] ) . '" />' . "\n";
            }
        }
    }
}

add_action('wp_head', 'powerpress_header');

function powerpress_wp_head_completed()
{
    $GLOBALS['powerpress_wp_head_completed'] = true;
}

add_action('wp_head', 'powerpress_wp_head_completed', 100000);

function powerpress_exit_on_http_head($recipienteturn)
{
    if( is_feed() )
    {
        // Set the content type for HTTP headers...
        header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);

        // Needs authentication?
        $GeneralSettings = get_option('powerpress_general', array());
        if( !empty($GeneralSettings['premium_caps']) )
        {
            $feed_slug = get_query_var('feed');
            $FeedSettings = get_option('powerpress_feed_'.$feed_slug, array());
            if( !empty($FeedSettings['premium']) )
            {
                return false; // Let the logic further into PowerPress authenticate this HEAD request
            }
        }
    }
    return $recipienteturn;
}

add_filter('exit_on_http_head', 'powerpress_exit_on_http_head' );

function powerpress_rss2_ns()
{
    if( !powerpress_is_podcast_feed() )
        return;

    // Okay, lets add the namespace
    echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"'.PHP_EOL;

    // Add the Podcast Index namespace
    echo 'xmlns:podcast="https://podcastindex.org/namespace/1.0"'.PHP_EOL;

    if( !defined('POWERPRESS_RAWVOICE_RSS') || POWERPRESS_RAWVOICE_RSS != false )
    {
        echo 'xmlns:rawvoice="https://blubrry.com/developer/rawvoice-rss/"'.PHP_EOL;
    }
}

function SanitizeEmbed($html) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    // force UTF-8 encoding
    $html_encoded = '<?xml encoding="UTF-8"><div>' . $html . '</div>';
    $dom->loadHTML($html_encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $allowed_tags = ['iframe', 'div'];
    $allowed_attrs = ['src', 'width', 'height', 'frameborder', 'allow', 'sandbox', 'referrerpolicy', 'loading', 'allowfullscreen', 'title', 'scrolling', 'alt'];

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//*');

    for ($i = $nodes->length - 1; $i >= 0; $i--) {
        $node = $nodes->item($i);
        // remove unauthorized tags
        if (!in_array($node->nodeName, $allowed_tags)) {
            $node->parentNode->removeChild($node);
            continue;
        }

        // clean attributes
        if ($node->hasAttributes()) {
            $attrsToRemove = [];
            foreach ($node->attributes as $attr) {
                // remove unauthorized attributes
                if (!in_array($attr->name, $allowed_attrs)) {
                    $attrsToRemove[] = $attr->name;
                    continue;
                }

                // remove any attributes with xss
                $bad_schemes = [
                    'javascript:',
                    'vbscript:',
                    'data:',
                    'file:',
                    'mhtml:'
                ];
                foreach ($bad_schemes as $scheme) {
                    if (stripos($attr->value, $scheme) !== false) {
                        $attrsToRemove[] = $attr->name;
                        continue 2;
                    }
                }
            }
            // Remove the bad attributes we found
            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }
    }

    // remove the wrapper
    $container = $dom->getElementsByTagName('div')->item(0);
    $output = '';
    if ($container) {
        foreach ($container->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }
    }
    return $output;
}


function powerpress_check_for_chartable()
{
    $found_chartable = false;
    $General = get_option('powerpress_general');
    if (!empty($General['redirect1'])) {
        if (is_chartable_url($General['redirect1'])) {
            update_option('powerpress_chartable_check', 'has_chartable');
            $found_chartable = true;
        }
    }
    if (!empty($General['redirect2'])) {
        if (is_chartable_url($General['redirect2'])) {
            update_option('powerpress_chartable_check', 'has_chartable');
            $found_chartable = true;
        }
    }
    if (!empty($General['redirect3'])) {
        if (is_chartable_url($General['redirect3'])) {
            update_option('powerpress_chartable_check', 'has_chartable');
            $found_chartable = true;
        }
    }

    // if we haven't found chartable in the saved redirects, check all media just to be safe
    if (!$found_chartable) {
        global $wpdb;
        $query = "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE \"%enclosure\"";
        $recipientesults_data = $wpdb->get_results($query, ARRAY_A);
        foreach ($recipientesults_data as $idx => $data) {
            $meta_parts = explode("\n", $data['meta_value']);
            $post_enclosure_url = $meta_parts[0];
            if (strpos($post_enclosure_url, 'chrt.fm') !== false || strpos($post_enclosure_url, 'chtbl.com') !== false) {
                update_option('powerpress_chartable_check', 'has_chartable');
                $found_chartable = true;
            }
        }
    }

    if (!$found_chartable) {
        update_option('powerpress_chartable_check', 'no_chartable');
    }
}
add_action('powerpress_check_for_chartable_hook', 'powerpress_check_for_chartable');

if (!function_exists('buildRedirect')) {
    function buildRedirect($recipientedirects)
    {
        $recipientedirect_result = '';
        for ($x = 3; $x >= 0; $x--) {
            $key = sprintf('redirect%d', $x);
            if (!empty($recipientedirects[$key])) {
                if (preg_match('/^https?:\/\/(.*)$/', trim($recipientedirects[$key]), $matches) == 0)
                    continue;

                if (is_chartable_url($recipientedirects[$key])) {
                    continue;
                }

                $recipientedirectClean = $matches[1];
                if (substr($recipientedirectClean, -1, 1) != '/') // Rediercts need to end with a slash /.
                    $recipientedirectClean .= '/';

                if (!empty($recipientedirectClean)) {
                    if (strpos($recipientedirectClean, '/') == 0) // Not a valid redirect URL
                        continue;

                    if (!strstr($recipientedirect_result, $recipientedirectClean)) // If the redirect is not already added...
                        $recipientedirect_result = $recipientedirectClean . $recipientedirect_result;
                }
            }
        }
        return 'https://' . $recipientedirect_result;
    }
}

if (!function_exists('powerpress_getAccessToken')) {
    function powerpress_getAccessToken()
    {
        // Look at the creds and use the latest access token, if its not the latest refresh it...
        $creds = get_option('powerpress_creds', array());
        if (!empty($creds['access_token']) && !empty($creds['access_expires']) && $creds['access_expires'] > time()) { // If access token did not expire
            return $creds['access_token'];
        }

        if (!empty($creds['refresh_token']) && !empty($creds['client_id']) && !empty($creds['client_secret'])) {

            // Create new access token with refresh token here...
            require_once('powerpressadmin-auth.class.php');
            $auth = new PowerPressAuth();
            $recipientesultTokens = $auth->getAccessTokenFromRefreshToken($creds['refresh_token'], $creds['client_id'], $creds['client_secret']);

            if (!empty($recipientesultTokens['access_token']) && !empty($recipientesultTokens['expires_in'])) {
                powerpress_save_settings(
                    array(
                        'access_token' => $recipientesultTokens['access_token'], 
                        'access_expires' => (time() + $recipientesultTokens['expires_in'] - 10)
                    ), 
                    'powerpress_creds'
                );

                return $recipientesultTokens['access_token'];
            } else {
                //if their refresh token is expired, sign them out so they can re-authenticate
                delete_option('powerpress_creds');
                powerpress_page_message_add_error(__('Your account has been logged out due to inactivity with Blubrry services.', 'powerpress'));
                powerpress_page_message_print();
            }
        }

        // If we failed to get credentials, return false
        return false;
    }
}

if (!function_exists('powerpress_save_settings')) {
    function powerpress_save_settings($SettingsNew = false, $field = 'powerpress_general')
    {
        if ($field == 'powerpress_taxonomy_podcasting' || $field == 'powerpress_itunes_featured') { // No merging settings for these fields...
            update_option($field, $SettingsNew);
            return;
        }
        // Save general settings
        if ($SettingsNew) {
            $Settings = get_option($field);
            if (!is_array($Settings))
                $Settings = [];
            foreach ($SettingsNew as $key => $value) {
                $Settings[$key] = $value;
            }
            if ($field == 'powerpress_general' && !isset($Settings['timestamp']))
                $Settings['timestamp'] = time();

            // Special case fields, if they are empty, we can delete them., this will keep the Settings array uncluttered
            if (isset($Settings['feed_links']) && $Settings['feed_links'] == 0) // If set to default value, no need to save it in the database
                unset($Settings['feed_links']);
            if (isset($Settings['suppress_unused_item_tags']) && $Settings['suppress_unused_item_tags'] == 0) // If set to default value, no need to save it in the database
                unset($Settings['suppress_unused_item_tags']);

            // We can unset settings that are set to their defaults to save database size...
            if ($field == 'powerpress_general') {
                if (isset($SettingsNew['new_episode_box_flag'])) {
                    /* Switch the settings over to the actual field name (to fix FCGI mode problem with older versions of PHP.
                    if (isset($SettingsNew['ebititle'])) {
                        if ($SettingsNew['ebititle'] == 'false') {
                            $Settings['new_episode_box_itunes_title'] = 2;
                        } else {
                            $Settings['new_episode_box_itunes_title'] = 1;
                            $SettingsNew['new_episode_box_itunes_title'] = 1;
                        }
                        unset($Settings['ebititle']);
                    }

                    if (isset($SettingsNew['ebinst'])) {
                        if ($SettingsNew['ebinst'] == 'false') {
                            $Settings['new_episode_box_itunes_nst'] = 2;
                        } else {
                            $Settings['new_episode_box_itunes_nst'] = 1;
                            $SettingsNew['new_episode_box_itunes_nst'] = 1;
                        }
                        unset($Settings['ebinst']);
                    }*/

                    if (!isset($SettingsNew['new_episode_box_embed']))
                        $Settings['new_episode_box_embed'] = 2;
                    if (!isset($SettingsNew['new_embed_replace_player']))
                        $Settings['new_embed_replace_player'] = 2;
                    if (!isset($SettingsNew['new_episode_box_no_player']))
                        $Settings['new_episode_box_no_player'] = 2;
                    if (!isset($SettingsNew['new_episode_box_no_links']))
                        $Settings['new_episode_box_no_links'] = 2;
                    if (!isset($SettingsNew['new_episode_box_no_player_and_links']))
                        $Settings['new_episode_box_no_player_and_links'] = 2;
                    if (!isset($SettingsNew['new_episode_box_cover_image']))
                        $Settings['new_episode_box_cover_image'] = 2;
                    if (!isset($SettingsNew['new_episode_box_player_size']))
                        $Settings['new_episode_box_player_size'] = 2;
                    if (!isset($SettingsNew['new_episode_box_subtitle']))
                        $Settings['new_episode_box_subtitle'] = 2;
                    if (!isset($SettingsNew['new_episode_box_summary']))
                        $Settings['new_episode_box_summary'] = 2;
                    if (!isset($SettingsNew['new_episode_box_author']))
                        $Settings['new_episode_box_author'] = 2;
                    if (!isset($SettingsNew['new_episode_box_explicit']))
                        $Settings['new_episode_box_explicit'] = 2;
                    if (!isset($SettingsNew['new_episode_box_pci']))
                        $Settings['new_episode_box_pci'] = 2;
                    if (!isset($SettingsNew['new_episode_box_block']))
                        $Settings['new_episode_box_block'] = 2;
                    if (!isset($SettingsNew['new_episode_box_itunes_image']))
                        $Settings['new_episode_box_itunes_image'] = 2;
                    if (!isset($SettingsNew['new_episode_box_order']))
                        $Settings['new_episode_box_order'] = 2;
                    if (!isset($SettingsNew['new_episode_box_itunes_title']))
                        $Settings['new_episode_box_itunes_title'] = 2;
                    if (!isset($SettingsNew['new_episode_box_itunes_nst']))
                        $Settings['new_episode_box_itunes_nst'] = 2;
                    if (!isset($SettingsNew['new_episode_box_gp_explicit']))
                        $Settings['new_episode_box_gp_explicit'] = 2;
                    if (!isset($SettingsNew['new_episode_box_feature_in_itunes']))
                        $Settings['new_episode_box_feature_in_itunes'] = 2;
                } elseif (isset($SettingsNew['pp-gen-settings-tabs'])) {
                    if (!isset($SettingsNew['skip_to_episode_settings']) || empty($SettingsNew['skip_to_episode_settings']))
                        unset($Settings['skip_to_episode_settings']);
                    if (!isset($SettingsNew['display_player_excerpt']) || empty($SettingsNew['display_player_excerpt']))
                        unset($Settings['display_player_excerpt']);
                    if (!isset($SettingsNew['hide_player_more']) || empty($SettingsNew['hide_player_more']))
                        unset($Settings['hide_player_more']);
                    if (!isset($SettingsNew['podcast_embed']) || empty($SettingsNew['podcast_embed']))
                        unset($Settings['podcast_embed']);
                    if (!isset($SettingsNew['subscribe_links']) || empty($SettingsNew['subscribe_links']))
                        unset($Settings['subscribe_links']);
                    if (!isset($SettingsNew['new_window_no_factor']) || empty($SettingsNew['new_window_no_factor']))
                        unset($Settings['new_window_no_factor']);
                } elseif (isset($SettingsNew['powerpress_bplayer_settings'])) {
                    unset($Settings['powerpress_bplayer_settings']);
                    if (!isset($SettingsNew['new_episode_box_itunes_image']) || empty($SettingsNew['new_episode_box_itunes_image']))
                        $Settings['new_episode_box_itunes_image'] = 2;
                    if (isset($SettingsNew['bp_episode_image']) && empty($SettingsNew['bp_episode_image']))
                        unset($Settings['bp_episode_image']);
                }


                if (isset($Settings['videojs_css_class']) && empty($Settings['videojs_css_class']))
                    unset($Settings['videojs_css_class']);
                if (isset($Settings['cat_casting']) && empty($Settings['cat_casting']))
                    unset($Settings['cat_casting']);
                if (isset($Settings['posttype_podcasting']) && empty($Settings['posttype_podcasting']))
                    unset($Settings['posttype_podcasting']);
                if (isset($Settings['taxonomy_podcasting']) && empty($Settings['taxonomy_podcasting']))
                    unset($Settings['taxonomy_podcasting']);
                if (isset($Settings['playlist_player']) && empty($Settings['playlist_player']))
                    unset($Settings['playlist_player']);
                if (isset($Settings['seo_feed_title']) && empty($Settings['seo_feed_title']))
                    unset($Settings['seo_feed_title']);
                if (isset($Settings['subscribe_feature_email']) && empty($Settings['subscribe_feature_email']))
                    unset($Settings['subscribe_feature_email']);
                if (isset($Settings['poster_image_video']) && empty($Settings['poster_image_video']))
                    unset($Settings['poster_image_video']);
                if (isset($Settings['poster_image_audio']) && empty($Settings['poster_image_audio']))
                    unset($Settings['poster_image_audio']);
                if (isset($Settings['itunes_image_audio']) && empty($Settings['itunes_image_audio']))
                    unset($Settings['itunes_image_audio']);
                if (isset($Settings['network_mode']) && empty($Settings['network_mode']))
                    unset($Settings['network_mode']);
                if (isset($Settings['use_caps']) && empty($Settings['use_caps']))
                    unset($Settings['use_caps']);
            } else // Feed or player settings...
            {
                if (isset($Settings['itunes_block']) && $Settings['itunes_block'] == 0)
                    unset($Settings['itunes_block']);
                if (isset($Settings['itunes_complete']) && $Settings['itunes_complete'] == 0)
                    unset($Settings['itunes_complete']);
                if (isset($Settings['maximize_feed']) && $Settings['maximize_feed'] == 0)
                    unset($Settings['maximize_feed']);
                if (isset($Settings['unlock_podcast']) && $Settings['unlock_podcast'] == 0)
                    unset($Settings['unlock_podcast']);
                if (isset($Settings['donate_link']) && $Settings['donate_link'] == 0)
                    unset($Settings['donate_link']);
                if (empty($Settings['donate_url']))
                    unset($Settings['donate_url']);
                if (empty($Settings['donate_label']))
                    unset($Settings['donate_label']);
                if (isset($Settings['allow_feed_comments']) && $Settings['allow_feed_comments'] == 0)
                    unset($Settings['allow_feed_comments']);
                if (empty($Settings['episode_itunes_image']))
                    unset($Settings['episode_itunes_image']);
            }

            if (!empty($Settings)) {
                if (isset($Settings['player'])) {
                    if ($Settings['player'] == 'blubrrymodern') {
                        if (!empty($_POST)) {
                            if (isset($_POST['ModernPlayer']['progress']) && isset($_POST['ModernPlayer']['border']) && isset($_POST['mode'])) {
                                if ($_POST['mode'] == 'Light' || $_POST['mode'] == 'Dark') {
                                    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i', $_POST['ModernPlayer']['progress']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i', $_POST['ModernPlayer']['border'])) {
                                        $updatedPlayerSettings = ['mode' => $_POST['mode'], 'border' => $_POST['ModernPlayer']['border'], 'progress' => $_POST['ModernPlayer']['progress']];
                                        update_option('powerpress_bplayer', json_encode($updatedPlayerSettings));
                                    }
                                }
                            }
                        }
                    }
                }
            }

            update_option($field, $Settings);
        }
    }
}

function powerpress_sync_progad() {

    // grab the redirect url prefixes for each feed slug and make an array
    $General = get_option('powerpress_general');
    // append general redirects to each other (starting with redirect1)
    $recipientedirects = array('redirect0'=>'', 'redirect1'=>'', 'redirect2'=>'', 'redirect3'=>'');
    if( !empty($General['redirect1']) )
        $recipientedirects['redirect1'] = $General['redirect1'];
    if( !empty($General['redirect2']) )
        $recipientedirects['redirect2'] = $General['redirect2'];
    if( !empty($General['redirect3']) )
        $recipientedirects['redirect3'] = $General['redirect3'];

    // add to redirect array with key 'enclosure'
    $main_redirect = buildRedirect($recipientedirects);
    $recipientedirect_array = array('enclosure' => $main_redirect);

    // then append custom feed redirects to beginning of main feed redirect with _slug:enclosure for each custom feed
    // channels
    if (!empty($General['custom_feeds'])) {
        foreach ($General['custom_feeds'] as $slug => $title) {
            $Feed = get_option('powerpress_feed_' . $slug, array());
            if (!empty($Feed['redirect'])) {
                $recipientedirects['redirect0'] = $Feed['redirect'];
                $recipientedirect_array += array('_' . $slug . ':enclosure' => buildRedirect($recipientedirects));
                $recipientedirects['redirect0'] = '';
            } else {
                $recipientedirect_array += array('_' . $slug . ':enclosure' => $main_redirect);
                // default stats redirect
            }
        }
    }

    // categories
    if (!empty($General['custom_cat_feeds'])) {
        foreach ($General['custom_cat_feeds'] as $idx => $id) {
            $category = get_category($id);
            // $category['slug']
            $Feed = get_option('powerpress_cat_feed_' . $id, array());
            if (!empty($Feed['redirect'])) {
                $recipientedirects['redirect0'] = $Feed['redirect'];
                $recipientedirect_array += array('_' . $category->slug . ':enclosure' => buildRedirect($recipientedirects));
                $recipientedirects['redirect0'] = '';
            } else {
                $recipientedirect_array += array('_' . $category->slug . ':enclosure' => $main_redirect);
                // default stats redirect
            }
        }
    }

    // taxonomies
    $PowerPressTaxonomies = get_option('powerpress_taxonomy_podcasting', array());
    if (!empty($PowerPressTaxonomies)) {
        foreach ($PowerPressTaxonomies as $tt_id => $null) {

            $taxonomy_type = '';
            $term_ID = '';
            $tt_id = intval($tt_id); // sanitize for sql

            global $wpdb;
            $term_info = $wpdb->get_results("SELECT term_id, taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = $tt_id", ARRAY_A);
            if (!empty($term_info[0]['term_id'])) {
                $term_ID = $term_info[0]['term_id'];
                $taxonomy_type = $term_info[0]['taxonomy'];
            } else {
                continue; // we didn't find this taxonomy relationship
            }

            $Feed = get_option('powerpress_taxonomy_' . $tt_id);
            $term_object = get_term( $term_ID, $taxonomy_type, OBJECT, 'edit');
            if (!empty($Feed['redirect'])) {
                $recipientedirects['redirect0'] = $Feed['redirect'];
                $recipientedirect_array += array('_' . $term_object->slug . ':enclosure' => buildRedirect($recipientedirects));
                $recipientedirects['redirect0'] = '';
            } else {
                $recipientedirect_array += array('_' . $term_object->slug . ':enclosure' => $main_redirect);
                // default stats redirect
            }
        }
    }

    // post types

    $post_types = array();
    $post_types_wp = get_post_types();
    foreach( $post_types_wp as $index => $post_type )
    {
        if( $post_type == 'redirect_rule' || $post_type == 'attachment' || $post_type == 'nav_menu_item' || $post_type == 'revision' || $post_type == 'action' )
            continue;

        $post_types[] = $post_type;

    }
    if (!empty($post_types)) {
        foreach ($post_types as $null => $post_type) {
            $PostTypeSettingsArray = get_option('powerpress_posttype_' . $post_type, array());
            if (empty($PostTypeSettingsArray))
                continue;

            foreach ($PostTypeSettingsArray as $feed_slug => $Feed) {
                if (!empty($Feed['redirect'])) {
                    $recipientedirects['redirect0'] = $Feed['redirect'];
                    $recipientedirect_array += array('_' . $feed_slug . ':enclosure' => buildRedirect($recipientedirects));
                    $recipientedirects['redirect0'] = '';
                } else {
                    $recipientedirect_array += array('_' . $feed_slug . ':enclosure' => $main_redirect);
                    // default stats redirect
                }
            }
        }
    }

    // figure out which shows we are enabling/disabling
    require_once('powerpressadmin-auth.class.php');
    $progad_error = '';
    $progad_enable_urls = array();
    $progad_disable_urls = array();
    $auth = new PowerPressAuth();
    $accessToken = powerpress_getAccessToken();
    $recipienteq_url = sprintf('/2/media/prog_ad_status.json?cache=' . md5(rand(0, 999) . time()));
    $recipienteq_url .= (defined('POWERPRESS_BLUBRRY_API_QSA') ? '?' . POWERPRESS_BLUBRRY_API_QSA : '');
    $recipienteq_url .= (defined('POWERPRESS_PUBLISH_PROTECTED') ? '&protected=true' : '');
    $progad_enabled_shows = $auth->api($accessToken, $recipienteq_url, array(), false, 60 * 30);
    if (!$progad_enabled_shows) {
        $progad_error = $auth->getLastError();
    }
    $past_shows_with_progad = get_option('pp_programmatic_enabled_shows');
    if (!empty($past_shows_with_progad) && !empty($progad_enabled_shows['programs'])) {
        $shows_to_enable = array_diff($progad_enabled_shows['programs'], $past_shows_with_progad);
        $shows_to_disable = array_diff($past_shows_with_progad, $progad_enabled_shows['programs']);
    } elseif (!empty($past_shows_with_progad) && empty($progad_enabled_shows['programs'])) {
        $shows_to_disable = $past_shows_with_progad;
    } elseif (!empty($progad_enabled_shows['programs']) && empty($past_shows_with_progad)) {
        $shows_to_enable = $progad_enabled_shows['programs'];
    }
    update_option('pp_programmatic_enabled_shows', $progad_enabled_shows['programs']);

    // use the API to get associated URLs for all URLs in any program whose ads were just enabled
    if (!empty($shows_to_enable)) {
        foreach ($shows_to_enable as $idx => $keyword) {
            $recipienteq_url = sprintf('/2/media/' . $keyword . '/prog_ad_urls.json?cache=' . md5(rand(0, 999) . time()));
            if (defined('POWERPRESS_PROGRAMMATIC_FIX')) {
                $recipienteq_url .= '&pp_first_release_fix=true';
            }
            $recipienteq_url .= (defined('POWERPRESS_BLUBRRY_API_QSA') ? '?' . POWERPRESS_BLUBRRY_API_QSA : '');
            $recipienteq_url .= (defined('POWERPRESS_PUBLISH_PROTECTED') ? '&protected=true' : '');
            $recipientesult_prog = $auth->api($accessToken, $recipienteq_url, array(), false, 60 * 30);
            if (isset($recipientesult_prog['urls']) && is_array($recipientesult_prog['urls'])) {
                foreach ($recipientesult_prog['urls'] as $i => $url_pair) {
                    // add the redirect to the key before adding this pair
                    $progad_enable_urls += $url_pair;
                }
            } elseif (isset($recipientesult_prog['message']) && $recipientesult_prog['message'] == 'no media') {
                // no error--continue
            }
            else {
                $progad_error = $auth->getLastError();
            }
        }
    }

    // use the API to get associated URLs for all URLs in any program whose ads were just disabled
    if (!empty($shows_to_disable)) {
        foreach ($shows_to_disable as $idx => $keyword) {
            $recipienteq_url = sprintf('/2/media/' . $keyword . '/prog_ad_urls.json?disable=true&cache=' . md5(rand(0, 999) . time()));
            $recipienteq_url .= (defined('POWERPRESS_BLUBRRY_API_QSA') ? '?' . POWERPRESS_BLUBRRY_API_QSA : '');
            $recipienteq_url .= (defined('POWERPRESS_PUBLISH_PROTECTED') ? '&protected=true' : '');
            $recipientesult_prog = $auth->api($accessToken, $recipienteq_url, array(), false, 60 * 30);
            $progad_error = $auth->getLastError();
            if (isset($recipientesult_prog['urls']) && is_array($recipientesult_prog['urls'])) {
                foreach ($recipientesult_prog['urls'] as $i => $url_pair) {
                    // add the redirect to the key before adding this pair
                    $progad_disable_urls += $url_pair;
                }
            } elseif (isset($recipientesult_prog['message']) && $recipientesult_prog['message'] == 'no media') {
                // no error--continue
            }
            else {
                $progad_error = $auth->getLastError();
            }
        }
    }

    // query the wordpress database to match up the URLs that we need to update
    global $wpdb;
    $query = "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE \"%enclosure\"";
    $recipientesults_data = $wpdb->get_results($query, ARRAY_A);
    foreach ($recipientesults_data as $idx => $data) {
        $meta_parts = explode("\n", $data['meta_value']);

        if (strpos($meta_parts[0], 'ins.blubrry.com')) {
            $parts_array = explode('ins.blubrry.com', $meta_parts[0]);
        } else if (strpos($meta_parts[0], 'content3.blubrry.biz')) {
            $parts_array = explode('content3.blubrry.biz', $meta_parts[0]);
        } else if (strpos($meta_parts[0], 'mc.blubrry.com')) {
            $parts_array = explode('mc.blubrry.com', $meta_parts[0]);
        } elseif (strpos($meta_parts[0], 'content.blubrry.com')) {
            $parts_array = explode('content.blubrry.com', $meta_parts[0]);
        } else {
            // not Blubrry hosted
            continue;
        }
        $url_without_prefix = $parts_array[1];
        $parts_drop_qs = explode('?', $url_without_prefix);
        if (!empty($progad_enable_urls) && array_key_exists($parts_drop_qs[0], $progad_enable_urls)) {
            // now, if they have a redirect for the feed that this url is in, we need to replace the https://media.blubrry.com/{keyword}/ with those redirects
            $progad_url_with_pp_redirect = preg_replace('#https://media.blubrry.com/(.*)/#U', $recipientedirect_array[$data['meta_key']], $progad_enable_urls[$parts_drop_qs[0]]);
            // replace the url in the meta_parts array, implode it back together, and update the program meta
            $meta_parts[0] = $progad_url_with_pp_redirect;
            $new_meta_value = implode("\n", $meta_parts);
            update_post_meta($data['post_id'], $data['meta_key'], $new_meta_value);
        } else if (!empty($progad_disable_urls) && array_key_exists($parts_drop_qs[0], $progad_disable_urls)) {
            $hosting_url_with_pp_redirect = preg_replace('#http(s?)://#U', $recipientedirect_array[$data['meta_key']], $progad_disable_urls[$parts_drop_qs[0]]);
            // replace the url in the meta_parts array, implode it back together, and update the program meta
            $meta_parts[0] = $hosting_url_with_pp_redirect;
            $new_meta_value = implode("\n", $meta_parts);
            update_post_meta($data['post_id'], $data['meta_key'], $new_meta_value);
        }
    }

    if ($progad_error) {
        update_option("pp_progad_sync_error", __("Error syncing Programmatic Advertising Settings:", 'powerpress') . " " . $progad_error);
    } else {
        update_option("pp_progad_sync_success", __("Successfully synced Programmatic Advertising Settings from Blubrry.", 'powerpress'));
    }
}
add_action('powerpress_sync_progad_hook', 'powerpress_sync_progad');

add_action('rss2_ns', 'powerpress_rss2_ns');
add_action('rss2_ns_powerpress', 'powerpress_rss2_ns');

function powerpress_rss2_head()
{
    // disable php notices inside feeds
    error_reporting(0);
    global $powerpress_feed;

    if( !powerpress_is_podcast_feed() )
        return; // Not a feed we manage

    $feed_slug = get_query_var( 'feed' );
    $cat_ID = get_query_var('cat');

    $Feed = get_option('powerpress_feed', array()); // Get the main feed settings
    $General = get_option('powerpress_general', array());

    $feed_url = "";
    if( !empty($powerpress_feed['category']) )
    {
        $CustomFeed = get_option('powerpress_cat_feed_'.$powerpress_feed['category'], array()); // Get the custom podcast feed settings saved in the database
        if( !empty($CustomFeed) )
            $Feed = powerpress_merge_empty_feed_settings($CustomFeed, $Feed);

        if( !empty($General['cat_casting_podcast_feeds']) )
            $feed_url = get_category_feed_link($powerpress_feed['category'], 'podcast');
        else // Use the old link
            $feed_url = get_category_feed_link($powerpress_feed['category']);
    }
    else if( !empty($powerpress_feed['term_taxonomy_id']) )
    {
        $CustomFeed = get_option('powerpress_taxonomy_'.$powerpress_feed['term_taxonomy_id'], array()); // Get the taxonomy podcast settings saved in the database
        if( !empty($CustomFeed) )
            $Feed = powerpress_merge_empty_feed_settings($CustomFeed, $Feed);

        global $wpdb;
        $term_info = $wpdb->get_results("SELECT term_id, taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = " . intval($powerpress_feed['term_taxonomy_id']),  ARRAY_A);
        $taxonomy_type = $term_info[0]['taxonomy'];
        $feed_url = get_term_feed_link($powerpress_feed['term_taxonomy_id'], $taxonomy_type, 'rss2');
    }
    else if( !empty($powerpress_feed['post_type']) )
    {
        $PostTypeSettingsArray = get_option('powerpress_posttype_'.$powerpress_feed['post_type'], array()); // Get the post type podcast feed settings saved in the database
        if( !empty($PostTypeSettingsArray[ $feed_slug ]) )
        {
            $CustomFeed = $PostTypeSettingsArray[ $feed_slug ];
            $Feed = powerpress_merge_empty_feed_settings($CustomFeed, $Feed, ($feed_slug == 'podcast') );
        }

        $feed_url = get_post_type_archive_feed_link($powerpress_feed['post_type'], $feed_slug);
    }
    else if( powerpress_is_custom_podcast_feed() ) // If we're handling a custom podcast feed...
    {
        $CustomFeed = get_option('powerpress_feed_'.$feed_slug, array()); // Get the custom podcast feed settings saved in the database
        $Feed = powerpress_merge_empty_feed_settings($CustomFeed, $Feed, ($feed_slug == 'podcast') );
        $feed_url = get_feed_link($feed_slug);
    }

    if( !isset($Feed['url']) || trim($Feed['url']) == '' )
    {
        if( is_category() )
            $Feed['url'] = get_category_link($cat_ID);
        else {

            $blogHomepage = get_option('page_for_posts');
            if( !empty($blogHomepage) ) {
                $Feed['url'] = get_permalink( $blogHomepage );
            }

            if( empty($Feed['url']) )
                $Feed['url'] = get_bloginfo('url');
        }
    }

    $General = get_option('powerpress_general', array());

    $feedComment = apply_filters('powerpress_feed_comment', '');
    $feedComment = trim($feedComment);
    if( !empty($feedComment) )
        echo $feedComment.' ';


    // Websub!
    if(!(defined('POWERPRESS_DISABLE_WEBSUB') && POWERPRESS_DISABLE_WEBSUB )) {
        echo "\t<atom:link rel=\"hub\" href=\"https://pubsubhubbub.appspot.com/\" />" . PHP_EOL;
    }

    // Podcast Index Locked Tag
    if (!empty($Feed['pp_enable_feed_lock'])) {
        echo "\t<podcast:locked>";
        if (!empty($Feed['unlock_podcast'])) {
            echo "no";
        } else {
            echo "yes";
        }
        echo "</podcast:locked>" . PHP_EOL;
    }

    // add the itunes:new-feed-url tag to feed
    if( powerpress_is_custom_podcast_feed() )
    {
        if( !empty($Feed['itunes_new_feed_url']) )
        {
            $Feed['itunes_new_feed_url'] = str_replace('&amp;', '&', $Feed['itunes_new_feed_url']);
            echo "\t<itunes:new-feed-url>". htmlspecialchars(trim($Feed['itunes_new_feed_url'])) .'</itunes:new-feed-url>'.PHP_EOL;
        }
    }
    else if( !empty($Feed['itunes_new_feed_url']) && ($feed_slug == 'feed' || $feed_slug == 'rss2') ) // If it is the default feed (We don't wnat to apply this to category or tag feeds
    {
        $Feed['itunes_new_feed_url'] = str_replace('&amp;', '&', $Feed['itunes_new_feed_url']);
        echo "\t<itunes:new-feed-url>". htmlspecialchars(trim($Feed['itunes_new_feed_url'])) .'</itunes:new-feed-url>'.PHP_EOL;
    }

    /*
    if( !empty($General['itunes_cdata']) ) {
        if( !empty($Feed['itunes_summary']) )
            echo "\t".'<itunes:summary><![CDATA['. powerpress_format_itunes_value( $Feed['itunes_summary'], 'summary', true ) .']]></itunes:summary>'.PHP_EOL;
        else
            echo "\t".'<itunes:summary><![CDATA['. powerpress_format_itunes_value( get_bloginfo('description'), 'summary', true ) .']]></itunes:summary>'.PHP_EOL;
    } else {
        if( !empty($Feed['itunes_summary']) )
            echo "\t".'<itunes:summary>'. powerpress_format_itunes_value( $Feed['itunes_summary'], 'summary' ) .'</itunes:summary>'.PHP_EOL;
        else
            echo "\t".'<itunes:summary>'.  powerpress_format_itunes_value( get_bloginfo('description'), 'summary' ) .'</itunes:summary>'.PHP_EOL;
    }
    */

    if( !empty($powerpress_feed['itunes_talent_name']) )
        echo "\t<itunes:author>" . esc_html($powerpress_feed['itunes_talent_name']) . '</itunes:author>'.PHP_EOL;

    // itunes:explicit is REQUIRED by Apple on channel level
    if( !empty($powerpress_feed['explicit']) )
        echo "\t".'<itunes:explicit>' . $powerpress_feed['explicit'] . '</itunes:explicit>'.PHP_EOL;

    if( !empty($Feed['itunes_block']) )
        echo "\t<itunes:block>yes</itunes:block>".PHP_EOL;

    if( !empty($Feed['itunes_complete']) )
        echo "\t<itunes:complete>yes</itunes:complete>".PHP_EOL;

    if( !empty($Feed['itunes_image']) )
    {
        echo "\t".'<itunes:image href="' . esc_html( powerpress_url_in_feed(str_replace(' ', '+', $Feed['itunes_image'])), 'double') . '" />'.PHP_EOL;
    }
    else
    {
        echo "\t".'<itunes:image href="' . powerpress_url_in_feed(powerpress_get_root_url()) . 'itunes_default.jpg" />'.PHP_EOL;
    }

    if( !empty($Feed['itunes_type']) ) {
        echo "\t".'<itunes:type>'. esc_html($Feed['itunes_type']) .'</itunes:type>'.PHP_EOL;
    }

    echo "\t".'<itunes:owner>'.PHP_EOL;
    echo "\t\t".'<itunes:name>' . esc_html($powerpress_feed['itunes_talent_name']) . '</itunes:name>'.PHP_EOL;
    if( !empty($Feed['email']) && (!isset($Feed['pp_enable_email']) || $Feed['pp_enable_email'] == 1))
    {
        echo "\t\t".'<itunes:email>' . esc_html($Feed['email']) . '</itunes:email>'.PHP_EOL;
    }
    echo "\t".'</itunes:owner>'.PHP_EOL;

    if ( !empty($Feed['apple_claim_token'])) {
        echo "\t"."<itunes:applepodcastsverify>".esc_html($Feed['apple_claim_token'])."</itunes:applepodcastsverify>".PHP_EOL;
        echo "\t".'<podcast:txt purpose="applepodcastsverify">'.esc_html($Feed['apple_claim_token'])."</podcast:txt>".PHP_EOL;
    }

    if( isset( $Feed['copyright'] ) && strlen($Feed['copyright']) > 1 )
    {
        $Feed['copyright'] = str_replace(array('&copy;', '(c)', '(C)', chr(194) . chr(169), chr(169) ), '&#xA9;', $Feed['copyright']);
        echo "\t".'<copyright>'. esc_html($Feed['copyright']) . '</copyright>'.PHP_EOL;
        if ( isset( $Feed['copyright_url'] ) && strlen($Feed['copyright_url']) > 1 ) {
            echo "\t".'<podcast:license url="' . esc_attr(powerpress_url_in_feed($Feed['copyright_url'])) . '">'. esc_html($Feed['copyright']) . '</podcast:license>'.PHP_EOL;
        } else {
            echo "\t".'<podcast:license>'. esc_html($Feed['copyright']) . '</podcast:license>'.PHP_EOL;
        }
    }

    if (!empty($Feed['txt_tag']) && is_array($Feed['txt_tag']))
    {
        foreach ($Feed['txt_tag'] as $txt_tag) {
            if (!empty($txt_tag['tag'])) {
                $tag_output = "\t" . '<podcast:txt';
                
                if (!empty($txt_tag['purpose'])) {
                    $tag_output .= ' purpose="' . esc_attr($txt_tag['purpose']) . '"';
                }
                
                $tag_output .= '>' . esc_html($txt_tag['tag']) . '</podcast:txt>' . PHP_EOL;
                
                echo $tag_output;
            }
        }
    }

    echo "\t".'<podcast:medium>'. esc_html($Feed['medium'] ?? 'podcast') . '</podcast:medium>'.PHP_EOL;
    /*
    if( !empty($Feed['itunes_subtitle']) )
        echo "\t".'<itunes:subtitle>' . powerpress_format_itunes_value($Feed['itunes_subtitle'], 'subtitle') . '</itunes:subtitle>'.PHP_EOL;
    else
        echo "\t".'<itunes:subtitle>'.  powerpress_format_itunes_value( get_bloginfo('description'), 'subtitle') .'</itunes:subtitle>'.PHP_EOL;
    */
    $podcast_title_safe = '';
    if( version_compare($GLOBALS['wp_version'], '4.4', '<' ) ) {
        $podcast_title_safe .= get_bloginfo_rss('name');
    }
    $podcast_title_safe .= get_wp_title_rss();
    if( empty($General['disable_rss_image']) )
    {
        if(!empty($Feed['itunes_image']) )
        {
            $recipientss_image = $Feed['itunes_image'];

            echo "\t". '<image>' .PHP_EOL;
            echo "\t\t".'<title>' . $podcast_title_safe . '</title>'.PHP_EOL;
            echo "\t\t".'<url>' . esc_html( str_replace(' ', '+', $recipientss_image)) . '</url>'.PHP_EOL;
            echo "\t\t".'<link>'. $Feed['url'] . '</link>' . PHP_EOL;
            echo "\t".'</image>' . PHP_EOL;
        }
        else // Use the default image
        {
            echo "\t". '<image>' .PHP_EOL;
            echo "\t\t".'<title>' . $podcast_title_safe . '</title>'.PHP_EOL;
            echo "\t\t".'<url>' . powerpress_get_root_url() . 'rss_default.jpg</url>'.PHP_EOL;
            echo "\t\t".'<link>'. $Feed['url'] . '</link>' . PHP_EOL;
            echo "\t".'</image>' . PHP_EOL;
        }
    }

    // Handle iTunes categories
    $Cat1 = false; $Cat2 = false; $Cat3 = false; $SubCat1 = false; $SubCat2 = false; $SubCat3 = false;
    if(defined('POWERPRESS_NEW_APPLE_CATEGORIES') && POWERPRESS_NEW_APPLE_CATEGORIES == true) {
        $Categories = powerpress_apple_categories();
        for ($i = 1; $i <= 3; $i++) {
            if(!empty($Feed['itunes_cat_'.$i]) && empty($Feed['apple_cat_'.$i])) {
                $mappings = array('01-00' => '01-00', '01-01' => '01-02', '01-02' => '01-03', '01-03' => '01-04', '01-04' => '01-01',
                    '01-05' => '01-05', '01-06' => '01-06', '02-00' => '02-00', '02-01' => '12-01', '02-02' => '02-01', '02-03' => '02-03',
                    '02-04' => '02-00', '02-05' => '02-00', '03-00' => '03-00', '04-00' => '04-00', '04-01' => '04-00', '04-02' => '04-00',
                    '04-03' => '09-01', '04-04' => '04-03', '04-05' => '04-00', '05-00' => '10-00', '05-01' => '10-02', '05-02' => '10-03',
                    '05-03' => '10-06', '05-04' => '10-05', '05-05' => '10-05', '06-00' => '06-00', '06-01' => '06-00', '06-02' => '06-00',
                    '06-03' => '06-00', '06-04' => '06-00', '07-00' => '07-00', '07-01' => '07-01', '07-02' => '07-00', '07-03' => '04-04',
                    '07-04' => '07-06', '08-00' => '09-00', '09-00' => '11-00', '11-00' => '13-00', '11-01' => '13-01', '11-02' => '13-02',
                    '11-03' => '13-03', '11-04' => '13-04', '11-05' => '13-05', '11-06' => '13-06', '11-07' => '13-07', '12-00' => '14-00',
                    '12-01' => '07-03', '12-02' => '14-06', '12-03' => '14-09', '13-00' => '15-00', '13-01' => '08-00', '13-02' => '15-02',
                    '13-03' => '15-03', '13-04' => '15-04', '14-00' => '16-00', '14-01' => '16-00', '14-02' => '16-00', '14-03' => '16-15',
                    '14-04' => '16-00', '15-00' => '17-00', '15-01' => '17-00', '15-02' => '12-07', '15-03' => '17-00', '15-04' => '17-00', '16-00' => '19-00');
                $Feed['apple_cat_'. $i] = $mappings[$Feed['itunes_cat_'.$i]];

            }
        }
        if (!empty($Feed['apple_cat_1']))
            list($Cat1, $SubCat1) = explode('-', $Feed['apple_cat_1']);
        if (!empty($Feed['apple_cat_2']))
            list($Cat2, $SubCat2) = explode('-', $Feed['apple_cat_2']);
        if (!empty($Feed['apple_cat_3']))
            list($Cat3, $SubCat3) = explode('-', $Feed['apple_cat_3']);
        $googleplay_category_mapping = array(
            '01-00'   => '01-00',
            '02-00'   => '02-00',
            '03-00'   => '03-00',
            '04-00'   => '04-00',
            '05-00'   => '13-00',
            '06-00'   => '06-00',
            '07-00'   => '07-00',
            '08-00'   => '13-00',
            '09-00'   => '08-00',
            '10-00'   => '05-00',
            '11-00'   => '09-00',
            '12-00'   => '10-00',
            '13-00'   => '11-00',
            '14-00'   => '12-00',
            '15-00'   => '13-00',
            '16-00'   => '14-00',
            '17-00'   => '15-00',
            '18-00'   => '13-00',
            '19-00'   => '16-00',
        );
    }
    else {
        $Categories = powerpress_itunes_categories();
        if (!empty($Feed['itunes_cat_1']))
            list($Cat1, $SubCat1) = explode('-', $Feed['itunes_cat_1']);
        if (!empty($Feed['itunes_cat_2']))
            list($Cat2, $SubCat2) = explode('-', $Feed['itunes_cat_2']);
        if (!empty($Feed['itunes_cat_3']))
            list($Cat3, $SubCat3) = explode('-', $Feed['itunes_cat_3']);
        $googleplay_category_mapping = array(
            '01-00'   => '01-00',
            '02-00'   => '02-00',
            '03-00'   => '03-00',
            '04-00'   => '04-00',
            '05-00'   => '05-00',
            '06-00'   => '06-00',
            '07-00'   => '07-00',
            '08-00'   => '08-00',
            '09-00'   => '09-00',
            '10-00'   => '10-00',
            '11-00'   => '11-00',
            '12-00'   => '12-00',
            '13-00'   => '13-00',
            '14-00'   => '14-00',
            '15-00'   => '15-00',
            '16-00'   => '16-00',
        );
    }

    $googleplay_categories = powerpress_googleplay_categories();

    if( $Cat1 )
    {
        $CatDesc = $Categories[$Cat1.'-00'];
        $SubCatDesc = $Categories[$Cat1.'-'.$SubCat1];
        echo "\t".'<itunes:category text="'. esc_attr($CatDesc);
        if( $SubCat1 != '00' ) {
            echo '">' . PHP_EOL . "\t\t" . '<itunes:category text="' . esc_attr($SubCatDesc) . '" />' . PHP_EOL;
            // End this category set
            echo "\t".'</itunes:category>'.PHP_EOL;
        } else {
            echo '" />'.PHP_EOL;
        }
    }

    if( $Cat2 )
    {
        $CatDesc = $Categories[$Cat2.'-00'];
        $SubCatDesc = $Categories[$Cat2.'-'.$SubCat2];

        echo "\t".'<itunes:category text="'. esc_attr($CatDesc);
        if( $SubCat2 != '00' ) {
            echo '">' . PHP_EOL . "\t\t" . '<itunes:category text="' . esc_attr($SubCatDesc) . '" />' . PHP_EOL;
            // End this category set
            echo "\t".'</itunes:category>'.PHP_EOL;
        } else {
            echo '" />'.PHP_EOL;
        }
    }

    if( $Cat3 )
    {
        $CatDesc = $Categories[$Cat3.'-00'];
        $SubCatDesc = $Categories[$Cat3.'-'.$SubCat3];

        echo "\t".'<itunes:category text="'. esc_attr($CatDesc);
        if( $SubCat3 != '00' ) {
            echo '">' . PHP_EOL . "\t\t" . '<itunes:category text="' . esc_attr($SubCatDesc) . '" />' . PHP_EOL;
            // End this category set
            echo "\t".'</itunes:category>'.PHP_EOL;
        } else {
            echo '" />'.PHP_EOL;
        }
    }
    // End Handle iTunes categories

    // RawVoice RSS Tags
    if( !defined('POWERPRESS_RAWVOICE_RSS') || POWERPRESS_RAWVOICE_RSS != false )
    {
        if( !empty($Feed['parental_rating']) )
            echo "\t<rawvoice:rating>". $Feed['parental_rating'] ."</rawvoice:rating>".PHP_EOL;

        $locations = [];
        if (!empty($Feed['location']) && is_array($Feed['location'])) {
            $first = reset($Feed['location']);
            if (is_array($first) && (isset($first['address']) || isset($first['location']))) {
                // nested format 
                foreach ($Feed['location'] as $loc) {
                    if (empty($loc) || !is_array($loc)) continue;
                    $address = $loc['address'] ?? $loc['location'] ?? '';
                    if ($address === '') continue;
                    $locations[] = [
                        'address' => $address,  
                        'pci_geo' => $loc['pci_geo'] ?? $loc['geo'] ?? '',  
                        'pci_osm' => $loc['pci_osm'] ?? $loc['osm'] ?? '',  
                        'pci_rel' => $loc['pci_rel'] ?? $loc['rel'] ?? '1',  
                        'pci_country' => $loc['pci_country'] ?? $loc['country'] ?? ''
                    ];
                }
            } else {
                // paralellized (legacy) 
                foreach ((array)$Feed['location'] as $i => $addr) {
                    if ($addr === '' || $addr === null) continue;
                    $locations[] = [
                        'address' => $addr,
                        'pci_geo' => $Feed['pci_geo'][$i] ?? $Feed['geo'][$i] ?? '',
                        'pci_osm' => $Feed['pci_osm'][$i] ?? $Feed['osm'][$i] ?? '',
                        'pci_rel' => $Feed['pci_rel'][$i] ?? $Feed['rel'][$i] ?? '1',
                        'pci_country' => $Feed['pci_country'][$i] ?? $Feed['country'][$i] ?? '',
                    ];
                }
            }
        }

        foreach ($locations as $location) { 
            $address = trim((string)($location['address'] ?? '')); 
            if ($address === '') continue;
            
            echo "\t<rawvoice:location>" . htmlspecialchars($address) . "</rawvoice:location>\n";
            echo "\t<podcast:location";
            
            if (!empty($location['pci_geo']))     echo ' geo="' . htmlspecialchars($location['pci_geo']) . '"';  
            if (!empty($location['pci_osm']))     echo ' osm="' . htmlspecialchars($location['pci_osm']) . '"';  
            if (!empty($location['pci_rel']))     echo ' rel="' . ($location['pci_rel'] == '1' ? 'subject' : 'creator') . '"';
            if (!empty($location['pci_country'])) echo ' country="' . htmlspecialchars($location['pci_country']) . '"'; 

            echo '>' . htmlspecialchars($address) . "</podcast:location>\n";
        }

        if (!empty($Feed['update_frequency'])) {
            $recipientrule = 'FREQ=' . ($Feed['update_frequency'] == '1' ? 'DAILY' : ($Feed['update_frequency'] == '2' ? 'WEEKLY' : 'MONTHLY'));
            $updateFrequency = 'Daily';

            if ($Feed['update_frequency'] == 2) {
                $days = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                $weekDays = $Feed['update_frequency_week'] ?? '';
                $selectedDays = !empty($weekDays) ? explode(',', $weekDays) : [];
                $byDays = [];
                foreach ($selectedDays as $day) {
                    $byDays[] = $days[(int) $day];
                }

                if (!empty($byDays))
                    $recipientrule .= ';BYDAY='.implode(',', $byDays);

                $updateFrequency = 'Weekly';

                if (count($selectedDays) == 2)
                    $updateFrequency = 'Biweekly';
            } else if ($Feed['update_frequency'] == 3) {
                $monthInterval = $Feed['update_frequency_month'] ?? 1;
                $recipientrule .= ';INTERVAL=' . $monthInterval;
                $updateFrequency = 'Monthly';

                if ($monthInterval == 2)
                    $updateFrequency = 'Bimonthly';
            }

            echo "\t<podcast:updateFrequency rrule=\"$recipientrule\">$updateFrequency</podcast:updateFrequency>\n";
        }

        if( !empty($Feed['frequency']) )
            echo "\t<rawvoice:frequency>". htmlspecialchars($Feed['frequency']) ."</rawvoice:frequency>".PHP_EOL;

        if (isset($Feed['block'])) {
            if (isset($Feed['block_all']) && $Feed['block_all'] != 0) {
                echo "\t<podcast:block>yes</podcast:block>\n";
                echo "\t<itunes:block>yes</itunes:block>\n";
            } else {
                // block individuals
                $blockListStr = $Feed['block_list'] ?? '';
                $blockList = !empty($blockListStr) ? explode(';', $blockListStr) : [];

                foreach ($blockList as $block) {
                    if ($block != '') {
                        echo "\t<podcast:block id=\"$block\">yes</podcast:block>\n";
                        if ($block == 'apple') {
                            echo "\t<itunes:block>yes</itunes:block>\n";
                        }
                    }
                }
            }
        }

        if (isset($Feed['remote_items']) && !empty($Feed['remote_items'])) {
            $existingRemoteItems = $Feed['remote_items'];
            $existingPodrollItems = [];
            $existingFeedItems = [];

            foreach ($existingRemoteItems as $recipientemoteItem) {
                $isPodroll = $recipientemoteItem['podroll'] ?? 0;
                if ($isPodroll == 1)
                    $existingPodrollItems[] = $recipientemoteItem;
                else
                    $existingFeedItems[] = $recipientemoteItem;
            }

            if (!empty($existingPodrollItems)) {
                echo "\t<podcast:podroll>\n";

                foreach ($existingPodrollItems as $recipientemoteItem) {
                    $feedGuid = $recipientemoteItem['feed_guid'];
                    echo "\t\t<podcast:remoteItem feedGuid=\"$feedGuid\"";

                    if (!empty($recipientemoteItem['item_guid'])) {
                        echo " itemGuid=\"" . $recipientemoteItem['item_guid'] . "\"";
                    }

                    if (!empty($recipientemoteItem['item_link'])) {
                        echo " feedUrl=\"" . $recipientemoteItem['item_link'] . "\"";
                    }

                    if (!empty($recipientemoteItem['medium'])) {
                        echo " medium=\"" . $recipientemoteItem['medium'] . "\"";
                    }

                    if (!empty($recipientemoteItem['item_title'])) {
                        echo " title=\"" . $recipientemoteItem['item_title'] . "\"";
                    }
                    echo " />\n";
                }
                echo "\t</podcast:podroll>\n";
            }

            foreach ($existingFeedItems as $recipientemoteItem) {
                $feedGuid = $recipientemoteItem['feed_guid'];
                $attrStr = "feedGuid=\"$feedGuid\"";

                if (!empty($recipientemoteItem['item_guid'])) {
                    $itemGuid = $recipientemoteItem['item_guid'] ?? '';
                    $attrStr .= " itemGuid=\"" . $itemGuid . "\"";
                }
                if (!empty($recipientemoteItem['item_link'])) {
                    $attrStr .= " feedUrl=\"" . $recipientemoteItem['item_link'] . "\"";
                }
                if (!empty($recipientemoteItem['medium'])) {
                    $attrStr .= " medium=\"" . $recipientemoteItem['medium'] . "\"";
                }
                if (!empty($recipientemoteItem['item_title'])) {
                    $attrStr .= " title=\"" . $recipientemoteItem['item_title'] . "\"";
                    }

                echo "\t<podcast:remoteItem $attrStr />\n";
            }
        }

        
        $value_recipients = [];
        if (!empty($Feed['value_recipients']) && is_array($Feed['value_recipients'])) {
            foreach ($Feed['value_recipients'] as $value_recipient) {
                if (empty($value_recipient['pubkey']) || empty($value_recipient['split'])) {
                    continue;
                }
                
                $value_recipients[] = [
                    'lightning' => $value_recipient['lightning'] ?? $value_recipient['name'] ?? '',
                    'split' => $value_recipient['split'],
                    'address' => $value_recipient['pubkey'], 
                    'customKey' => $value_recipient['custom_key'] ?? $value_recipient['customKey'] ?? '',
                    'customValue' => $value_recipient['custom_value'] ?? $value_recipient['customValue'] ?? '',
                    'fee' => (isset($value_recipient['fee']) && $value_recipient['fee'] === 'true') ? 'true' : 'false'
                ];
            }
        } elseif (!empty($Feed['value_pubkey']) && !empty($Feed['value_split'])) {
            foreach ($Feed['value_pubkey'] as $id => $pubkey) { 
                if (empty($pubkey)) continue;
                $value_recipients[] = [
                    'lightning' => $Feed['value_lightning'][$id] ?? '',
                    'split' => $Feed['value_split'][$id], 
                    'address' => $pubkey,
                    'customKey' => $Feed['value_custom_key'][$id] ?? '',
                    'customValue' => $Feed['value_custom_value'][$id] ?? '', 
                    'fee' => isset($Feed['value_is_fee'][$id]) && $Feed['value_is_fee'][$id] ? 'true' : 'false' 
                ];
            }
        }

        if (!empty($value_recipients)) {
            $value_recipients[] = [  
                'lightning' => 'blubrry@getalby.com',
                'split' => 3,
                'address' => '03b8a595e4d8e19efa8faa3fbe2524b0d39f7c812415ff831d38a466a4a1bc888a', 
                'customKey' => '696969',
                'customValue' => 'qAHJuqKLmMhTNFualcIj',
                'fee' => 'true'
            ];
            
            echo "\t".'<podcast:value type="lightning" method="keysend" suggested="0.00000005000">'."\n";

            foreach ($value_recipients as $value_recipient) {  
                $attrStr = 'type="node" split="'.$value_recipient['split'].'" address="'.$value_recipient['address'].'"';
                
                if (!empty($value_recipient['lightning'])) {
                    $attrStr .= ' name="'.htmlspecialchars($value_recipient['lightning']).'"';  
                }
                
                if (!empty($value_recipient['customKey'])) {
                    $attrStr .= ' customKey="'.htmlspecialchars($value_recipient['customKey']).'"';
                }
                
                if (!empty($value_recipient['customValue'])) {
                    $attrStr .= ' customValue="'.htmlspecialchars($value_recipient['customValue']).'"';
                }
                
                if ($value_recipient['fee'] === 'true') { 
                    $attrStr .= ' fee="true"';
                }
                
                echo "\t\t"."<podcast:valueRecipient $attrStr/>\n";
            }
            
            echo "\t".'</podcast:value>'."\n";
        }

        // DONATE
        if( !empty($Feed['donate_link']) && !empty($Feed['donate_url']) ) {
            echo "\t<rawvoice:donate href=\"" . htmlspecialchars($Feed['donate_url']) . "\">" . htmlspecialchars((empty($Feed['donate_label']) ? '' : $Feed['donate_label'])) . "</rawvoice:donate>" . PHP_EOL;
            echo "\t<podcast:funding url=\"" . htmlspecialchars($Feed['donate_url']) . "\">" . htmlspecialchars((empty($Feed['donate_label']) ? '' : $Feed['donate_label'])) . "</podcast:funding>" . PHP_EOL;
        }

        // CREDITS
        // nested
        $channel_credits = [];
        if (!empty($Feed['credits'])) {
            $channel_credits = $Feed['credits'];
        } 
        // parallelized (legacy)
        else if ( !empty($Feed['person_names']) ) {
            $personNames = $Feed['person_names'];
            $personRoles = $Feed['person_roles'] ?? [];
            $personURLs = $Feed['person_urls'] ?? [];
            $linkURLs = $Feed['link_urls'] ?? [];

            foreach ($personNames as $i => $name) {
                if ($name == '') continue;
                $channel_credits[] = [
                    'name' => $name,
                    'role' => $personRoles[$i] ?? '',
                    'person_url' => $personURLs[$i] ?? '',
                    'link_url' => $linkURLs[$i] ?? ''
                ];
            }
        }

        // RSS output
        foreach ($channel_credits as $credit) {
            if (empty($credit['name'])) continue;

            echo "\t<podcast:person";
            if (!empty($credit['role'])) {
                echo " role=\"" . htmlspecialchars($credit['role']) . "\"";
            }
            if (!empty($credit['person_url'])) {
                echo " img=\"" . htmlspecialchars($credit['person_url']) . "\"";
            }
            if (!empty($credit['link_url'])) {
                echo " href=\"" . htmlspecialchars($credit['link_url']) . "\"";
            }
            echo ">" . htmlspecialchars($credit['name']) . "</podcast:person>" . PHP_EOL;

        }

        echo "\t<podcast:podping usesPodping=\"true\" />" . PHP_EOL;

        require_once('uuid5.class.php');
        // This will be the same every time, but moved this logic into here to expedite removal of duplicate GUIDs
        $guidFeedURL = str_replace("http://", "", str_replace("https://", "", $feed_url));
        $guidFeedURL = rtrim($guidFeedURL,"/");
        $guid = UUID::v5('ead4c236-bf58-58c6-a2c6-a6b28d128cb6', $guidFeedURL);
        if (UUID::is_valid($guid)) {
            $Feed['podcast_guid'] = $guid;
        }
        if (!empty($Feed['guid_override_check']) && !empty($Feed['guid_override']))
            echo "\t<podcast:guid>".$Feed['guid_override']."</podcast:guid>" . PHP_EOL;
        elseif (isset($Feed['podcast_guid']) && UUID::is_valid($Feed['podcast_guid']) && $guidFeedURL != '')
            echo "\t<podcast:guid>".$Feed['podcast_guid']."</podcast:guid>" . PHP_EOL;

        if (isset($Feed['live_item']) && $Feed['live_item']['enabled'] == '1' && UUID::is_valid($Feed['live_item']['guid'])) {
            $liveItem = $Feed['live_item'];
            $tzName = timezone_name_from_abbr($liveItem['timezone']);

            $status = strtolower($liveItem['status']);
            $startArr = explode('T', $liveItem['start_date_time']);
            $startDate = new DateTime($startArr[0] . ' ' . $startArr[1], new DateTimeZone($tzName));
            $start = $startDate->format('c');

            $endArr = explode('T', $liveItem['end_date_time']);
            $endDate = new DateTime($endArr[0] . ' ' . $endArr[1], new DateTimeZone($tzName));
            $end = $endDate->format('c');
            echo "\t<podcast:liveItem status=\"$status\" start=\"$start\" end=\"$end\">" . PHP_EOL;
            echo "\t\t<title>".esc_html($liveItem['title'])."</title>" . PHP_EOL;
            echo "\t\t<guid isPermalink=\"false\">".$liveItem['guid']."</guid>" . PHP_EOL;

            if (!empty($liveItem['description']))
                echo "\t\t<description>".esc_html($liveItem['description'])."</description>" . PHP_EOL;

            if (!empty($liveItem['coverart_link']))
                echo "\t\t<podcast:images srcset=\"".($liveItem['cover_art'] ?? '')." 1400w\" />" . PHP_EOL;


            $EnclosureAttr = 'url="'.$liveItem['stream_link'].'" ';
            $EnclosureAttr .= 'length="5242880" ';
            $EnclosureAttr .= 'type="'.$liveItem['stream_type'].'"';

            echo "\t\t<enclosure $EnclosureAttr />" . PHP_EOL;
            echo "\t\t<podcast:alternateEnclosure type=\"".$liveItem['stream_type']."\" length=\"5242880\">" . PHP_EOL;
            echo "\t\t\t<podcast:source uri=\"".$liveItem['stream_link']."\" />" . PHP_EOL;
            echo "\t\t</podcast:alternateEnclosure>" . PHP_EOL;


            if (!empty($liveItem['episode_link']))
                echo "\t\t<link>".$liveItem['episode_link']."</link>" . PHP_EOL;

            echo "\t\t<podcast:contentLink href=\"".$liveItem['fallback_link']."\">Listen Live!</podcast:contentLink>" . PHP_EOL;
            echo "\t\t<podcast:timezone>".$liveItem['timezone']."</podcast:timezone>" . PHP_EOL;
            echo "\t</podcast:liveItem>" . PHP_EOL;
        }

        if( !empty($Feed['itunes_url']) || !empty($Feed['blubrry_url']) || !empty($Feed['tunein_url']) || !empty($Feed['spotify_url']) ) {
            echo "\t<rawvoice:subscribe feed=\"";
            self_link();
            echo '"';

            // Subscribe page // empty($FeedSettings['subscribe_page_link_href']) && empty($FeedSettings['subscribe_page_link_id'])
            if( !empty($Feed['subscribe_page_link_id']) ) {
                $link = get_page_link($Feed['subscribe_page_link_id']);
                if( !empty($link) ) {
                    echo " html=\"". htmlspecialchars( $link ) .'"';
                }
            } else if( !empty($Feed['subscribe_page_link_href']) ) {
                echo " html=\"". htmlspecialchars( $Feed['subscribe_page_link_href'] ) .'"';
            }

            if( !empty($Feed['itunes_url']) )
                echo " itunes=\"". htmlspecialchars( $Feed['itunes_url'] ) .'"';
            if( !empty($Feed['blubrry_url']) && !empty($General['blubrry_hosting']) )
                echo " blubrry=\"". htmlspecialchars( $Feed['blubrry_url'] ) .'"';
            if( !empty($Feed['tunein_url']) )
                echo " tunein=\"". htmlspecialchars( $Feed['tunein_url'] ) .'"';
            if( !empty($Feed['spotify_url']) )
                echo " spotify=\"". htmlspecialchars( $Feed['spotify_url'] ) .'"';
            if( !empty($Feed['amazon_url']) )
                echo " amazon_music=\"". htmlspecialchars( $Feed['amazon_url'] ) .'"';
            if( !empty($Feed['pcindex_url']) )
                echo " pcindex=\"". htmlspecialchars( $Feed['pcindex_url'] ) .'"';
            if( !empty($Feed['iheart_url']) )
                echo " iheart=\"". htmlspecialchars( $Feed['iheart_url'] ) .'"';
            if( !empty($Feed['pandora_url']) )
                echo " pandora=\"". htmlspecialchars( $Feed['pandora_url'] ) .'"';
            if( !empty($Feed['deezer_url']) )
                echo " deezer=\"". htmlspecialchars( $Feed['deezer_url'] ) .'"';
            if( !empty($Feed['jiosaavn_url']) )
                echo " jiosaavn=\"". htmlspecialchars( $Feed['jiosaavn_url'] ) .'"';
            if( !empty($Feed['podchaser_url']) )
                echo " podchaser=\"". htmlspecialchars( $Feed['podchaser_url'] ) .'"';
            if( !empty($Feed['gaana_url']) )
                echo " gaana=\"". htmlspecialchars( $Feed['gaana_url'] ) .'"';
            if( !empty($Feed['anghami_url']) )
                echo " anghami=\"". htmlspecialchars( $Feed['anghami_url'] ) .'"';
            if( !empty($Feed['youtube_url']) )
                echo " youtube=\"". htmlspecialchars( $Feed['youtube_url'] ) .'"';
            echo "></rawvoice:subscribe>".PHP_EOL;
        }
    }
}

add_action('rss2_head', 'powerpress_rss2_head');
add_action('rss2_head_powerpress', 'powerpress_rss2_head');

function powerpress_rss2_item()
{
    global $post, $powerpress_feed;

    // disable php notices inside feeds
    error_reporting(0);

    // are we processing a feed that powerpress should handle
    if( !powerpress_is_podcast_feed() )
        return;

    if( function_exists('post_password_required') )
    {
        if( post_password_required($post) )
            return;
    }

    // Check and see if we're working with a podcast episode
    $custom_enclosure = false;
    if( powerpress_is_custom_podcast_feed() && get_query_var('feed') !== 'podcast' && !is_category() && !is_tax() && !is_tag() )
    {
        $EpisodeData = powerpress_get_enclosure_data($post->ID, get_query_var('feed') );
        $custom_enclosure = true;
    }
    else
    {
        $EpisodeData = powerpress_get_enclosure_data($post->ID, 'podcast');
        if( !$EpisodeData && !empty($powerpress_feed['process_podpress']) )
        {
            $EpisodeData = powerpress_get_enclosure_data_podpress($post->ID);
            $custom_enclosure = true;
        }
    }

    // No episode data to include
    if( empty($EpisodeData) || empty($EpisodeData['url']) || $EpisodeData['url'] == 'no' )
        return;

    // If enclosure not added, check to see why...
    if( defined('POWERPRESS_ENCLOSURE_FIX') && POWERPRESS_ENCLOSURE_FIX && !$custom_enclosure && $GLOBALS['powerpress_rss_enclosure_post_id'] != $post->ID )
    {
        $enclosure_in_wp = apply_filters('rss_enclosure', '<enclosure url="' . trim(htmlspecialchars($EpisodeData['url']) . '" length="' . $EpisodeData['size'] . '" type="' . $EpisodeData['type'] . '" />' . "\n") );
        if( !$enclosure_in_wp )
            $custom_enclosure = true;
    }

    // Lets print the enclosure tag
    if( $custom_enclosure ) // We need to add the enclosure tag here...
    {
        if( empty($EpisodeData['size']) )
            $EpisodeData['size'] = 5242880; // Use the dummy 5MB size since we don't have a size to quote

        // encode htmlspecialchars if necessary
        $decoded = htmlspecialchars_decode($EpisodeData['url']);
        if (strlen($decoded) != strlen($EpisodeData['url'])) {
            // already encoded
            $media_url = $EpisodeData['url'];
        } else {
            // might need encoded/no risk of double encoding
            $media_url = htmlspecialchars($EpisodeData['url']);
        }

        echo "\t\t" . sprintf('<enclosure url="%s" length="%d" type="%s" />%s',
                powerpress_url_in_feed(trim($media_url)),
                trim($EpisodeData['size']),
                trim($EpisodeData['type']),
                PHP_EOL);
    }

    if (!empty($EpisodeData['alternate_enclosure'])) {
        $episode_str = '';

        foreach ($EpisodeData['alternate_enclosure'] as $alternate_enclosure) {

            $episode_str = '';
            $episode_str .= "\t\t<podcast:alternateEnclosure ";
            // support both 'length' (new) and 'size' (legacy) field names
            $alt_length = $alternate_enclosure['length'] ?? $alternate_enclosure['size'] ?? 0;
            if (!empty($alt_length) && $alt_length > 0) {
                $episode_str .= ' length="' . esc_attr($alt_length) . '"';
            }

            if (!empty($alternate_enclosure['type'])){
                $episode_str .= ' type="' . esc_attr($alternate_enclosure['type']) . '"';
            }

            if (!empty($alternate_enclosure['height'])) {
                $episode_str .= ' height="' . esc_attr($alternate_enclosure['height']) . '"';
            }

            if (!empty($alternate_enclosure['title'])) {
                $episode_str .= ' title="' . esc_attr($alternate_enclosure['title']) . '"';
            }

            if (!empty($alternate_enclosure['lang'])) {
                $episode_str .= ' lang="' . esc_attr($alternate_enclosure['lang']) . '"';
            }

            if (!empty($alternate_enclosure['rel'])) {
                $episode_str .= ' rel="' . esc_attr($alternate_enclosure['rel']) . '"';
            }

            if (!empty($alternate_enclosure['codecs'])) {
                $episode_str .= ' codecs="' . esc_attr($alternate_enclosure['codecs']) . '"';
            }

            if (!empty($alternate_enclosure['bitrate'])) {
                $episode_str .= ' bitrate="' . esc_attr($alternate_enclosure['bitrate']) . '"';
            }

            if (!empty($alternate_enclosure['is_default'])) {
                if ($alternate_enclosure['is_default']) {
                    $episode_str .= ' default="true"';
                }
            }
            $episode_str .= ">\n";

            // Process Alternate Enclosure's URI values if present
            $episode_str .= "\t\t\t" . sprintf('<podcast:source uri="%s" contentType="%s"/>%s',
                    powerpress_url_in_feed(trim(htmlspecialchars($alternate_enclosure['url']))),
                    trim(htmlspecialchars($alternate_enclosure['type'])),
                    PHP_EOL);

            if (!empty($alternate_enclosure['uris']) && is_array($alternate_enclosure['uris'])) {
                foreach ($alternate_enclosure['uris'] as $uri_data) {
                    $uri_url = !empty($uri_data['uri']) ? trim(htmlspecialchars($uri_data['uri'])) : '';
                    if (empty($uri_url)) continue;

                    $uri_type = trim(htmlspecialchars(powerpress_get_contenttype($uri_url)));
                    if ($uri_url === trim(htmlspecialchars($EpisodeData['url'])) || $uri_type === '') continue;

                    $episode_str .= "\t\t\t" . sprintf('<podcast:source uri="%s" contentType="%s"/>%s ', esc_url(powerpress_url_in_feed($uri_url)), $uri_type, PHP_EOL);
                }
            }
            $episode_str .= "\t\t" . sprintf('</podcast:alternateEnclosure>%s', PHP_EOL);
            echo $episode_str;
        }
    }

    if (!empty($EpisodeData['content_link'])) {
        foreach ($EpisodeData['content_link'] as $i => $content_link) {
            $url = htmlspecialchars($content_link['url']);
            $label = htmlspecialchars($content_link['label'] ?? '');

            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $href_string = 'href="' . trim($url) . '"';
                echo "\t\t" . sprintf('<podcast:contentLink %s>%s</podcast:contentLink>%s',
                        $href_string,
                        $label,
                        PHP_EOL);
            }
        }
    }


    $author = $powerpress_feed['itunes_talent_name'];
    if( isset($powerpress_feed['itunes_author_post']) )
        $author = get_the_author();

    $explicit = $powerpress_feed['explicit'];
    $summary = '';
    $subtitle = '';
    $block = false;
    $cc = false;

    if( isset( $EpisodeData['summary'] )  && strlen($EpisodeData['summary']) > 1 )
        $summary = $EpisodeData['summary'];
    // if( isset( $EpisodeData['subtitle'] )  && strlen($EpisodeData['subtitle']) > 1 )
        // $subtitle = $EpisodeData['subtitle'];
    if( isset( $EpisodeData['explicit'] ) && is_numeric($EpisodeData['explicit']) )
    {
        // switching from 'not set' 'yes' 'clean' to 'true' 'false'--for backwards compatibility, 'not set' will now be 'false'
        $explicit_array = array("false", "true", "false");
        $explicit = $explicit_array[$EpisodeData['explicit']];
    }

    if( !empty( $EpisodeData['author'] ) )
        $author = $EpisodeData['author'];
    if( !empty( $EpisodeData['block'] ) )
        $block = 'yes';
    if( !empty( $EpisodeData['cc'] ) )
        $cc = 'yes';

    $General = get_option('powerpress_general', array());
    // $summary_cdata = ( !empty( $General['itunes_cdata'] ) ? true : false );

    if ( empty ($General['suppress_unused_item_tags']) || !$General['suppress_unused_item_tags']) {
        if (empty($subtitle)) {
            $subtitle = powerpress_get_the_exerpt(false, !empty($General['feed_action_hook']));
        }

        // If no summary specified and we have enhanced summary enabled...
        // if (empty($summary) && !empty($powerpress_feed['enhance_itunes_summary'])) {
        //     $summary = powerpress_enhanced_itunes_summary(!empty($General['feed_action_hook']));
        //     if (!empty($summary)) {
        //         $summary_cdata = true; // Always use CDATA for enhanced iTunes summary
        //     }
        // }

        if (empty($summary)) { // Backwards compatibility with PodPress, the excerpt is used as the itunes summary if set
            $summary = powerpress_get_the_exerpt(true, !empty($General['feed_action_hook'])); // Will call powerpress_get_the_content(true) if the excerpt does not exist
        }

        // itunes:subtitle and itunes:summary deprecated by Apple
        // if (!empty($subtitle)) {
        //     echo "\t\t<itunes:subtitle>" . powerpress_format_itunes_value($subtitle, 'subtitle') . '</itunes:subtitle>' . PHP_EOL;
        // }

        // if (!empty($summary)) {
        //     if ($summary_cdata) {
        //         echo "\t\t<itunes:summary><![CDATA[" . powerpress_format_itunes_value($summary, 'summary', true) . ']]></itunes:summary>' . PHP_EOL;
        //     } else {
        //         echo "\t\t<itunes:summary>" . powerpress_format_itunes_value($summary, 'summary') . '</itunes:summary>' . PHP_EOL;
        //     }
        // }

        if( !empty($author) ) {
            echo "\t\t<itunes:author>" . esc_html($author) . '</itunes:author>'.PHP_EOL;
        }
    }

    // itunes episode image
    if( !empty( $EpisodeData['itunes_image']) ) {
        echo "\t\t".'<itunes:image href="' . esc_attr( powerpress_url_in_feed(str_replace(' ', '+', $EpisodeData['itunes_image'])), 'double') . '" />'.PHP_EOL;
    } else if( !empty($powerpress_feed['itunes_image']) ) {
        echo "\t\t".'<itunes:image href="' . esc_attr( powerpress_url_in_feed(str_replace(' ', '+', $powerpress_feed['itunes_image'])), 'double') . '" />'.PHP_EOL;
    }

    if( !empty($EpisodeData['season']) ) {
        echo "\t\t".'<itunes:season>'. esc_html($EpisodeData['season']) .'</itunes:season>'.PHP_EOL;
        echo "\t\t".'<podcast:season>'. esc_html($EpisodeData['season']) .'</podcast:season>'.PHP_EOL;
    }

    if( !empty($EpisodeData['episode_no']) ) {
        echo "\t\t".'<itunes:episode>'. esc_html(floor($EpisodeData['episode_no'])) .'</itunes:episode>'.PHP_EOL;
        if (!empty($EpisodeData['episode_no_display'])) {
            echo "\t\t" . '<podcast:episode display="' . esc_html($EpisodeData['episode_no_display']) . '">' . esc_html(floor($EpisodeData['episode_no'])) . '</podcast:episode>' . PHP_EOL;
        } else {
            echo "\t\t" . '<podcast:episode>' . esc_html(floor($EpisodeData['episode_no'])) . '</podcast:episode>' . PHP_EOL;
        }
    }

    // TXT Tag
    if ( !empty($EpisodeData['txt_tag']) ) {
        foreach ($EpisodeData['txt_tag'] as $tag) {
            if (empty($tag['tag'])) {
                continue;
            }
            echo "\t\t<podcast:txt";

            if (!empty($tag['purpose'])) {
                $tag_purpose = esc_html($tag['purpose']);
                echo " purpose=\"" . $tag_purpose . "\">";
            } else {
                echo ">";
            }

            $tag_content = esc_html(trim($tag['tag']));
            echo $tag_content . "</podcast:txt>" . PHP_EOL;
        }
    }

    if( !empty($EpisodeData['episode_title']) ) {
        echo "\t\t".'<itunes:title>'. esc_html($EpisodeData['episode_title']) .'</itunes:title>'.PHP_EOL;
    }

    if(empty($EpisodeData['episode_type'])) {
        $EpisodeData['episode_type'] = 'full';
    }
    echo "\t\t".'<itunes:episodeType>'. esc_html($EpisodeData['episode_type']) .'</itunes:episodeType>'.PHP_EOL;

    // episode explicit only outputs when overriding channel explicit 
    // clean channel+explicit episode | explicit channel + clean episode
    if( !empty($explicit) && $explicit != $powerpress_feed['explicit'] ) {
        echo "\t\t<itunes:explicit>" . $explicit . '</itunes:explicit>'.PHP_EOL;
    }

    if( !empty($EpisodeData['duration']) && preg_match('/^(\d{1,2}:){0,2}\d{1,2}$/i', ltrim($EpisodeData['duration'], '0:') ) ) { // Include duration if it is valid
        echo "\t\t<itunes:duration>" . ltrim($EpisodeData['duration'], '0:') . '</itunes:duration>'.PHP_EOL;
    }

    if( $block && $block == 'yes' ) {
        echo "\t\t<itunes:block>yes</itunes:block>".PHP_EOL;
    }

    if ( empty ($General['suppress_unused_item_tags']) || !$General['suppress_unused_item_tags']) {
        if ($cc && $cc == 'yes') {
            echo "\t\t<itunes:isClosedCaptioned>yes</itunes:isClosedCaptioned>" . PHP_EOL;
        }
    }

    if( !empty($powerpress_feed['itunes_feature']) ) { // We are using the itunes:order option to feature a specific episode.
        // Skip inserting the order tag
    } else {
        if( isset( $EpisodeData['order'] ) && is_numeric( $EpisodeData['order'] ) )
            echo "\t\t<itunes:order>". $EpisodeData['order'] ."</itunes:order>".PHP_EOL;
    }

    // Podcast index tags:
    if (!empty($EpisodeData['pci_transcript']) && !empty($EpisodeData['pci_transcript_url'])) {
        echo "\t\t<podcast:transcript url=\"" . $EpisodeData['pci_transcript_url'] . "\"";
        $transcript_type = powerpress_get_contenttype($EpisodeData['pci_transcript_url']);
        if (!empty($EpisodeData['pci_transcript_language'])) {
            echo " language=\"" . $EpisodeData['pci_transcript_language'] . "\"";
        }
        if (!empty($transcript_type)) {
            echo " type=\"" . $transcript_type . "\" rel=\"captions\" />".PHP_EOL;
        } else {
            echo " type=\"text/plain\" rel=\"captions\" />".PHP_EOL;
        }
    }
    if (!empty($EpisodeData['pci_chapters']) && !empty($EpisodeData['pci_chapters_url'])) {
        echo "\t\t<podcast:chapters url=\"" . $EpisodeData['pci_chapters_url'] . "\" type=\"application/json+chapters\" />".PHP_EOL;
    }

    if (!empty($EpisodeData['disable_episode_comments'])) {
        echo "\t\t<podcast:socialInteract protocol=\"disabled\" />" . PHP_EOL;
    } elseif (!empty($EpisodeData['social_interact']) && is_array($EpisodeData['social_interact'])) {
        foreach ($EpisodeData['social_interact'] as $social_interact) {
            // Skip Empty Tags
            if (empty($social_interact['uri']) || empty($social_interact['protocol']) || $social_interact['protocol'] === 'disabled') {
                continue;
            }

            echo "\t\t<podcast:socialInteract";

            // If disabled skip the other options
            if ($social_interact['protocol'] === 'disabled') {
                echo " protocol=\"disabled\" />" . PHP_EOL;
                continue;
            } else {
                $protocol = $social_interact['protocol'];
                echo " protocol=\"" . esc_attr($social_interact['protocol']) . "\"";
            }

            if (!empty($social_interact['uri'])) {
                echo " uri=\"" . esc_attr($social_interact['uri']) . "\"";
            }

            if (!empty($social_interact['account_id'])) {
                echo " accountId=\"" . esc_attr($social_interact['account_id']) . "\"";
            }

            if (!empty($social_interact['accountUrl'])) {
                echo " accountUrl=\"" . esc_attr($social_interact['accountUrl']) . "\"";
            }

            if (!empty($social_interact['priority'])) {
                echo " priority=\"" . esc_attr($social_interact['priority']) . "\"";
            }

            echo " />" . PHP_EOL;
        }
    } elseif (!empty($EpisodeData['social_interact_uri'])) {
        //      Legacy social_interact handler
        if (!empty($EpisodeData['social_interact_account_id']))
            echo "\t\t<podcast:socialInteract uri=\"" . esc_attr($EpisodeData['social_interact_uri']) . "\" protocol=\"".$EpisodeData['social_interact_protocol']."\" accountId=\"".esc_attr($EpisodeData['social_interact_account_id'])."\" />".PHP_EOL;
        else
            echo "\t\t<podcast:socialInteract uri=\"" . esc_attr($EpisodeData['social_interact_uri']) . "\" protocol=\"".$EpisodeData['social_interact_protocol']."\" />".PHP_EOL;
    }

    // <podcast:funding>
    if (!empty($EpisodeData['donate_url'])) {
        echo "\t\t<podcast:funding url=\"".esc_attr($EpisodeData['donate_url'])."\">". (!empty($EpisodeData['donate_label']) ? esc_html($EpisodeData['donate_label']) : '')."</podcast:funding>".PHP_EOL;
    } 
    // legacy naming convention
    else if (!empty($EpisodeData['funding_url'])) {
        echo "\t\t<podcast:funding url=\"".esc_attr($EpisodeData['funding_url'])."\">".esc_html($EpisodeData['funding_label'])."</podcast:funding>".PHP_EOL;
    }

    // <podcast:license>
    if( isset( $EpisodeData['copyright'] ) && strlen($EpisodeData['copyright']) > 1 ) {
        if ( isset( $EpisodeData['copyright_url'] ) && strlen($EpisodeData['copyright_url']) > 1 ) {
            echo "\t\t".'<podcast:license url="' . esc_attr(powerpress_url_in_feed($EpisodeData['copyright_url'])) . '">'. esc_html($EpisodeData['copyright']) . '</podcast:license>'.PHP_EOL;
        } else {
            echo "\t\t".'<podcast:license>'. esc_html($EpisodeData['copyright']) . '</podcast:license>'.PHP_EOL;
        }
    }
    
    // inheritence check
    $credits = [];
    if (!empty($EpisodeData['inherit_channel_credits'])) {
        $Feed = get_option('powerpress_feed', array());
        
        if (!empty($Feed['credits'])) {
            $credits = $Feed['credits'];
        } else if (!empty($Feed['person_names'])) {
            foreach ($Feed['person_names'] as $i => $name) {
                if ($name === '') continue;
                $credits[] = [
                    'name' => $name,
                    'role' => $Feed['person_roles'][$i] ?? '',
                    'person_url' => $Feed['person_urls'][$i] ?? '',
                    'link_url' => $Feed['link_urls'][$i] ?? ''
                ];
            }
        }
    }
    // load episode credits 
    $episode_credits = [];
    // nested
    if (!empty($EpisodeData['credits'])) {
        $episode_credits = $EpisodeData['credits'];
    } 
    // parallelized (legacy)
    else if (!empty($EpisodeData['person_names'])) {
        foreach ($EpisodeData['person_names'] as $i => $name) {
            if ($name === '') continue;
            $episode_credits[] = [
                'name' => $name,
                'role' => $EpisodeData['person_roles'][$i] ?? '',
                'person_url' => $EpisodeData['person_urls'][$i] ?? '',
                'link_url' => $EpisodeData['link_urls'][$i] ?? ''
            ];
        }
    }
    // print to feed
    $credits = array_merge($credits, $episode_credits);
    foreach ($credits as $credit) {
        if (empty($credit['name'])) continue;

        echo "\t\t<podcast:person";
        if (!empty($credit['role'])) {
            echo " role=\"" . htmlspecialchars($credit['role']) . "\"";
        }
        if (!empty($credit['person_url'])) {
            echo " img=\"" . htmlspecialchars($credit['person_url']) . "\"";
        }
        if (!empty($credit['link_url'])) {
            echo " href=\"" . htmlspecialchars($credit['link_url']) . "\"";
        }
        echo ">" . htmlspecialchars($credit['name']) . "</podcast:person>" . PHP_EOL;
    }

    if (!empty($EpisodeData['soundbites']) && is_array($EpisodeData['soundbites'])) {
        // nested
        foreach ($EpisodeData['soundbites'] as $soundbite) {
            $start = $soundbite['start'] ?? '';
            $duration = $soundbite['duration'] ?? '';
            $title = $soundbite['title'] ?? '';
            
            $float_start = (float) $start;
            $is_string_float = (strval($float_start) == $start);
            if ($start == "" || !$is_string_float)
                continue;
        
            $float_duration = (float) $duration;
            $is_string_float = (strval($float_duration) == $duration);
            if ($duration == "" || !$is_string_float)
                continue;
            
            $attrStr = ' startTime="' . $start . '"';
            $attrStr .= ' duration="' . $duration . '"';
            echo "\t\t<podcast:soundbite$attrStr>" . esc_html($title) . "</podcast:soundbite>" . PHP_EOL;
        }
    } elseif (!empty($EpisodeData['soundbite_starts'])) {
        // parallelized (legacy)
        $soundbiteStarts = $EpisodeData['soundbite_starts'];
        $soundbiteDurations = $EpisodeData['soundbite_durations'];
        $soundbiteTitles = $EpisodeData['soundbite_titles'];
        
        for ($i = 0; $i < count($soundbiteStarts); $i++) {
            $start = $soundbiteStarts[$i];
            $float_start = (float) $start;
            $is_string_float = (strval($float_start) == $start);
            if ($start == "" || !$is_string_float)
                continue;
            
            $duration = $soundbiteDurations[$i];
            $float_duration = (float) $duration;
            $is_string_float = (strval($float_duration) == $duration);
            if ($duration == "" || !$is_string_float)
                continue;
            
            $attrStr = ' startTime="' . $start . '"';
            $attrStr .= ' duration="' . $duration . '"';
            $title = $soundbiteTitles[$i];
            echo "\t\t<podcast:soundbite$attrStr>" . esc_html($title) . "</podcast:soundbite>" . PHP_EOL;
        }
    }


    if( !empty($EpisodeData['location']) ) {
        $locations = [];
        
        if (is_array($EpisodeData['location'])) {
            $first_location = reset($EpisodeData['location']);
            
            if (is_array($first_location)) {
                foreach ($EpisodeData['location'] as $location_item) {
                    if (empty($location_item) || !is_array($location_item)) {
                        continue;
                    }
                    
                    $address = '';
                    if (!empty($location_item['location'])) $address = $location_item['location']; 
                    elseif (!empty($location_item['address'])) $address = $location_item['address']; 
                    
                    if (!empty($address)) {
                        $locations[] = [
                            'address' => $address,
                            'geo' => $location_item['geo'] ?? $location_item['pci_geo'] ?? '',
                            'osm' => $location_item['osm'] ?? $location_item['pci_osm'] ?? '',
                            'rel' => $location_item['rel'] ?? $location_item['pci_rel'] ?? '1',
                            'country' => $location_item['country'] ?? $location_item['pci_country'] ?? '',
                        ];
                    }
                }
            } else {
                // legacy
                foreach ($EpisodeData['location'] as $i => $address) {
                    if (!empty($address)) {
                        $locations[] = [
                            'address' => $address,
                            'geo' => $EpisodeData['pci_geo'][$i] ?? '',
                            'osm' => $EpisodeData['pci_osm'][$i] ?? '',
                            'rel' => $EpisodeData['pci_rel'][$i] ?? '1',
                            'country' => $EpisodeData['pci_country'][$i] ?? '',
                        ];
                    }
                }
            }
        }
        foreach ($locations as $location) {
            if (empty($location['address'])) {
                continue;
            }

            echo "\t\t<podcast:location";

            if (!empty($location['geo']))         echo ' geo="' . htmlspecialchars($location['geo']) . '"';
            if (!empty($location['osm']))         echo ' osm="' . htmlspecialchars($location['osm']) . '"';
            if (!empty($location['rel']))         echo ' rel="' . ($location['rel'] == '1' ? 'subject' : 'creator') . '"';
            if (!empty($location['country']))     echo ' country="' . htmlspecialchars($location['country']) . '"';

            echo ">" . htmlspecialchars($location['address']) . "</podcast:location>" . PHP_EOL;
        }
    }

    $recipients = [];
    // v4v channel level inheritence
    if (isset($EpisodeData['channel_level_recipients'])) {
        $Feed = get_option('powerpress_feed', array());
        // nested format
        if (!empty($Feed['value_recipients'])) {
            foreach ($Feed['value_recipients'] as $recipient) {
                $pubkey = trim($recipient['pubkey'] ?? '');
                $split = (int)($recipient['split'] ?? 0);
                if ($pubkey === '' || $split <= 0) continue;

                $recipients[] = [
                    'lightning' => $recipient['lightning'] ?? $recipient['name'] ?? '',
                    'split' => $split,
                    'pubkey' => $pubkey,
                    'customKey' => $recipient['custom_key'] ?? $recipient['customKey'] ?? '',
                    'customValue' => $recipient['custom_value'] ?? $recipient['customValue'] ?? '',
                    'fee' => !empty($recipient['fee']) ? 'true' : 'false'
                ];
            }
        // legacy format
        } else if ((!empty($Feed['value_pubkey']) && is_array($Feed['value_pubkey'])) 
                && (!empty($Feed['value_split']) && is_array($Feed['value_split']))) {
                $pubKeys = (array)$Feed['value_pubkey'];
                $splits = (array)$Feed['value_split'];
                $lightnings = (array)($Feed['value_lightning'] ?? []);
                $customKeys = (array)($Feed['value_custom_key'] ?? []);
                $customVals = (array)($Feed['value_custom_value'] ?? []);
                $fees = (array)($Feed['value_is_fee'] ?? []);

                foreach ($pubKeys as $i => $pubkey) {
                    $pubkey = trim((string)$pubkey);
                    $split  = (int)($splits[$i] ?? 0);
                    if ($pubkey === '' || $split <= 0) continue;

                    $recipients[] = [
                        'lightning' => (string)($lightnings[$i] ?? ''),
                        'split' => $split,
                        'pubkey' => $pubkey,
                        'customKey' => (string)($customKeys[$i] ?? ''),
                        'customValue' => (string)($customVals[$i] ?? ''),
                        'fee' => (isset($fees[$i]) && $fees[$i] === 'true') ? 'true' : 'false',
                    ];
                }
        }
    }
    // nested
    if (isset($EpisodeData['value_recipients']) && is_array($EpisodeData['value_recipients'])) {
        foreach ($EpisodeData['value_recipients'] as $recipient) {
            $pubkey = trim($recipient['pubkey'] ?? '');
            $split = (int)($recipient['split'] ?? 0);
            if ($pubkey === '' || $split <= 0) continue;

            $recipients[] = [
                'lightning' => $recipient['lightning'] ?? $recipient['name'] ?? '',
                'split' => $split,
                'pubkey' => $pubkey,
                'customKey' => $recipient['custom_key'] ?? $recipient['customKey'] ?? '',
                'customValue'=> $recipient['custom_value'] ?? $recipient['customValue'] ?? '',
                'fee' => (isset($recipient['fee']) && $recipient['fee'] === 'true') ? 'true' : 'false',
            ];
        }
    // paralellized (legacy)
    } elseif (!empty($EpisodeData['value_pubkey']) && is_array($EpisodeData['value_pubkey'])
           && !empty($EpisodeData['value_split']) && is_array($EpisodeData['value_split'])) {

        $pubKeys = (array)$EpisodeData['value_pubkey'];
        $splits = (array)$EpisodeData['value_split'];
        $lightnings = (array)($EpisodeData['value_lightning'] ?? []);
        $customKeys = (array)($EpisodeData['value_custom_key'] ?? []);
        $customVals = (array)($EpisodeData['value_custom_value'] ?? []);
        $fees = (array)($EpisodeData['value_is_fee'] ?? []);

        foreach ($pubKeys as $i => $pubkey) {
            $pubkey = trim((string)$pubkey);
            $split  = (int)($splits[$i] ?? 0);
            if ($pubkey === '' || $split <= 0) continue;

            $recipients[] = [
                'lightning' => (string)($lightnings[$i] ?? ''),
                'split' => $split,
                'pubkey' => $pubkey,
                'customKey' => (string)($customKeys[$i] ?? ''),
                'customValue' => (string)($customVals[$i] ?? ''),
                'fee' => (isset($fees[$i]) && $fees[$i] === 'true') ? 'true' : 'false',
            ];
        }
    }

    // output podcast:value block if we have recipients OR vts with remote items
    $hasVts = isset($EpisodeData['vts_order']) && !empty($EpisodeData['vts_order']);
    if (!empty($recipients) || $hasVts) {
        // add blubrry fee recipient when there are other recipients
        if (!empty($recipients)) {
            $recipients[] = [
                'lightning' => 'blubrry@getalby.com',
                'split' => 3,
                'pubkey' => '03b8a595e4d8e19efa8faa3fbe2524b0d39f7c812415ff831d38a466a4a1bc888a',
                'customKey' => '696969',
                'customValue' => 'qAHJuqKLmMhTNFualcIj',
                'fee' => 'true',
            ];
        }

        echo "\t\t" . '<podcast:value type="lightning" method="keysend" suggested="0.00000005000">' . PHP_EOL;

        foreach ($recipients as $recipient) {
            $attr = 'type="node" split="' . (int)$recipient['split'] . '" address="' . htmlspecialchars($recipient['pubkey']) . '"';

            if (!empty($recipient['lightning'])) $attr .= ' name="' . htmlspecialchars($recipient['lightning']) . '"';
            if (!empty($recipient['customKey'])) $attr .= ' customKey="' . htmlspecialchars($recipient['customKey']) . '"';
            if (!empty($recipient['customValue'])) $attr .= ' customValue="' . htmlspecialchars($recipient['customValue']) . '"';
            if (!empty($recipient['fee']) && $recipient['fee'] === 'true') $attr .= ' fee="true"';

            echo "\t\t\t" . "<podcast:valueRecipient $attr/>" . PHP_EOL;
        }

        $feed_slug = get_query_var('feed');
        // if this is a blog feed, we need to access the settings associated to the podcast feed
        if (is_category() && $feed_slug == 'feed') {
            $feed_slug = 'podcast';
        }
        if ($hasVts) {
            $valueTimeSplits = get_option('vts_'.$feed_slug.'_'.get_the_ID());
            if (!is_array($valueTimeSplits))
                $valueTimeSplits = [];

            foreach ($EpisodeData['vts_order'] as $vts_id) {
                if (!isset($valueTimeSplits[$vts_id])) continue;
                
                $timeSplit = $valueTimeSplits[$vts_id];
                if (empty($timeSplit['duration'])) continue;

                $vtsAttrs = [
                    'startTime' => $timeSplit['start_time'] ?? 0,
                    'duration' => $timeSplit['duration']
                ];

                $recipientType = $timeSplit['recipient'] ?? 0;
                if ($recipientType == 0)
                    $vtsAttrs['remotePercentage'] = $timeSplit['remote_percent'] ?? 0;

                $vtsAttrParts = [];
                foreach ($vtsAttrs as $key => $value) {
                    $vtsAttrParts[] = $key.'="'.$value.'"';
                }

                echo "\t\t\t"."<podcast:valueTimeSplit ".implode(' ', $vtsAttrParts).">\n";

                if ($recipientType == 0) {
                    $recipientemoteItem = $timeSplit['remote_item'] ?? [];

                    if (!empty($recipientemoteItem['feed_guid'])) {
                        $attrs = ['feedGuid' => $recipientemoteItem['feed_guid']];

                        $itemGuid = $recipientemoteItem['item_guid'] ?? '';
                        if (!empty($itemGuid) && $itemGuid != 'none')
                            $attrs['itemGuid'] = $itemGuid;

                        if (!empty($recipientemoteItem['feed_link'])) {
                            $attrs['feedUrl'] = $recipientemoteItem['feed_link'];
                        }

                        if (!empty($recipientemoteItem['medium'])) {
                            $attrs['medium'] = $recipientemoteItem['medium'];
                        }

                        if (!empty($recipientemoteItem['item_title'])) {
                            $attrs['title'] = $recipientemoteItem['item_title'];
                        }

                        $attrParts = [];
                        foreach ($attrs as $key => $value) {
                            $attrParts[] = $key.'="'.htmlspecialchars($value).'"';
                        }

                        echo "\t\t\t\t"."<podcast:remoteItem ".implode(' ', $attrParts)."/>\n";
                    }
                } else {
                    $valueRecipients = $timeSplit['value_recipients'] ?? array();

                    foreach ($valueRecipients as $valueRecipient) {
                        if (empty($valueRecipient['pubkey'])) continue;
                        
                        $attrs = [
                            "type" => "node",
                            "split" => $valueRecipient['split'] ?? 0,
                            "address" => $valueRecipient['pubkey'],
                        ];

                        if (!empty($valueRecipient['lightning']))
                            $attrs['name'] = $valueRecipient['lightning'];

                        if (!empty($valueRecipient['custom_key']))
                            $attrs["customKey"] = $valueRecipient['custom_key'];

                        if (!empty($valueRecipient['custom_value']))
                            $attrs["customValue"] = $valueRecipient['custom_value'];

                        if (!empty($valueRecipient['value_is_fee']))
                            $attrs["value_is_fee"] = $valueRecipient['value_is_fee'];


                        $attrStr = "";
                        foreach ($attrs as $key => $value) {
                            $attrStr .= ' '.$key.'="'.$value.'"';
                        }

                        echo "\t\t\t\t"."<podcast:valueRecipient $attrStr/>\n";
                    }
                }

                echo "\t\t\t"."</podcast:valueTimeSplit>\n";
            }
        }

        echo "\t\t".'</podcast:value>'.PHP_EOL;
    }

    // RawVoice RSS Tags
    if( empty($powerpress_feed['feed_maximizer_on']) )
    {
        if( !defined('POWERPRESS_RAWVOICE_RSS') || POWERPRESS_RAWVOICE_RSS != false )
        {
            if( !empty($EpisodeData['ishd']) )
                echo "\t\t<rawvoice:isHD>yes</rawvoice:isHD>".PHP_EOL;;
            if( !empty($EpisodeData['image']) )
                echo "\t\t<rawvoice:poster url=\"". $EpisodeData['image'] ."\" />".PHP_EOL;
            if( !empty($EpisodeData['embed']) )
                echo "\t\t<rawvoice:embed>". htmlspecialchars($EpisodeData['embed']) ."</rawvoice:embed>".PHP_EOL;
            else if( !empty($powerpress_feed['podcast_embed_in_feed']) && function_exists('powerpress_generate_embed') )
            {
                $player = powerpressplayer_embedable($EpisodeData['url'], $EpisodeData);
                $embed_content = '';

                if( $player )
                    $embed_content = powerpress_generate_embed($player, $EpisodeData);
                if( $embed_content )
                    echo "\t\t<rawvoice:embed>". htmlspecialchars( $embed_content ) ."</rawvoice:embed>".PHP_EOL;
            }

            if( !empty($EpisodeData['webm_src']) )
            {
                echo "\t\t<rawvoice:webm src=\"". $EpisodeData['webm_src'] ."\"";
                if( $EpisodeData['webm_length'] )
                    echo " length=\"". $EpisodeData['webm_length'] ."\"";
                echo " type=\"video/webm\" />".PHP_EOL;
            }

            $GeneralSettings = get_option('powerpress_general', array());

            require_once(POWERPRESS_ABSPATH .'/powerpress-metamarks.php');
            powerpress_metamarks_print_rss2($EpisodeData);
        }
    }
}

add_filter('rss2_item', 'powerpress_rss2_item');
add_filter('rss2_item_powerpress', 'powerpress_rss2_item');

/*
This filter is only necessary for feeds that are not specifically for podcasting, e.g. a category feed that did not have category podcasting added to it
*/
function powerpress_filter_rss_enclosure($content)
{
    if( defined('PODPRESS_VERSION') || isset($GLOBALS['podcasting_player_id']) || isset($GLOBALS['podcast_channel_active']) || defined('PODCASTING_VERSION') ) {
        return $content; // Another podcasting plugin is enabled...
    }

    if( powerpress_is_custom_podcast_feed() && get_query_var('feed') !== 'podcast' && !is_category() && !is_tag() && !is_tax() )
        return ''; // We will handle this enclosure in the powerpress_rss2_item() function

    $match_count = preg_match('/\surl="([^"]*)"/', $content, $matches); // No URL found, weird
    if( count($matches) != 2)
        return $content;

    // Original Media URL
    $OrigURL = $matches[1];

    if( substr($OrigURL, 0, 5) != 'http:' && substr($OrigURL, 0, 6) != 'https:' )
        return ''; // The URL value is invalid

    global $post, $powerpress_rss_enclosure_post_id;
    if( empty($powerpress_rss_enclosure_post_id) )
        $powerpress_rss_enclosure_post_id = -1;

    if( $powerpress_rss_enclosure_post_id == $post->ID )
        return ''; // we've already included one enclosure, lets not allow anymore
    $powerpress_rss_enclosure_post_id = $post->ID;

    $EpisodeData = powerpress_get_enclosure_data($post->ID);

    // Modified Media URL
    $ModifiedURL = powerpress_url_in_feed($EpisodeData['url']); // powerpress_add_redirect_url($OrigURL);

    // Check that the content type is a valid one...
    $match_count = preg_match('/\stype="([^"]*)"/', $content, $matches);
    if( count($matches) > 1 && strstr($matches[1], '/') == false )
    {
        $ContentType = powerpress_get_contenttype($ModifiedURL);
        $content = str_replace("type=\"{$matches[1]}\"", "type=\"$ContentType\"", $content);
    }

    // Check that the content length is a digit greater that zero
    $match_count = preg_match('/\slength="([^"]*)"/', $content, $matches);
    if( count($matches) > 1 && empty($matches[1]) )
    {
        $content = str_replace("length=\"{$matches[1]}\"", "length=\"5242880\"", $content);
    }

    // encode htmlspecialchars if necessary
    $decoded = htmlspecialchars_decode($ModifiedURL);
    if (strlen($decoded) == strlen($EpisodeData['url'])) {
        // might need encoded/no risk of double encoding
        $ModifiedURL = htmlspecialchars($ModifiedURL);
    }

    // Replace the original url with the modified one...
    if( $OrigURL != $ModifiedURL )
        $content = str_replace($OrigURL, $ModifiedURL, $content);

    // add proper indentation for rss formatting
    return "\t\t" . trim($content) . "\n";
}


add_filter('rss_enclosure', 'powerpress_filter_rss_enclosure', 11);

function powerpress_bloginfo_rss($content, $field = '')
{
    $new_value = '';
    if( powerpress_is_custom_podcast_feed() )
    {
        if( is_category() ) {
            $Feed = get_option('powerpress_cat_feed_'.get_query_var('cat'), array() );
        }
        else if( is_tax() || is_tag() ) {
            global $powerpress_feed;
            if( !empty($powerpress_feed['term_taxonomy_id']) )
                $Feed = get_option('powerpress_taxonomy_'.$powerpress_feed['term_taxonomy_id'], array() );
        }
        else
        {
            global $powerpress_feed;

            if( !empty($powerpress_feed['post_type']) )
            {
                $feed_slug = get_query_var('feed');
                $PostTypeSettingsArray = get_option('powerpress_posttype_'.$powerpress_feed['post_type'], array() );
                if( !empty($PostTypeSettingsArray[ $feed_slug ]) )
                    $Feed = $PostTypeSettingsArray[ $feed_slug ];
            }
            else
            {
                $Feed = get_option('powerpress_feed_'.get_query_var('feed'), array() );
                if( empty($Feed) && get_query_var('feed') === 'podcast' )
                    $Feed = get_option('powerpress_feed', array());
            }
        }

        if( !empty($Feed) )
        {
            switch( $field )
            {
                case 'description': {
                    if( !empty($Feed['description']) )
                        $new_value = $Feed['description'];
                    else if( is_category() )
                    {
                        $category = get_category( get_query_var('cat') );
                        if( $category->description )
                            $new_value = $category->description;
                    }
                }; break;
                case 'url': {
                    // If the website URL is set for this podcast then lets use it...
                    if( !empty($Feed['url']) )
                        return trim($Feed['url']);

                    if( is_category() ) {
                        return get_category_link( get_query_var('cat') );
                    } else {
                        $urlTemp = '';
                        $blogHomepage = get_option('page_for_posts');
                        if( !empty($blogHomepage) ) {
                            $urlTemp = get_permalink( $blogHomepage );
                        }

                        if( empty($urlTemp) )
                            $urlTemp = get_bloginfo('url');
                        if( !empty($urlTemp) )
                            return $urlTemp;
                    }
                }; break;
                case 'name': { // As of wp 4.4+ title is handled by get_the_title_rss completely.
                    if( !empty($Feed['title']) )
                        $new_value = $Feed['title'];
                }; break;
                case 'language': {
                    // Get the feed language
                    $lang = '';
                    if( isset($Feed['rss_language']) && $Feed['rss_language'] != '' )
                        $lang = $Feed['rss_language'];
                    if( strlen($lang) == 5 )
                        $lang = substr($lang,0,3) .  strtoupper( substr($lang, 3) ); // Format example: en-US for English, United States
                    if( !empty($lang) )
                        return $lang;
                }; break;
            }
        }
    }

    if( !empty($new_value) )
    {
        $GeneralSettings = get_option('powerpress_general');
        // disable smart typography check
        if( empty($GeneralSettings['disable_wptexturize']) )
            $new_value = wptexturize($new_value);
        $new_value = convert_chars($new_value);
        // decode html entities before escaping so numeric entities like &#8212; render as actual chars
        if( !empty($GeneralSettings['disable_wptexturize']) )
            $new_value = html_entity_decode($new_value, ENT_QUOTES, 'UTF-8');
        // convert named HTML entities to numeric for XML compatibility (e.g. &copy; → &#169;)
        $new_value = ent2ncr($new_value);
        $new_value = esc_html($new_value);
        return $new_value;
    }

    return $content;
}

add_filter('get_bloginfo_rss', 'powerpress_bloginfo_rss', 10, 2);


function powerpress_wp_title_rss($title)
{
    if( version_compare($GLOBALS['wp_version'], '4.4', '>=' ) )
    {
        if( powerpress_is_custom_podcast_feed() )
        {
            if( is_category() ) {
                $Feed = get_option('powerpress_cat_feed_'.get_query_var('cat'), array() );
            }
            else if( is_tax() || is_tag() ) {
                global $powerpress_feed;
                if( !empty($powerpress_feed['term_taxonomy_id']) )
                    $Feed = get_option('powerpress_taxonomy_'.$powerpress_feed['term_taxonomy_id'], array() );
            }
            else
            {
                global $powerpress_feed;

                if( !empty($powerpress_feed['post_type']) )
                {
                    $feed_slug = get_query_var('feed');
                    if( !empty($feed_slug) ) {
                        $PostTypeSettingsArray = get_option('powerpress_posttype_'.$powerpress_feed['post_type'], array() );
                        if( !empty($PostTypeSettingsArray[ $feed_slug ]) )
                            $Feed = $PostTypeSettingsArray[ $feed_slug ];
                    }
                }
                else
                {
                    $feed_slug = get_query_var('feed');
                    $Feed = false;
                    if( !empty($feed_slug) ) {
                        $Feed = get_option('powerpress_feed_'.get_query_var('feed') );
                    }
                    if( empty($Feed) && get_query_var('feed') === 'podcast' )
                        $Feed = get_option('powerpress_feed');
                }
            }

            if( !empty($Feed) )
            {
                if( !empty($Feed['title']) )
                    return esc_html( $Feed['title'] );
            }
        }
    }
    else
    {
        if( powerpress_is_custom_podcast_feed() )
        {
            if( is_category() )
            {
                $Feed = get_option('powerpress_cat_feed_'.get_query_var('cat') );
                if( $Feed && isset($Feed['title']) && $Feed['title'] != '' )
                    return ''; // We alrady did a custom title, lets not add the category to it...
            }
            else
            {
                return ''; // It is not a category, lets not mess with our beautiful title then
            }
        }
    }

    return $title;
}

add_filter('get_wp_title_rss', 'powerpress_wp_title_rss');

function powerpress_the_title_rss($title)
{
    $new_title = $title;
    $GeneralSettings = get_option('powerpress_general');
    // If it is a custom podcast channel...
    if( !empty($GeneralSettings['seo_feed_title']) )
    {
        $feed_slug = 'podcast';
        // IF custom post type or channel, use that feed slug...
        if( get_query_var('feed') !== 'podcast' && !is_category() && !is_tax() && !is_tag() )
            $feed_slug = get_query_var('feed');

        // Get the episode specific title...
        $EpisodeData = powerpress_get_enclosure_data(get_the_ID(), $feed_slug);
        if( !empty($EpisodeData['feed_title']) )
        {
            $feed_title = ent2ncr( $EpisodeData['feed_title'] );
            $feed_title = strip_tags( $feed_title );
            $feed_title = esc_html( $feed_title );

            //switch( $GeneralSettings['custom_feed_title'] )
            switch( $GeneralSettings['seo_feed_title'] )
            {
                case 1: { // Replaces title
                    $new_title = $feed_title;
                }; break;
                case 2: { // Prefixes title
                    $new_title = $feed_title . ' ' . $title;
                }; break;
                case 3: { // Postfixes title
                    $new_title = $title . ' ' . $feed_title;
                }; break;
            }
        }
    }

    return $new_title;
}

add_filter('the_title_rss', 'powerpress_the_title_rss', 11);


function powerpress_feed_content_type($content_type = '', $feedslug = '')
{
    switch( $feedslug )
    {
        case 'rss':
        case 'rss2':
        case 'atom':
        case 'rdf': {
            // Do nothing, let WordPress take care of these
        }; break;
        case 'podcast': {
            // This one is ours!
            $content_type = 'application/rss+xml';
        }; break;
        default: { // Check for the custom podcast feeds
            $GeneralSettings = get_option('powerpress_general');
            if( !empty($GeneralSettings['custom_feeds'][ $feedslug ]) )
            {
                $content_type = 'application/rss+xml';
            }
            else if( !empty($GeneralSettings['posttype_podcasting']) )
            {
                // We need to look up these settings...
                $FeedSlugPostTypesArray = get_option('powerpress_posttype-podcasting');
                if( is_array($FeedSlugPostTypesArray) && !empty($FeedSlugPostTypesArray[ $feedslug ]) )
                {
                    $content_type = 'application/rss+xml';
                }
            }
        }
    }

    return $content_type;
}

add_filter( 'feed_content_type', 'powerpress_feed_content_type', 10, 2 );

function wpse_152316_wp_audio_extensions( $ext )
{
    remove_filter( current_filter(), __FUNCTION__ );
    $ext[] = '';
    return $ext;
}

/**
 * Allow unrecognized audio sources hosted on trusted hosts that use query strings on their podcast media.
 *
 * @see http://wordpress.stackexchange.com/a/152352/26350
 */

add_filter( 'wp_audio_shortcode_override',
    function( $html, $atts )
    {
        if (isset($atts['src'])) {
            $trusted_hosts_use_qstrings = array('traffic.libsyn.com', 'cdn.simplecast.com', 'buzzsprout.com', 'audioboom.com', 'mc.blubrry.com');
            foreach ($trusted_hosts_use_qstrings as $host) {
                if (strpos($atts['src'], $host) !== false) {
                    add_filter('wp_audio_extensions', 'wpse_152316_wp_audio_extensions');
                }
            }
        }
        return $html;
    }
    , PHP_INT_MAX, 2 );

// Following code only works for WP 3.3 or older. WP 3.4+ now uses the get_locale setting, so we have to override directly in the get_bloginfo_rss functoin.
if( version_compare($GLOBALS['wp_version'], '3.4', '<') )
{
    function powerpress_rss_language($value)
    {
        if( powerpress_is_custom_podcast_feed() )
        {
            global $powerpress_feed;
            if( $powerpress_feed && isset($powerpress_feed['rss_language']) && $powerpress_feed['rss_language'] != '' )
                $value = $powerpress_feed['rss_language'];
        }
        return $value;
    }

    add_filter('option_rss_language', 'powerpress_rss_language');
}

//filter to ensure that guid doesn't come up blank
function powerpress_the_guid($guid) {
    global $post;

    // Simple case, what is in the DB is better than an empty value
    if( empty($guid) && !empty($post->guid) ) {
        return $post->guid;
    }

    if( !empty($post->guid) ) {
        if( preg_match('/^https?:\/\//i', $post->guid, $matches) == false ) {
            $powerpressGuid = get_post_meta($post->ID, '_powerpress_guid', true);
            if( !empty($powerpressGuid) )
                return $powerpressGuid;
        }
    }

    return $guid;
}

function powerpress_do_podcast_feed($for_comments=false)
{
    global $wp_query, $powerpress_feed;

    powerpress_is_podcast_feed(); // Loads the feed settings if not already loaded...

    $GeneralSettings = get_option('powerpress_general');
    if( isset($GeneralSettings['premium_caps']) && $GeneralSettings['premium_caps'] )
    {
        $feed_slug = get_query_var('feed');

        if( $feed_slug != 'podcast' )
        {
            $FeedSettings = get_option('powerpress_feed_'.$feed_slug);
            if( !empty($FeedSettings['premium']) )
            {
                require_once( POWERPRESS_ABSPATH.'/powerpress-feed-auth.php');
                powerpress_feed_auth( $feed_slug );
            }
        }
    }

    // Use the template to gurantee future WordPress behavior
    if( defined('POWERPRESS_FEED_TEMPLATE') ) {
        load_template( POWERPRESS_FEED_TEMPLATE );
    } else {
        load_template( POWERPRESS_ABSPATH . '/feed-podcast.php' );
    }
}

function powerpress_template_redirect()
{
    if( is_feed() && powerpress_is_custom_podcast_feed() )
    {
        // clean any existing output buffers to rm \n or WS from themes/plugins/wp-config (externally introduced)
        // ensures our feed starts with no newlines
        while( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        if ( defined('WPSEO_VERSION') && version_compare(WPSEO_VERSION, '7.7',  '>=') && class_exists( 'WPSEO_Frontend' ) ) {
            $wpseo_frontend = WPSEO_Frontend::get_instance();
            if( !empty($wpseo_frontend) ) {
                remove_action( 'template_redirect', array( $wpseo_frontend, 'noindex_feed' ) );
            }
        }

        remove_action('template_redirect', 'ol_feed_redirect'); // Remove this action so feedsmith doesn't redirect
        global $powerpress_feed;
        if( !isset($powerpress_feed['feed_redirect_url']) )
            $powerpress_feed['feed_redirect_url'] = '';
        $recipientedirect_value = ( !empty($_GET['redirect'])? $_GET['redirect'] : false );
        $user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        if( is_array($powerpress_feed) && trim($powerpress_feed['feed_redirect_url']) != '' && !preg_match("/feedburner|feedsqueezer|feedvalidator/i", $user_agent ) && $recipientedirect_value != 'no' )
        {
            if (function_exists('status_header'))
                status_header( 301 );
            header("Location: " . trim($powerpress_feed['feed_redirect_url']));
            header("HTTP/1.1 301 Moved Permanently");
            exit();
        }
    }
}

add_action('template_redirect', 'powerpress_template_redirect', 0);


function powerpress_rewrite_rules_array($array)
{
    global $wp_rewrite;
    $settings = get_option('powerpress_general');

    $podcast_feeds = array('podcast'=>true);
    if( isset($settings['custom_feeds']) && is_array($settings['custom_feeds']) )
        $podcast_feeds = array_merge($settings['custom_feeds'], $podcast_feeds );

    $merged_slugs = '';
    foreach( $podcast_feeds as $feed_slug=> $feed_title )
    {
        if( $merged_slugs != '' )
            $merged_slugs .= '|';
        $merged_slugs .= $feed_slug;
    }

    // $wp_rewrite->index most likely index.php
    $new_array[ 'feed/('.$merged_slugs.')/?$' ] = $wp_rewrite->index. '?feed='. $wp_rewrite->preg_index(1);

    // If feature is not enabled, use the default permalinks
    if( empty($settings['permalink_feeds_only']) )
        return array_merge($new_array, $array);

    global $wpdb;
    reset($podcast_feeds);
    foreach( $podcast_feeds as $feed_slug=> $feed_title )
    {
        $page_name_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name = '".$feed_slug."'");
        if( $page_name_id )
        {
            $new_array[ $feed_slug.'/?$' ] = $wp_rewrite->index. '?pagename='. $feed_slug.'&page_id='.$page_name_id;
            unset($podcast_feeds[ $feed_slug ]);
            continue;
        }

        $category = get_category_by_slug($feed_slug);
        if( $category )
        {
            $new_array[ $feed_slug.'/?$' ] = $wp_rewrite->index. '?cat='. $category->term_id; // category_name='. $feed_slug .'&
            unset($podcast_feeds[ $feed_slug ]);
        }
    }

    if( count($podcast_feeds) > 0 )
    {
        reset($podcast_feeds);
        $recipientemaining_slugs = '';
        foreach( $podcast_feeds as $feed_slug=> $feed_title )
        {
            if( $recipientemaining_slugs != '' )
                $recipientemaining_slugs .= '|';
            $recipientemaining_slugs .= $feed_slug;
        }

        $new_array[ '('.$recipientemaining_slugs.')/?$' ] = $wp_rewrite->index. '?pagename='. $wp_rewrite->preg_index(1);
    }

    return array_merge($new_array, $array);
}

add_filter('rewrite_rules_array', 'powerpress_rewrite_rules_array');


function powerpress_pre_transient_rewrite_rules($recipienteturn_rules)
{
    global $wp_rewrite;
    $GeneralSettings = get_option('powerpress_general');
    if( !in_array('podcast', $wp_rewrite->feeds) )
        $wp_rewrite->feeds[] = 'podcast';

    if( $GeneralSettings && isset($GeneralSettings['custom_feeds']) && is_array($GeneralSettings['custom_feeds']) )
    {
        foreach( $GeneralSettings['custom_feeds'] as $feed_slug=> $null )
        {
            if( !in_array($feed_slug, $wp_rewrite->feeds) )
                $wp_rewrite->feeds[] = $feed_slug;
        }
    }

    return $recipienteturn_rules;
}

add_filter('pre_transient_rewrite_rules', 'powerpress_pre_transient_rewrite_rules');

function powerpress_init()
{
    // Translation support loaded:
    load_plugin_textdomain('powerpress', // domain / keyword name of plugin
        POWERPRESS_ABSPATH .'/languages', // Absolute path
        basename(POWERPRESS_ABSPATH).'/languages' ); // relative path in plugins folder

    /*
    ####
    # Defines that effect translation defined now:
    ####
    */
    // Set specific play and download labels for your installation of PowerPress
    if( !defined('POWERPRESS_LINKS_TEXT') )
        define('POWERPRESS_LINKS_TEXT', __('Podcast', 'powerpress') );
    if( !defined('POWERPRESS_DURATION_TEXT') )
        define('POWERPRESS_DURATION_TEXT', __('Duration', 'powerpress') );
    if( !defined('POWERPRESS_PLAY_IN_NEW_WINDOW_TEXT') )
        define('POWERPRESS_PLAY_IN_NEW_WINDOW_TEXT', __('Play in new window', 'powerpress') );
    if( !defined('POWERPRESS_DOWNLOAD_TEXT') )
        define('POWERPRESS_DOWNLOAD_TEXT', __('Download', 'powerpress') );
    if( !defined('POWERPRESS_PLAY_TEXT') )
        define('POWERPRESS_PLAY_TEXT', __('Play', 'powerpress') );
    if( !defined('POWERPRESS_EMBED_TEXT') )
        define('POWERPRESS_EMBED_TEXT', __('Embed', 'powerpress') );
    if( !defined('POWERPRESS_READ_TEXT') )
        define('POWERPRESS_READ_TEXT', __('Read', 'powerpress') );

    $GeneralSettings = get_option('powerpress_general');


    if( empty($GeneralSettings['disable_appearance']) || $GeneralSettings['disable_appearance'] == false )
    {
        require_once( POWERPRESS_ABSPATH.'/powerpress-player.php');
        powerpressplayer_init($GeneralSettings);
    }

    // Enable the playlist feature for PowerPress
    if( !empty($GeneralSettings['playlist_player']) ) // Either not set or set on
    {
        require_once(POWERPRESS_ABSPATH.'/powerpress-playlist.php');
    }

    if( defined('PODPRESS_VERSION') || isset($GLOBALS['podcasting_player_id']) || isset($GLOBALS['podcast_channel_active']) || defined('PODCASTING_VERSION') )
        return false; // Another podcasting plugin is enabled...

    // If we are to process podpress data..
    if( !empty($GeneralSettings['process_podpress']) )
    {
        powerpress_podpress_redirect_check();
    }

    // Add the podcast feeds;
    if( !defined('POWERPRESS_NO_PODCAST_FEED') )
    {
        add_feed('podcast', 'powerpress_do_podcast_feed');
    }

    if( $GeneralSettings && isset($GeneralSettings['custom_feeds']) && is_array($GeneralSettings['custom_feeds']) )
    {
        foreach( $GeneralSettings['custom_feeds'] as $feed_slug=> $feed_title )
        {
            if( $feed_slug != 'podcast' )
                add_feed($feed_slug, 'powerpress_do_podcast_feed');
        }
    }

    if( !empty($GeneralSettings['posttype_podcasting']) )
    {
        // Loop through the posttype podcasting settings and set the feeds for the custom post type slugs...
        global $wp_rewrite;


        $FeedSlugPostTypesArray = get_option('powerpress_posttype-podcasting'); // Changed field slightly so it does not conflict with a post type "podcasting"
        if( $FeedSlugPostTypesArray === false )
        {
            // Simple one-time fix...
            $FeedSlugPostTypesArray = get_option('powerpress_posttype_podcasting');
            if( empty($FeedSlugPostTypesArray) )
                $FeedSlugPostTypesArray = array();
            update_option('powerpress_posttype-podcasting', $FeedSlugPostTypesArray);
            if( !array_key_exists('title', $FeedSlugPostTypesArray) ) // AS long as it doesn't have post type specific settings...
                delete_option('powerpress_posttype_podcasting');
        }

        if( empty($FeedSlugPostTypesArray) )
        {
            $FeedSlugPostTypesArray = array();
        }
        foreach( $FeedSlugPostTypesArray as $feed_slug=> $FeedSlugPostTypes )
        {
            if ( !in_array($feed_slug, $wp_rewrite->feeds) ) // we need to add this feed name
            {
                add_feed($feed_slug, 'powerpress_do_podcast_feed');
                foreach( $FeedSlugPostTypes as $post_type_slug=> $title )
                {
                    add_rewrite_rule( '/'. $post_type_slug .'/feed/'. $feed_slug .'/?$', 'index.php?post_type='. $post_type_slug .'&feed='.$feed_slug, 'top' ); // capture the post type feeds
                    add_rewrite_rule( '/'. $post_type_slug .'/feed/'. $feed_slug .'/?$', 'index.php?post_type='. $post_type_slug .'&feed='.$feed_slug, 'bottom' ); // capture the post type feeds
                }
            }
        }
    }

    if( defined('GAWP_VERSION') )
    {
        add_filter('the_content', 'powerpress_yoast_gawp_fix', 120 );
    }

    if( !empty($GeneralSettings['subscribe_links']) )
    {
        // 2 Subscribe page shortocde [powerpress_subscribe feedslug="podcast"]
        // 3 Subscribe sidebar widget: iTunes, RSS
        add_filter('powerpress_player_subscribe_links', 'powerpressplayer_link_subscribe_pre', 1, 3);
        add_filter('powerpress_player_subscribe_links', 'powerpressplayer_link_subscribe_post', 1000, 3);
    }
    wp_register_style(
        'powerpress-subscribe-style',
        powerpress_get_root_url() . 'css/subscribe.css',
        array(),
        '20141021',
        'all' );

    if( !empty($GeneralSettings['rss_emoji']) ) {
        if( has_filter('the_content_feed',   'wp_staticize_emoji') ) {
            remove_filter( 'the_content_feed', 'wp_staticize_emoji' ); // Remove the emoji images
            remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
            //add_filter( 'the_content_feed', 'wp_encode_emoji' ); // Convert an emoji to &#x1Fxxx;
            //add_filter( 'get_wp_title_rss', 'wp_encode_emoji' );
        }
    }

    if( !defined('POWERPRESS_NO_REMOVE_WP_HEAD') ) {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }

    add_filter( 'the_guid', 'powerpress_the_guid', 11 );



    if (!isset($GeneralSettings)) {
        $GeneralSettings = get_option('powerpress_general');
    }

    if (!empty($GeneralSettings['powerpress_network'])) {
        require_once( POWERPRESS_ABSPATH .'/powerpress-network.php');
        if (class_exists('PowerPressNetwork')) {
            $GLOBALS['ppn_object'] = new PowerPressNetwork('powerpressadmin_basic');
            $GLOBALS['ppn_object']->setDisplay();

            if (is_admin()) {
                if (defined('WP_DEBUG')) {
                    if (WP_DEBUG) {
                        wp_register_style('powerpress-network', powerpress_get_root_url() . 'css/style.css', array(), POWERPRESS_VERSION);
                    } else {
                        wp_register_style('powerpress-network', powerpress_get_root_url() . 'css/style.min.css', array(), POWERPRESS_VERSION);
                    }
                }
                else {
                    wp_register_style('powerpress-network', powerpress_get_root_url() . 'css/style.min.css', array(), POWERPRESS_VERSION);
                }

                wp_enqueue_style('powerpress-network');
                //wp_enqueue_script('powerpress-network', $this::powerpress_network_plugin_url() . 'js/admin.js', array('jquery'));
            }

            $plugin_url = plugin_dir_url(__FILE__);
            //wp_enqueue_style('style', $plugin_url . 'css/stylesheet.css');

            if (defined('WP_DEBUG')) {
                if (WP_DEBUG) {
                    wp_enqueue_style('style', $plugin_url . 'css/blueprint.css', array(), POWERPRESS_VERSION);
                } else {
                    wp_enqueue_style('style', $plugin_url . 'css/blueprint.min.css', array(), POWERPRESS_VERSION);
                }
            } else {
                wp_enqueue_style('style', $plugin_url . 'css/blueprint.min.css', array(), POWERPRESS_VERSION);
            }
        }
    }

}

add_action('init', 'powerpress_init', -100); // We need to add the feeds before other plugins start screwing with them

function powerpress_init_block() {
    if (function_exists('register_block_type')) {
        // register block(s)
        register_block_type(__DIR__ . '/blocks/player-block/build', array('render_callback' => function ($attributes, $content, $block) {
            $recipienteturn = '';
            $GeneralSettings = get_option('powerpress_general');

            // first, dropdown to select feed if necessary
            $is_backend = defined('REST_REQUEST') && REST_REQUEST == true && filter_input(INPUT_GET, 'context', FILTER_SANITIZE_SPECIAL_CHARS) == 'edit';
            if ($is_backend && !empty($GeneralSettings['custom_feeds']) && !empty($attributes['id'])) {
                $recipienteturn .= "<select id='select-feed-{$attributes['id']}' disabled>";
                if (empty($attributes['feed_slug'])) {
                    $recipienteturn .= '<option value="" class="pp-block-select">Channel: No selection</option>';
                } else {
                    $recipienteturn .= '<option value="" class="pp-block-select">Channel: No selection</option>';
                }
                if ($attributes['feed_slug'] == 'podcast') {
                    $recipienteturn .= '<option value="podcast" class="pp-block-select" selected>Channel: Main Feed</option>';
                } else {
                    $recipienteturn .= '<option value="podcast" class="pp-block-select">Channel: Main Feed</option>';
                }
                foreach ($GeneralSettings['custom_feeds'] as $slug => $title) {
                    if (!empty($attributes['feed_slug']) && $attributes['feed_slug'] == $slug) {
                        $recipienteturn .= '<option value="' . $slug . '" class="pp-block-select" selected>' . 'Channel: ' . $title . '</p>';
                    } else {
                        $recipienteturn .= '<option value="' . $slug . '" class="pp-block-select">' . 'Channel: ' . $title . '</p>';
                    }
                }
                $recipienteturn .= "</select>";
            }

            // print shortcode on public side
            if (!$is_backend) {
                if (!empty($attributes['feed_slug'])) {
                    return '[powerpress channel="' . $attributes['feed_slug'] . '"]';
                }
                return '';
            }

            if (!empty($attributes['feed_slug'])) {
                // for editor, generate html from the shortcode and send it
                $recipienteturn .= "<div>";
                $recipienteturn .= "<p class='pp-block-error-{$attributes['id']}'></p><div class='pp-block-sample'>";
                $recipienteturn .= do_shortcode('[powerpress sample="1" channel="' . $attributes['feed_slug'] . '"]');
                $recipienteturn .= "</div></div>";
            }

            // randomly, sometimes, when we add the clientId on the frontend, the block does not re-render and just returns empty
            if (empty($recipienteturn)) {
                $recipienteturn = "<p class='alert alert-danger'>" . __("Something went wrong. Please delete this block and restart it.", "powerpress") . "</p>";
            }
            return $recipienteturn;
        }, 'attributes' => array(
            'updated' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'feed_slug' => array(
                'type' => 'string',
                'default' => empty($GeneralSettings['custom_feeds']) ? 'podcast' : '',
            ),
            'id' => array(
                'type' => 'string',
                'default' => '',
            ),
        )));
    }
}

add_action('init', 'powerpress_init_block', 100); // We need to add this AFTER everything initializes


function powerpress_wp_print_styles()
{
    $Settings = get_option('powerpress_general');

    if( !empty($Settings['audio_player_max_width']) )
    {
        echo '<style type="text/css">'."\n";
        if( is_numeric($Settings['audio_player_max_width']) )
            $Settings['audio_player_max_width'] .= 'px';
        echo '.powerpress_player .wp-audio-shortcode { max-width: '.$Settings['audio_player_max_width'].'; }'."\n";
        echo '</style>'."\n";
    }
}

add_action('wp_print_styles', 'powerpress_wp_print_styles');

function powerpress_request($qv)
{
    if( !empty($qv['feed']) )
    {
        $podcast_feed_slug = false;
        if( $qv['feed'] == 'podcast' ) {
            $GeneralSettings = get_option('powerpress_general');
            if( empty($GeneralSettings['posttype_podcasting']) )
                $podcast_feed_slug = 'podcast';
        } else if( $qv['feed'] == 'rss' || $qv['feed'] == 'rss2' || $qv['feed'] == 'atom' || $qv['feed'] == 'rdf' || $qv['feed'] == 'feed' ) { //  'feed', 'rdf', 'rss', 'rss2', 'atom'
            // Skip
        } else {
            $GeneralSettings = get_option('powerpress_general');
            if( empty($GeneralSettings['posttype_podcasting']) && isset($GeneralSettings['custom_feeds']) && is_array($GeneralSettings['custom_feeds']) && !empty($GeneralSettings['custom_feeds'][ $qv['feed'] ] ) )
                $podcast_feed_slug = $qv['feed'];


        }

        if( $podcast_feed_slug )
        {
            if( !defined('POWERPRESS_POSTTYPE_MIXING') && $qv['feed'] == 'podcast' ) {
                $qv['post_type'] = 'post';
            } else {
                $qv['post_type'] = get_post_types( array('public'=> true, 'capability_type'=>'post') );
                if( !empty($qv['post_type']['attachment']) )
                    unset($qv['post_type']['attachment']);
            }

            $FeedCustom = get_option('powerpress_feed_'.$podcast_feed_slug); // Get custom feed specific settings
            // See if the user set a custom post type only...
            if( !empty($FeedCustom) && !empty( $FeedCustom['custom_post_type']) )
                $qv['post_type'] = $FeedCustom['custom_post_type'];
        }
    }
    return $qv;
}

add_filter('request', 'powerpress_request');


function powerpress_plugins_loaded()
{
}
add_action('plugins_loaded', 'powerpress_plugins_loaded');


function powerpress_w3tc_can_print_comment($settings)
{
    return false;
}

// Disable minifying if W3TC is enabled
function powerpress_w3tc_minify_enable($enable)
{
    if( is_feed() )
        return false;
    return $enable;
}

// Load the general feed settings for feeds handled by powerpress
function powerpress_load_general_feed_settings()
{
    global $wp_query;
    global $powerpress_feed;

    if( $powerpress_feed !== false ) // If it is not false (either NULL or an array) then we already looked these settings up
    {
        $powerpress_feed = false;

        // Get the powerpress settings
        $GeneralSettings = get_option('powerpress_general');
        if( !isset($GeneralSettings['custom_feeds']['podcast']) )
            $GeneralSettings['custom_feeds']['podcast'] = 'Podcast Feed'; // Fixes scenario where the user never configured the custom default podcast feed.
        if( empty($GeneralSettings['default_url']) )
            $GeneralSettings['default_url'] = '';

        if( $GeneralSettings )
        {
            $FeedSettingsBasic = get_option('powerpress_feed'); // Get overall feed settings
            if( is_feed() && defined( 'WPCACHEHOME' ) && empty($GeneralSettings['allow_feed_comments']) )
            {
                global $wp_super_cache_comments;
                $wp_super_cache_comments = 0;
            }

            if( is_feed() && defined('W3TC') && empty($GeneralSettings['allow_feed_comments']) )
            {
                add_filter( 'w3tc_can_print_comment', 'powerpress_w3tc_can_print_comment', 10, 1 );
            }

            if( is_feed() && defined('W3TC') )
            {
                add_filter( 'w3tc_minify_enable', 'powerpress_w3tc_minify_enable');
            }

            // If we're in advanced mode and we're dealing with a category feed we're extending, lets work with it...
            if( is_category() && isset($GeneralSettings['custom_cat_feeds']) && is_array($GeneralSettings['custom_cat_feeds']) && in_array( get_query_var('cat'), $GeneralSettings['custom_cat_feeds']) )
            {
                $cat_ID = get_query_var('cat');
                $FeedCustom = get_option('powerpress_cat_feed_'.$cat_ID); // Get custom feed specific settings
                $Feed = powerpress_merge_empty_feed_settings($FeedCustom, $FeedSettingsBasic);

                $powerpress_feed = array();
                if( !empty($GeneralSettings['feed_accel']) )
                    $powerpress_feed['feed_accel'] = true;
                $powerpress_feed['is_custom'] = true;
                $powerpress_feed['category'] = $cat_ID;
                $powerpress_feed['process_podpress'] = !empty($GeneralSettings['process_podpress']); // Category feeds could originate from Podpress
                $powerpress_feed['rss_language'] = ''; // default, let WordPress set the language
                $powerpress_feed['default_url'] = '';
                if( !empty($GeneralSettings['default_url']) )
                    $powerpress_feed['default_url'] = rtrim($GeneralSettings['default_url'], '/') .'/';
                // switching from 'not set' 'yes' 'clean' to 'true' 'false'--for backwards compatibility, 'not set' will now be 'false'
                $explicit_array = array("false", "true", "false");
                $powerpress_feed['explicit'] = $explicit_array[$Feed['itunes_explicit']];
                if( !empty($Feed['itunes_talent_name']) )
                    $powerpress_feed['itunes_talent_name'] = $Feed['itunes_talent_name'];
                else
                    $powerpress_feed['itunes_talent_name'] = get_wp_title_rss();
                $powerpress_feed['enhance_itunes_summary'] = $Feed['enhance_itunes_summary'];
                if( !empty($GeneralSettings['seo_itunes']) )
                    $powerpress_feed['enhance_itunes_summary'] = 1;
                if( !empty($GeneralSettings['disable_wptexturize']) )
                    $powerpress_feed['disable_wptexturize'] = true;
                $powerpress_feed['posts_per_rss'] = false;
                if( !empty($Feed['posts_per_rss']) && is_numeric($Feed['posts_per_rss']) && $Feed['posts_per_rss'] > 0 )
                    $powerpress_feed['posts_per_rss'] = $Feed['posts_per_rss'];
                $powerpress_feed['feed_redirect_url'] = '';
                if( !empty($Feed['feed_redirect_url']) )
                    $powerpress_feed['feed_redirect_url'] = $Feed['feed_redirect_url'];
                if( !empty($Feed['itunes_author_post']) )
                    $powerpress_feed['itunes_author_post'] = true;
                if( !empty($Feed['rss_language']) )
                    $powerpress_feed['rss_language'] = $Feed['rss_language'];

                if( !empty($GeneralSettings['podcast_embed_in_feed']) )
                    $powerpress_feed['podcast_embed_in_feed'] = true;
                if( !empty($Feed['maximize_feed']) )
                    $powerpress_feed['maximize_feed'] = true;
                if( !empty($Feed['unlock_podcast']) )
                    $powerpress_feed['unlock_podcast'] = true;
                if( !empty($Feed['episode_itunes_image']) && !empty($Feed['itunes_image']) )
                    $powerpress_feed['itunes_image'] = $Feed['itunes_image'];
                return;
            }
            else if( ( defined('POWERPRESS_TAXONOMY_PODCASTING') || !empty($GeneralSettings['taxonomy_podcasting']) ) && ( is_tag() || is_tax() ) )
            {
                // We need to get the term_id and the tax_id (tt_id)
                $term_slug = get_query_var('term');
                $taxonomy = get_query_var('taxonomy');

                if( empty($term_slug) && empty($taxonomy) ) // Handle situation where tag is the taxonomy we're working with
                {
                    $term_slug = get_query_var('tag');
                    if( !empty($term_slug) )
                        $taxonomy = 'post_tag';
                }

                $term = false;
                if( !empty($term_slug) && !empty($taxonomy) )
                {
                    $term = term_exists($term_slug, $taxonomy);
                }

                if( !empty($term['term_taxonomy_id']) )
                {
                    $FeedCustom = get_option('powerpress_taxonomy_'.$term['term_taxonomy_id'] ); // Get custom feed specific settings
                    if( $FeedCustom )
                    {
                        $Feed = powerpress_merge_empty_feed_settings($FeedCustom, $FeedSettingsBasic);

                        $powerpress_feed = array();
                        if( !empty($GeneralSettings['feed_accel']) )
                            $powerpress_feed['feed_accel'] = true;
                        $powerpress_feed['is_custom'] = true;
                        $powerpress_feed['term_taxonomy_id'] = $term['term_taxonomy_id'];
                        $powerpress_feed['process_podpress'] = false; // Taxonomy feeds will not originate from Podpress
                        $powerpress_feed['rss_language'] = ''; // default, let WordPress set the language
                        $powerpress_feed['default_url'] = rtrim($GeneralSettings['default_url'], '/') .'/';
                        // switching from 'not set' 'yes' 'clean' to 'true' 'false'--for backwards compatibility, 'not set' will now be 'false'
                        $explicit_array = array("false", "true", "false");
                        $powerpress_feed['explicit'] = $explicit_array[$Feed['itunes_explicit']];
                        if( !empty($Feed['itunes_talent_name']) )
                            $powerpress_feed['itunes_talent_name'] = $Feed['itunes_talent_name'];
                        else
                            $powerpress_feed['itunes_talent_name'] = get_wp_title_rss();
                        $powerpress_feed['enhance_itunes_summary'] = $Feed['enhance_itunes_summary'];
                        if( !empty($GeneralSettings['seo_itunes']) )
                            $powerpress_feed['enhance_itunes_summary'] = 1;
                        if( !empty($GeneralSettings['disable_wptexturize']) )
                            $powerpress_feed['disable_wptexturize'] = true;
                        $powerpress_feed['posts_per_rss'] = false;
                        if( !empty($Feed['posts_per_rss']) && is_numeric($Feed['posts_per_rss']) && $Feed['posts_per_rss'] > 0 )
                            $powerpress_feed['posts_per_rss'] = $Feed['posts_per_rss'];
                        if( !empty($Feed['feed_redirect_url']) )
                            $powerpress_feed['feed_redirect_url'] = $Feed['feed_redirect_url'];
                        if( !empty($Feed['itunes_author_post']) )
                            $powerpress_feed['itunes_author_post'] = true;
                        if( !empty($Feed['rss_language']) )
                            $powerpress_feed['rss_language'] = $Feed['rss_language'];

                        if( !empty($GeneralSettings['podcast_embed_in_feed']) )
                            $powerpress_feed['podcast_embed_in_feed'] = true;
                        if( !empty($Feed['maximize_feed']) )
                            $powerpress_feed['maximize_feed'] = true;
                        if( !empty($Feed['unlock_podcast']) )
                            $powerpress_feed['unlock_podcast'] = true;
                        if( !empty($Feed['episode_itunes_image']) && !empty($Feed['itunes_image']) )
                            $powerpress_feed['itunes_image'] = $Feed['itunes_image'];
                        return;
                    }
                }
            }

            $feed_slug = get_query_var('feed');
            // Are we dealing with a custom podcast channel or a custom post type podcast feed...
            if( !empty($GeneralSettings['posttype_podcasting']) || isset($GeneralSettings['custom_feeds'][ $feed_slug ]) )
            {
                $Feed = false;
                if( !empty($GeneralSettings['posttype_podcasting']) )
                {
                    $post_type = get_query_var('post_type');

                    if( !empty($post_type) )
                    {
                        if ( is_array( $post_type ) ) {
                            $post_type = reset( $post_type ); // get first element in array
                        }

                        // Get the settings for this podcast post type
                        $PostTypeSettingsArray = get_option('powerpress_posttype_'. $post_type);
                        if( !empty($PostTypeSettingsArray[ $feed_slug ]) )
                        {
                            $FeedCustom = $PostTypeSettingsArray[ $feed_slug ];
                            $Feed = powerpress_merge_empty_feed_settings($FeedCustom, $FeedSettingsBasic);
                            $Feed['post_type'] = $post_type;
                        }
                    }
                }
                if( empty($Feed) && isset($GeneralSettings['custom_feeds'][ $feed_slug ]) )
                {
                    $FeedCustom = get_option('powerpress_feed_'.$feed_slug); // Get custom feed specific settings
                    $Feed = powerpress_merge_empty_feed_settings($FeedCustom, $FeedSettingsBasic, ($feed_slug == 'podcast') );
                }

                if( $Feed )
                {
                    $powerpress_feed = array();
                    if( !empty($GeneralSettings['feed_accel']) )
                        $powerpress_feed['feed_accel'] = true;
                    $powerpress_feed['is_custom'] = true;
                    $powerpress_feed['feed-slug'] = $feed_slug;
                    if( !empty($Feed['post_type']) )
                        $powerpress_feed['post_type'] = $Feed['post_type'];
                    $powerpress_feed['process_podpress'] = ($feed_slug=='podcast'? !empty($GeneralSettings['process_podpress']): false); // We don't touch podpress data for custom feeds
                    $powerpress_feed['rss_language'] = ''; // RSS language should be set by WordPress by default
                    $powerpress_feed['default_url'] = '';
                    if( !empty($powerpress_feed['default_url']) )
                        $powerpress_feed['default_url'] = rtrim($GeneralSettings['default_url'], '/') .'/';
                    // switching from 'not set' 'yes' 'clean' to 'true' 'false'--for backwards compatibility, 'not set' will now be 'false'
                    $explicit = ["false", "true", "false"];
                    $powerpress_feed['explicit'] = 'false';
                    if( !empty($Feed['itunes_explicit']) )
                        $powerpress_feed['explicit'] = $explicit[ $Feed['itunes_explicit'] ];
                    if( !empty($Feed['itunes_talent_name']) )
                        $powerpress_feed['itunes_talent_name'] = $Feed['itunes_talent_name'];
                    else
                        $powerpress_feed['itunes_talent_name'] = get_wp_title_rss();
                    $powerpress_feed['enhance_itunes_summary'] = $Feed['enhance_itunes_summary'];
                    if( !empty($GeneralSettings['seo_itunes']) )
                        $powerpress_feed['enhance_itunes_summary'] = 1;
                    if( !empty($GeneralSettings['disable_wptexturize']) )
                        $powerpress_feed['disable_wptexturize'] = true;
                    $powerpress_feed['posts_per_rss'] = false;
                    if( !empty($Feed['posts_per_rss']) && is_numeric($Feed['posts_per_rss']) && $Feed['posts_per_rss'] > 0 )
                        $powerpress_feed['posts_per_rss'] = $Feed['posts_per_rss'];
                    if( !empty($Feed['feed_redirect_url']) )
                        $powerpress_feed['feed_redirect_url'] = $Feed['feed_redirect_url'];
                    if( !empty($Feed['itunes_author_post'] ) )
                        $powerpress_feed['itunes_author_post'] = true;
                    if( !empty($Feed['rss_language']) )
                        $powerpress_feed['rss_language'] = $Feed['rss_language'];
                    if( !empty($GeneralSettings['podcast_embed_in_feed']) )
                        $powerpress_feed['podcast_embed_in_feed'] = true;
                    if( !empty($Feed['maximize_feed']) )
                        $powerpress_feed['maximize_feed'] = true;
                    if( !empty($Feed['unlock_podcast']) )
                        $powerpress_feed['unlock_podcast'] = true;
                    if( !empty($Feed['episode_itunes_image']) && !empty($Feed['itunes_image']) )
                        $powerpress_feed['itunes_image'] = $Feed['itunes_image'];
                    return;
                }
            }

            if ($FeedSettingsBasic === false || !is_array($FeedSettingsBasic)) {
                $FeedSettingsBasic = [];
            }

            if( !isset($FeedSettingsBasic['apply_to']) )
                $FeedSettingsBasic['apply_to'] = 1;

            // We fell this far,we must be in simple mode or the user never saved customized their custom feed settings
            switch( $FeedSettingsBasic['apply_to'] )
            {
                case 0: // enhance only the podcast feed added by PowerPress, with the logic above this code should never be reached but it is added for readability.
                {
                    if( $feed_slug != 'podcast' )
                        break;
                } // important: no break here!
                case 2: // RSS2 Main feed and podcast feed added by PowerPress only
                {
                    if( $feed_slug != 'feed' && $feed_slug != 'rss2' && $feed_slug != 'podcast' )
                        break; // We're only adding podcasts to the rss2 feed in this situation

                    if( $wp_query->is_category ) // don't touch the category feeds...
                        break;

                    if( $wp_query->is_tag ) // don't touch the tag feeds...
                        break;

                    if( $wp_query->is_comment_feed ) // don't touch the comments feeds...
                        break;
                } // important: no break here!
                case 1: // All feeds
                    {
                        $powerpress_feed = array(); // Only store what's needed for each feed item
                        if( !empty($GeneralSettings['feed_accel']) )
                            $powerpress_feed['feed_accel'] = true;
                        $powerpress_feed['is_custom'] = false; // ($feed_slug == 'podcast'?true:false);
                        $powerpress_feed['feed-slug'] = $feed_slug;
                        $powerpress_feed['process_podpress'] = !empty($GeneralSettings['process_podpress']); // We don't touch podpress data for custom feeds
                        $powerpress_feed['default_url'] = '';
                        if( !empty($GeneralSettings['default_url']) )
                            $powerpress_feed['default_url'] = rtrim($GeneralSettings['default_url'], '/') .'/';
                        // switching from 'not set' 'yes' 'clean' to 'true' 'false'--for backwards compatibility, 'not set' will now be 'false'
                        $explicit = array("false", "true", "false");
                        $powerpress_feed['explicit'] = 'false';
                        if( !empty($FeedSettingsBasic['itunes_explicit']) )
                            $powerpress_feed['explicit'] = $explicit[$FeedSettingsBasic['itunes_explicit']];
                        if( !empty($FeedSettingsBasic['itunes_talent_name']) )
                            $powerpress_feed['itunes_talent_name'] = $FeedSettingsBasic['itunes_talent_name'];
                        else
                            $powerpress_feed['itunes_talent_name'] = get_wp_title_rss();
                        $powerpress_feed['enhance_itunes_summary'] = 0;
                        if( isset($FeedSettingsBasic['enhance_itunes_summary']) )
                            $powerpress_feed['enhance_itunes_summary'] = $FeedSettingsBasic['enhance_itunes_summary'];
                        if( !empty($GeneralSettings['seo_itunes']) )
                            $powerpress_feed['enhance_itunes_summary'] = 1;
                        if( !empty($GeneralSettings['disable_wptexturize']) )
                            $powerpress_feed['disable_wptexturize'] = true;
                        $powerpress_feed['posts_per_rss'] = false;
                        if( !empty($FeedSettingsBasic['posts_per_rss']) && is_numeric($FeedSettingsBasic['posts_per_rss']) && $FeedSettingsBasic['posts_per_rss'] > 0 )
                            $powerpress_feed['posts_per_rss'] = $FeedSettingsBasic['posts_per_rss'];
                        if( !empty($FeedSettingsBasic['itunes_author_post']) )
                            $powerpress_feed['itunes_author_post'] = true;
                        $powerpress_feed['rss_language'] = ''; // Cannot set the language setting in simple mode
                        if( !empty($GeneralSettings['podcast_embed_in_feed']) )
                            $powerpress_feed['podcast_embed_in_feed'] = true;
                        if( !empty($FeedSettingsBasic['episode_itunes_image']) && !empty($FeedSettingsBasic['itunes_image']) )
                            $powerpress_feed['itunes_image'] = $FeedSettingsBasic['itunes_image'];

                    }; break;
                // All other cases we let fall through
            }
        }
    }
}

// Returns true of the feed should be treated as a podcast feed
function powerpress_is_podcast_feed()
{
    if( defined('PODPRESS_VERSION') || isset($GLOBALS['podcasting_player_id']) || isset($GLOBALS['podcast_channel_active']) || defined('PODCASTING_VERSION') )
        return false; // Another podcasting plugin is enabled...

    global $powerpress_feed;
    if( $powerpress_feed !== false && !is_array($powerpress_feed) )
        powerpress_load_general_feed_settings();
    if( $powerpress_feed === false )
        return false;
    return true;
}

// Returns true if the feed is a custom feed added by PowerPress
function powerpress_is_custom_podcast_feed()
{
    if( defined('PODPRESS_VERSION') || isset($GLOBALS['podcasting_player_id']) || isset($GLOBALS['podcast_channel_active']) || defined('PODCASTING_VERSION') )
        return false; // Another podcasting plugin is enabled...

    global $powerpress_feed;
    if( $powerpress_feed !== false && !is_array($powerpress_feed) )
        powerpress_load_general_feed_settings();
    if( $powerpress_feed === false )
        return false;
    return $powerpress_feed['is_custom'];
}

function powerpress_posts_fields($cols)
{
    if( !is_feed() )
        return $cols;

    if( is_category() || is_tag() || is_tax() ) {
        if( get_query_var('feed') !== 'podcast' )
            return $cols;
    }

    if( powerpress_is_custom_podcast_feed() || get_query_var('feed') === 'podcast' )
    {
        if( !empty($GLOBALS['powerpress_feed']['feed_accel']) )
        {
            $feed_slug = get_query_var('feed');
            global $wpdb;
            $cols .= ", pp_{$wpdb->postmeta}.meta_value AS podcast_meta_value ";
        }
    }

    return $cols;
}
//$fields = apply_filters_ref_array( 'posts_fields', array( $fields, &$this ) );
add_filter('posts_fields', 'powerpress_posts_fields' );

function powerpress_posts_join($join)
{
    if( !is_feed() )
        return $join;

    if( is_category() || is_tag() || is_tax() ) {
        if( get_query_var('feed') !== 'podcast' )
            return $join;
    }

    if( powerpress_is_custom_podcast_feed() || get_query_var('feed') === 'podcast' )
    {
        global $wpdb;
        $join .= " INNER JOIN {$wpdb->postmeta} AS pp_{$wpdb->postmeta} ";
        $join .= " ON {$wpdb->posts}.ID = pp_{$wpdb->postmeta}.post_id ";
    }

    return $join;
}

add_filter('posts_join', 'powerpress_posts_join' );

function powerpress_posts_where($where)
{
    if( !is_feed() )
        return $where;
    if( is_category() || is_tag() || is_tax() ) {
        if( get_query_var('feed') !== 'podcast' )
            return $where;
    }

    if( powerpress_is_custom_podcast_feed() || get_query_var('feed') === 'podcast' )
    {
        global $wpdb, $powerpress_feed;
        $where .= " AND (";

        if( powerpress_is_custom_podcast_feed() && get_query_var('feed') !== 'podcast' )
            $where .= " pp_{$wpdb->postmeta}.meta_key = '_". get_query_var('feed') .":enclosure' AND pp_{$wpdb->postmeta}.meta_value NOT LIKE 'no%' ";
        else
            $where .= " pp_{$wpdb->postmeta}.meta_key = 'enclosure' AND pp_{$wpdb->postmeta}.meta_value NOT LIKE 'no%' ";

        // Include Podpress data if exists...
        if( !empty($powerpress_feed['process_podpress']) && get_query_var('feed') === 'podcast' )
            $where .= " OR pp_{$wpdb->postmeta}.meta_key = 'podPressMedia' OR pp_{$wpdb->postmeta}.meta_key = '_podPressMedia' ";

        $where .= ") ";
    }
    return $where;
}

add_filter('posts_where', 'powerpress_posts_where' );

// Add the groupby needed for enclosures only
function powerpress_posts_groupby($groupby)
{
    if( !is_feed() )
        return $groupby;

    if( is_category() || is_tag() || is_tax() ) {
        if( get_query_var('feed') !== 'podcast' )
            return $groupby;
    }

    if( powerpress_is_custom_podcast_feed() || get_query_var('feed') === 'podcast' )
    {
        global $wpdb;
        $groupby = " {$wpdb->posts}.ID ";
    }
    return $groupby;
}
add_filter('posts_groupby', 'powerpress_posts_groupby');

function powerpress_post_limits($limits)
{
    if( !is_feed() )
        return $limits;

    if( powerpress_is_custom_podcast_feed() || get_query_var('feed') === 'podcast' )
    {
        global $powerpress_feed;
        if( !empty($powerpress_feed['posts_per_rss']) && preg_match('/^(\d)+$/', trim($powerpress_feed['posts_per_rss'])) )
            $limits = "LIMIT 0, {$powerpress_feed['posts_per_rss']}";
    }
    return $limits;
}
add_filter('post_limits', 'powerpress_post_limits');


function powerpress_do_all_pings()
{
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_encloseme' ");

    // Now call the WordPress do_all_pings()...
    do_all_pings();
    remove_action('do_pings', 'do_all_pings');
}

remove_action('do_pings', 'do_all_pings');
add_action('do_pings', 'powerpress_do_all_pings', 1, 1);

/*
Helper functions:
*/
function powerpress_podpress_redirect_check()
{
    if( preg_match('/podpress_trac\/([^\/]+)\/([^\/]+)\/([^\/]+)\/(.*)$/', $_SERVER['REQUEST_URI'], $matches) )
    {
        $post_id = $matches[2];
        $mediaNum = $matches[3];
        //$filename = $matches[4];
        //$method = $matches[1];

        if( is_numeric($post_id) && is_numeric($mediaNum))
        {
            $EpisodeData = powerpress_get_enclosure_data_podpress($post_id, $mediaNum);
            if( $EpisodeData && isset($EpisodeData['url']) )
            {
                if( strpos($EpisodeData['url'], 'http://' ) !== 0 && strpos($EpisodeData['url'], 'https://' ) !== 0 )
                {
                    die('Error occurred obtaining the URL for the requested media file.');
                    exit;
                }

                $EnclosureURL = str_replace(' ', '%20', $EpisodeData['url']);
                header('Location: '.$EnclosureURL, true, 302);
                header('Content-Length: 0');
                exit;
            }
            // Let the WordPress 404 page load as normal
        }
    }
}

function the_powerpress_content()
{
    echo get_the_powerpress_content();
}

/** returns the player and download link HTML for podcast episodes attached to the current post */
function get_the_powerpress_content()
{
    global $post;

    if( defined('PODPRESS_VERSION') || isset($GLOBALS['podcasting_player_id']) || isset($GLOBALS['podcast_channel_active']) || defined('PODCASTING_VERSION') )
        return '';

    if( function_exists('post_password_required') )
    {
        if( post_password_required($post) )
            return '';
    }

    // PowerPress settings:
    $GeneralSettings = get_option('powerpress_general');

    // No player or links to add to content...
    if( !empty($GeneralSettings['disable_appearance']) )
        return '';

    if( !isset($GeneralSettings['custom_feeds']) )
        $GeneralSettings['custom_feeds'] = array('podcast'=>'Default Podcast Feed');

    // Re-order so the default podcast episode is the top most...
    $Temp = $GeneralSettings['custom_feeds'];
    $GeneralSettings['custom_feeds'] = array();
    $GeneralSettings['custom_feeds']['podcast'] = 'Default Podcast Feed';

    if (is_array($Temp)){
        foreach ($Temp as $feed_slug => $feed_title) {
            if ($feed_slug == 'podcast')
                continue;
            $GeneralSettings['custom_feeds'][$feed_slug] = $feed_title;
        }
    }
    // Handle post type feeds....
    if( !empty($GeneralSettings['posttype_podcasting']) )
    {
        $post_type = get_query_var('post_type');
        if ( is_array( $post_type ) ) {
            $post_type = reset( $post_type ); // get first element in array
        }

        // Get the feed slugs and titles for this post type
        $PostTypeSettingsArray = get_option('powerpress_posttype_'.$post_type);
        // Loop through this array of post type settings...
        if( !empty($PostTypeSettingsArray) )
        {
            switch($post_type)
            {
                case 'post':
                case 'page': {
                    // Do nothing!, we want the default podcast to appear in these post types
                }; break;
                default: {
                    if( !empty($post_type) && empty($PostTypeSettingsArray['podcast']) )
                        unset($GeneralSettings['custom_feeds']['podcast']); // special case, we do not want an accidental podcast episode to appear in a custom post type if the feature is enabled
                }; break;
            }

            if (is_array($PostTypeSettingsArray)) {
                foreach ($PostTypeSettingsArray as $feed_slug => $postTypeSettings) {
                    if (!empty($postTypeSettings['title']))
                        $GeneralSettings['custom_feeds'][$feed_slug] = $postTypeSettings['title'];
                    else
                        $GeneralSettings['custom_feeds'][$feed_slug] = $feed_slug;
                }
            }
        }
    }

    if( !isset($GeneralSettings['display_player']) )
        $GeneralSettings['display_player'] = 1;
    if( !isset($GeneralSettings['player_function']) )
        $GeneralSettings['player_function'] = 1;
    if( !isset($GeneralSettings['podcast_link']) )
        $GeneralSettings['podcast_link'] = 1;

    // Figure out which players are alerady in the body of the page...
    $ExcludePlayers = array();
    if( isset($GeneralSettings['disable_player']) )
        $ExcludePlayers = $GeneralSettings['disable_player']; // automatically disable the players configured

    // LOOP HERE TO DISPLAY EACH MEDIA TYPE
    $new_content = '';
    foreach( $GeneralSettings['custom_feeds'] as $feed_slug=> $feed_title )
    {
        // Get the enclosure data
        $EpisodeData = powerpress_get_enclosure_data($post->ID, $feed_slug);

        if( !$EpisodeData && !empty($GeneralSettings['process_podpress']) && $feed_slug == 'podcast' )
            $EpisodeData = powerpress_get_enclosure_data_podpress($post->ID);

        if( !$EpisodeData || !$EpisodeData['url'] )
            continue;

        // Just in case, if there's no URL lets escape!
        if( !$EpisodeData['url'] )
            continue;

        // If the player is not already inserted in the body of the post using the shortcode...
        //if( preg_match('/\[powerpress(.*)\]/is', $content) == 0 )
        if( !isset($ExcludePlayers[ $feed_slug ]) ) // If the player is not in our exclude list because it's already in the post body somewhere...
        {
            if( isset($GeneralSettings['premium_caps']) && $GeneralSettings['premium_caps'] && !powerpress_premium_content_authorized($feed_slug) )
            {
                $new_content .=  powerpress_premium_content_message($post->ID, $feed_slug, $EpisodeData);
            }
            else
            {
                if( $GeneralSettings['player_function'] != 3 && $GeneralSettings['player_function'] != 0 ) // Play in new window only or disabled
                {
                    do_action('wp_powerpress_player_scripts');
                    $AddDefaultPlayer = empty($EpisodeData['no_player']);

                    if( $EpisodeData && !empty($EpisodeData['embed']) )
                    {
                        $new_content .=  SanitizeEmbed(trim($EpisodeData['embed']));
                        if( !empty($GeneralSettings['embed_replace_player']) )
                            $AddDefaultPlayer = false;
                    }

                    if( $AddDefaultPlayer )
                    {
                        $image = '';
                        $width = '';
                        $height = '';
                        if( isset($EpisodeData['image']) && $EpisodeData['image'] != '' )
                            $image = $EpisodeData['image'];
                        if( !empty($EpisodeData['width']) && is_numeric($EpisodeData['width']) )
                            $width = $EpisodeData['width'];
                        if( !empty($EpisodeData['height']) && is_numeric($EpisodeData['height']) )
                            $height = $EpisodeData['height'];

                        $new_content .= apply_filters('powerpress_player', '', powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData );
                    }
                }

                if( !isset($EpisodeData['no_links']) )
                {
                    do_action('wp_powerpress_player_scripts');
                    $new_content .= apply_filters('powerpress_player_links', '',  powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData );
                    $new_content .= apply_filters('powerpress_player_subscribe_links', '',  powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData );
                }
            }
        }
    }

    return $new_content;
}



// Adds content types that are missing from the default wp_check_filetype function
function powerpress_get_contenttype($file, $use_wp_check_filetype = true)
{
    $parts = pathinfo($file);
    if( !empty($parts['extension']) )
    {
        switch( strtolower($parts['extension']) )
        {
            // HLS formats
            case 'm3u8':
                return 'application/vnd.apple.mpegurl'; // Standard MIME type
            case 'm3u':
                return 'audio/mpegurl'; // Legacy Playlist format
            case 'ts':
                return 'video/mp2t'; // HLS Transport Stream (MPEG-2)
            // Audio formats
            case 'mp3': // most common
            case 'mpga':
            case 'mp2':
            case 'mp2a':
            case 'm2a':
            case 'm3a':
                return 'audio/mpeg';
            case 'm4a':
                return 'audio/x-m4a';
            case 'm4b': // Audio book format
                return 'audio/m4b';
            case 'm4r': // iPhone ringtone format
                return 'audio/m4r';
            // OGG Internet content types as set forth by rfc5334 (http://tools.ietf.org/html/rfc5334)
            case 'opus':
            case 'oga':
            case 'spx':
                return 'audio/ogg';
            case 'wma':
                return 'audio/x-ms-wma';
            case 'wax':
                return 'audio/x-ms-wax';
            case 'ra':
            case 'ram':
                return 'audio/x-pn-realaudio';
            case 'mp4a':
                return 'audio/mp4';
            case 'aac':
                return 'audio/aac';

            // Video formats
            case 'm4v':
                return 'video/x-m4v';
            case 'mpeg':
            case 'mpg':
            case 'mpe':
            case 'm1v':
            case 'm2v':
                return 'video/mpeg';
            case 'mp4':
            case 'mp4v':
            case 'mpg4':
                return 'video/mp4';
            case 'asf':
            case 'asx':
                return 'video/x-ms-asf';
            case 'wmx':
                return 'video/x-ms-wmx';
            case 'avi':
                return 'video/x-msvideo';
            case 'wmv':
                return 'video/x-ms-wmv'; // Check this
            case 'flv':
                return 'video/x-flv';
            case 'mov':
            case 'qt':
                return 'video/quicktime';
            case 'divx':
                return 'video/divx';
            case '3gp':
                return 'video/3gpp';
            case 'webm':
                return 'video/webm';
            case 'ogg': {
                if( !defined('POWERPRESS_OGG_VIDEO') )
                    return 'audio/ogg';
            } // Let this fall through as ogg/video
            case 'ogv':
                return 'video/ogg';

            // rarely used
            case 'mid':
            case 'midi':
                return 'audio/midi';
            case 'wav':
                return 'audio/wav';
            case 'aa':
                return 'audio/audible';
            case 'pdf':
                return 'application/pdf';
            case 'torrent':
                return 'application/x-bittorrent';
            case 'swf':
                return 'application/x-shockwave-flash';
            case 'ogx':
                return 'application/ogg';

            // Most recently added by Apple:
            case 'epub':
                return 'document/x-epub';

            // Content type for transcript files
            case 'srt':
                return 'application/srt';
            case 'json':
                return 'application/json';
            case 'vtt':
                return 'text/vtt';
            case 'html':
                return 'text/html';
            case 'txt':
                return 'text/plain';

            default: // Let it fall through
        }
    }

    // Last case let wordpress detect it:
    if( $use_wp_check_filetype )
    {
        $FileType = wp_check_filetype($file);
        if( $FileType && isset($FileType['type']) )
            return $FileType['type'];
    }
    return '';
}


function powerpress_itunes_categories($PrefixSubCategories = false)
{
    $temp = array();
    $temp['01-00'] = 'Arts';
    $temp['01-01'] = 'Design';
    $temp['01-02'] = 'Fashion & Beauty';
    $temp['01-03'] = 'Food';
    $temp['01-04'] = 'Literature';
    $temp['01-05'] = 'Performing Arts';
    $temp['01-06'] = 'Visual Arts';

    $temp['02-00'] = 'Business';
    $temp['02-01'] = 'Business News';
    $temp['02-02'] = 'Careers';
    $temp['02-03'] = 'Investing';
    $temp['02-04'] = 'Management & Marketing';
    $temp['02-05'] = 'Shopping';

    $temp['03-00'] = 'Comedy';

    $temp['04-00'] = 'Education';
    $temp['04-01'] = 'Education Technology';
    $temp['04-02'] = 'Higher Education';
    $temp['04-03'] = 'K-12';
    $temp['04-04'] = 'Language Courses';
    $temp['04-05'] = 'Training';

    $temp['05-00'] = 'Games & Hobbies';
    $temp['05-01'] = 'Automotive';
    $temp['05-02'] = 'Aviation';
    $temp['05-03'] = 'Hobbies';
    $temp['05-04'] = 'Other Games';
    $temp['05-05'] = 'Video Games';

    $temp['06-00'] = 'Government & Organizations';
    $temp['06-01'] = 'Local';
    $temp['06-02'] = 'National';
    $temp['06-03'] = 'Non-Profit';
    $temp['06-04'] = 'Regional';

    $temp['07-00'] = 'Health';
    $temp['07-01'] = 'Alternative Health';
    $temp['07-02'] = 'Fitness & Nutrition';
    $temp['07-03'] = 'Self-Help';
    $temp['07-04'] = 'Sexuality';

    $temp['08-00'] = 'Kids & Family';

    $temp['09-00'] = 'Music';

    $temp['10-00'] = 'News & Politics';

    $temp['11-00'] = 'Religion & Spirituality';
    $temp['11-01'] = 'Buddhism';
    $temp['11-02'] = 'Christianity';
    $temp['11-03'] = 'Hinduism';
    $temp['11-04'] = 'Islam';
    $temp['11-05'] = 'Judaism';
    $temp['11-06'] = 'Other';
    $temp['11-07'] = 'Spirituality';

    $temp['12-00'] = 'Science & Medicine';
    $temp['12-01'] = 'Medicine';
    $temp['12-02'] = 'Natural Sciences';
    $temp['12-03'] = 'Social Sciences';

    $temp['13-00'] = 'Society & Culture';
    $temp['13-01'] = 'History';
    $temp['13-02'] = 'Personal Journals';
    $temp['13-03'] = 'Philosophy';
    $temp['13-04'] = 'Places & Travel';

    $temp['14-00'] = 'Sports & Recreation';
    $temp['14-01'] = 'Amateur';
    $temp['14-02'] = 'College & High School';
    $temp['14-03'] = 'Outdoor';
    $temp['14-04'] = 'Professional';

    $temp['15-00'] = 'Technology';
    $temp['15-01'] = 'Gadgets';
    $temp['15-02'] = 'Tech News';
    $temp['15-03'] = 'Podcasting';
    $temp['15-04'] = 'Software How-To';

    $temp['16-00'] = 'TV & Film';

    if( $PrefixSubCategories )
    {
        foreach( $temp as $key=> $val )
        {
            $parts = explode('-', $key);
            $cat = $parts[0];
            $subcat = $parts[1];

            if( $subcat != '00' )
                $temp[$key] = $temp[$cat.'-00'].' > '.$val;
        }
        reset($temp);
    }

    return $temp;
}

/**
 * Categories for 2019+ Apple Podcast directory
 */
function powerpress_apple_categories($PrefixSubCategories = false) {
    $temp = array();
    $temp['01-00'] = 'Arts';
    $temp['01-01'] = 'Books';
    $temp['01-02'] = 'Design';
    $temp['01-03'] = 'Fashion & Beauty';
    $temp['01-04'] = 'Food';
    $temp['01-05'] = 'Performing Arts';
    $temp['01-06'] = 'Visual Arts';

    $temp['02-00'] = 'Business';
    $temp['02-01'] = 'Careers';
    $temp['02-02'] = 'Entrepreneurship';
    $temp['02-03'] = 'Investing';
    $temp['02-04'] = 'Management';
    $temp['02-05'] = 'Marketing';
    $temp['02-06'] = 'Non-Profit';

    $temp['03-00'] = 'Comedy';
    $temp['03-01'] = 'Comedy Interviews';
    $temp['03-02'] = 'Improv';
    $temp['03-03'] = 'Stand-Up';

    $temp['04-00'] = 'Education';
    $temp['04-01'] = 'Courses';
    $temp['04-02'] = 'How To';
    $temp['04-03'] = 'Language Learning';
    $temp['04-04'] = 'Self-Improvement';

    $temp['05-00'] = 'Fiction';
    $temp['05-01'] = 'Comedy Fiction';
    $temp['05-02'] = 'Drama';
    $temp['05-03'] = 'Science Fiction';

    $temp['06-00'] = 'Government';

    $temp['07-00'] = 'Health & Fitness';
    $temp['07-01'] = 'Alternative Health';
    $temp['07-02'] = 'Fitness';
    $temp['07-03'] = 'Medicine';
    $temp['07-04'] = 'Mental Health';
    $temp['07-05'] = 'Nutrition';
    $temp['07-06'] = 'Sexuality';

    $temp['08-00'] = 'History';

    $temp['09-00'] = 'Kids & Family';
    $temp['09-01'] = 'Education for Kids';
    $temp['09-02'] = 'Parenting';
    $temp['09-03'] = 'Pets & Animals';
    $temp['09-04'] = 'Stories for Kids';

    $temp['10-00'] = 'Leisure';
    $temp['10-01'] = 'Animation & Manga';
    $temp['10-02'] = 'Automotive';
    $temp['10-03'] = 'Aviation';
    $temp['10-04'] = 'Crafts';
    $temp['10-05'] = 'Games';
    $temp['10-06'] = 'Hobbies';
    $temp['10-07'] = 'Home & Garden';
    $temp['10-08'] = 'Video Games';

    $temp['11-00'] = 'Music';
    $temp['11-01'] = 'Music Commentary';
    $temp['11-02'] = 'Music History';
    $temp['11-03'] = 'Music Interviews';

    $temp['12-00'] = 'News';
    $temp['12-01'] = 'Business News';
    $temp['12-02'] = 'Daily News';
    $temp['12-03'] = 'Entertainment News';
    $temp['12-04'] = 'News Commentary';
    $temp['12-05'] = 'Politics';
    $temp['12-06'] = 'Sports News';
    $temp['12-07'] = 'Tech News';

    $temp['13-00'] = 'Religion & Spirituality';
    $temp['13-01'] = 'Buddhism';
    $temp['13-02'] = 'Christianity';
    $temp['13-03'] = 'Hinduism';
    $temp['13-04'] = 'Islam';
    $temp['13-05'] = 'Judaism';
    $temp['13-06'] = 'Religion';
    $temp['13-07'] = 'Spirituality';

    $temp['14-00'] = 'Science';
    $temp['14-01'] = 'Astronomy';
    $temp['14-02'] = 'Chemistry';
    $temp['14-03'] = 'Earth Sciences';
    $temp['14-04'] = 'Life Sciences';
    $temp['14-05'] = 'Mathematics';
    $temp['14-06'] = 'Natural Sciences';
    $temp['14-07'] = 'Nature';
    $temp['14-08'] = 'Physics';
    $temp['14-09'] = 'Social Sciences';

    $temp['15-00'] = 'Society & Culture';
    $temp['15-01'] = 'Documentary';
    $temp['15-02'] = 'Personal Journals';
    $temp['15-03'] = 'Philosophy';
    $temp['15-04'] = 'Places & Travel';
    $temp['15-06'] = 'Relationships';

    $temp['16-00'] = 'Sports';
    $temp['16-01'] = 'Baseball';
    $temp['16-02'] = 'Basketball';
    $temp['16-03'] = 'Cricket';
    $temp['16-04'] = 'Fantasy Sports';
    $temp['16-05'] = 'Football';
    $temp['16-06'] = 'Golf';
    $temp['16-07'] = 'Hockey';
    $temp['16-08'] = 'Rugby';
    $temp['16-09'] = 'Running';
    $temp['16-10'] = 'Soccer';
    $temp['16-11'] = 'Swimming';
    $temp['16-12'] = 'Tennis';
    $temp['16-13'] = 'Volleyball';
    $temp['16-15'] = 'Wilderness';
    $temp['16-16'] = 'Wrestling';

    $temp['17-00'] = 'Technology';

    $temp['18-00'] = 'True Crime';

    $temp['19-00'] = 'TV & Film';
    $temp['19-01'] = 'After Shows';
    $temp['19-02'] = 'Film History';
    $temp['19-03'] = 'Film Interviews';
    $temp['19-04'] = 'Film Reviews';
    $temp['19-05'] = 'TV Reviews';

    if( $PrefixSubCategories )
    {
        foreach( $temp as $key=> $val )
        {
            $parts = explode('-', $key);
            $cat = $parts[0];
            $subcat = $parts[1];

            if( $subcat != '00' )
                $temp[$key] = $temp[$cat.'-00'].' > '.$val;
        }
        reset($temp);
    }

    return $temp;
}

function powerpress_googleplay_categories()
{
    $temp = array();
    $temp['01-00'] = 'Arts';
    $temp['02-00'] = 'Business';
    $temp['03-00'] = 'Comedy';
    $temp['04-00'] = 'Education';
    $temp['05-00'] = 'Games & Hobbies';
    $temp['06-00'] = 'Government & Organizations';
    $temp['07-00'] = 'Health';
    $temp['08-00'] = 'Kids & Family';
    $temp['09-00'] = 'Music';
    $temp['10-00'] = 'News & Politics';
    $temp['11-00'] = 'Religion & Spirituality';
    $temp['12-00'] = 'Science & Medicine';
    $temp['13-00'] = 'Society & Culture';
    $temp['14-00'] = 'Sports & Recreation';
    $temp['15-00'] = 'Technology';
    $temp['16-00'] = 'TV & Film';

    return $temp;
}

function powerpress_get_root_url()
{
    /*
    // OLD CODE:
    $powerpress_dirname = basename( POWERPRESS_ABSPATH );
    return WP_PLUGIN_URL . '/'. $powerpress_dirname .'/';
    */
    $local_path = __FILE__;
    if( DIRECTORY_SEPARATOR == '\\' ) { // Win32 fix
        $local_path = basename(dirname(__FILE__)) .'/'. basename(__FILE__);
    }
    $plugin_url = plugins_url('', $local_path);
    return $plugin_url . '/';
}

function powerpress_get_the_exerpt($for_summary = false, $no_filters = false, $post_id = false)
{
    if( $no_filters ) {
        if( $post_id > 0 ) {
            $post = get_post($post_id);
            $subtitle = $post->post_excerpt;
            if ( $subtitle == '') {

                $subtitle = $post->post_content;
                $shortcodesTemp = $GLOBALS['shortcode_tags'];
                $GLOBALS['shortcode_tags']['skipto'] = 'powerpress_shortcode_skipto';
                $subtitle = do_shortcode($subtitle);
                $GLOBALS['shortcode_tags'] = $shortcodesTemp;

                $subtitle = strip_shortcodes( $subtitle );
                $subtitle = str_replace(']]>', ']]&gt;', $subtitle);
                $subtitle = strip_tags($subtitle);
            }
        }
        else if( is_object($GLOBALS['post']) )
        {
            $subtitle = $GLOBALS['post']->post_excerpt;
            if ( $subtitle == '') {

                $subtitle = $GLOBALS['post']->post_content;

                $shortcodesTemp = $GLOBALS['shortcode_tags'];
                $GLOBALS['shortcode_tags']['skipto'] = 'powerpress_shortcode_skipto';
                $subtitle = do_shortcode($subtitle);
                $GLOBALS['shortcode_tags'] = $shortcodesTemp;

                $subtitle = strip_shortcodes( $subtitle );
                $subtitle = str_replace(']]>', ']]&gt;', $subtitle);
                $subtitle = strip_tags($subtitle);
            }
        }
    } else {
        $subtitle = get_the_excerpt();
    }

    $subtitle = trim( strip_tags( $subtitle ) );
    if( !empty($subtitle) )
        return $subtitle;
    return powerpress_get_the_content( $for_summary, $no_filters );
}

function powerpress_get_the_content($for_summary = true, $no_filters = false, $no_strip_tags = false) {
    if( $no_filters ) {
        global $post;
        $content_no_html = $post->post_content;

        $shortcodesTemp = $GLOBALS['shortcode_tags'];
        $GLOBALS['shortcode_tags']['skipto'] = 'powerpress_shortcode_skipto';
        $content_no_html = do_shortcode($content_no_html);
        $GLOBALS['shortcode_tags'] = $shortcodesTemp;

        //$content_no_html = strip_shortcodes( $content_no_html );
        $content_no_html = str_replace(']]>', ']]&gt;', $content_no_html);
        $content_no_html = wp_staticize_emoji( _oembed_filter_feed_content( $content_no_html ) );
    } else {
        $content_no_html = get_the_content();
    }

    $content_no_html = strip_shortcodes( $content_no_html );
    if( $no_strip_tags )
        return $content_no_html;

    if( $for_summary ) {
        return trim( strip_tags($content_no_html, '<a><p><br><ul><li>') );
    }
    return trim( strip_tags($content_no_html) );
}


// 
//  DEPRECATED WITH ITUNES:SUMMARY TAG 10/1/25
//
function powerpress_enhanced_itunes_summary($no_filters = false)
{
    if( $no_filters ) {
        $summary = powerpress_get_the_content(false, true, true);
    } else {
        $summary = apply_filters( 'the_content', powerpress_get_the_content(false, $no_filters, true) );
    }
    $summary = str_replace("<li>", '<li>* ', $summary); // Make sure our bullet lists stay nicely formatted.
    $summary = strip_tags($summary, '<a>');             // We can leave a tags for itunes:summary, this will also strip CDATA tags
    return $summary;
}

function powerpress_url_in_feed($url) {
    if( defined('POWERPRESS_FEEDS_FORCE_HTTP') && is_feed() ) {
        if( preg_match('/^https:\/\/(.*)$/', $url, $matches) ) {
            return 'http://'.$matches[1];
        }
    }
    else if( defined('POWERPRESS_FEEDS_FORCE_HTTPS') && is_feed() ) {
        if( preg_match('/^http:\/\/(.*)$/', $url, $matches) ) {
            return 'https://'.$matches[1];
        }
    }
    return $url;
}

function powerpress_format_itunes_value($value, $tag, $cdata=false)
{
    // DEPRECATED FEATURE FOR ITUNES:SUMMARY
    if( $tag == 'summary' )
        $value = nl2br($value); // Does not remove existing br tags if present.

    if( $cdata ) {
        $value = str_replace(']]>', ']]&gt;', $value);
        return powerpress_trim_itunes_value($value, $tag);
    }

    if( !defined('POWERPRESS_DISABLE_ITUNES_UTF8') || POWERPRESS_DISABLE_ITUNES_UTF8 == false ) // If not defined or it is false
    {
        global $wpdb;
        switch( $wpdb->charset )
        {
            case 'utf8': break;
            case 'utf8mb3': break;
            case 'utf8mb4': break;
            default: {

                // preg_match fails when it encounters invalid UTF8 in $string
                if ( 1 !== @preg_match( '/^./us', $value ) ) {
                    $encoding_detected = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
                    if ($encoding_detected !== false) {
                        $value = mb_convert_encoding($value, 'UTF-8', $encoding_detected);
                    } else {
                        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                    }
                    // legacy
                    // $value = utf8_encode($value); <-- utf8_encode deprecated function
                }
            }
        }
    }

    // Code added to solve issue with KimiliFlashEmbed plugin and also remove the shortcode for the WP Audio Player
    // 99.9% of the time this code will not be necessary
    $value = preg_replace("/\[(kml_(flash|swf)embed|audio\:)\b(.*?)(?:(\/))?(\]|$)/isu", '', $value);
    $value = @html_entity_decode($value, ENT_COMPAT, 'UTF-8'); // Remove any additional entities such as &nbsp;
    $value = preg_replace( '/&amp;/ui' , '&', $value); // Precaution in case it didn't get removed from function above.

    return esc_html( powerpress_trim_itunes_value($value, $tag) );
}

function powerpress_trim_itunes_value($value, $tag = 'summary')
{
    $value = trim($value); // First we need to trim the string
    $length = (function_exists('mb_strlen')?mb_strlen($value):strlen($value) );
    $trim_at = false;
    $recipientemove_new_lines = false;

    switch($tag)
    {
        // Updated character limit from 4000 to 10000 per PSP-1 recommendations (Issue #13)
        // Major podcast apps requested descriptions not exceed 10,000 characters max
        case 'description':
        case 'summary': {
            // 10,000 character limit
            if( $length > 10000 )
                $trim_at = 9997; // 3 less characters so we can add a dot dot dot
        }; break;
        case 'subtitle':
        case 'author':
        case 'name':
        default: {
            $recipientemove_new_lines = true;
            // 255 character limit
            if( $length > 255 )
                $trim_at = 252; // Allow 3 dots to be added after the trim
        };
    }

    if( $trim_at )
    {
        // Start trimming
        $value = (function_exists('mb_substr')?mb_substr($value, 0, $trim_at):substr($value, 0, $trim_at) );
        $clean_break = false;
        if( preg_match('/(.*[,\n.\?!])[^,\n.\?!]/isu', $value, $matches) ) // pattern modifiers: case (i)nsensitive, entire (s)tring and (u)nicode
        {
            if( isset( $matches[1]) )
            {
                $detected_eof_pos = (function_exists('mb_strlen')?mb_strlen($matches[1]):strlen($matches[1]) );
                // Look back at most 50 characters...
                if( $detected_eof_pos > 3950 || ($detected_eof_pos > 205 && $detected_eof_pos < 255 ) )
                {
                    $value = $matches[1];
                    $clean_break = true;
                }
                // Otherwise we want to continue with the same value we started with...
            }
        }

        if( $clean_break == false && $tag = 'subtitle' ) // Subtitle we want to add a ... at the end
        {
            if( $trim_at )
                $value = (function_exists('mb_substr')?mb_substr($value, 0, $trim_at):substr($value, 0, $trim_at) ). '...';
        }
    }

    if( $recipientemove_new_lines )
        $value = str_replace( array("\r\n\r\n", "\n", "\r", "\t","-  "), array(' - ',' ', '', ' ', ''), $value );

    return $value;
}

function powerpress_add_redirect_url($MediaURL, $EpisodeData = false) // $channel = 'podcast')
{
    if( preg_match('/^https?:\/\//i', $MediaURL) == 0 )
        return $MediaURL; // If the user is hosting media not via http (e.g. ftp) then we can't handle the redirect

    // don't add redirects to youtube urls (handles youtube links already saved in wpdb)
    if(isYoutubeURL($MediaURL)) return $MediaURL;

    if( !is_array($EpisodeData) )
    {
        $feed_slug = '';
        if( is_string($EpisodeData) && !empty($EpisodeData) ) {
            $feed_slug = $EpisodeData;
        }

        $EpisodeData = array();
        if( !empty($feed_slug) )
            $EpisodeData['feed'] = $EpisodeData;
    }

    if( empty($EpisodeData['feed']) )
        $EpisodeData['feed'] = 'podcast';

    $NewURL = apply_filters( 'powerpress_redirect_url',  $MediaURL, $EpisodeData );

    $URLScheme = ( (preg_match('/^https:\/\//i', $NewURL) != 0 ) ? 'https://':'http://');

    $GeneralSettings = get_option('powerpress_general');
    $recipientedirects = array('redirect0'=>'', 'redirect1'=>'', 'redirect2'=>'', 'redirect3'=>'');
    if( !empty($GeneralSettings['redirect1']) )
        $recipientedirects['redirect1'] = $GeneralSettings['redirect1'];
    if( !empty($GeneralSettings['redirect2']) )
        $recipientedirects['redirect2'] = $GeneralSettings['redirect2'];
    if( !empty($GeneralSettings['redirect3']) )
        $recipientedirects['redirect3'] = $GeneralSettings['redirect3'];

    if( !empty($GeneralSettings['cat_casting']) ) { // If category podcasting...

        if( !empty($EpisodeData['category']) ) {

            $FeedCatSettings = get_option('powerpress_cat_feed_'.$EpisodeData['category'] );
            if( !empty($FeedCatSettings['redirect']) ) {
                $recipientedirects['redirect0'] = $FeedCatSettings['redirect'];
                $recipientedirects['redirect1'] = '';
                $recipientedirects['redirect2'] = '';
                $recipientedirects['redirect3'] = '';
            }
            if( !empty($FeedCatSettings['redirect2']) ) {
                $recipientedirects['redirect1'] = $FeedCatSettings['redirect2'];
            }
        } else { // Use the old way

            if( is_category() ) { // Special case where we want to track the category separately
                $FeedCatSettings = get_option('powerpress_cat_feed_'.get_query_var('cat') );
                if( $FeedCatSettings && !empty($FeedCatSettings['redirect']) ) {
                    $recipientedirects['redirect0'] = $FeedCatSettings['redirect'];
                    $recipientedirects['redirect1'] = '';
                    $recipientedirects['redirect2'] = '';
                    $recipientedirects['redirect3'] = '';
                    if( !empty($FeedCatSettings['redirect2']) ) {
                        $recipientedirects['redirect1'] = $FeedCatSettings['redirect2'];
                    }
                }
            } else if( is_single() ) {
                $categories = wp_get_post_categories( get_the_ID() );
                if( count($categories) == 1 ) { // See if only one category is associated with this post
                    foreach( $categories as $null=> $cat_id ) {
                        break;
                    }
                    $FeedCatSettings = get_option('powerpress_cat_feed_'.$cat_id );
                    if( $FeedCatSettings && !empty($FeedCatSettings['redirect']) ) {
                        $recipientedirects['redirect0'] = $FeedCatSettings['redirect'];
                        $recipientedirects['redirect1'] = '';
                        $recipientedirects['redirect2'] = '';
                        $recipientedirects['redirect3'] = '';
                        if( !empty($FeedCatSettings['redirect2']) ) {
                            $recipientedirects['redirect1'] = $FeedCatSettings['redirect2'];
                        }
                    }
                }
            }
        }
    }

    //custom_feeds
    if( !empty($GeneralSettings['channels']) ) {

        $FeedSettings = get_option('powerpress_feed_'. $EpisodeData['feed']);
        if( !empty($FeedSettings['redirect']) )
        {
            // Override the redirect
            $recipientedirects['redirect0'] = $FeedSettings['redirect'];
            $recipientedirects['redirect1'] = '';
            $recipientedirects['redirect2'] = '';
            $recipientedirects['redirect3'] = '';
        }
        if( !empty($FeedSettings['redirect2']) ) {
            $recipientedirects['redirect1'] = $FeedSettings['redirect2'];
        }
    }

    if( !empty($GeneralSettings['posttype_podcasting']) )  // Post Type Podcasting
    {
        $post_type = get_post_type();
        switch($post_type) {
            case 'post':
            case 'page': {
                // Do nothing!, we want the default podcast and channels to appear in these post types
            }; break;
            default: {
                $PostTypeSettingsArray = get_option('powerpress_posttype_'.$post_type);

                // We found a post type statsitics tracking
                if( !empty($PostTypeSettingsArray[ $EpisodeData['feed'] ]['redirect']) )
                {
                    $recipientedirects['redirect0'] = $PostTypeSettingsArray[ $EpisodeData['feed'] ]['redirect'];
                    $recipientedirects['redirect1'] = '';
                    $recipientedirects['redirect2'] = '';
                    $recipientedirects['redirect3'] = '';
                }
                if( !empty($PostTypeSettingsArray[ $EpisodeData['feed'] ]['redirect2']) ) {
                    $recipientedirects['redirect1'] = $PostTypeSettingsArray[ $EpisodeData['feed'] ]['redirect2'];
                }
            };
        }
    }

    if( version_compare($GLOBALS['wp_version'], '4.5', '>=' ) )
    {
        if( !empty($GeneralSettings['taxonomy_podcasting']) )  // Taxonomy Podcasting
        {
            $PowerPressTaxonomies = get_option('powerpress_taxonomy_podcasting');
            if( !empty($PowerPressTaxonomies) )
            {
                foreach ($PowerPressTaxonomies as $key => $value) {
                    $ttid_found = $key;

                    $TaxonomySettings = get_option('powerpress_taxonomy_'.$ttid_found);
                    // Found it???
                    if( !empty($TaxonomySettings['redirect']) )
                    {
                        $recipientedirects['redirect0'] = $TaxonomySettings['redirect'];
                        $recipientedirects['redirect1'] = '';
                        $recipientedirects['redirect2'] = '';
                        $recipientedirects['redirect3'] = '';
                        break;
                    }
                }
            }
        }
    }

    // Allow other apps to update the redirects
    $recipientedirects = apply_filters('powerpress_redirects', $recipientedirects, $EpisodeData);

    for( $x = 3; $x >= 0; $x-- )
    {
        $key = sprintf('redirect%d', $x);
        if( !empty($recipientedirects[ $key ]) )
        {
            if( preg_match('/^https?:\/\/(.*)$/', trim($recipientedirects[ $key ]) , $matches ) == 0 )
                continue;

            // skip adding redirect to enclosure URL if its charable
            if (is_chartable_url($recipientedirects[$key])) {
                continue;
            }

            $recipientedirectClean = $matches[1];
            if( substr($recipientedirectClean, -1, 1) != '/' ) // Rediercts need to end with a slash /.
                $recipientedirectClean .= '/';

            if( !empty($recipientedirectClean) )
            {
                if( strpos($recipientedirectClean, '/') == 0 ) // Not a valid redirect URL
                    continue;

                if( !strstr($NewURL, $recipientedirectClean) ) // If the redirect is not already added...
                    $NewURL = $URLScheme. $recipientedirectClean . str_replace($URLScheme, '', $NewURL);
            }
        }
    }

    return $NewURL;
}

if (!function_exists('is_chartable_url')) {
    function is_chartable_url($recipientedirectUrl)
    {
        if (strpos($recipientedirectUrl, 'chrt.fm') !== false || strpos($recipientedirectUrl, 'chtbl.com') !== false) {
            return true;
        } else {
            return false;
        }
    }
}

function powerpress_add_flag_to_redirect_url($MediaURL, $Flag)
{
    // First strip any previous flags...
    return $MediaURL;
}

/*
Code contributed from upekshapriya on the Blubrry Forums
*/
function powerpress_byte_size($ppbytes)
{
    $ppbytes = intval($ppbytes);
    if( empty($ppbytes) )
        return '';
    $ppsize = intval($ppbytes) / 1024;
    if($ppsize < 1024)
    {
        $ppsize = number_format($ppsize, 1);
        $ppsize .= 'KB';
    }
    else
    {
        if($ppsize / 1024 < 1024)
        {
            $ppsize = number_format($ppsize / 1024, 1);
            $ppsize .= 'MB';
        }
        else if ($ppsize / 1024 / 1024 < 1024)
        {
            $ppsize = number_format($ppsize / 1024 / 1024, 1);
            $ppsize .= 'GB';
        }
    }
    return $ppsize;
}

// Merges settings from feed settings page to empty custom feed settings
function powerpress_merge_empty_feed_settings($CustomFeedSettings, $FeedSettings, $DefaultPodcastFeed = false)
{
    unset($FeedSettings['apply_to']);
    // Remove settings from main $FeedSettings that should not be copied to custom feed.
    if( !$DefaultPodcastFeed )
    {
        unset($FeedSettings['itunes_new_feed_url']);
        unset($FeedSettings['feed_redirect_url']);
        unset($FeedSettings['itunes_complete']);
        unset($FeedSettings['itunes_block']);
        unset($FeedSettings['maximize_feed']);
        unset($FeedSettings['live_item']);
    }

    // USED FOR DEPRECATED ITUNES:SUMMARY
    // If the setting is not already set, set the enhnaced itunes setting if they have PHP5+ on by default
    // if( !isset($FeedSettings['enhance_itunes_summary']) )
    //     $FeedSettings['enhance_itunes_summary'] = 0;

    if( !$CustomFeedSettings )
        return $FeedSettings; // If the $CustomFeedSettings is false

    if (is_array($CustomFeedSettings)) {
        foreach ($CustomFeedSettings as $key => $value) {
            if ($value !== '' || !isset($FeedSettings[$key]))
                $FeedSettings[$key] = $value;
        }
    }

    return $FeedSettings;
}

function powerpress_readable_duration($duration, $include_hour=false)
{
    $seconds = 0;
    $parts = explode(':', $duration);
    // phpstan: explode returns strings, type safety
    if( count($parts) == 3 )
        $seconds = (int)$parts[2] + ((int)$parts[1]*60) + ((int)$parts[0]*60*60);
    else if ( count($parts) == 2 )
        $seconds = (int)$parts[1] + ((int)$parts[0]*60);
    else
        $seconds = (int)$parts[0];

    $hours = 0;
    $minutes = 0;
    if( $seconds >= (60*60) )
    {
        $hours = floor( $seconds /(60*60) );
        $seconds -= (60*60*$hours);
    }
    if( $seconds >= (60) )
    {
        $minutes = floor( $seconds /(60) );
        $seconds -= (60*$minutes);
    }

    if( $hours || $include_hour ) // X:XX:XX (readable)
        return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);

    return sprintf('%d:%02d', $minutes, $seconds); // X:XX or 0:XX (readable)
}

// Duratoin in form of seconds (parses hh:mm:ss)
function powerpress_raw_duration($duration)
{
    $duration = trim($duration);
    $Parts = explode(':',$duration);
    if( empty($Parts) )
        return $duration;

    if( count($Parts) == 3 )
        return ((intval($Parts[0])*60*60) + (intval($Parts[1])*60) + intval($Parts[2]));
    else if( count($Parts) == 2 )
        return ((intval($Parts[0])*60) + intval($Parts[1]));
    //else if( count($Parts) == 1 )
    //	return ($Parts[0]);

    // We never found any colons, so we assume duration is seconds
    return $duration;
}

// For grabbing data from Podpress data stored serialized, the strings for some values can sometimes get corrupted, so we fix it...

function powerpress_repair_serialize($string)
{
    // allowed_classes => false prevents php object injection via crafted serialized data
    if( @unserialize($string, ['allowed_classes' => false]) )
        return $string; // Nothing to repair...

    $string = preg_replace_callback('/(s:(\d+):"([^"]*)")/',
        'powerpress_repair_serialize_callback',
        $string);

    if( substr($string, 0, 2) == 's:' ) // Sometimes the serialized data is double serialized, so we need to re-serialize the outside string
    {
        $string = preg_replace_callback('/(s:(\d+):"(.*)"(;))$/',
            'powerpress_repair_serialize_callback',
            $string);
    }

    return $string;
}

function powerpress_repair_serialize_callback($matches)
{
    if( strlen($matches[3]) == $matches[2] )
        return $matches[0];
    return sprintf('s:%d:"%s"', strlen($matches[3]), $matches[3]) . (!empty($matches[4])?';':'');
}

function powerpress_base64_encode($value)
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}
/*
	powerpress_get_post_meta()
	Safe function to retrieve corrupted PodPress data from the database
	@post_id - post id to retrieve post meta for
	@key - key to retrieve post meta for
*/
function powerpress_get_post_meta($post_id, $key)
{
    $pp_meta_cache = wp_cache_get($post_id, 'post_meta');
    if ( !$pp_meta_cache ) {
        update_postmeta_cache($post_id);
        $pp_meta_cache = wp_cache_get($post_id, 'post_meta');
    }

    $meta = false;
    if ( isset($pp_meta_cache[$key]) )
        $meta = $pp_meta_cache[$key][0];

    if ( is_serialized( $meta ) ) // Logic used up but not including WordPress 2.8, new logic doesn't make sure if unserialized failed or not
    {
        // allowed_classes => false prevents php object injection via crafted serialized data
        if ( false !== ( $gm = @unserialize( $meta, ['allowed_classes' => false] ) ) )
            return $meta;
    }

    return $meta;
}

function powerpress_get_enclosure($post_id, $feed_slug = 'podcast')
{
    $Data = powerpress_get_enclosure_data($post_id, $feed_slug);
    if( $Data )
        return $Data['url'];
    return false;
}

function powerpress_get_enclosure_data($post_id, $feed_slug = 'podcast', $recipientaw_data = false, $add_redirect=true)
{
    global $post;
    if( false != $recipientaw_data )
        $MetaData = $recipientaw_data;
    else
    {
        if( !empty($post->podcast_meta_value) && $post->ID == $post_id) // See if we got the meta data from the initial query...
        {
            // Make sure this is not serialized data from PodPress...
            $partsTest = explode("\n", $post->podcast_meta_value, 4);
            if( count($partsTest) > 2 ) {
                $PodcastData = powerpress_get_enclosure_data($post_id, $feed_slug, $post->podcast_meta_value, true);
                return $PodcastData;
            }
        }

        if( 'podcast' == $feed_slug || '' == $feed_slug )
            $MetaData = get_post_meta($post_id, 'enclosure', true);
        else
            $MetaData = get_post_meta($post_id, '_'. $feed_slug .':enclosure', true);
    }
    if( empty($MetaData) )
        return false;

    $MetaParts = explode("\n", $MetaData, 4);

    $Serialized = false;
    $Data = array();
    $Data['id'] = $post_id;
    $Data['feed'] = $feed_slug;
    $Data['url'] = '';
    $Data['duration'] = '';
    $Data['size'] = '';
    $Data['type'] = '';
    $Data['width'] = '';
    $Data['height'] = '';

    if( count($MetaParts) > 0 )
        $Data['url'] = trim($MetaParts[0]);
    if( count($MetaParts) > 1 )
        $Data['size'] = trim($MetaParts[1]);
    if( count($MetaParts) > 2 )
        $Data['type'] = trim($MetaParts[2]);
    if( count($MetaParts) > 3 )
        $Serialized = $MetaParts[3];

    if ($MetaParts[0] == 'no') {
        return false;
    }

    if( $Serialized )
    {
        // allowed_classes => false prevents php object injection via crafted serialized data
        $ExtraData = @unserialize($Serialized, ['allowed_classes' => false]);
        if( $ExtraData && is_array($ExtraData) )
        {
            foreach( $ExtraData as $key=> $value ) {

                // Make sure specific fields are not overwritten...
                switch( $key ) {
                    case 'id':
                    case 'feed':
                    case 'url':
                    case 'size':
                    case 'type': break;
                    default: $Data[ $key ] = $value;
                }
            }

            if( isset($Data['length']) ) // Setting from the "Podcasting" plugin...
                $Data['duration'] = powerpress_readable_duration($Data['length'], true);

            if( !empty($Data['webm_src']) )
            {
                $Data['webm_src'] = trim($Data['webm_src']);
            }


            if( strpos($MetaParts[0], 'http://') !== 0 && !empty($Data['hosting']) ) // if the URL is not set (just file name) and we're a hosting customer...
            {
                $post_status = get_post_status($post_id);
                switch( $post_status )
                {
                    case 'pending':
                    case 'draft':
                    case 'auto-draft': {
                        // Determine if audio or video, then set the demo episode here...
                        $Data['url'] = 'http://media.blubrry.com/blubrry/content.blubrry.com/blubrry/preview.mp3'; // audio
                        if( strstr($Data['type'], 'video') )
                            $Data['url'] = 'http://media.blubrry.com/blubrry/content.blubrry.com/blubrry/preview.mp4'; // video
                    }; break;
                }
            }
        }
    }

    // If the URL is using Blubrry hosting, then lets pump it up to https...
    if( is_ssl() && preg_match('/^http:\/\/(.*\/content\.blubrry\.com\/.*)$/i', $Data['url'], $matches) )
    {
        $Data['url'] = 'https://'. $matches[1];
    }

    // Check that the content type is a valid one...
    if( strstr($Data['type'], '/') == false )
        $Data['type'] = powerpress_get_contenttype($Data['url']);

    // Do redirect filter here...
    if( $add_redirect && !empty($Data['url']) )
        $Data['url'] = powerpress_add_redirect_url( $Data['url'], $Data );

    if( $add_redirect && !empty($Data['webm_src']) )
        $Data['webm_src'] = powerpress_add_redirect_url( $Data['webm_src'], $Data );

    return apply_filters('powerpress_get_enclosure_data', $Data);
}

function powerpress_get_enclosure_data_podpress($post_id, $mediaNum = 0, $include_premium = false)
{
    $podPressMedia = powerpress_get_post_meta($post_id, 'podPressMedia');
    if( !$podPressMedia )
        $podPressMedia = powerpress_get_post_meta($post_id, '_podPressMedia'); // handles latest verions of PodPress
    if( $podPressMedia )
    {

        if( !is_array($podPressMedia) )
        {
            // Sometimes the stored data gets messed up, we can fix it here:
            $podPressMedia = powerpress_repair_serialize($podPressMedia);
            // allowed_classes => false prevents php object injection via crafted serialized data
            $podPressMedia = @unserialize($podPressMedia, ['allowed_classes' => false]);
        }

        // Do it a second time in case it is double serialized
        if( !is_array($podPressMedia) )
        {
            // Sometimes the stored data gets messed up, we can fix it here:
            $podPressMedia = powerpress_repair_serialize($podPressMedia);
            // allowed_classes => false prevents php object injection via crafted serialized data
            $podPressMedia = @unserialize($podPressMedia, ['allowed_classes' => false]);
        }

        if( is_array($podPressMedia) && isset($podPressMedia[$mediaNum]) && isset($podPressMedia[$mediaNum]['URI']) )
        {
            if( $include_premium == false && isset($podPressMedia[$mediaNum]['premium_only']) && ($podPressMedia[$mediaNum]['premium_only'] == 'on' || $podPressMedia[$mediaNum]['premium_only'] == true) )
                return false;

            $Data = array();
            $Data['id'] = $post_id;
            $Data['feed'] = 'podcast';
            $Data['duration'] = 0;
            $Data['url'] = '';
            $Data['size'] = 0;
            $Data['type'] = '';
            $Data['width'] = '';
            $Data['height'] = '';

            $Data['url'] = $podPressMedia[$mediaNum]['URI'];
            if( isset($podPressMedia[$mediaNum]['size']) )
                $Data['size'] = $podPressMedia[$mediaNum]['size'];
            if( isset($PodPressSettings[$mediaNum]['duration']) )
                $Data['duration'] = $podPressMedia[$mediaNum]['duration'];
            if( isset($PodPressSettings[$mediaNum]['previewImage']) )
                $Data['image'] = $podPressMedia[$mediaNum]['previewImage'];

            if( strpos($Data['url'], 'http://' ) !== 0 && strpos($Data['url'], 'https://' ) !== 0 )
            {
                $PodPressSettings = get_option('podPress_config');
                if( $PodPressSettings && isset($PodPressSettings['mediaWebPath']) )
                    $Data['url'] = rtrim($PodPressSettings['mediaWebPath'], '/') . '/' . ltrim($Data['url'], '/');
                unset($PodPressSettings);
            }

            if( strpos($Data['url'], 'http://' ) !== 0 && strpos($Data['url'], 'https://' ) !== 0 )
            {
                $Settings = get_option('powerpress_general');
                if( $Settings && isset($Settings['default_url']) )
                    $Data['url'] = rtrim($Settings['default_url'], '/') . '/' . ltrim($Data['url'], '/');
            }

            if( strpos($Data['url'], 'http://' ) !== 0 && strpos($Data['url'], 'https://' ) !== 0 )
                return false;

            $Data['type'] = powerpress_get_contenttype($Data['url']); // Detect the content type
            $Data['url'] = powerpress_add_redirect_url($Data['url'], $Data); // Add redirects to Media URL

            return apply_filters('powerpress_get_enclosure_data', $Data);
        }
    }
    return false;
}

function powerpress_get_apple_id($url, $strict=false)
{
    if( $strict )
    {
        $recipientesults = preg_match('/apple\.com\/.*\/id(\d+)/i', $url, $matches);
        if( !$recipientesults )
            $recipientesults = preg_match('/apple\.com\/.*id\=(\d+)/i', $url, $matches);
        if( $recipientesults )
            return $matches[1];
        return 0;
    }
    $recipientesults = preg_match('/\/id(\d+)/i', $url, $matches);
    if( !$recipientesults )
        $recipientesults = preg_match('/id\=(\d+)/i', $url, $matches);
    if( $recipientesults )
        return $matches[1];
    return 0;
}


function the_powerpress_all_players($slug = false, $no_link=false)
{
    echo get_the_powerpress_all_players($slug, $no_link);
}

function get_the_powerpress_all_players($slug = false, $no_link=false)
{
    $recipienteturn = '';
    //Use this function to insert the Powerpress player anywhere in the page.
    //Made by Nicolas Bouliane (http://nicolasbouliane.com/)

    /*We're going to use the Loop to retrieve the latest post with the 'enclosure' custom key set
    //then interpret it and manually launch powerpressplayer_build with the URL contained within
    //that data.*/

    //Let's reset the Loop to make sure we look through all posts
    rewind_posts();

    // Get the list of podcast channel slug names...
    $GeneralSettings = get_option('powerpress_general');

    // No player or links to add to content...
    if( !empty($GeneralSettings['disable_appearance']) )
        return $recipienteturn;

    $ChannelSlugs = array('podcast');
    if( $slug == false )
    {
        if( isset($GeneralSettings['custom_feeds']['podcast']) )
            $ChannelSlugs = array(); // Reset the array so it is added from the list in specified order
        foreach( $GeneralSettings['custom_feeds'] as $feed_slug=> $null )
            $ChannelSlugs[] = $feed_slug;
    }
    else if( is_array($slug) )
    {
        $ChannelSlugs = $slug;
    }
    else
    {
        $ChannelSlugs = array($slug);
    }

    // Loop through the posts
    while( have_posts() )
    {
        the_post();

        foreach( $ChannelSlugs as $null=> $feed_slug )
        {
            // Do we follow the global settings to disable a player?
            if( isset($GeneralSettings['disable_player']) && isset($GeneralSettings['disable_player'][$feed_slug]) && $slug == false )
                continue;

            $EpisodeData = powerpress_get_enclosure_data(get_the_ID(), $feed_slug);
            if( !$EpisodeData && !empty($GeneralSettings['process_podpress']) && $feed_slug == 'podcast' )
                $EpisodeData = powerpress_get_enclosure_data_podpress(get_the_ID());

            if( !$EpisodeData )
                continue;

            $AddDefaultPlayer = true;
            if( !empty($EpisodeData['embed']) )
            {
                $recipienteturn .= SanitizeEmbed($EpisodeData['embed']);
                if( !empty($GeneralSettings['embed_replace_player']) )
                    $AddDefaultPlayer = false;
            }

            if( isset($GeneralSettings['premium_caps']) && $GeneralSettings['premium_caps'] && !powerpress_premium_content_authorized($feed_slug) )
            {
                $recipienteturn .= powerpress_premium_content_message(get_the_ID(), $feed_slug, $EpisodeData);
                continue;
            }

            if( !isset($EpisodeData['no_player']) && $AddDefaultPlayer )
            {
                do_action('wp_powerpress_player_scripts');
                $recipienteturn .= apply_filters('powerpress_player', '', powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData );
            }
            if( !isset($EpisodeData['no_links']) && $no_link == false )
            {
                do_action('wp_powerpress_player_scripts');
                $recipienteturn .= apply_filters('powerpress_player_links', '',  powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData );
                $recipienteturn .= apply_filters('powerpress_player_subscribe_links', '',  powerpress_add_flag_to_redirect_url($EpisodeData['url'], 'p'), $EpisodeData );
            }
        }
        reset($ChannelSlugs);
    }

    return $recipienteturn;
}

function powerpress_premium_content_authorized_filter($default, $feed_slug)
{
    if( $feed_slug != 'podcast' )
    {
        $FeedSettings = get_option('powerpress_feed_'. $feed_slug);
        if( isset($FeedSettings['premium']) && $FeedSettings['premium'] != '' )
            return current_user_can($FeedSettings['premium']);
    }

    $post_type = get_query_var('post_type');
    if ( is_array( $post_type ) ) {
        $post_type = reset( $post_type ); // get first element in array
    }

    if( $post_type != 'post' )
    {
        $GeneralSettings = get_option('powerpress_general');
        if( !empty($GeneralSettings['posttype_podcasting']) ) // Custom Post Types
        {
            // Get the feed slugs and titles for this post type
            $PostTypeSettingsArray = get_option('powerpress_posttype_'.$post_type);
            if( !empty($PostTypeSettingsArray[$feed_slug]['premium']) )
                return current_user_can($PostTypeSettingsArray[$feed_slug]['premium']);
        }
    }

    return $default;
}
add_filter('powerpress_premium_content_authorized', 'powerpress_premium_content_authorized_filter', 10, 2);

function powerpress_premium_content_authorized($feed_slug)
{
    return apply_filters('powerpress_premium_content_authorized', true, $feed_slug );
}

function powerpress_premium_content_message($post_id, $feed_slug, $EpisodeData = false)
{
    if( !$EpisodeData && $post_id )
        $EpisodeData = powerpress_get_enclosure_data($post_id, $feed_slug);

    if( !$EpisodeData )
        return '';
    $FeedSettings = get_option('powerpress_feed_'.$feed_slug);
    $post_type = get_query_var('post_type');
    if ( is_array( $post_type ) ) {
        $post_type = reset( $post_type ); // get first element in array
    }

    if( $post_type != 'post' )
    {
        $GeneralSettings = get_option('powerpress_general');
        if( !empty($GeneralSettings['posttype_podcasting']) ) // Custom Post Types
        {
            // Get the feed slugs and titles for this post type
            $PostTypeSettingsArray = get_option('powerpress_posttype_'.$post_type);
            if( !empty($PostTypeSettingsArray[$feed_slug]['premium']) )
            {
                $FeedSettings = $PostTypeSettingsArray[$feed_slug];
            }
        }
    }

    $extension = 'unknown';
    $parts = pathinfo($EpisodeData['url']);
    if( $parts && isset($parts['extension']) )
        $extension  = strtolower($parts['extension']);

    if( isset($FeedSettings['premium_label']) && $FeedSettings['premium_label'] != '' ) // User has a custom label
        return '<p class="powerpress_links powerpress_links_'. $extension .'">'. $FeedSettings['premium_label'] . '</p>'.PHP_EOL_WEB;

    return '<p class="powerpress_links powerpress_links_'. $extension .'">'. htmlspecialchars($FeedSettings['title']) .': <a href="'. get_bloginfo('url') .'/wp-login.php" title="Protected Content">(Protected Content)</a></p>'.PHP_EOL_WEB;
}

function powerpress_is_mobile_client()
{
    _deprecated_function( __FUNCTION__, '7.0' );
    return false;
}

function powerpress_get_api_array()
{
    $recipienteturn = array();
    if( strstr(POWERPRESS_BLUBRRY_API_URL, 'http://api.blubrry.com') == false ) // If not the default
    {
        $recipienteturn = explode(';', POWERPRESS_BLUBRRY_API_URL);
    }
    else
    {
        $recipienteturn[] = 'https://api.blubrry.com/'; // Use secure URL first when possible
        $recipienteturn[] = 'https://api.blubrry.net/';
    }

    return $recipienteturn;
}


function powerpress_in_wp_head()
{
    $e = new Exception();
    $trace = $e->getTrace();

    if( !empty($trace) ) {
        foreach( $trace as $index=> $call ) {
            if( isset($call['function']) ) {
                // Which calls should we not add the player and links...
                switch( $call['function'] ) {
                    case 'wp_head': return true; break;
                }
            }
        }
    }
    return false;
}

function powerpress_in_custom_post_widget()
{
    if( !class_exists('custom_post_widget') )
        return false;

    $e = new Exception();
    $trace = $e->getTrace();

    if( !empty($trace) ) {

        foreach( $trace as $index=> $call ) {
            if( isset($call['function']) ) {
                // Which calls should we not add the player and links...
                switch( $call['function'] ) {
                    case 'custom_post_widget_shortcode': return true; break;
                }
            }
        }
    }
    return false;
}

function powerpress_admin_migration_notice() {
    $QueuedResults = get_option('powerpress_migrate_queued');
    $Status = get_option('powerpress_migrate_status');
    $completed = false;

    // we have successfully migrated all media, or there is no more media to migrate
    if (count($QueuedResults) == $Status['completed'] || ($Status['queued'] == 0 && $Status['downloading'] == 0)) {
        $completed = true;
    }

    $alert_link = $recipientoot_url = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://' ) ) . htmlspecialchars($_SERVER['HTTP_HOST']) . "/wp-admin/admin.php?page=powerpress/powerpressadmin_migrate.php";
    $alert_class = 'powerpress-notice notice is-dismissible ';
    if ($completed) {
        $alert_class .= ' notice-success ';
        $alert_message = 'Your migration has completed. ';
        $alert_link = $recipientoot_url . "&action=powerpress-migrate-media&migrate_step=3";;
        $alert_link_message = " to update your episodes.";
    } else {
        $alert_class .= ' notice-info ';
        $alert_message = 'Your migration is in progress. ';
        $alert_link = $recipientoot_url . "&action=powerpress-migrate-media&refresh_migrate_status=1";
        $alert_link_message = " to check the status of your migration.";
    }


    $html = "<p class='alertMessage'>$alert_message<a href='$alert_link'>Click here</a>$alert_link_message</p>"
        . '<p>&nbsp; <a style="float:right;" href="#" class="notice-dismiss-link"></a></p>' . PHP_EOL;
    powerpress_page_message_add_notice($html, 'inline', false);
}

// rvMigrateMedia::isYoutubeURL
function isYoutubeURL($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    if (empty($host)) {
        return false;
    }

    $youtubeHostnames = [
        'www.youtube.com',
        'youtube.com',
        'm.youtube.com',
        'www.youtube-nocookie.com',
        'music.youtube.com',
    ];

    if (in_array($host, $youtubeHostnames)) {
        return true;
    }

    // see https://gist.github.com/afeld/1254889 for regex details
    $youtube_regexp = "/^https?:\/\/(?:www\.)?(?:youtube.com|youtu.be)\/(?:watch\?(?=.*v=([\w\-]+))(?:\S+)?|([\w\-]+))$/i";

    if (preg_match($youtube_regexp, $url)) {
        return true;
    }

    return false;
}

function getRemoteFileSize($url, $userAgent = 'PowerPress')
{
    $cUrl = curl_init();
    curl_setopt($cUrl, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($cUrl, CURLOPT_URL, $url);
    curl_setopt($cUrl, CURLOPT_FOLLOWLOCATION, 1); // Handles location: refreshes
    curl_setopt($cUrl, CURLOPT_MAXREDIRS, 12); // Max 12
    curl_setopt($cUrl, CURLOPT_HEADER, 1);
    curl_setopt($cUrl, CURLOPT_TIMEOUT, (45)); // trnasfer timeout (45 seconds)
    curl_setopt($cUrl, CURLOPT_CONNECTTIMEOUT, 15); // Connect time out (15 seconds)
    curl_setopt($cUrl, CURLOPT_ENCODING, 'gzip,deflate'); // Added to support compression
    curl_setopt($cUrl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($cUrl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($cUrl, CURLOPT_CAINFO, dirname(__FILE__) . '/certificates/ca-bundle.crt');
    curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cUrl, CURLOPT_NOBODY, true); // convert to a HEAD request

    $contentLength = 0;
    $pageContent = curl_exec($cUrl);
    $length = curl_getinfo($cUrl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    if (!empty($length)) {
        $contentLength = intval($length);
    } else {
        $lines = explode("\n", $pageContent);
        foreach ($lines as $recipientowNumber => $line) {

            if (preg_match('/^content-length: (.*)$/i', $line, $matches)) {
                $contentLength = $matches[1];
            }
        }
    }

    curl_close($cUrl);
    return $contentLength;
}

/**
 *  Generate Select options for common countries, value is tied to country code
 */
function powerpress_print_select_options_country($selectedOption = '')
{
    $countries = [
        'United States' => 'US', 'Afghanistan' => 'AF', 'Albania' => 'AL', 'Algeria' => 'DZ', 'American Samoa' => 'AS', 'Andorra' => 'AD', 'Angola' => 'AO',
        'Anguilla' => 'AI', 'Antarctica' => 'AQ', 'Antigua and Barbuda' => 'AG', 'Argentina' => 'AR', 'Armenia' => 'AM', 'Aruba' => 'AW', 'Australia' => 'AU',
        'Austria' => 'AT', 'Azerbaijan' => 'AZ', 'Bahamas' => 'BS', 'Bahrain' => 'BH', 'Bangladesh' => 'BD', 'Barbados' => 'BB', 'Belarus' => 'BY', 'Belgium' => 'BE',
        'Belize' => 'BZ', 'Benin' => 'BJ', 'Bermuda' => 'BM', 'Bhutan' => 'BT', 'Bolivia (Plurinational State of)' => 'BO', 'Bosnia and Herzegovina' => 'BA', 'Botswana' => 'BW',
        'Bouvet Island' => 'BV', 'Brazil' => 'BR', 'British Indian Ocean Territory' => 'IO', 'Brunei Darussalam' => 'BN', 'Bulgaria' => 'BG',
        'Burkina Faso' => 'BF', 'Burundi' => 'BI', 'Cabo Verde' => 'CV', 'Cambodia' => 'KH', 'Cameroon' => 'CM', 'Canada' => 'CA', 'Caribbean Netherlands' => 'BQ',
        'Cayman Islands' => 'KY', 'Central African Republic' => 'CF', 'Chad' => 'TD', 'Chile' => 'CL', 'China' => 'CN', 'Christmas Island' => 'CX', 'Cocos (Keeling) Islands' => 'CC',
        'Colombia' => 'CO', 'Comoros' => 'KM', 'Congo' => 'CG', 'Congo, Democratic Republic of the' => 'CD', 'Cook Islands' => 'CK', 'Costa Rica' => 'CR', 'Croatia' => 'HR', 'Cuba' => 'CU',
        'Cyprus' => 'CY', 'Czech Republic' => 'CZ', 'Denmark' => 'DK', 'Djibouti' => 'DJ', 'Dominica' => 'DM', 'Dominican Republic' => 'DO', 'Ecuador' => 'EC', 'Egypt' => 'EG',
        'El Salvador' => 'SV', 'Equatorial Guinea' => 'GQ', 'Eritrea' => 'ER', 'Estonia' => 'EE', 'Eswatini (Swaziland)' => 'SZ', 'Ethiopia' => 'ET', 'Falkland Islands (Malvinas)' => 'FK',
        'Faroe Islands' => 'FO', 'Fiji' => 'FJ', 'Finland' => 'FI', 'France' => 'FR', 'French Guiana' => 'GF', 'French Polynesia' => 'PF', 'French Southern Territories' => 'TF',
        'Gabon' => 'GA', 'Gambia' => 'GM', 'Georgia' => 'GE', 'Germany' => 'DE', 'Ghana' => 'GH', 'Gibraltar' => 'GI', 'Greece' => 'GR', 'Greenland' => 'GL',
        'Grenada' => 'GD', 'Guadeloupe' => 'GP', 'Guam' => 'GU', 'Guatemala' => 'GT', 'Guernsey' => 'GG', 'Guinea' => 'GN', 'Guinea-Bissau' => 'GW', 'Guyana' => 'GY', 'Haiti' => 'HT',
        'Heard Island and Mcdonald Islands' => 'HM', 'Honduras' => 'HN', 'Hong Kong' => 'HK', 'Hungary' => 'HU', 'Iceland' => 'IS', 'India' => 'IN', 'Indonesia' => 'ID', 'Iran' => 'IR', 'Iraq' => 'IQ',
        'Ireland' => 'IE', 'Isle of Man' => 'IM', 'Italy' => 'IT', 'Jamaica' => 'JM', 'Japan' => 'JP', 'Jersey' => 'JE', 'Jordan' => 'JO', 'Kazakhstan' => 'KZ',
        'Kenya' => 'KE', 'Kiribati' => 'KI', 'Korea, North' => 'KP', 'Korea, South' => 'KR', 'Kosovo' => 'XK', 'Kuwait' => 'KW', 'Kyrgyzstan' => 'KG', 'Lao People\'s Democratic Republic' => 'LA', 'Latvia' => 'LV', 'Lebanon' => 'LB',
        'Lesotho' => 'LS', 'Liberia' => 'LR', 'Libya' => 'LY', 'Liechtenstein' => 'LI', 'Lithuania' => 'LT', 'Luxembourg' => 'LU', 'Macao' => 'MO', 'Macedonia North' => 'MK', 'Madagascar' => 'MG',
        'Malawi' => 'MW', 'Malaysia' => 'MY', 'Maldives' => 'MV', 'Mali' => 'ML', 'Malta' => 'MT', 'Marshall Islands' => 'MH', 'Martinique' => 'MQ', 'Mauritania' => 'MR', 'Mauritius' => 'MU', 'Mayotte' => 'YT',
        'Mexico' => 'MX', 'Micronesia' => 'FM', 'Moldova' => 'MD', 'Monaco' => 'MC', 'Mongolia' => 'MN', 'Montenegro' => 'ME', 'Montserrat' => 'MS', 'Morocco' => 'MA', 'Mozambique' => 'MZ',
        'Myanmar (Burma)' => 'MM', 'Namibia' => 'NA', 'Nauru' => 'NR', 'Nepal' => 'NP', 'Netherlands' => 'NL', 'Netherlands Antilles' => 'AN', 'New Caledonia' => 'NC', 'New Zealand' => 'NZ', 'Nicaragua' => 'NI', 'Niger' => 'NE',
        'Nigeria' => 'NG', 'Niue' => 'NU', 'Norfolk Island' => 'NF', 'Northern Mariana Islands' => 'MP', 'Norway' => 'NO', 'Oman' => 'OM', 'Pakistan' => 'PK', 'Palau' => 'PW', 'Palestine' => 'PS', 'Panama' => 'PA', 'Papua New Guinea' => 'PG',
        'Paraguay' => 'PY', 'Peru' => 'PE', 'Philippines' => 'PH', 'Pitcairn Islands' => 'PN', 'Poland' => 'PL', 'Portugal' => 'PT', 'Puerto Rico' => 'PR', 'Qatar' => 'QA', 'Reunion' => 'RE', 'Romania' => 'RO',
        'Russian Federation' => 'RU', 'Rwanda' => 'RW', 'Saint Barthélemy' => 'BL', 'Saint Helena' => 'SH', 'Saint Kitts and Nevis' => 'KN',
        'Saint Lucia' => 'LC', 'Saint Martin' => 'MF', 'Saint Pierre and Miquelon' => 'PM', 'Saint Vincent and the Grenadines' => 'VC', 'Samoa' => 'WS', 'San Marino' => 'SM', 'Sao Tome and Principe' => 'ST',
        'Saudi Arabia' => 'SA', 'Senegal' => 'SN', 'Serbia' => 'RS', 'Serbia and Montenegro' => 'CS', 'Seychelles' => 'SC', 'Sierra Leone' => 'SL',
        'Singapore' => 'SG', 'Sint Maarten' => 'SX', 'Slovakia' => 'SK', 'Slovenia' => 'SI', 'Solomon Islands' => 'SB', 'Somalia' => 'SO', 'South Africa' => 'ZA',
        'South Georgia and the South Sandwich Islands' => 'GS', 'South Sudan' => 'SS', 'Spain' => 'ES', 'Sri Lanka' => 'LK', 'Sudan' => 'SD', 'Suriname' => 'SR', 'Svalbard and Jan Mayen' => 'SJ', 'Sweden' => 'SE', 'Switzerland' => 'CH', 'Syria' => 'SY',
        'Taiwan' => 'TW', 'Tajikistan' => 'TJ', 'Tanzania' => 'TZ', 'Thailand' => 'TH', 'Timor-Leste' => 'TL', 'Togo' => 'TG', 'Tokelau' => 'TK', 'Tonga' => 'TO',
        'Trinidad and Tobago' => 'TT', 'Tunisia' => 'TN', 'Turkmenistan' => 'TM', 'Turks and Caicos Islands' => 'TC', 'Tuvalu' => 'TV', 'U.S. Outlying Islands' => 'UM', 'Uganda' => 'UG', 'Ukraine' => 'UA', 'United Arab Emirates' => 'AE',
        'United Kingdom' => 'GB', 'Uruguay' => 'UY', 'Uzbekistan' => 'UZ', 'Vanuatu' => 'VU', 'Vatican City Holy See' => 'VA', 'Venezuela' => 'VE', 'Vietnam' => 'VN', 'Virgin Islands, British' => 'VG', 'Virgin Islands, U.S' => 'VI', 'Wallis and Futuna' => 'WF', 'Western Sahara' => 'EH', 'Yemen' => 'YE', 'Zambia' => 'ZM', 'Zimbabwe' => 'ZW',
    ];
    foreach ($countries as $countryName => $countryCode) {
        if ($countryCode == $selectedOption) {
            echo '<option selected value="'.$countryCode.'">'.__($countryName, "powerpress").'</option>';
        } else {
            echo '<option value="'.$countryCode.'">'.__($countryName, "powerpress").'</option>';
        }
    }
}


// Language List -> Moved from powerpressadmin.php
function powerpress_languages()
{
    // List copied from PodPress:
    $langs = array();
    $langs['en-US'] = __('English (United States)', 'powerpress');

    $langs['af'] = __('Afrikaans', 'powerpress');
    $langs['sq'] = __('Albanian', 'powerpress');
    $langs['ar'] = __('Arabic', 'powerpress');
    $langs['ar-SA'] = __('Arabic (Saudi Arabia)', 'powerpress');
    $langs['ar-EG'] = __('Arabic (Egypt)', 'powerpress');
    $langs['ar-DZ'] = __('Arabic (Algeria)', 'powerpress');
    $langs['ar-TN'] = __('Arabic (Tunisia)', 'powerpress');
    $langs['ar-YE'] = __('Arabic (Yemen)', 'powerpress');
    $langs['ar-JO'] = __('Arabic (Jordan)', 'powerpress');
    $langs['ar-KW'] = __('Arabic (Kuwait)', 'powerpress');
    $langs['ar-BH'] = __('Arabic (Bahrain)', 'powerpress');
    $langs['eu'] = __('Basque', 'powerpress');
    $langs['be'] = __('Belarusian', 'powerpress');
    $langs['bg'] = __('Bulgarian', 'powerpress');
    $langs['ca'] = __('Catalan', 'powerpress');
    $langs['zh-CN'] = __('Chinese (Simplified)', 'powerpress');
    $langs['zh-TW'] = __('Chinese (Traditional)', 'powerpress');
    $langs['hr'] = __('Croatian', 'powerpress');
    $langs['cs'] = __('Czech', 'powerpress');
    $langs['cr'] = __('Cree', 'powerpress');
    $langs['da'] = __('Danish', 'powerpress');
    $langs['nl'] = __('Dutch', 'powerpress');
    $langs['nl-BE'] = __('Dutch (Belgium)', 'powerpress');
    $langs['nl-NL'] = __('Dutch (Netherlands)', 'powerpress');
    $langs['en'] = __('English', 'powerpress');
    $langs['en-AU'] = __('English (Australia)', 'powerpress');
    $langs['en-BZ'] = __('English (Belize)', 'powerpress');
    $langs['en-CA'] = __('English (Canada)', 'powerpress');
    $langs['en-IE'] = __('English (Ireland)', 'powerpress');
    $langs['en-JM'] = __('English (Jamaica)', 'powerpress');
    $langs['en-NZ'] = __('English (New Zealand)', 'powerpress');
    $langs['en-PH'] = __('English (Phillipines)', 'powerpress');
    $langs['en-ZA'] = __('English (South Africa)', 'powerpress');
    $langs['en-TT'] = __('English (Trinidad)', 'powerpress');
    $langs['en-GB'] = __('English (United Kingdom)', 'powerpress');
    $langs['en-ZE'] = __('English (Zimbabwe)', 'powerpress');
    $langs['et'] = __('Estonian', 'powerpress');
    $langs['fo'] = __('Faeroese', 'powerpress');
    $langs['fi'] = __('Finnish', 'powerpress');
    $langs['fr'] = __('French', 'powerpress');
    $langs['fr-BE'] = __('French (Belgium)', 'powerpress');
    $langs['fr-CA'] = __('French (Canada)', 'powerpress');
    $langs['fr-FD'] = __('French (France)', 'powerpress');
    $langs['fr-LU'] = __('French (Luxembourg)', 'powerpress');
    $langs['fr-MC'] = __('French (Monaco)', 'powerpress');
    $langs['fr-CH'] = __('French (Switzerland)', 'powerpress');
    $langs['gl'] = __('Galician', 'powerpress');
    $langs['gd'] = __('Gaelic', 'powerpress');
    $langs['de'] = __('German', 'powerpress');
    $langs['de-AT'] = __('German (Austria)', 'powerpress');
    $langs['de-DE'] = __('German (Germany)', 'powerpress');
    $langs['de-LI'] = __('German (Liechtenstein)', 'powerpress');
    $langs['de-LU'] = __('German (Luxembourg)', 'powerpress');
    $langs['de-CH'] = __('German (Switzerland)', 'powerpress');
    $langs['el'] = __('Greek', 'powerpress');
    $langs['haw'] = __('Hawaiian', 'powerpress');
    $langs['he'] = __('Hebrew', 'powerpress');
    $langs['hu'] = __('Hungarian', 'powerpress');
    $langs['is'] = __('Icelandic', 'powerpress');
    $langs['id'] = __('Indonesian', 'powerpress');
    $langs['ga'] = __('Irish', 'powerpress');
    $langs['it'] = __('Italian', 'powerpress');
    $langs['hi'] = __('Hindi', 'powerpress');
    $langs['it-IT'] = __('Italian (Italy)', 'powerpress');
    $langs['it-CH'] = __('Italian (Switzerland)', 'powerpress');
    $langs['ja'] = __('Japanese', 'powerpress');
    $langs['ko'] = __('Korean', 'powerpress');
    $langs['mk'] = __('Macedonian', 'powerpress');
    $langs['no'] = __('Norwegian', 'powerpress');
    $langs['pa'] = __('Punjabi', 'powerpress');
    $langs['pl'] = __('Polish', 'powerpress');
    $langs['pt'] = __('Portuguese', 'powerpress');
    $langs['pt-BR'] = __('Portuguese (Brazil)', 'powerpress');
    $langs['pt-PT'] = __('Portuguese (Portugal)', 'powerpress');
    $langs['ro'] = __('Romanian', 'powerpress');
    $langs['ro-MO'] = __('Romanian (Moldova)', 'powerpress');
    $langs['ro-RO'] = __('Romanian (Romania)', 'powerpress');
    $langs['ru'] = __('Russian', 'powerpress');
    $langs['ru-MO'] = __('Russian (Moldova)', 'powerpress');
    $langs['ru-RU'] = __('Russian (Russia)', 'powerpress');
    $langs['sr'] = __('Serbian', 'powerpress');
    $langs['sk'] = __('Slovak', 'powerpress');
    $langs['sl'] = __('Slovenian', 'powerpress');
    $langs['es'] = __('Spanish', 'powerpress');
    $langs['es-AR'] = __('Spanish (Argentina)', 'powerpress');
    $langs['es-BO'] = __('Spanish (Bolivia)', 'powerpress');
    $langs['es-CL'] = __('Spanish (Chile)', 'powerpress');
    $langs['es-CO'] = __('Spanish (Colombia)', 'powerpress');
    $langs['es-CR'] = __('Spanish (Costa Rica)', 'powerpress');
    $langs['es-DO'] = __('Spanish (Dominican Republic)', 'powerpress');
    $langs['es-EC'] = __('Spanish (Ecuador)', 'powerpress');
    $langs['es-SV'] = __('Spanish (El Salvador)', 'powerpress');
    $langs['es-GT'] = __('Spanish (Guatemala)', 'powerpress');
    $langs['es-HN'] = __('Spanish (Honduras)', 'powerpress');
    $langs['es-MX'] = __('Spanish (Mexico)', 'powerpress');
    $langs['es-NI'] = __('Spanish (Nicaragua)', 'powerpress');
    $langs['es-PA'] = __('Spanish (Panama)', 'powerpress');
    $langs['es-PY'] = __('Spanish (Paraguay)', 'powerpress');
    $langs['es-PE'] = __('Spanish (Peru)', 'powerpress');
    $langs['es-PR'] = __('Spanish (Puerto Rico)', 'powerpress');
    $langs['es-ES'] = __('Spanish (Spain)', 'powerpress');
    $langs['es-UY'] = __('Spanish (Uruguay)', 'powerpress');
    $langs['es-VE'] = __('Spanish (Venezuela)', 'powerpress');
    $langs['sv'] = __('Swedish', 'powerpress');
    $langs['sv-FI'] = __('Swedish (Finland)', 'powerpress');
    $langs['sv-SE'] = __('Swedish (Sweden)', 'powerpress');
    $langs['sw'] = __('Swahili', 'powerpress');
    $langs['ta'] = __('Tamil', 'powerpress');
    $langs['th'] = __('Thai', 'powerpress');
    $langs['bo'] = __('Tibetan', 'powerpress');
    $langs['tr'] = __('Turkish', 'powerpress');
    $langs['uk'] = __('Ukranian', 'powerpress');
    $langs['ve'] = __('Venda', 'powerpress');
    $langs['vi'] = __('Vietnamese', 'powerpress');
    $langs['zu'] = __('Zulu', 'powerpress');
    $langs['fa'] = __('Persian', 'powerpress');
    $langs['fa-AF'] = __('Persian (Afghanistan)', 'powerpress');

    return $langs;
}

/**
 *  Generates Select options for common Language codes with geographic distinctions
 */
function powerpress_print_select_options_lang_codes($selectedOption = '')
{
    $lang_code = powerpress_languages();

    $options = '';
    foreach ($lang_code as $code => $name) {
        $selected = ($code === $selectedOption) ? ' selected' : '';
        $options .= '<option value="' . esc_attr($code) . '"' . $selected . '>' . esc_html($name) . '</option>';
    }
    return $options;
}

/**
 *  Generate Select Options for Roles
 */
function powerpress_print_select_options_roles($selectedOption = 'Guest') {
    $options = [
        "Director", "Assistant Director", "Executive Producer", "Senior Producer", "Producer",
        "Associate Producer", "Development Producer", "Creative Director", "Host", "Co-Host",
        "Guest Host", "Guest", "Voice Actor", "Narrator", "Announcer", "Reporter", "Author",
        "Editorial Director", "Co-Writer", "Writer", "Songwriter", "Guest Writer", "Story Editor",
        "Managing Editor", "Script Editor", "Script Coordinator", "Researcher", "Editor", "Fact Checker",
        "Translator", "Transcriber", "Logger", "Studio Coordinator", "Technical Director", "Technical Manager",
        "Audio Engineer", "Remote Recording Engineer", "Post Production Engineer", "Audio Editor", "Sound Designer",
        "Foley Artist", "Composer", "Theme Music", "Music Production", "Music Contributor", "Production Coordinator",
        "Booking Coordinator", "Production Assistant", "Content Manager", "Marketing Manager", "Sales Representative",
        "Sales Manager", "Graphic Designer", "Cover Art Designer", "Social Media Manager", "Consultant", "Intern",
        "Camera Operator", "Lighting Designer", "Camera Grip", "Assistant Camera", "Editor", "Assistant Editor"
    ];

    foreach ($options as $option) {
        if ($option == $selectedOption) {
            echo '<option selected value="' . $option . '">' . __($option, "powerpress").'</option>';
        } else {
            echo '<option value="' . $option . '">' . __($option, "powerpress") . '</option>';
        }
    }
}

/**
 * Conver raw seconds to string in the form of HH:MM:SS
 */
function powerpress_seconds_to_hms($secs)
{
    $secs = (int)$secs;
    $h = floor($secs / 3600);
    $m = floor(($secs % 3600) / 60);
    $s = $secs % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

/*
End Helper Functions
*/


/*
Template Rendering Function
*/

function powerpress_render_template($config) {
    $type = $config['type'];
    $FeedSlug = $config['FeedSlug'];
    $DataSource = $config['Data'];
    $namePrefix = $config['NamePrefix'];
    $section_data = [];
    
    switch ($type) {
        case 'location':
            $template = 'location-section-template.php';
            break;

        case 'copyright':
            $template = 'copyright-section-template.php';
            break;

        case 'credit':
            $template = 'credit-section-template.php';
            break;

        case 'v4v':
            $template = 'v4v-section-template.php';
            break;
        
        case 'soundbites':
            $template = 'soundbite-section-template.php';
            break;

        case 'social_interact':
            $template = 'social-interact-section-template.php';
            break;

        case 'donate':
            $template = 'donate-section-template.php';
            break;

        case 'txt_tag':
            $template = 'txt-tag-section-template.php';
            break;

        case 'alternate_enclosure':
            $template = 'alt-enclosure-section-template.php';
            break;
        
        case 'content_link':
            $template = 'content-link-section-template.php';
            break;

        default:
            throw new InvalidArgumentException("Unknown section type: {$type}");
        }

        include(POWERPRESS_ABSPATH . '/templates/' . $template);
}
//
// END TEMPLATE RENDERER
//


// Are we in the admin?
if( is_admin() )
{
    require_once(POWERPRESS_ABSPATH.'/powerpressadmin.php');
    register_activation_hook( __FILE__, 'powerpress_admin_activate' );
}

if( defined('POWERPRESS_SUBSCRIBE') && POWERPRESS_SUBSCRIBE )
{
    require_once(POWERPRESS_ABSPATH.'/powerpress-subscribe.php');
}

// For testing purposes in development
if( defined('POWERPRESS_NEW_CODE') && POWERPRESS_NEW_CODE && file_exists(POWERPRESS_ABSPATH.'/powerpress-new-code.php') )
{
    require_once(POWERPRESS_ABSPATH.'/powerpress-new-code.php');
}

if( defined('POWERPRESS_PREMIUM_GROUPS_PLUGIN') ) {

    function powerpress_pre_get_posts($query) {
        if( $query->is_feed() && powerpress_is_custom_podcast_feed() && method_exists('Groups_Post_Access', 'posts_where') )
        {
            $feed_slug = get_query_var('feed');

            if( $feed_slug != 'podcast' )
            {
                $FeedSettings = get_option('powerpress_feed_'.$feed_slug);
                if( !empty($FeedSettings['premium']) )
                {
                    if( has_filter('posts_where', 'Groups_Post_Access::posts_where') )
                    {
                        remove_filter('posts_where', 'Groups_Post_Access::posts_where');
                    }
                }
            }
        }
    }
    add_filter('pre_get_posts', 'powerpress_pre_get_posts');
}

// eof
