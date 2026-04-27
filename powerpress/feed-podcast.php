<?php
/**
 * RSS2 Podcast Feed Template for displaying RSS2 Podcast Posts feed.
 *
 * @package WordPress
 */

function powerpress_get_the_content_feed($feed_type, $no_filters = false)
{
    if( $no_filters == false )
        return get_the_content_feed($feed_type);

    if ( post_password_required() ) {
        return __( 'There is no content because this is a protected post.' );
    }

    global $post, $powerpress_feed;
    $content = $post->post_content;
    if( function_exists('do_blocks') )
        $content = do_blocks($content);
    if( empty($powerpress_feed['disable_wptexturize']) ) // disable smart typography
        $content = wptexturize($content);
    $content = wpautop($content);
    $content = shortcode_unautop($content);
    $content = prepend_attachment($content);
    if( function_exists('wp_filter_content_tags') )
        $content = wp_filter_content_tags($content);

    $shortcodesTemp = $GLOBALS['shortcode_tags'];
    $GLOBALS['shortcode_tags']['skipto'] = 'powerpress_shortcode_skipto';
    $content = do_shortcode($content);
    $GLOBALS['shortcode_tags'] = $shortcodesTemp;

    $content = capital_P_dangit($content);

    $content = convert_smilies($content);

    $content = strip_shortcodes( $content );
    $content = str_replace(']]>', ']]&gt;', $content);

    // convert named HTML entities to numeric for XML compatibility (e.g. &copy; → &#169;)
    $content = convert_chars( ent2ncr( $content ) );

    if( function_exists('_oembed_filter_feed_content') ) // WP 4.4+
        return ( _oembed_filter_feed_content( $content ) );

	if( function_exists('wp_encode_emoji') ) // WP 4.2+
		return wp_encode_emoji( $content );

    return $content;
}


function powerpress_get_the_excerpt_rss($no_filters = true)
{
	if( $no_filters == false ) {
		$output = get_the_excerpt();
		return apply_filters( 'the_excerpt_rss', $output );
	}

	global $post;

	if ( post_password_required() ) {
		return __( 'There is no excerpt because this is a protected post.' );
	}
	$output = strip_tags($post->post_excerpt);
	if ( $output == '') {
		$output = $post->post_content;
		$shortcodesTemp = $GLOBALS['shortcode_tags'];
		$GLOBALS['shortcode_tags']['skipto'] = 'powerpress_shortcode_skipto';
		$output = do_shortcode($output);
		$GLOBALS['shortcode_tags'] = $shortcodesTemp;
		$output = strip_shortcodes( $output );
		$output = str_replace(']]>', ']]&gt;', $output);
		$output = strip_tags($output);
	}

	return convert_chars( ent2ncr( $output ) );
}

$GeneralSettings = get_option('powerpress_general');

if (is_feed() && !headers_sent()) {
    // Add the Access-Control-Allow-Origin header to allow all origins.
    header("Access-Control-Allow-Origin: *");
}
header('Content-Type: application/rss+xml; charset=' . get_option('blog_charset'), true);
$more = 1;


$FeedActionHook = '';
if( !empty($GeneralSettings['feed_action_hook']) )
	$FeedActionHook = '_powerpress';
	
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'."\n"; ?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action('rss2_ns'.$FeedActionHook); ?>
>
<channel>
	<title><?php if( version_compare($GLOBALS['wp_version'], '4.4', '<' ) ) { bloginfo_rss('name'); } wp_title_rss(); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
	<?php if( !empty($FeedActionHook) ) { echo '<generator>Blubrry PowerPress/' . POWERPRESS_VERSION . '</generator>'. PHP_EOL; } ?>
	<?php do_action('rss2_head'.$FeedActionHook); ?>
