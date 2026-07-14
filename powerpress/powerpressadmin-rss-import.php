<?php

// Load Importer API
require_once( ABSPATH . 'wp-admin/includes/import.php');

if ( !class_exists( 'WP_Importer' ) ) {
	if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-importer.php' ) )
		require_once( ABSPATH . 'wp-admin/includes/class-wp-importer.php' );
}

require_once ( POWERPRESS_ABSPATH . '/powerpress-feed-parser.class.php');


/**
 * PowerPress RSS Podcast Importer
 *
 * Will process a Podcast RSS feed for importing posts into WordPress. 
 *
 */
if ( class_exists( 'WP_Importer' ) ) {
class PowerPress_RSS_Podcast_Import extends WP_Importer {

	var $m_content = '';
	var $m_item_pos = 0;
	var $m_item_inserted_count = 0;
	var $m_item_skipped_count = 0;
	var $m_item_migrate_count = 0;
	var $m_step = 0;
	var $m_errors = array();
	private $isHostedOnBlubrry = false; //used to show Blubrry signin during onboarding process

    private $parser = null;

    function migrateCount() {
		return $this->m_item_migrate_count;
	}
	
	function importCount() {
		return $this->m_item_inserted_count;
	}
	
	function skippedCount() {
		return $this->m_item_skipped_count;
	}
	
	function errorsExist() {
		return ( count($this->m_errors) > 0 );
	}
	
	function getErrors() {
		return $this->m_errors;
	}
	
	function addError($msg) {
		$this->m_errors[] = $msg;
	}


	function header() {
        powerpress_enqueue_assets([
            'powerpress_onboarding_styles' => ['path' => 'css/onboarding'],
        ]);
        echo '<div class="wrap" style="min-height: 100vh">';
		echo '<div class="pp_container" style="max-width: 100rem;">';
    }


	function greet() {
		$General = powerpress_get_settings('powerpress_general');
		if (isset($_GET['from']) && ($_GET['from'] == 'gs' || $_GET['from'] == 'onboarding')) {
		    $from_onboarding = true;
        } else {
		    $from_onboarding = false;
        }
		if( !empty($_GET['import']) )
		{
			switch($_GET['import'] )
			{
				case 'powerpress-soundcloud-rss-podcast': echo '<h2 class="pp_align-center">'.__('Import Podcast from SoundCloud', 'powerpress').'</h2>'; break;
				case 'powerpress-libsyn-rss-podcast': echo '<h2 class="pp_align-center">'.__('Import Podcast from LibSyn', 'powerpress').'</h2>'; break;
				case 'powerpress-podbean-rss-podcast': echo '<h2 class="pp_align-center">'.__('Import Podcast from PodBean', 'powerpress').'</h2>'; break;
				case 'powerpress-squarespace-rss-podcast': echo '<h2 class="pp_align-center">'.__('Import Podcast from Squarespace', 'powerpress').'</h2>'; break;
				case 'powerpress-anchor-rss-podcast':  echo '<h2 class="pp_align-center">'.__('Import Podcast from Anchor.fm', 'powerpress').'</h2>'; break;
                case 'powerpress-buzzsprout-rss-podcast':  echo '<h2 class="pp_align-center">'.__('Import Podcast from Buzzsprout', 'powerpress').'</h2>'; break;

				case 'powerpress-rss-podcast':
				default: echo '<h2 style="margin-bottom: 0;">'.__('Import Podcast RSS Feed', 'powerpress').'</h2>'; break;
			}
		}
		else
		{
			echo '<h2 style="margin-bottom: 0;">'.__('Import Podcast RSS Feed', 'powerpress').'</h2>';
		}
?>
<p class="pp_align-center"><b><?php echo __('The following tool will import your podcast episodes to this website.', 'powerpress'); ?></b></p>
        <hr />
<section id="one" class="pp_wrapper">
<div class="pp_inner">
<form enctype="multipart/form-data" action="" method="post" name="import-podcast-feed">
<?php wp_nonce_field('import-powerpress-rss') ?>
<input type="hidden" name="step" value="1" />
<input type="hidden" name="import" value="<?php echo( !empty($_REQUEST['import']) ? htmlspecialchars($_REQUEST['import']) : ''); ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo wp_max_upload_size(); ?>" />
<div class="pp_flex-grid">
<div class="pp_form-group" style="width: 100%;">
    <p class="label" style="font-size: 12px;"><?php echo __('Podcast Feed URL', 'powerpress'); ?></p>
    <?php
        $placeholder = 'https://example.com/feed.xml';
        switch($_GET['import']) {
            case 'powerpress-soundcloud-rss-podcast': $placeholder = 'http://feeds.soundcloud.com/users/soundcloud:users:00000000/sounds.rss'; break;
            case 'powerpress-libsyn-rss-podcast': $placeholder = 'http://yourshow.libsyn.com/rss'; break;
            case 'powerpress-podbean-rss-podcast': $placeholder = 'http://yourshow.podbean.com/feed/'; break;
            case 'powerpress-squarespace-rss-podcast': $placeholder = 'http://example.com/podcast/?format=rss'; break;
            case 'powerpress-anchor-rss-podcast': $placeholder = 'https://anchor.fm/s/xxxxxx/podcast/rss'; break;
        }
    ?>
    <input type="text" name="podcast_feed_url" id="podcast_feed_url" class="pp_outlined" style="width: 100%; font-size: 12px;" placeholder="<?php echo esc_attr($placeholder); ?>" />
</div>

</div>
<div class="pp_col">
<link rel="stylesheet" href="<?php echo powerpress_get_root_url(); ?>css/admin.css" type="text/css" media="screen" />
<script language="javascript">

jQuery(document).ready( function() {
	
	jQuery('.pp-expand-section').click( function(e) {
		e.preventDefault();
		
		if( jQuery(this).hasClass('pp-expand-section-expanded') ) {
			jQuery(this).removeClass('pp-expand-section-expanded');
			jQuery(this).parent().next('div').hide(400);
            //jQuery('#import_from_local_disk').hide(400);
            jQuery(this).blur();
		} else {
			jQuery(this).addClass('pp-expand-section-expanded');
			jQuery(this).parent().next('div').show(400);
            //jQuery('#import_from_local_disk').show(400);
            jQuery(this).blur();
		}
	});

	jQuery('#podcast_feed_file').change(function(e) {
	    let filepath_parts;
	    if (e.currentTarget.value.includes('/')) {
            filepath_parts = e.currentTarget.value.split('/');
        } else {
            filepath_parts = e.currentTarget.value.split('\\');
        }
	    jQuery('#importFilePath').val(filepath_parts[filepath_parts.length - 1]);
    });

	<?php
    if(empty($_GET['import']) || $_GET['import'] != 'powerpress-libsyn-rss-podcast'){
    ?>
    jQuery('#podcast_feed_url').on('input', function () {
        if(jQuery(this).val().toUpperCase().includes('LIBSYN')){
            jQuery('#remove_query_string_input').prop('disabled', true);
            jQuery('#remove_query_string_input').prop('checked', true);
        } else {
            jQuery('#remove_query_string_input').prop('disabled', false);
            jQuery('#remove_query_string_input').prop('checked', false);
        }
    });
	<?php } ?>
});

</script>
<style>
    .ppi-option {
        margin: 4px 0;
        font-size: 12px;
    }
    .ppi-option p,
    .ppi-option label,
    .ppi-option select {
        font-size: 12px;
    }
    .pp-expand-section:before, .pp-expand-section-expanded:before {
        height: 16px;
        width: 16px;
        margin-right: 8px;
        font-size: 12px;
        line-height: 12px;
        content: '+';
    }
    .pp-expand-section {
        font-size: 12px;
    }

</style>
<h6><a href="#" class="pp-expand-section"><?php echo __('Advanced Options', 'powerpress'); ?></a></h6>

    <div style="display: none;">
        <div id="import_from_local_disk" style="margin-top: 2em;">
            <p class="label" style="font-size: 12px;margin-bottom: 0;"><?php echo __('Choose from your local disk:', 'powerpress'); ?></p>
            <div id="upload-import-button" onclick="document.getElementById('podcast_feed_file').click();">
                <img style="color: #3c434a; vertical-align: middle;" src="<?php echo powerpress_get_root_url(); ?>images/onboarding/upload.svg" />
                <span style="vertical-align: middle; line-height: 24px;"><?php echo __('Choose RSS/XML File', 'powerpress'); ?></span>
                <input type="file" id="podcast_feed_file" name="podcast_feed_file" class="pp_file_upload" style="display: none;" />
            </div>
            <input type="text" id="importFilePath" readonly class="pp_outlined" style="margin: 0 0 1ch 0; display: inline-block;" placeholder="No File Chosen">
        </div>
        <div class="pp-import-advanced-columns">
            <div class="pp-import-column-container left">
            <div class="ppi-option">
                <h4><?php echo __('Blubrry Podcast Media Hosting', 'powerpress'); ?></h4>
            </div>
        <?php
        if( empty($General['blubrry_hosting']) || $General['blubrry_hosting'] === 'false' ) {
            ?>
            <div class="ppi-option">
                <label><input type="checkbox" name="NULL" value="1" disabled> <?php echo __('Migrate media to your Blubrry hosting account', 'powerpress'); ?></label>
            </div>
            <?php
        } else { ?>
            <div class="ppi-option">
                <label><input type="checkbox" name="migrate_to_blubrry" value="1" checked> <?php echo __('Migrate media to your Blubrry hosting account', 'powerpress'); ?></label>
            </div>
            <?php
        }
        ?>
            <!--
    <p><?php echo sprintf(__('Importing your feed does not migrate your media files. Please use the %s tool to migrate your media once your feed is imported.', 'powerpress'), '<strong><a href="'.admin_url('admin.php?page=powerpress/powerpressadmin_migrate.php') .'">'. __('Migrate Media', 'powerpress') .'</a></strong>'); ?></p>
    -->
            <div class="ppi-option">
                <h4 style="margin: 1em 0 0 0;"><?php echo __('Import Podcast To', 'powerpress'); ?></h4>
            </div>

            <div class="ppi-option">
                <label><input type="radio" name="import_to" id="import_to_default" value="default" checked /> <?php echo __('Default podcast feed', 'powerpress'); ?></label><br />
                <div class="import-to" id="import-to-default" style="display: none;">
                    <div style="margin: 4px 0 4px 24px;">
                        <label><input type="checkbox" name="import_overwrite_program_info" value="1" <?php echo isset($_GET['from']) ? 'checked': '' ?> > <?php echo __('Import program information', 'powerpress'); ?></label>
                    </div>
                    <div style="margin: 4px 0 4px 24px;">
                        <label><input type="checkbox" name="import_itunes_image" value="1" <?php echo isset($_GET['from']) ? 'checked': '' ?>> <?php echo __('Import Program artwork', 'powerpress'); ?></label>
                    </div>
                </div>
            </div>

            <div class="ppi-option">
                <label><input type="radio" name="import_to" id="import_to_category" value="category" /> <?php echo __('Podcast Category feed', 'powerpress'); ?></label>
                <div class="import-to" id="import-to-category" style="display: none;">
                    <div style="margin: 10px 0 10px 24px;">
                        <label for="category"><?php echo __('Category', 'powerpress'); ?></label> &nbsp; <?php
                        wp_dropdown_categories(array('show_option_none' => __( '&mdash; Select &mdash;' ), 'option_none_value' => '', 'hide_empty' => 0, 'id'=>'category', 'name' => 'category', 'orderby' => 'name', 'selected' => '', 'hierarchical' => true));
                        ?>
                    </div>
                    <div style="margin: 10px 0 10px 24px;">
                        <label><input type="checkbox" name="import_overwrite_program_info_category" value="1"> <?php echo __('Import program information', 'powerpress'); ?></label>
                    </div>
                    <div style="margin: 10px 0 10px 24px;">
                        <label><input type="checkbox" name="import_itunes_image_category" value="1"> <?php echo __('Import Program artwork', 'powerpress'); ?></label>
                    </div>
                </div>
            </div>
            <?php

            if( !empty($General['channels']) )
            {
                // List rall of teh podcast channel feeds
                $Feeds = array();
                if( isset($General['custom_feeds']) )
                    $Feeds = $General['custom_feeds'];
                if( isset($General['custom_feeds']['podcast']) )
                    unset($General['custom_feeds']['podcast']);
                if( !empty($Feeds) )
                {
                    ?>
                    <div class="ppi-option">
                        <label><input type="radio" name="import_to" id="import_to_channel" value="channel" /> <?php echo __('Podcast Channel feed', 'powerpress'); ?></label><br />
                        <div class="import-to" id="import-to-channel" style="display: none;">
                            <div style="margin: 10px 0 10px 24px;">
                                <select id="feed_slug" name="feed_slug" class="large-input">
                                    <option value=""><?php echo __('Select Channel feed', 'powerpress'); ?></option>
                                    <?php

                                    asort($Feeds, SORT_STRING); // Sort feeds
                                    foreach( $Feeds as $feed_slug => $feed_title ) {

                                        echo "\t<option value=\"$feed_slug\">$feed_title ($feed_slug)</option>\n";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div style="margin: 10px 0 10px 24px;">
                                <label><input type="checkbox" name="import_overwrite_program_info_channel" value="1"> <?php echo __('Import program information', 'powerpress'); ?></label>
                            </div>
                            <div style="margin: 10px 0 10px 24px;">
                                <label><input type="checkbox" name="import_itunes_image_channel" value="1"> <?php echo __('Import Program artwork', 'powerpress'); ?></label>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } // end podcast channel

            if( !empty($General['posttype_podcasting']) )
            {
                ?>
                <div class="ppi-option">
                    <label><input type="radio" name="import_to" id="import_to_post_type" value="post_type" /> <?php echo __('Podcast Post Type feed', 'powerpress'); ?></label>
                    <div class="import-to" id="import-to-post_type" style="display: none;">
                        <div style="margin: 10px 0 10px 24px;">
                            <label for="post_type"><?php echo __('Post type', 'powerpress'); ?></label> &nbsp;
                            <input type="text" name="post_type" id="post_type" class="medium-text" value="" />
                        </div>
                        <div style="margin: 10px 0 10px 24px;">
                            <label for="post_type_feed_slug"><?php echo __('Feed slug', 'powerpress'); ?></label> &nbsp;
                            <input type="text" name="post_type_feed_slug" id="post_type_feed_slug" class="medium-text" value="" />
                        </div>
                        <div style="margin: 10px 0 10px 24px;">
                            <label><input type="checkbox" name="import_overwrite_program_info_post_type" value="1"> <?php echo __('Import program information', 'powerpress'); ?></label>
                        </div>
                        <div style="margin: 10px 0 10px 24px;">
                            <label><input type="checkbox" name="import_itunes_image_post_type" value="1"> <?php echo __('Import Program artwork', 'powerpress'); ?></label>
                        </div>
                    </div>
                </div>
                <?php
            } // end post type

            if( !empty($General['taxonomy_podcasting']) )
            {
            $PowerPressTaxonomies = get_option('powerpress_taxonomy_podcasting', array());

            ?>
            <div class="ppi-option">
                <label><input type="radio" name="import_to" id="import_to_taxonomy" value="taxonomy" /> <?php echo __('Podcast Taxonomy feed', 'powerpress'); ?></label>
                <div class="import-to" id="import-to-taxonomy" style="display: none;">
                    <div style="margin: 10px 0 10px 24px;">
                        <?php

                        if( !empty($PowerPressTaxonomies) ) { // If taxonomy podcasting feeds exist..

                        global $wpdb;
                        $tt_ids = '';

                        $SelectOptions = array();
                        foreach( $PowerPressTaxonomies as $tt_id => $null ) {
                            if( !empty($tt_ids) )
                                $tt_ids .= ',';
                            $tt_ids .= $tt_id;

                            $term = get_term_by('term_taxonomy_id', $tt_id);
                            if( is_wp_error($term) )
                                continue;
                            $SelectOptions[ $tt_id ] = sprintf('%s (%s)', $term->name, $term->slug);
                        }

                        ?>
                        <select id="podcast_ttid" name="podcast_ttid" style="min-width: 240px;" class="postform">
                            <option value=""><?php echo __('Select Taxonomy Podcast', ''); ?></option>
                            <?php

                            foreach( $SelectOptions as $tt_id => $label )
                            {
                                echo "\t<option value=\"$tt_id\">". htmlspecialchars($label). "</option>\n";
                            }

                            ?>
                        </select>
                    </div>
                    <div style="margin: 10px 0 10px 24px;">
                        <label><input type="checkbox" name="import_overwrite_program_info_taxonomy" value="1"> <?php echo __('Import program information', 'powerpress'); ?></label>
                    </div>
                    <div style="margin: 10px 0 10px 24px;">
                        <label><input type="checkbox" name="import_itunes_image_taxonomy" value="1"> <?php echo __('Import Program artwork', 'powerpress'); ?></label>
                    </div>
                    <?php } else { // else no taxonomy feeds have been created yet ?>
                        <div style="margin: 10px 0 10px 24px;">
                            <label><?php echo __('Please create a taxonomy podcast to continue.', 'powerpress'); ?></label>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php
            } // End if taxonomy podcasting enabled
            ?>
            </div>
            <div class="pp-import-column-container">
        <div class="ppi-option">
            <h4><?php echo __('Import Options', 'powerpress'); ?></h4>
        </div>
        <div class="ppi-option">
            <label><input type="checkbox" name="NULL" value="1" checked disabled> <?php echo __('Match episode by GUID (required)', 'powerpress'); ?></label>
        </div>
        <div class="ppi-option">
            <label><input type="checkbox" name="match_filename" value="1" checked> <?php echo __('Match episode by filename (recommended)', 'powerpress'); ?></label>
        </div>
        <div class="ppi-option">
            <label><input type="checkbox" name="match_title" value="1"> <?php echo __('Match episode by post title', 'powerpress'); ?></label>
        </div>
        <div class="ppi-option">
            <label><input type="checkbox" name="match_date" value="1"> <?php echo __('Match episode by exact post date and time', 'powerpress'); ?></label>
        </div>
        <div class="ppi-option">
            <label><input type="checkbox" name="import_blog_posts" value="1" > <?php echo __('Include blog posts', 'powerpress'); ?></label>
        </div>
        <div class="ppi-option">
            <label><input type="checkbox" name="match_existing_posts" value="1" > <?php echo __('Add podcast episodes to existing posts that match', 'powerpress'); ?></label>
        </div>
        <div class="ppi-option">
        <input type="hidden" name="remove_query_string" value="0" />
            <label><input id="remove_query_string_input" type="checkbox" name="remove_query_string" value="1" <?php if( !empty($_REQUEST['import']) && $_REQUEST['import'] == 'powerpress-libsyn-rss-podcast' ) {
                echo 'checked disabled'; } ?> > <?php echo __('Remove query strings from media URLs', 'powerpress'); ?></label>
        </div>
            </div>
            <div class="pp-import-column-container">
        <div class="ppi-option" style="margin-top: 3em;">
            <label for="import_post_status"><?php echo __('Post Status', 'powerpress'); ?></label> &nbsp;
            <select id="import_post_status" name="import_post_status" class="medium-text">
    <?php
        $post_statuses = get_post_statuses();
        foreach( $post_statuses as $post_status_slug => $post_status_label ) {

        echo "\t<option value=\"$post_status_slug\"". ($post_status_slug=='publish'?' selected':'') .">".  htmlspecialchars("$post_status_label ($post_status_slug)") . "</option>\n";
    }
    ?>
    </select>
        </div>
        <div class="ppi-option" style="margin-top: 2em;">
            <label for="import_item_limit"><?php echo __('Episode Limit', 'powerpress'); ?></label> &nbsp;
            <input type="text" name="import_item_limit" id="import_item_limit" style="width: 100%;font-size: 12px;" value="" />
        </div>
    </div>

    </div>
    </div>
    <div class="pp_col" style="padding: 20px 0px;">
                    <hr class="pp_align-center">
                    <div class="pp_button-container" style="float: right;">
                        <button name="submit" type="submit" class="pp_button" value="Import Podcast"><span><?php echo __('Import Podcast', 'powerpress'); ?></span></button>
                    </div>
    </form>
    </div>
</div>
</div>
</div>
<script>
jQuery(document).ready( function() {
	
	var import_type = jQuery("input[name='import_to']:checked").val()
	jQuery('#import-to-'+import_type).show();
	jQuery("input[name='import_to']").change( function(e) {
		jQuery('.import-to').hide(400);
		jQuery('#import-to-'+jQuery(this).val() ).show(400);
	});
});
</script>
<?php
	return;
		echo '<div class="narrow">';
		
		echo '<h2>'.__('Import saved Feed', 'powerpress') .'</h2>';
		wp_import_upload_form("admin.php?import=rss-podcast&amp;step=1");
		echo '</div>';
	}

	function _normalize_tag( $matches ) {
		return '<' . strtolower( $matches[1] );
	}
	
	function import_program_info($overwrite=false, $download_itunes_image=false, $category_id = '', $feed_slug ='', $post_type = '', $ttid = '') {
		$Feed = get_option('powerpress_feed_podcast', array() );
		if( empty($Feed) )
			$Feed = get_option('powerpress_feed', array());
		
		if( !empty($category_id) ) {
			$Feed = get_option('powerpress_cat_feed_'.$category_id, array());
		} else if( !empty($feed_slug) ) {
			$Feed = get_option('powerpress_feed_'.$feed_slug, array());
		}  else if( !empty($ttid) ) {
			$Feed = get_option('powerpress_taxonomy_'.$ttid, array());
		}
		
		$NewSettings = array();

        // ==================
        // BASIC CHANNEL TAGS
        // ==================

        // <title>
        if (($v = $this->parser->get_channel_title()) !== null && ($overwrite || empty($Feed['title']))) {
            $NewSettings['title'] = $v;
        }

        // <language>
        if (($v = $this->parser->get_channel_language()) !== null && ($overwrite || empty($Feed['rss_language']))) {
            $NewSettings['rss_language'] = $v;
        }

        // <copyright>
        if (($v = $this->parser->get_channel_copyright()) !== null && ($overwrite || empty($Feed['copyright']))) {
            $NewSettings['copyright'] = $v;
        }

        // <description>
        if (($v = $this->parser->get_channel_description()) !== null && ($overwrite || empty($Feed['description']))) {
            $NewSettings['description'] = $v;
        }

        // ===================
        // ITUNES CHANNEL TAGS
        // ===================

		// <itunes:author>
        if (($v = $this->parser->get_channel_author()) !== null && ($overwrite || empty($Feed['itunes_talent_name']))) {
            $NewSettings['itunes_talent_name'] = $v;
        }

		// <itunes:owner> -> <itunes:name>
        if (empty($NewSettings['itunes_talent_name'])
            && ($v = $this->parser->get_channel_owner_name()) !== null
            && ($overwrite || empty($Feed['itunes_talent_name']))) {
            $NewSettings['itunes_talent_name'] = $v;
        }

		// <itunes:owner> -> <itunes:email>
        if (($v = $this->parser->get_channel_owner_email()) !== null && ($overwrite || empty($Feed['email']))) {
            $NewSettings['email'] = $v;
        }
		
		// <itunes:explicit>
        if (($v = $this->parser->get_channel_explicit()) !== null && ($overwrite || empty($Feed['itunes_explicit']))) {
            $NewSettings['itunes_explicit'] = $v;
        }

        // <itunes:type>
        if (($v = $this->parser->get_channel_type()) !== null && ($overwrite || empty($Feed['itunes_type']))) {
            $NewSettings['itunes_type'] = $v;
        }

		// <itunes:image>
        $itunes_image = $this->parser->get_channel_artwork();
        if ($itunes_image !== null) {
			// download the image then save it locally...
			if( $download_itunes_image ) {
				
				echo '<div id="pp-imported-artwork">';
				echo '<p style="margin: 0 0 1ch 0;"><strong>'. __('Program image', 'powerpress') .'</strong></p>';
				
				$upload_path = false;
				$upload_url = false;
				$UploadArray = wp_upload_dir();
				if( false === $UploadArray['error'] )
				{
					$upload_path =  $UploadArray['basedir'].'/powerpress/';
					$upload_url =  $UploadArray['baseurl'].'/powerpress/';
					$filename = str_replace(" ", "_", basename($itunes_image) );
					
					if( file_exists($upload_path . $filename ) )
					{
						$filenameParts = pathinfo($filename);
						if( !empty($filenameParts['extension']) ) {
							do {
								$filename_no_ext = substr($filenameParts['basename'], 0, (strlen($filenameParts['extension'])+1) * -1 );
								$filename = sprintf('%s-%03d.%s', $filename_no_ext, md5( rand(0, 999) . time() ), $filenameParts['extension'] );
							} while( file_exists($upload_path . $filename ) );
						}
					}
					
					$options = array();
					$options['user-agent'] = 'Blubrry PowerPress/'.POWERPRESS_VERSION;
					if( !empty($_GET['import']) && $_GET['import'] == 'powerpress-squarespace-rss-podcast' )
						$options['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
					$options['timeout'] = 10;
					
					$image_data = '';
					$response = wp_safe_remote_get($itunes_image, $options);
					if ( !is_wp_error( $response ) ) {
						$image_data = wp_remote_retrieve_body( $response );
					} else {
						$this->addError( __('Error downloading program image.', 'powerpress') );
					}
		
					if( !empty($image_data) ) {
						file_put_contents($upload_path.$filename, $image_data);
						$NewSettings['itunes_image'] = $upload_url . $filename;
                        echo "<img id='pp-onboarding-artwork-preview' src='{$NewSettings['itunes_image']}' alt='{$NewSettings['itunes_image']}' />";
                        echo '<ul class="ul-disc" id="pp-onboarding-artwork-link">';
						echo sprintf('Program image saved to:<br /> <a href="%s" style="margin-top: 1em;">%s</a>.', ($upload_url . $filename), ($upload_url . $filename) );
                        echo '</ul>';
					} else {
						$this->addError( __('No program image downloaded.', 'powerpress') );
						echo 'Error occurred downloading program image.';
					}
				} else {
					echo 'Unable to save image to local folder.';
				}

				echo "</div>";
			} else if( $overwrite || empty($Feed['itunes_image']) ) {
				$NewSettings['itunes_image'] = $itunes_image;
			}
		}

        // <itunes:category>
        foreach ($this->parser->get_channel_categories() as $i => $code) {
            $field = sprintf('apple_cat_%d', $i + 1);
            if ($overwrite || empty($Feed[$field])) {
                $NewSettings[$field] = $code;
            }
        }

        // <itunes:complete>
        if (($v = $this->parser->get_channel_complete()) !== null
            && empty($NewSettings['itunes_complete'])
            && ($overwrite || empty($Feed['itunes_complete']))) {
            $NewSettings['itunes_complete'] = $v;
        }

        // <itunes:block> + <podcast:block>
        if (($block = $this->parser->get_channel_block()) !== null) {
            // EXTRACT ITUNES BLOCK
            if ($block['itunes_block'] !== null && ($overwrite || empty($Feed['itunes_block']))) {
                $NewSettings['itunes_block'] = $block['itunes_block'];
            }

            // EXTRACT PCI BLOCK
            if ($block['block_all'] !== null && ($overwrite || empty($Feed['block_all']))) {
                $NewSettings['block_all'] = $block['block_all'];
            }
        }
		
        // ====================
        // PODCAST CHANNEL TAGS
        // ====================
		
        // <podcast:locked>
        // PRESERVE LOCK ON IMPORT, USER REQUIRED TO UNLOCK AT ORIGINAL FEED SOURCE
        if ($this->parser->get_channel_locked() && ($overwrite || empty($Feed['pp_enable_feed_lock']))) {
            $NewSettings['pp_enable_feed_lock'] = 1;
        }

        // <podcast:medium>
        if (($v = $this->parser->get_channel_medium()) !== null && ($overwrite || empty($Feed['medium']))) {
            $NewSettings['medium'] = $v;
        }

        // <podcast:guid>
        if (($v = $this->parser->get_channel_guid()) !== null && ($overwrite || empty($Feed['podcast_guid']))) {
            $NewSettings['podcast_guid'] = $v;
        }

        // <podcast:license>
        if (($license = $this->parser->get_channel_license()) !== null) {
            // EXTRACT COPYRIGHT TITLE
            if ($license['copyright'] !== null
                && empty($NewSettings['copyright'])
                && ($overwrite || empty($Feed['copyright']))) {
                $NewSettings['copyright'] = $license['copyright'];
            }

            // EXTRACT COPYRIGHT URL
            if ($license['url'] !== null && ($overwrite || empty($Feed['copyright_url']))) {
                $NewSettings['copyright_url'] = $license['url'];
            }
        }
		
        // <podcast:funding>
        if (($funding = $this->parser->get_channel_funding()) !== null) {
            // EXTRACT FUNDING URL
            if ($funding['url'] !== null && ($overwrite || empty($Feed['donate_url']))) {
                $NewSettings['donate_url'] = $funding['url'];
            }

            // EXTRACT FUNDING LABEL
            if ($funding['label'] !== null && ($overwrite || empty($Feed['donate_label']))) {
                $NewSettings['donate_label'] = $funding['label'];
            }
        }

        // <podcast:person>
        $persons = $this->parser->get_channel_persons();
        if (!empty($persons) && ($overwrite || empty($Feed['credits']))) {
            $NewSettings['credits'] = $persons;
        }

        // <podcast:location>
        $locations = $this->parser->get_channel_locations();
        if (!empty($locations) && ($overwrite || empty($Feed['location']))) {
            $NewSettings['location'] = $locations;
        }
    
        // <podcast:podroll> + <podcast:remoteItem>
        $podroll_items = $this->parser->get_channel_podroll();
        $standalone_items = $this->parser->get_channel_remote_items();
        $remote_items = array_merge($podroll_items, $standalone_items);
        if (!empty($remote_items) && ($overwrite || empty($Feed['remote_items']))) {
            $NewSettings['remote_items'] = $remote_items;
        }

        // <podcast:value> -> <podcast:valueRecipients>
        $value_recipients = $this->parser->get_channel_value_recipients();
        if (!empty($value_recipients) && ($overwrite || empty($Feed['value_recipients']))) {
            $NewSettings['value_recipients'] = $value_recipients;
        }

        // <podcast:updateFrequency>
        if (($frequency = $this->parser->get_channel_update_frequency()) !== null) {
            // EXTRACT COMPLETE
            if (isset($frequency['complete'])) {
                if ($frequency['complete'] === true && ($overwrite || empty($Feed['itunes_complete']))) {
                    $NewSettings['itunes_complete'] = 1;
                }
                unset($frequency['complete']);
            }

            // EXTRACT DTSTART
            if (isset($frequency['dtstart'])) {
                if ($overwrite || empty($Feed['dtstart'])) {
                    $NewSettings['dtstart'] = $frequency['dtstart'];
                }
                unset($frequency['dtstart']);
            }

            // EXTRACT FREQUENCY
            if (!empty($frequency) && ($overwrite || empty($Feed['update_frequency']))) {
                $NewSettings['update_frequency'] = $frequency;
            }
        }

        // <podcast:txt>
        $txt_tags = array_values(array_filter(
            $this->parser->get_channel_txt_tags(),
            function ($entry) {
                return ($entry['purpose'] ?? null) !== 'applepodcastsverify';
            }
        ));
        if (!empty($txt_tags) && ($overwrite || empty($Feed['txt_tag']))) {
            $NewSettings['txt_tag'] = $txt_tags;
        }

        // <podcast:liveItem>
        $live_item = $this->parser->get_channel_live_item();
        if ($live_item !== null && ($overwrite || empty($Feed['live_item']))) {
            $NewSettings['live_item'] = $live_item;
        }

        // =====================
        // RAWVOICE CHANNEL TAGS
        // =====================

        // <rawvoice:rating>
        if (($v = $this->parser->get_channel_rating()) !== null && ($overwrite || empty($Feed['parental_rating']))) {
            $NewSettings['parental_rating'] = $v;
        }

        // <rawvoice:frequency>
        if (($v = $this->parser->get_channel_frequency()) !== null 
            && empty($NewSettings['update_frequency'])
            && ($overwrite || empty($Feed['update_frequency']))) {
            $NewSettings['frequency'] = $v;
        }

        // <rawvoice:donate href>
        if (($donate = $this->parser->get_channel_donate()) !== null) {
            // EXTRACT URL
            if ($donate['url'] !== null
                && empty($NewSettings['donate_url'])
                && ($overwrite || empty($Feed['donate_url']))) {
                $NewSettings['donate_url'] = $donate['url'];
            }

            // EXTRACT LABEL
            if ($donate['label'] !== null
                && empty($NewSettings['donate_label'])
                && ($overwrite || empty($Feed['donate_label']))) {
                $NewSettings['donate_label'] = $donate['label'];
            }
        }

        // <rawvoice:subscribe>
        foreach ($this->parser->get_channel_subscribe_urls() as $key => $value) {
            if ($overwrite || empty($Feed[$key])) {
                $NewSettings[$key] = esc_url_raw($value);
            }
        }

        // ========
        // SANITIZE
        // ========

        $nss_url_keys = ['itunes_image', 'copyright_url', 'donate_url'];
        foreach ($nss_url_keys as $nss_key) {
            if (isset($NewSettings[$nss_key]))
                $NewSettings[$nss_key] = esc_url_raw($NewSettings[$nss_key]);
        }
        if (isset($NewSettings['email']))
            $NewSettings['email'] = sanitize_email($NewSettings['email']);

        // ============
        // SAVE HANDLER
        // ============

		if( !empty($NewSettings) )
		{
			if( empty($category_id) && empty($feed_slug) && empty($post_type) && empty($ttid) ) {
				// Save here..
				if( get_option('powerpress_feed_podcast') ) { // If the settings were moved to the podcast channels feature...
					powerpress_save_settings($NewSettings, 'powerpress_feed_podcast' ); // save a copy here if that is the case.
				} else {
					powerpress_save_settings($NewSettings, 'powerpress_feed' );
				}
			} else if( !empty($category_id) ) {
				
				// First save the new settings into the specified options row...
				powerpress_save_settings($NewSettings, 'powerpress_cat_feed_'.$category_id ); // save a copy here if that is the case.
				
				// Then add the category id to the global array...
				$CurrentSettings = powerpress_get_settings('powerpress_general');
				if( !in_array($category_id, $CurrentSettings['custom_cat_feeds']) )
				{
					$NewSettings = array();
					if( !empty($CurrentSettings['custom_cat_feeds']) )
						$NewSettings['custom_cat_feeds'] = $CurrentSettings['custom_cat_feeds'];
					$NewSettings['custom_cat_feeds'][] = $category_id;
					if( empty($CurrentSettings['cat_casting']) ) {
						$NewSettings['cat_casting'] = 1; // Turn on category podcasting if not enabled
						$NewSettings['cat_casting_podcast_feeds'] = 1;
						$NewSettings['cat_casting_strict'] = 1;
					}
					
					powerpress_save_settings($NewSettings);
				}
			} else if ( !empty($post_type) ) {
				// TODO
			} else if ( !empty($feed_slug) ) {
				powerpress_save_settings($NewSettings, 'powerpress_feed_'.$feed_slug );
			} else if ( !empty($ttid) ) {
				powerpress_save_settings($NewSettings, 'powerpress_taxonomy_'. $ttid );
			}
			
			

			$field_labels = [
				'title' => __('Feed Title (Show Title)', 'powerpress'),
				'rss_language' => __('Feed Language', 'powerpress'),
				'description' => __('Feed Description', 'powerpress'),
				'copyright' => __('Copyright', 'powerpress'),
				'copyright_url' => __('Copyright URL', 'powerpress'),
				'itunes_talent_name' => __('Author Name', 'powerpress'),
				'itunes_image' => __('Program Image', 'powerpress'),
				'itunes_explicit' => __('Explicit', 'powerpress'),
				'itunes_type' => __('Show Type', 'powerpress'),
				'email' => __('Email', 'powerpress'),
				'itunes_cat_1' => __('Category', 'powerpress'),
				'itunes_cat_2' => __('Category 2', 'powerpress'),
				'itunes_cat_3' => __('Category 3', 'powerpress'),
				'apple_cat_1' => __('Apple Podcasts Category', 'powerpress'),
				'apple_cat_2' => __('Apple Podcasts Category 2', 'powerpress'),
				'apple_cat_3' => __('Apple Podcasts Category 3', 'powerpress'),
				'medium' => __('Medium', 'powerpress'),
				'podcast_guid' => __('Podcast GUID', 'powerpress'),
				'donate_url' => __('Donate URL', 'powerpress'),
				'donate_label' => __('Donate Label', 'powerpress'),
				'credits' => __('Channel Credits', 'powerpress'),
				'location' => __('Channel Location', 'powerpress'),
				'value_recipients' => __('Value Recipients', 'powerpress'),
				'txt_tag' => __('Txt Tags', 'powerpress'),
				'parental_rating' => __('Parental Rating', 'powerpress'),
				'frequency' => __('Frequency (Legacy)', 'powerpress'),
				'update_frequency' => __('Update Frequency', 'powerpress'),
				'dtstart' => __('Start Date', 'powerpress'),
				'remote_items' => __('Related Shows / Podroll', 'powerpress'),
			];

			echo '<p><strong>'. __('Program information imported', 'powerpress') .'</strong></p>';
			echo '<ul class="ul-disc">';
			foreach( $NewSettings as $field => $value )
			{
				if( $field === 'rss2_image' ) continue;
				$label = $field_labels[$field] ?? $field;
				echo '<li>' . esc_html($label) . '</li>';
			}
			echo '</ul>';
		}
	}
	
	function import_item($feed_item, $MatchFilter, $import_blog_posts=false, $category_strict='', $feed_slug='', $post_type = '', $taxonomy = '', $term = '', $remove_query_string = false, $post_status = 'publish', $match_existing_posts = false) {
		global $wpdb;
		$this->m_item_pos++;
		
		$matches = array();

        // <title>
        $raw_title = $this->parser->get_item_post_title($feed_item);
        if ($raw_title === null) {
            echo sprintf(__('Empty episode title for item %d', 'powerpress'), $this->m_item_pos);
            $this->m_item_skipped_count++;
            return false;
        }
		$post_title = $this->_sanatize_tag_value($raw_title);
		
		// Look for an enclosure, if not found skip it...
        // <enclosure>
        $enclosure = $this->_parse_enclosure($feed_item, $category_strict);
		if (empty($enclosure['url'])) {
			echo sprintf(__('No Media found for item %d', 'powerpress'), $this->m_item_pos);
			//echo '<pre>'.htmlspecialchars($post).'</pre>'; // Uncomment for debugging
			if( empty($import_blog_posts) ) {
				$this->m_item_skipped_count++;
				return false;
			}

			echo ' - ';
		}
		
		// GUID has to be last, as we will use the media URL as the guid as a last resort
        // <guid>
        $guid = $this->parser->get_item_guid($feed_item);
        if ($guid !== null) {
            $guid = $this->_sanatize_tag_value($guid);
        }
        else if (!empty($enclosure['url'])) {
            $guid = $enclosure['url'];    
        }
        

		$media_url = '';
		if( !empty($enclosure['url']) ) {
			if( !empty($remove_query_string) && !empty($enclosure['url']) && strstr($enclosure['url'], '?') ) {	
				$enclosure['url'] = strtok($enclosure['url'],'?');  //Tund3r: added for libsyn
			}
			$media_url = $enclosure['url'];
		}
		if(preg_match('/https?:\/\/(www\.)?media\.blubrry\.com\//m', $media_url)) {
            $this->isHostedOnBlubrry = true;
        }

        // <pubDate>
        $ts = $this->parser->get_item_pubdate($feed_item);
		$post_date_gmt = gmdate('Y-m-d H:i:s', $ts ?? 0);
		$post_date = get_date_from_gmt( $post_date_gmt );
		
		// Before we go any further, lets see if we have imported this one already...
		$exists = $this->_find_post(
			(empty($MatchFilter['match_guid'])?'':$guid),
			(empty($MatchFilter['match_title'])?'':$post_title),
			(empty($MatchFilter['match_date'])?'':$post_date),
			(empty($MatchFilter['match_filename'])?'':$media_url),
			$feed_slug
			);
		
		if( !empty($exists) )
		{
		    $existing_enclosure_data = true;
		    if ($match_existing_posts) {
                // check for enclosure in the existing post
                if ('podcast' == $feed_slug || '' == $feed_slug)
                    $existing_enclosure_data = get_post_meta($exists, 'enclosure', true);
                else
                    $existing_enclosure_data = get_post_meta($exists, '_' . $feed_slug . ':enclosure', true);
            }

            if (!$existing_enclosure_data) {
                // if there's no enclosure yet, we can add the one from the feed
                $post_to_save = compact('post_date', 'post_date_gmt', 'post_title', 'guid', 'enclosure');

                if( !empty($post_type) ) // If the post should go into a custom post type...
                {
                    $post_to_save['post_type'] = $post_type;
                }
                $this->m_item_inserted_count++;

                $post_id = $this->_import_post_to_db($post_to_save, $feed_slug, $exists);

                ?>
                <td><?php echo htmlspecialchars($post_title) ?></td>
                <td>&#x2714;&#xFE0F;</td>
                <td><?php echo htmlspecialchars(__('Episode Added to Existing Post', 'powerpress')); ?></td>
                <?php
                return true;
            } else {
                ?>
                <td><?php echo htmlspecialchars($post_title) ?></td>
                <td>&#x274c;</td>
                <td><?php echo htmlspecialchars(__('Episode Already Imported', 'powerpress')); ?></td>
                <?php
                $this->m_item_skipped_count++;
                return false;
            }
		}
		
		// Okay awesome, lets dig through the rest...
        // <category>
        $categories = $this->parser->get_item_categories($feed_item);
		

        // <content:encoded>
        $post_content = $this->parser->get_item_content($feed_item);
        $post_content = $post_content !== null ? $this->_sanatize_tag_value($post_content) : '';

        if (empty($post_content) && !empty($enclosure['summary'])) {
            $post_content = $enclosure['summary'];
        }
        
		// Clean up content
		$post_content = preg_replace_callback('|<(/?[A-Z]+)|', array( &$this, '_normalize_tag' ), $post_content);
		$post_content = str_replace('<br>', '<br />', $post_content);
		$post_content = str_replace('<hr>', '<hr />', $post_content);

		$post_author = get_current_user_id();
		
		// Save this episode to the database...
		$post_to_save = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_status', 'guid', 'categories', 'enclosure');
		
		if( !empty($post_type) ) // If the post should go into a custom post type...
		{
			$post_to_save['post_type'] = $post_type;
		}
		$this->m_item_inserted_count++;
		
		$post_id = $this->_import_post_to_db($post_to_save, $feed_slug);
		if( empty($post_id) || is_wp_error($post_id) ) {
		    ?>
              <td><?php echo htmlspecialchars($post_title) ?></td>
              <td><?php echo htmlspecialchars(__('Import Failed', 'powerpress')); ?></td>
              <td>&#x274c;</td>
		    <?php
			return false;
		}
		$permalink = get_permalink($post_id);
		?>
          <td><?php echo "<a href=\"".  esc_attr($permalink) ."\" target='_blank'>" . esc_html($post_title) . "</a>" ?></td>
          <td><?php echo htmlspecialchars(__('Episode Imported', 'powerpress')); ?></td>
          <td>&#x2714;&#xFE0F;</td>
		<?php
		
		// Display a link to the blog post
		//echo ' <a href="'. get_permalink($post_id) .'" target="_blank"><i class="wp-menu-image dashicons-before dashicons-admin-links"></i></a>';

		// Category strict
		if( !empty($category_strict) )
		{
			wp_set_post_categories( $post_id, array($category_strict), true );
		}
		
		// Set specific taxonomy term to this post
		if( !empty($taxonomy) && !empty($term) )
		{
			wp_set_post_terms( $post_id, array($term), $taxonomy, true );
		}
		
		return ( $post_id > 0 );
	}
	
	function _sanatize_tag_value($value)
	{
		if( !is_string($value) )
			return '';
		
		$value = trim($value);
		if( preg_match('/^<!\[CDATA\[(.*)\]\]>$/is', $value, $matches) ) {
			$value = $matches[1];
		} else {
			$value = html_entity_decode($value);
		}
		
		return $value;
	}

	function import_episodes($MatchFilter, $import_blog_posts=false, $import_item_limit=0, $category='', $feed_slug='', $post_type = '', $ttid = '', $remove_query_string = false, $post_status='publish', $match_existing_posts = false) {
		global $wpdb;
		@set_time_limit(60*15); // Give it 15 minutes
		$this->m_item_pos = 0;
		$taxonomy = '';
		$term = '';
		if( $ttid )
		{
			$TaxTermObj = get_term_by('term_taxonomy_id', $ttid);
			if( $TaxTermObj )
			{
				$term = $TaxTermObj->name;
				$taxonomy = $TaxTermObj->taxonomy;
				// Now get the post type if the taxonomy, which may not be "post"...
				$TaxonomyObj = get_taxonomy($taxonomy);
				// Set the post type to import into
				if( !empty($TaxonomyObj->object_type[0]) && $TaxonomyObj->object_type[0] != 'post' ) {
					$post_type = $TaxonomyObj->object_type[0];
				}
				// We should use the term's ID rather than it's name
				if( !empty($TaxonomyObj->hierarchical) ) {
					$term = intval($TaxTermObj->term_id);
				}
			}
			else
			{
				// Do not go any further, there is an error here!
				echo '<p><strong>';
				echo __('Error, unable to locate term taxonomy.', 'powerpress');
				echo '</strong></p>';
				return;
			}
		}
		
        $feed_items = $this->parser ? $this->parser->get_items() : [];
        $item_count = is_array($feed_items) ? count($feed_items) : 0;
		echo '<div class="pp_flex-grid">';
        ?>
        <style>
        #table_header {
          box-sizing: border-box;
          width: 100%;
          padding-bottom: 20px;
          margin-top: 3em;
        }
        table tbody td {
            padding: 10px 5px 12px 5px;
        }
        table {
          width: 100%;
          table-layout: fixed;
          border-collapse: separate;
          border: 1px solid rgba(144, 144, 144, 0.40);
          border-radius: 4px;
        }
        .left {
          float: left;
        }
        .right {
          float: right;
        }
        /*thead th {
          padding: 20px;
          border-bottom: 1px solid rgba(144, 144, 144, 0.40);
        }*/
        thead th:nth-child(1) {
          width: 3%
        }
        thead th:nth-child(2) {
          width: 58%;
        }
        thead th:nth-child(3) {
          width: 24%;
        }
        tbody td:nth-child(3) {
          text-align: right;
        }
        tbody td {
          padding: 15px 20px;
          border-bottom: 1px solid rgba(144, 144, 144, 0.40);
        }
        tbody td:nth-child(2) {
          font-weight: bold;
          text-align: left;
        }
        tbody td:nth-child(3) {
          text-align: right;
        }
        tbody td:nth-child(4) {
          text-align: right;
        }
        tbody tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }
        tbody tr:last-child td{
            border: unset;
        }
        tbody tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }
        .green-text {
          color: green;
        }
        .warning-text {
          color: orange;
        }
        .subtle-text {

        }
        </style>
        <div class="pp_col" style="flex: 5; margin: 0;">
        <div id="table_header">
            <strong class="left">Imported Episodes</strong>
            <span class="right"><?php echo  sprintf( __('%d Episodes Found', 'powerpress'), intval($item_count)) ?></span>
        </div>
        <table>
          <thead>
            <tr>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
        <?php
		@flush();

		$count = 0;
        $wpdb->query('START TRANSACTION');
		foreach( $feed_items as $feed_item )
		{
			$count++;

			if( $import_item_limit > 0 && $this->m_item_pos >= $import_item_limit ) {
				break;
			}

			echo "<tr><td>{$count}</td>";
			$this->import_item($feed_item, $MatchFilter, $import_blog_posts, $category, $feed_slug, $post_type, $taxonomy, $term, $remove_query_string, $post_status, $match_existing_posts);
			echo '</tr>';

			if( $count % 25 == 0 ) {
                $wpdb->query('COMMIT');
                $wpdb->query('START TRANSACTION');
				@flush();
            }
		}
        $wpdb->query('COMMIT');
	}

	function import() {
?>
        <h3><?php _e('PowerPress', 'powerpress') ?></h3>
<h5><?php _e('Importing Podcast', 'powerpress') ?>
<?php

		$result = false;
		if ( empty($_POST['podcast_feed_url']) ) {
			?><?php _e(' from uploaded file...', 'powerpress'); ?></h5><hr /><?php
			$result = $this->_import_handle_upload();
		}
		else
		{
			?><?php _e(' from URL: ', 'powerpress'); echo esc_html($_POST['podcast_feed_url']) ?></h5><hr /><?php
			$result = $this->_import_handle_url();
		}
		
		if( $result == false ) {
			$this->addError( __('Error occurred importing podcast.', 'powerpress') );
			return;
        }

        $this->parser = PowerPress_FeedParser::from_raw($this->m_content);
        if (is_wp_error($this->parser)) {
            $this->addError(__('Failed to parse podcast feed: ', 'powerpress') . $this->parser->get_error_message());
            $this->parser = null;
            return;
        }
		
		// Match posts by:
		$MatchFilter = array('match_guid'=>true);
		$MatchFilter['match_date'] = (!empty($_POST['match_date'])?true:false);
		$MatchFilter['match_title'] = (!empty($_POST['match_title'])?true:false);
		$MatchFilter['match_filename'] = (!empty($_POST['match_filename'])?true:false);

        $match_existing_posts = (!empty($_POST['match_existing_posts'])?true:false);
		$import_blog_posts = (!empty($_POST['import_blog_posts'])?true:false);
		$import_item_limit  = (!empty($_POST['import_item_limit'])?intval($_POST['import_item_limit']):0);
		$remove_query_string = (!empty($_POST['remove_query_string'])?true:false);
		$post_status = ( !empty($_POST['import_post_status']) ? $_POST['import_post_status']: 'publish' );
		$category  = (!empty($_POST['category'])?intval($_POST['category']):'');
		$feed_slug  = (!empty($_POST['feed_slug'])?($_POST['feed_slug']):'');
		$post_type = (!empty($_POST['post_type'])?($_POST['post_type']):'');
		$post_type_feed_slug = (!empty($_POST['post_type_feed_slug'])?($_POST['post_type_feed_slug']):'');
		$ttid = (!empty($_POST['podcast_ttid'])?intval($_POST['podcast_ttid']):'');
		//$import_  = (!empty($_POST['import_item_limit'])?intval($_POST['import_item_limit']):0);
		$import_to = 'default';
		if( !empty($_POST['import_to']) && $_POST['import_to'] != 'default' )
			$import_to = $_POST['import_to'];
		if( !empty($_REQUEST['import']) && $_REQUEST['import'] == 'powerpress-libsyn-rss-podcast' )
			$remove_query_string = true;

		// Libsyn feeds must always have this option enabled.
        if(!$remove_query_string && !empty($_POST['podcast_feed_url']) && strpos($_POST['podcast_feed_url'], 'libsyn') !== false){
            $remove_query_string = true;
        }
		
		// Set the correct parameters going in...
		switch( $import_to )
		{
			case 'category': {
				$feed_slug = '';
				$post_type = '';
				$ttid = '';
				if( empty($category) ) {
					echo '<p>No category selected.</p>';
					return;
				}
			}; break;
			case 'channel': {
				$category = '';
				$post_type = '';
				$ttid = '';
				if( empty($feed_slug) ) {
					echo '<p>No podcast channel selected.</p>';
					return;
				}
			}; break;
			case 'post_type': {
				$category = '';
				$feed_slug = $post_type_feed_slug;
				$ttid = '';
				
				if( empty($feed_slug) ) {
					echo '<p>No feed slug specified.</p>';
					return;
				}
				if( empty($post_type) ) {
					echo '<p>No post type specified.</p>';
					return;
				}
			}; break;
			case 'taxonomy': {
				$category = '';
				$feed_slug = '';
				$post_type = '';
				
				if( empty($ttid) ) {
					echo '<p>No taxonomy podcast selected.</p>';
					return;
				}
					
			}; break;
			case 'default':
			default: {
				$category = '';
				$feed_slug = '';
				$post_type = '';
				$ttid = '';
			}; break;
		}

		// Need to check for podcast:locked tag before importing anything
		if (preg_match_all('/<podcast:locked\s*owner=["\'](.*)["\']\s*>([\s\S]*)<\/podcast:locked>/', $this->m_content, $matches)) {
            if (strpos($matches[2][0], 'yes') !== false) {
                echo '<p>Failed to import: podcast feed is locked.</p>';
                return;
            }
        }

		// First import program info...
		if( preg_match('/^(.*)<item>/is', $this->m_content, $matches) )
		{
			if( $import_to == 'default' ) {
				$overwrite_program_info = (!empty($_POST['import_overwrite_program_info'])?true:false);
				$import_itunes_image = (!empty($_POST['import_itunes_image'])?true:false);
				if( $overwrite_program_info || $import_itunes_image )
					$this->import_program_info($overwrite_program_info, $import_itunes_image);
			} else if( $import_to == 'category' ) {
				$overwrite_program_info = (!empty($_POST['import_overwrite_program_info_category'])?true:false);
				$import_itunes_image = (!empty($_POST['import_itunes_image_category'])?true:false);
				if( $overwrite_program_info || $import_itunes_image )
					$this->import_program_info($overwrite_program_info, $import_itunes_image, $category);
			} else if( $import_to == 'channel' ) {
				$overwrite_program_info = (!empty($_POST['import_overwrite_program_info_channel'])?true:false);
				$import_itunes_image = (!empty($_POST['import_itunes_image_channel'])?true:false);
				if( $overwrite_program_info || $import_itunes_image )
					$this->import_program_info($overwrite_program_info, $import_itunes_image, false, $feed_slug);
			} else if( $import_to == 'post_type' ) {
				$overwrite_program_info = (!empty($_POST['import_overwrite_program_info_post_type'])?true:false);
				$import_itunes_image = (!empty($_POST['import_itunes_image_post_type'])?true:false);
				if( $overwrite_program_info || $import_itunes_image )
					$this->import_program_info($overwrite_program_info, $import_itunes_image, false, $feed_slug, $post_type);
			} else if( $import_to == 'taxonomy' ) {
				$overwrite_program_info = (!empty($_POST['import_overwrite_program_info_taxonomy'])?true:false);
				$import_itunes_image = (!empty($_POST['import_itunes_image_taxonomy'])?true:false);
				if( $overwrite_program_info || $import_itunes_image )
					$this->import_program_info($overwrite_program_info, $import_itunes_image, false, false, false, $ttid);
			}
		}
		
		$this->import_episodes($MatchFilter, $import_blog_posts, $import_item_limit, $category, $feed_slug, $post_type, $ttid, $remove_query_string, $post_status, $match_existing_posts);
		
		?>
        <tr><td colspan="4" style="text-align: right">
        <?php
        if ($this->m_item_inserted_count != 0) {
            echo $this->m_item_inserted_count . " Episodes Imported";
        }
        if ($this->m_item_skipped_count != 0) {
            if($this->m_item_inserted_count != 0) {
                echo ' / ';
            }
            echo $this->m_item_skipped_count . " Episodes Skipped";
        }
        ?>
        </td></tr>
		<?php
		echo '</tbody></table></div></div>';
		$migrated_to_blubrry = false;
		if( !empty($_POST['migrate_to_blubrry'])  && !empty($GLOBALS['pp_migrate_media_urls']) ) {
			require_once( POWERPRESS_ABSPATH .'/powerpressadmin-migrate.php');
			$migrated_to_blubrry = true;
			
			$update_option = true;
			$QueuedFiles = get_option('powerpress_migrate_queued');
			if( !is_array($QueuedFiles) ) {
				$QueuedFiles = array();
				$update_option = false;
			}
			
			$add_urls = '';
			foreach( $GLOBALS['pp_migrate_media_urls'] as $meta_id => $url )
			{
				if( empty($QueuedFiles[ $meta_id ]) ) { // Add to the array if not already added
					$QueuedFiles[ $meta_id ] = $url;
					if( !empty($add_urls) ) {
						$add_urls .= "\n";
					}
					$this->m_item_migrate_count++;
					$add_urls .= $url;
				}
			}
            if (!isset($_GET['from']) || ($_GET['from'] != 'gs' || $_GET['from'] != 'onboarding')) {
                echo '<h3>';
                echo __('Migration request...', 'powerpress');
                echo '</h3>';
                echo '<pre style="border: 1px solid #333; background-color: #FFFFFF; padding: 4px 8px; white-space: pre-wrap; word-break: break-all;">';
                echo $add_urls;
                echo '</pre>';
            }
			$UpdateResults = powepress_admin_migrate_add_urls($add_urls);
			if( !empty($UpdateResults) )
			{
				echo '<p>Migration queued successfully.</p>';
				// Queued ok...
				if( $update_option )
					update_option('powerpress_migrate_queued', $QueuedFiles);
				else
					add_option('powerpress_migrate_queued', $QueuedFiles, '', 'no');
			}
			else
			{
				echo '<p>Failed to request migration.</p>';
			}
		}
		powerpress_page_message_print();
		if( !empty( $this->m_item_migrate_count ) )
			echo '<p>'. sprintf(__('Media files queued for migration: %d', 'powerpress'), $this->m_item_migrate_count).'</p>';
		
		echo '';
		if( $migrated_to_blubrry ) {
			echo '<p>'. sprintf(__('Visit %s to monitor the migration process.','powerpress'), '<strong><a href="'.admin_url('admin.php?page=powerpress/powerpressadmin_migrate.php') .'">'. __('Migrate Media', 'powerpress') .'</a></strong>' ). '</p>';
		}
		$nextUrl = '';
		$GeneralSettings = powerpress_get_settings('powerpress_general');
		if(!empty($_GET['from']) && $_GET['from'] == 'onboarding') {
		    if (isset($GeneralSettings['blubrry_hosting']) && $GeneralSettings['blubrry_hosting'] != null) {
                $nextUrl = admin_url("admin.php?page=powerpressadmin_basic&import=true&migrate=true");
            } else {
                if ($this->isHostedOnBlubrry) {
                    $pp_nonce = powerpress_login_create_nonce();
                    $nextUrl = add_query_arg( '_wpnonce', $pp_nonce, admin_url("admin.php?page=powerpressadmin_basic&step=blubrrySignin&import=true"));
                } else {
                    $nextUrl = admin_url("admin.php?page=powerpressadmin_basic&step=nohost&import=true&from=import");
                }
            }
        }
		else if (!empty($_GET['from']) && $_GET['from'] == 'gs') {
            if (isset($GeneralSettings['blubrry_hosting']) && $GeneralSettings['blubrry_hosting'] != null) {
                $nextUrl = admin_url("admin.php?page=powerpressadmin_basic&import=true&migrate=true");
            } else {
                if ($this->isHostedOnBlubrry) {
                    $pp_nonce = powerpress_login_create_nonce();
                    $nextUrl = add_query_arg( '_wpnonce', $pp_nonce, admin_url("admin.php?page=powerpressadmin_onboarding.php&step=blubrrySignin&import=true"));
                } else {
                    $nextUrl = admin_url("admin.php?page=powerpressadmin_onboarding.php&step=nohost&import=true&from=import");
                }
            }
		}
		if(!empty($_GET['from'])) {
        ?>

            <div class="pp_col" style="padding: 20px 0px;margin-top: 2em;">
                <div class="pp_button-container" style="float: right;">
                    <a href="<?php echo htmlspecialchars($nextUrl) ?>"><button name="submit" type="button" class="pp_button" value="Import Podcast"><span>Continue</span></button></a>
                </div>

            </div>
        <?php
		}
	}

	function dispatch() {
		
		$this->m_step = 0;
		if( !empty($_POST['step']) )
			$this->m_step = intval($_POST['step']);
		else if( !empty($_GET['step']) )
			$this->m_step = intval($_GET['step']);
			
		// Drop back down a step if not setup for hosting...
		if( !empty($_POST['migrate_to_blubrry']) ) {
			$Settings = get_option('powerpress_general', array());
            $creds = get_option('powerpress_creds');
			if( empty($Settings['blubrry_auth']) && !$creds ) {
				echo '<div class="notice is-dismissible updated"><p>'. sprintf(__('You must have a blubrry Podcast Hosting account to continue.', 'powerpress')) .' '. '<a href="https://blubrry.com/services/podcast-hosting/" target="_blank">'. __('Learn More', 'powerpress') .'</a>'. '</p></div>';
				$this->m_step = 0; // Drop back a step
			}
		}
		
		$this->header();

		switch ($this->m_step) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				check_admin_referer('import-powerpress-rss');
				$result = $this->import();
				if ( is_wp_error( $result ) )
					echo htmlspecialchars($result->get_error_message());
				break;
		}

	}

	function get_step() {

		return $this->m_step;
	}
	
	function _find_post_by_guid($guid)
	{
		global $wpdb;

		$post_guid = wp_unslash( sanitize_post_field( 'guid', $guid, 0, 'db' ) );
        if (empty($post_guid)) return 0;

		$query = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid = %s", $post_guid);
        return intval($wpdb->get_var($query));
	}
	
	function _find_post_by_title($title)
	{
		global $wpdb;

		$post_guid = wp_unslash( sanitize_post_field( 'post_title', $title, 0, 'db' ) );

		$query = "SELECT ID FROM $wpdb->posts WHERE 1=1 ";
		$args = array();

		if ( !empty ( $post_guid ) ) {
			$query .= 'AND post_title = %s';
			$args[] = $title;
		}

		if ( !empty ( $args ) ) {
			$found = intval( $wpdb->get_var( $wpdb->prepare($query, $args) ) );
			if( $found > 0 )
				return $found;
		}
		
		return 0;
	}
	
	function _find_post_by_date($date)
	{
		global $wpdb;

		$post_guid = wp_unslash( sanitize_post_field( 'post_date', $date, 0, 'db' ) );

		$query = "SELECT ID FROM $wpdb->posts WHERE 1=1 ";
		$args = array();

		if ( !empty ( $post_guid ) ) {
			$query .= 'AND post_date = %s';
			$args[] = $date;
		}

		if ( !empty ( $args ) ) {
			$found = intval( $wpdb->get_var( $wpdb->prepare($query, $args) ) );
			if( $found > 0 )
				return $found;
		}
		
		return 0;
	}
	
	function _find_post_by_enclosure_filename($filename, $feed_slug = '')
	{
		global $wpdb;
		
		$meta_key = 'enclosure';
		if( !empty($feed_slug) && $feed_slug != 'podcast' )
			$meta_key = '_'. $feed_slug .':enclosure';
		
        $like = '%/' . $wpdb->esc_like($filename) . '%';
		
		$query = "SELECT p.ID ";
		$query .= "FROM {$wpdb->posts} AS p ";
		$query .= "INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id ";
		$query .= "WHERE pm.meta_key = %s ";
		$query .= "AND pm.meta_value LIKE %s ";
		$query .= "AND p.post_type != 'revision' ";
		$query .= "GROUP BY p.ID ";
		$query .= "ORDER BY p.post_date ASC LIMIT 1 "; // Make sure we use the oldest date
		$query = $wpdb->prepare($query, $meta_key, $like);
		
		$results = $wpdb->get_results($query, ARRAY_A);
		if( !empty($results) )
		{
			foreach( $results as $null => $row ) {
				return (int) $row['ID'];
			}
		}
		
		return 0;
	}
	
	function _find_post($guid = '', $title = '', $date = '', $media_url = '', $feed_slug='') {
		global $wpdb;
		
		if( !empty($guid) )
		{
			$found = $this->_find_post_by_guid($guid);
			if( $found )
				return $found;
		}
		
		if( !empty($media_url) )
		{
			$filename = basename(strtok($media_url, '?'));
			if( !empty($filename) ) {
				$found = $this->_find_post_by_enclosure_filename($filename, $feed_slug);
				if( $found )
					return $found;
			}
		}
		
		if( !empty($title) )
		{
			$found = $this->_find_post_by_title($title);
			if( $found )
				return $found;
		}
		
		if( !empty($date) )
		{
			$found = $this->_find_post_by_date($date);
				return $found;
		}

		return 0;
	}
	
	function _import_post_to_db($post, $feed_slug = '', $post_id = false)
	{
	    global $wpdb;
        $categories = $post['categories'] ?? [];
        $enclosure = $post['enclosure'] ?? [];
	    if ($post_id === false) {
            if (isset($post['post_content']))
                $post['post_content'] = wp_kses_post($post['post_content']);
            if (isset($post['post_title']))
                $post['post_title'] = sanitize_text_field($post['post_title']);
            $post_id = wp_insert_post($post);
            if (0 != count($categories))
                wp_create_categories($categories, $post_id);
        }

		if ( is_wp_error( $post_id ) )
			return $post_id;
		if (!$post_id) {
			_e('Couldn&#8217;t get post ID', 'powerpress');
			return false;
		}

		//Update the post to overwrite wordpress's guid (or the old guid)
		$query = $wpdb->prepare("UPDATE {$wpdb->posts} SET guid=%s WHERE ID=%d", $post['guid'], $post_id);
		$return = $wpdb->query($query);

		// If the GUID does not start with a http or https protocol, lets also save it to this custom field so it gets picked up as it was from the original source.
		if( preg_match('/^https?:\/\//i', $post['guid']) == false ) {
			add_post_meta($post_id, '_powerpress_guid', $post['guid'], true);
		}

					
		if( !empty($enclosure['url']) )
		{
            // vts stored in wp_options instead of serialized metadata, early extract -> unset
            if (!empty($enclosure['_pending_vts'])) {
                $vts_feed_slug = empty($feed_slug) ? 'podcast' : $feed_slug;
                $vts_feed_slug = sanitize_key($vts_feed_slug ?: 'podcast');
                update_option('vts_' . $vts_feed_slug . '_' . $post_id, $enclosure['_pending_vts']);
                unset($enclosure['_pending_vts']);
            }

			$encstring = $enclosure['url'] . "\n" . $enclosure['length'] . "\n" . $enclosure['type'];
			$reserved = ['url', 'length', 'type', 'category'];
            $serialize = [];

            foreach ($enclosure as $key => $value) {
                if (in_array($key, $reserved, true)) continue;

                if ($value === null || $value === '' || $value === []) continue;

                if ($key === 'duration' && function_exists('powerpress_raw_duration')) {
                    $value = powerpress_raw_duration($value);
                }

                $serialize[$key] = $value;
            }
            

			if( !empty($serialize) )
				$encstring .= "\n". serialize( $serialize );
				
			if( empty($feed_slug) || $feed_slug == 'podcast' ) // 'podcast' == $feed_slug || '' == $feed_slug
				$meta_id = add_post_meta($post_id, 'enclosure', $encstring, true);
			else
				$meta_id = add_post_meta($post_id, '_'. $feed_slug .':enclosure', $encstring, true);
		
			if( $meta_id ) {
				if( empty($GLOBALS['pp_migrate_media_urls']) )
					$GLOBALS['pp_migrate_media_urls'] = array();
				$GLOBALS['pp_migrate_media_urls'][ $meta_id ] = $enclosure['url'];
			}
		}
		return $post_id;
	}
	
    function _parse_enclosure($feed_item, $category_strict='') {
        if (!$this->parser || !$feed_item) return [];

        $enc = $this->parser->get_item_enclosure($feed_item);
        $url = $enc ? esc_url_raw($this->parser->get_item_enclosure_url($enc)) : '';

        if (!empty($url)) {
            $enclosure = [
                'url' => $url,
                'length' => $this->parser->get_item_enclosure_length($enc) ?? 1,
                'type' => $this->parser->get_item_enclosure_type($enc) ?? '',
            ];
        } else {
            $enclosure = $this->_enclosure_from_alternate($feed_item);
        }

        if (empty($enclosure['url'])) return [];

        if (empty($enclosure['type'])) {
            $enclosure['type'] = powerpress_get_contenttype($enclosure['url']);
        }

        return array_merge($enclosure, $this->_parse_episode_metadata($feed_item, $category_strict));
    }

    function _enclosure_from_alternate($feed_item) {
        $alts = $this->parser->get_item_alternate_enclosures($feed_item);
        if (empty($alts)) return [];

        $chosen = $alts[0];
        foreach ($alts as $alt) {
            if (!empty($alt['is_default'])) {
                $chosen = $alt;
                break;
            }
        }

        return [
            'url' => $chosen['url'],
            'length' => $chosen['length'] ?? 1,
            'type' => $chosen['type'] ?? '',
        ];
    }


	function _parse_episode_metadata($feed_item, $category_strict='')
    {
        if (!$this->parser || !$feed_item) return [];

        $meta = [];

        // ================
        // ITUNES NAMESPACE
        // ================

        // <itunes:title>
        if (($v = $this->parser->get_item_title($feed_item)) !== null)
            $meta['episode_title'] = $v;

        // <itunes:episodeType>
        if (($v = $this->parser->get_item_episode_type($feed_item)) !== null)
            $meta['episode_type'] = $v;

        // <itunes:duration>
        if (($v = $this->parser->get_item_duration($feed_item)) !== null)
            $meta['duration'] = $v;
        
        // <itunes:summary>
        if (($v = $this->parser->get_item_summary($feed_item)) !== null)
            $meta['show_notes'] = $v;

        // <itunes:author>
        if (($v = $this->parser->get_item_author($feed_item)) !== null)
            $meta['author'] = $v;

        // <itunes:block>
        if (($v = $this->parser->get_item_block($feed_item)) !== null)
            $meta['block'] = $v;

        // <itunes:image>
        if (($v = $this->parser->get_item_artwork($feed_item)) !== null)
            $meta['itunes_image'] = $v;

        // <itunes:explicit>
        if (($v = $this->parser->get_item_explicit($feed_item)) !== null)
            $meta['explicit'] = $v;

        // =======================
        // PODCAST INDEX NAMESPACE
        // =======================

        // <podcast:season> | <itunes:season>
        if (($v = $this->parser->get_item_pci_season($feed_item)) !== null) {
            $meta['season'] = $v;
        } elseif (($v = $this->parser->get_item_itunes_season($feed_item)) !== null) {
            $meta['season'] = $v;
        }

        // <podcast:episode display> | <itunes:episode>
        if (($ep = $this->parser->get_item_pci_episode($feed_item)) !== null) {
            $meta['episode_no'] = $ep['number'];
            if (!empty($ep['display']))
                $meta['episode_no_display'] = $ep['display'];
        } else if (($v = $this->parser->get_item_itunes_episode($feed_item)) !== null) {
            $meta['episode_no'] = $v;
        }

        // <podcast:funding>
        if (($v = $this->parser->get_item_funding($feed_item)) !== null) {
            if ($v['url'] !== null) $meta['donate_url'] = $v['url'];
            if ($v['label'] !== null) $meta['donate_label'] = $v['label'];
        }

        // <podcast:license>
        if (($v = $this->parser->get_item_license($feed_item)) !== null) {
            if ($v['copyright'] !== null) $meta['copyright'] = $v['copyright'];
            if ($v['url'] !== null) $meta['copyright_url'] = $v['url'];
        }
        
        // <podcast:chapters>
        if (($v = $this->parser->get_item_chapters($feed_item)) !== null) {
            $meta['pci_chapters'] = '1';
            $meta['pci_chapters_url'] = $v;
        }

        // <podcast:transcript>
        if (($v = $this->parser->get_item_transcript($feed_item)) !== null) {
            $meta['pci_transcript'] = '1';
            $meta['pci_transcript_url'] = $v['url'];
            if ($v['language'] !== null) $meta['pci_transcript_language'] = $v['language'];
        }

        // <podcast:person>
        $persons = $this->parser->get_item_persons($feed_item);
        if (!empty($persons))
            $meta['credits'] = $persons;

        // <podcast:soundbite>
        $soundbites = $this->parser->get_item_soundbites($feed_item);
        if (!empty($soundbites))
            $meta['soundbites'] = $soundbites;

        // <podcast:location>
        $locations = $this->parser->get_item_locations($feed_item);
        if (!empty($locations))
            $meta['location'] = $locations;

        // <podcast:contentLink>
        $content_links = $this->parser->get_item_content_links($feed_item);
        if (!empty($content_links))
            $meta['content_link'] = $content_links;

        // <podcast:txt>
        $txt_tags = $this->parser->get_item_txt_tags($feed_item);
        if (!empty($txt_tags))
            $meta['txt_tag'] = $txt_tags;

        // <podcast:alternateEnclosure>
        $alt_enclosures = $this->parser->get_item_alternate_enclosures($feed_item);
        if (!empty($alt_enclosures))
            $meta['alternate_enclosure'] = $alt_enclosures;

        // <podcast:value> -> <podcast:valueRecipient>
        $value_recipients = $this->parser->get_item_value_recipients($feed_item);
        if (!empty($value_recipients))
            $meta['value_recipients'] = $value_recipients;

        // <podcast:valueTimeSplit>
        $value_time_splits = $this->parser->get_item_value_time_splits($feed_item);
        if (!empty($value_time_splits)) {
            $vts_dict = [];
            $vts_order = [];
            foreach ($value_time_splits as $vts) {
                $vts_id = wp_generate_uuid4();
                $vts['vts_id'] = $vts_id;
                $vts_dict[$vts_id] = $vts;
                $vts_order[] = $vts_id;
            }

            $meta['_pending_vts'] = $vts_dict;
            $meta['vts_order'] = $vts_order;
        }

        // <podcast:socialInteract>
        $social_interacts = $this->parser->get_item_social_interacts($feed_item);
        if (!empty($social_interacts))
            $meta['social_interact'] = $social_interacts;

        // ==================
        // RAWVOICE NAMESPACE
        // ==================

        // <rawvoice:pid>
        if (($v = $this->parser->get_item_pid($feed_item)) !== null)
            $meta['podcast_id'] = $v;

        // CATEGORY
        if (!empty($category_strict))
            $meta['category'] = $category_strict;

        // ====================
        // SANITIZE META FIELDS
        // ====================

        // URLS
        $url_keys = ['itunes_image', 'donate_url', 'copyright_url', 'pci_chapters_url', 'pci_transcript_url'];
        foreach ($url_keys as $url_key) {
            if (isset($meta[$url_key]))
                $meta[$url_key] = esc_url_raw($meta[$url_key]);
        }

        // FREE TEXT FIELDS
        $text_keys = ['episode_title', 'author', 'donate_label', 'copyright'];
        foreach ($text_keys as $text_key) {
            if (isset($meta[$text_key]))
                $meta[$text_key] = sanitize_text_field($meta[$text_key]);
        }
        if (isset($meta['show_notes']))
            $meta['show_notes'] = wp_kses_post($meta['show_notes']);

        return $meta;
	}
	
	function _import_handle_url() {
		
		if( empty($_POST['podcast_feed_url']) ) {
			echo '<p>'.	__( 'URL is empty.', 'powerpress' ) .'<p>';
			return false;
		}
		
        // DEFAULT UA + CONFIG
		$options = [
            'user-agent' => 'Blubrry PowerPress/' . POWERPRESS_VERSION,
            'timeout' => 10,
        ];

        // SQUARESPACE
        if( !empty($_GET['import']) && $_GET['import'] == 'powerpress-squarespace-rss-podcast' )
			$options['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        // PODBEAN
		else if( !empty($_GET['import']) && $_GET['import'] == 'powerpress-podbean-rss-podcast' )
			$options['user-agent'] = 'iTunes/12.2.2 (Macintosh; OS X 10.10.5) AppleWebKit/600.8.9';  // Common user agent
		// 'gPodder/3.8.4 (+http://gpodder.org/)';
		
		$response = wp_safe_remote_get($_POST['podcast_feed_url'], $options);
		if ( is_wp_error( $response ) ) {
			echo '<p>'.	htmlspecialchars($response->get_error_message()) .'<p>';
			return false;
		}
		
		$this->m_content = wp_remote_retrieve_body( $response );
		return true;
	}
	
	function _import_handle_upload() {
		if ( ! isset( $_FILES['podcast_feed_file'] )  || empty($_FILES['podcast_feed_file']['tmp_name']) ) {
			echo '<p>'.	__( 'Upload failed.', 'powerpress' ).'<p>';
			return false;
		}
		
		$this->m_content = file_get_contents($_FILES['podcast_feed_file']['tmp_name']);
		return true;
	}
} // end PowerPress_RSS_Podcast_Import class

	$powerpress_rss_podcast_import = new PowerPress_RSS_Podcast_Import();

	register_importer('powerpress-soundcloud-rss-podcast', __('Podcast from SoundCloud', 'powerpress'), __('Import episodes from a SoundCloud podcast feed.', 'powerpress'), array ($powerpress_rss_podcast_import, 'dispatch'));
	register_importer('powerpress-libsyn-rss-podcast', __('Podcast from LibSyn', 'powerpress'), __('Import episodes from a LibSyn podcast feed.', 'powerpress'), array ($powerpress_rss_podcast_import, 'dispatch'));
	register_importer('powerpress-podbean-rss-podcast', __('Podcast from PodBean ', 'powerpress'), __('Import episodes from a PodBean podcast feed.', 'powerpress'), array ($powerpress_rss_podcast_import, 'dispatch'));
	register_importer('powerpress-squarespace-rss-podcast', __('Podcast from Squarespace', 'powerpress'), __('Import episodes from a Squarespace podcast feed.', 'powerpress'), array ($powerpress_rss_podcast_import, 'dispatch'));
	register_importer('powerpress-anchor-rss-podcast', __('Podcast from Anchor.fm', 'powerpress'), __('Import episodes from an Anchor.fm podcast feed.', 'powerpress'), array ($powerpress_rss_podcast_import, 'dispatch'));
    register_importer('powerpress-buzzsprout-rss-podcast', __('Podcast from Buzzsprout', 'powerpress'), __('Import episodes from a Buzzsprout podcast feed.', 'powerpress'), array ($powerpress_rss_podcast_import, 'dispatch'));
	register_importer('powerpress-rss-podcast', __('Podcast RSS Feed', 'powerpress'), __('Import episodes from a RSS podcast feed.', 'powerpress'), array ($powerpress_rss_podcast_import, 'dispatch'));
	
}; // end if WP_Importer exists

// eof