<?php

    $ItemCount = 0;
    $trailerCount = 0;

    while( have_posts()) :
        the_post();
        $EpisodeData = powerpress_get_enclosure_data(get_the_ID());

        $trailerAttrs = false;
        if( !empty($EpisodeData['episode_type']) && $EpisodeData['episode_type']=='trailer') {
            $trailerAttrs = array(
                'url' => trim(esc_url($EpisodeData['url'])),
                'length' => ((int) ($EpisodeData['size'] ?? 0)) ?: 5242880, // take 5242880 if invalid
                'type' => esc_attr(trim($EpisodeData['type'])),
                'title' => esc_html(powerpress_trim_value($EpisodeData['episode_title'] ?? get_the_title(), 'trailer')),
                'pubdate' => mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false),
            );

            if ( !empty($EpisodeData['season']) )
                $trailerAttrs['season'] = esc_attr($EpisodeData['season']);
        }

        if ($trailerAttrs) {
            $trailerCount += 1;
            
            $season_attr = isset($trailerAttrs['season']) ? " season=\"{$trailerAttrs['season']}\"" : '';
            ?>
        <podcast:trailer url="<?php echo $trailerAttrs['url']; ?>" pubdate="<?php echo $trailerAttrs['pubdate']; ?>" length="<?php echo $trailerAttrs['length']; ?>" type="<?php echo $trailerAttrs['type']; ?>"<?php echo $season_attr; ?>><?php echo $trailerAttrs['title']; ?></podcast:trailer>
            <?php
        }

        if ($trailerCount > 0)
            break;
    endwhile;

    rewind_posts();

    while( have_posts()) :
		//if( empty($GeneralSettings['feed_accel']) )
		the_post();
		//else
		//	$GLOBALS['post'] = $GLOBALS['wp_query']->next_post(); // Use this rather than the_post() that way we do not add additional queries to the database

		$EpisodeData = powerpress_get_enclosure_data(get_the_ID());
?>
	<item>
		<title><?php the_title_rss(); ?></title>
		<link><?php the_permalink_rss(); ?></link>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<guid isPermaLink="false"><?php the_guid(); ?></guid>
<?php
		// If feed maximizer off
		if (empty($GLOBALS['powerpress_feed']['feed_maximizer_on'])) {
			if (empty($GeneralSettings['feed_accel'])) {
				// feed comments
				echo "\t\t<comments>"; comments_link_feed(); echo "</comments>\n";
				echo "\t\t<wfw:commentRss>" . esc_url(get_post_comments_feed_link(null, 'rss2')) . "</wfw:commentRss>\n";
				echo "\t\t<slash:comments>" . get_comments_number() . "</slash:comments>\n";
				// category
				the_category_rss('rss2');
			}

			// show_notes override: use if available, fall back to legacy summary, then excerpt/content
			if ( !empty($EpisodeData['show_notes']) ) {
				echo "\t\t<description>" . powerpress_format_itunes_value($EpisodeData['show_notes'], 'description') . "</description>\n";
			} else if ( !empty($EpisodeData['summary']) ) {
				// legacy itunes:summary data - preserve for existing episodes
				echo "\t\t<description>" . powerpress_format_itunes_value($EpisodeData['summary'], 'description') . "</description>\n";
			} else if (get_option('rss_use_excerpt')) {
				echo "\t\t<description>" . powerpress_format_itunes_value(powerpress_get_the_excerpt_rss(!empty($GeneralSettings['feed_action_hook'])), 'description') . "</description>\n";
			} else {
				$content = powerpress_get_the_content_feed('rss2', !empty($GeneralSettings['feed_action_hook']));
				if (strlen($content) > 0) {
					echo "\t\t<description><![CDATA[" . rtrim($content) . "]]></description>\n";
				} else {
					echo "\t\t<description><![CDATA[" . rtrim(powerpress_get_the_excerpt_rss(!empty($GeneralSettings['feed_action_hook']))) . "]]></description>\n";
				}
			}
		}
		// If feed maximizer on
		else {
			// show_notes override: use if available, fall back to legacy summary, then excerpt
			if ( !empty($EpisodeData['show_notes']) ) {
				echo "\t\t<description>" . powerpress_format_itunes_value($EpisodeData['show_notes'], 'description') . "</description>\n";
			} else if ( !empty($EpisodeData['summary']) ) {
				// legacy itunes:summary data - preserve for existing episodes
				echo "\t\t<description>" . powerpress_format_itunes_value($EpisodeData['summary'], 'description') . "</description>\n";
			} else {
				echo "\t\t<description>" . powerpress_format_itunes_value(powerpress_get_the_excerpt_rss(!empty($GeneralSettings['feed_action_hook'])), 'description') . "</description>\n";
			}
		}
		rss_enclosure();
		apply_filters('rss2_item'.$FeedActionHook, '');
?>
	</item>
<?php
    endwhile;
?>
</channel>
</rss>
<?php

if( defined('POWERPRESS_DEBUG_QUERIES') ) {
	if( !empty($wpdb->queries) ) {
		echo "<!--\n";
		echo "SQL Queries for this feed:\n";
		print_r($wpdb->queries);
		echo "\n-->";
	}
}
?>
<?php exit; // exit feed to prevent possible notices ?>