<?php
	// settings_tab_destinations.php
	$cat_ID = '';
	if( !empty($FeedAttribs['category_id']) )
		$cat_ID = $FeedAttribs['category_id'];
	if( empty($FeedAttribs['type']) )
		$FeedAttribs['type'] = '';


	$feed_url = '';
	switch( $FeedAttribs['type'] )
	{
		case 'ttid': {
			$feed_url = get_term_feed_link($FeedAttribs['term_taxonomy_id'], $FeedAttribs['taxonomy_type'], 'rss2' );
		}; break;
		case 'category': {
			if( !empty($General['cat_casting_podcast_feeds']) )
				$feed_url = get_category_feed_link($cat_ID, 'podcast');
			else
				$feed_url = get_category_feed_link($cat_ID);
		}; break;
		case 'channel': {
			$feed_url = get_feed_link($FeedAttribs['feed_slug']);
		}; break;
		case 'post_type': {
			$feed_url = get_post_type_archive_feed_link($FeedAttribs['post_type'], $FeedAttribs['feed_slug']);
		}; break;
		case 'general':
		default: {
			$feed_url = get_feed_link('podcast');
		}
	}

    $feed_slug = 'podcast';
    if (!empty($FeedAttribs['feed_slug'])) {
        $feed_slug = $FeedAttribs['feed_slug'];
    }
	
	if( empty($FeedSettings['itunes_url']) )
		$FeedSettings['itunes_url'] = '';
	if( empty($FeedSettings['blubrry_url']) )
		$FeedSettings['blubrry_url'] = '';
	if( empty($FeedSettings['tunein_url']) )
		$FeedSettings['tunein_url'] = '';	
	if( empty($FeedSettings['spotify_url']) )
		$FeedSettings['spotify_url'] = '';
	if(empty($FeedSettings['iheart_url']) )
	    $FeedSettings['iheart_url'] = '';
	if(empty($FeedSettings['deezer_url']) )
	    $FeedSettings['deezer_url'] = '';
	if(empty($FeedSettings['pandora_url']) )
	    $FeedSettings['pandora_url'] = '';
    if(empty($FeedSettings['amazon_url']) )
        $FeedSettings['amazon_url'] = '';
    if(empty($FeedSettings['pcindex_url']) )
        $FeedSettings['pcindex_url'] = '';
    if(empty($FeedSettings['jiosaavn_url']) )
        $FeedSettings['jiosaavn_url'] = '';
    if(empty($FeedSettings['podchaser_url']) )
        $FeedSettings['podchaser_url'] = '';
    if(empty($FeedSettings['gaana_url']) )
        $FeedSettings['gaana_url'] = '';
    if(empty($FeedSettings['anghami_url']) )
        $FeedSettings['anghami_url'] = '';
    if(empty($FeedSettings['youtube_url']) )
        $FeedSettings['youtube_url'] = '';
    if( empty($FeedSettings['apple_claim_token']) )
        $FeedSettings['apple_claim_token'] = '';

    $Settings['subscribe_feature_rss'] = (isset($General['subscribe_feature_rss']) ? $General['subscribe_feature_rss'] : true );
    $Settings['subscribe_feature_email'] = (isset($General['subscribe_feature_email']) ? $General['subscribe_feature_email'] : false );
    $Settings['subscribe_feature_apple'] = (isset($General['subscribe_feature_apple']) ? $General['subscribe_feature_apple'] : false );
    $Settings['subscribe_feature_tunein'] = (isset($General['subscribe_feature_tunein']) ? $General['subscribe_feature_tunein'] : false );
    $Settings['subscribe_feature_spotify'] = (isset($General['subscribe_feature_spotify']) ? $General['subscribe_feature_spotify'] : false );
    $Settings['subscribe_feature_iheart'] = (isset($General['subscribe_feature_iheart']) ? $General['subscribe_feature_iheart'] : false );
    $Settings['subscribe_feature_deezer'] = (isset($General['subscribe_feature_deezer']) ? $General['subscribe_feature_deezer'] : false );
    $Settings['subscribe_feature_pandora'] = (isset($General['subscribe_feature_pandora']) ? $General['subscribe_feature_pandora'] : false );
    $Settings['subscribe_feature_android'] = (isset($General['subscribe_feature_android']) ? $General['subscribe_feature_android'] : false );
    $Settings['subscribe_feature_blubrry'] = (isset($General['subscribe_feature_blubrry']) ? $General['subscribe_feature_blubrry'] : false );
    $Settings['subscribe_feature_amazon'] = (isset($General['subscribe_feature_amazon']) ? $General['subscribe_feature_amazon'] : false );
    $Settings['subscribe_feature_pcindex'] = (isset($General['subscribe_feature_pcindex']) ? $General['subscribe_feature_pcindex'] : false );
    $Settings['subscribe_feature_jiosaavn'] = (isset($General['subscribe_feature_jiosaavn']) ? $General['subscribe_feature_jiosaavn'] : false );
    $Settings['subscribe_feature_podchaser'] = (isset($General['subscribe_feature_podchaser']) ? $General['subscribe_feature_podchaser'] : false );
    $Settings['subscribe_feature_gaana'] = (isset($General['subscribe_feature_gaana']) ? $General['subscribe_feature_gaana'] : false );
    $Settings['subscribe_feature_anghami'] = (isset($General['subscribe_feature_anghami']) ? $General['subscribe_feature_anghami'] : false );
    $Settings['subscribe_feature_youtube'] = (isset($General['subscribe_feature_youtube']) ? $General['subscribe_feature_youtube'] : false );
    $Settings['subscribe_feature_rss_shortcode'] = (isset($General['subscribe_feature_rss_shortcode']) ? $General['subscribe_feature_rss_shortcode'] : true );
    $Settings['subscribe_feature_email_shortcode'] = (isset($General['subscribe_feature_email_shortcode']) ? $General['subscribe_feature_email_shortcode'] : true );
    $Settings['subscribe_feature_apple_shortcode'] = (isset($General['subscribe_feature_apple_shortcode']) ? $General['subscribe_feature_apple_shortcode'] : true );
    $Settings['subscribe_feature_tunein_shortcode'] = (isset($General['subscribe_feature_tunein_shortcode']) ? $General['subscribe_feature_tunein_shortcode'] : true );
    $Settings['subscribe_feature_spotify_shortcode'] = (isset($General['subscribe_feature_spotify_shortcode']) ? $General['subscribe_feature_spotify_shortcode'] : true );
    $Settings['subscribe_feature_android_shortcode'] = (isset($General['subscribe_feature_android_shortcode']) ? $General['subscribe_feature_android_shortcode'] : true );
    $Settings['subscribe_feature_blubrry_shortcode'] = (isset($General['subscribe_feature_blubrry_shortcode']) ? $General['subscribe_feature_blubrry_shortcode'] : true );
    $Settings['subscribe_feature_iheart_shortcode'] = (isset($General['subscribe_feature_iheart_shortcode']) ? $General['subscribe_feature_iheart_shortcode'] : true );
    $Settings['subscribe_feature_deezer_shortcode'] = (isset($General['subscribe_feature_deezer_shortcode']) ? $General['subscribe_feature_deezer_shortcode'] : true );
    $Settings['subscribe_feature_pandora_shortcode'] = (isset($General['subscribe_feature_pandora_shortcode']) ? $General['subscribe_feature_pandora_shortcode'] : true );
    $Settings['subscribe_feature_amazon_shortcode'] = (isset($General['subscribe_feature_amazon_shortcode']) ? $General['subscribe_feature_amazon_shortcode'] : true );
    $Settings['subscribe_feature_pcindex_shortcode'] = (isset($General['subscribe_feature_pcindex_shortcode']) ? $General['subscribe_feature_pcindex_shortcode'] : true );
    $Settings['subscribe_feature_jiosaavn_shortcode'] = (isset($General['subscribe_feature_jiosaavn_shortcode']) ? $General['subscribe_feature_jiosaavn_shortcode'] : false );
    $Settings['subscribe_feature_podchaser_shortcode'] = (isset($General['subscribe_feature_podchaser_shortcode']) ? $General['subscribe_feature_podchaser_shortcode'] : false );
    $Settings['subscribe_feature_gaana_shortcode'] = (isset($General['subscribe_feature_gaana_shortcode']) ? $General['subscribe_feature_gaana_shortcode'] : false );
    $Settings['subscribe_feature_anghami_shortcode'] = (isset($General['subscribe_feature_anghami_shortcode']) ? $General['subscribe_feature_anghami_shortcode'] : false );
    $Settings['subscribe_feature_youtube_shortcode'] = (isset($General['subscribe_feature_youtube_shortcode']) ? $General['subscribe_feature_youtube_shortcode'] : true );
    $Settings['subscribe_feature_rss_sidebar'] = (isset($General['subscribe_feature_rss_sidebar']) ? $General['subscribe_feature_rss_sidebar'] : true );
    $Settings['subscribe_feature_email_sidebar'] = (isset($General['subscribe_feature_email_sidebar']) ? $General['subscribe_feature_email_sidebar'] : true );
    $Settings['subscribe_feature_apple_sidebar'] = (isset($General['subscribe_feature_apple_sidebar']) ? $General['subscribe_feature_apple_sidebar'] : true );
    $Settings['subscribe_feature_tunein_sidebar'] = (isset($General['subscribe_feature_tunein_sidebar']) ? $General['subscribe_feature_tunein_sidebar'] : false );
    $Settings['subscribe_feature_spotify_sidebar'] = (isset($General['subscribe_feature_spotify_sidebar']) ? $General['subscribe_feature_spotify_sidebar'] : false );
    $Settings['subscribe_feature_iheart_sidebar'] = (isset($General['subscribe_feature_iheart_sidebar']) ? $General['subscribe_feature_iheart_sidebar'] : false );
    $Settings['subscribe_feature_deezer_sidebar'] = (isset($General['subscribe_feature_deezer_sidebar']) ? $General['subscribe_feature_deezer_sidebar'] : false );
    $Settings['subscribe_feature_pandora_sidebar'] = (isset($General['subscribe_feature_pandora_sidebar']) ? $General['subscribe_feature_pandora_sidebar'] : false );
    $Settings['subscribe_feature_android_sidebar'] = (isset($General['subscribe_feature_android_sidebar']) ? $General['subscribe_feature_android_sidebar'] : true );
    $Settings['subscribe_feature_blubrry_sidebar'] = (isset($General['subscribe_feature_blubrry_sidebar']) ? $General['subscribe_feature_blubrry_sidebar'] : false );
    $Settings['subscribe_feature_amazon_sidebar'] = (isset($General['subscribe_feature_amazon_sidebar']) ? $General['subscribe_feature_amazon_sidebar'] : false );
    $Settings['subscribe_feature_pcindex_sidebar'] = (isset($General['subscribe_feature_pcindex_sidebar']) ? $General['subscribe_feature_pcindex_sidebar'] : false );
    $Settings['subscribe_feature_jiosaavn_sidebar'] = (isset($General['subscribe_feature_jiosaavn_sidebar']) ? $General['subscribe_feature_jiosaavn_sidebar'] : false );
    $Settings['subscribe_feature_podchaser_sidebar'] = (isset($General['subscribe_feature_podchaser_sidebar']) ? $General['subscribe_feature_podchaser_sidebar'] : false );
    $Settings['subscribe_feature_gaana_sidebar'] = (isset($General['subscribe_feature_gaana_sidebar']) ? $General['subscribe_feature_gaana_sidebar'] : false );
    $Settings['subscribe_feature_anghami_sidebar'] = (isset($General['subscribe_feature_anghami_sidebar']) ? $General['subscribe_feature_anghami_sidebar'] : false );
    $Settings['subscribe_feature_youtube_sidebar'] = (isset($General['subscribe_feature_youtube_sidebar']) ? $General['subscribe_feature_youtube_sidebar'] : false );


function subscribeSetting($directory, $feed_url, $listing_url, $apple_claim_token = '') {
        $style = " style=\"height: 32px;\" ";
	    $link_at_top = true;
	    $id_tail = "-subsection";
	    $class = " class='pp-heading'";
	    $android_url = "";
	    $email_url = "";
        if( preg_match('/^(https?:\/\/)(.*)$/i', $feed_url, $matches ) ) {
            $android_url =  $matches[1] . 'subscribeonandroid.com/' . $matches[2];
            $email_url =  $matches[1] . 'subscribebyemail.com/' . $matches[2];
        }

	    switch ($directory) {
            case 'apple': ?>

                    <h2 class="pp-heading">
                        <span id="apple-icon" class="destinations-side-icon"></span>
                        <span class="directory-summary-head"><?php echo __('Apple Podcast', 'powerpress'); ?></span>
                        <?php powerpress_settings_save_button(); ?>
                    </h2>
                    <?php if ($link_at_top) { ?>
                        <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-itunes/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo __('How to submit a podcast to Apple', 'powerpress'); ?></a></b></p>
                    <?php } ?>
                    <p class="pp-settings-text"><?php echo __('Follow the steps to submit your podcast to Apple then come back here and enter the Subscription URL. Apple will email your Subscription URL to your Apple Email when your podcast is accepted into the Apple Podcasts Directory.', 'powerpress'); ?></p>
                    <?php if (!$link_at_top) { ?>
                    <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-itunes/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo __('How to submit a podcast to Apple', 'powerpress'); ?></a></b></p>
                    <?php } ?>
                    <input class="pp-settings-text-input-less-wide" type="text" id="itunes_url<?php echo $id_tail; ?>" name="Feed[itunes_url]" placeholder="<?php echo __('Apple Subscription URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                    <label for="itunes_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://itunes.apple.com/podcast/title-of-podcast/id000000000'); ?></label>

                    <br>
                    <hr/>
                    <br>
                    <input class="pp-settings-text-input-less-wide" type="text"
                           id="apple_claim_token<?php echo $id_tail; ?>" name="Feed[apple_claim_token]"
                           placeholder="<?php echo __('Apple Claim Token', 'powerpress'); ?>"
                           value="<?php echo esc_attr($apple_claim_token); ?>" maxlength="255"/>
                    <label for="apple_claim_token"
                           class="pp-settings-label-under"><?php echo sprintf(__('%s', 'powerpress'), 'For claiming a show from within your Apple Podcast Connect account.'); ?></label>

                <?php
                break;
            case 'tunein': ?>

                    <h2 class="pp-heading">
                        <span id="tunein-icon" class="destinations-side-icon"></span>
                        <span class="directory-summary-head"><?php echo __('Tunein', 'powerpress'); ?></span>
                        <?php powerpress_settings_save_button(); ?>
                    </h2>
                    <p class="pp-settings-text"><b><?php echo  __('TuneIn is no longer taking podcast submissions. If you have a valid TuneIn URL from before they stopped taking submissions, you can enter it below.', 'powerpress'); ?></b></p>
                    <input class="pp-settings-text-input-less-wide" type="text" id="tunein_url<?php echo $id_tail; ?>" name="Feed[tunein_url]" placeholder="<?php echo __('TuneIn Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                    <label for="tunein_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'http://tunein.com/radio/your-podcast-p000000/'); ?></label>


                <?php
                break;
            case 'spotify': ?>

                    <h2 class="pp-heading">
                        <span id="spotify-icon" class="destinations-side-icon"></span>
                        <span class="directory-summary-head"><?php echo __('Spotify', 'powerpress'); ?></span>
                        <?php powerpress_settings_save_button(); ?>
                    </h2>
                    <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-spotify/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Spotify', 'powerpress'); ?></a></b></p>
                    <input class="pp-settings-text-input-less-wide" type="text" id="spotify_url<?php echo $id_tail; ?>" name="Feed[spotify_url]" placeholder="<?php echo __('Spotify Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                    <label for="spotify_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://open.spotify.com/show/abcdefghijklmnopqrstu'); ?></label>


                <?php
                break;
            case 'blubrry': ?>

                    <h2<?php echo $class; ?>>
                        <img class="pp-directory-icon" <?php echo $style; ?>alt="" src="<?php echo powerpress_get_root_url(); ?>images/settings_nav_icons/blubrry.svg">
                        <?php echo __('Blubrry Podcast Directory', 'powerpress'); ?>
                        <?php powerpress_settings_save_button(); ?>
                    </h2>
                    <p class="pp-settings-text">
                        <b><?php echo __('Get listed on the largest podcast directory in the world! ', 'powerpress'); ?></b><?php echo sprintf(__('Once listed, %s to expand your podcast distribution to Blubrry\'s SmartTV Apps (e.g. Roku) and apply to be on Spotify.', 'powerpress'), '<a href="https://blubrry.com/services/blubrry-podcast-directory/" target="_blank">'. __('Get Featured', 'powerpress').'</a>' ); ?>
                    </p>
                    <input class="pp-settings-text-input-less-wide" type="text" id="blubrry_url<?php echo $id_tail; ?>" name="Feed[blubrry_url]" placeholder="<?php echo __('Blubrry Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                    <label for="blubrry_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://blubrry.com/title_of_podcast/'); ?></label>
                <?php
                break;
            case 'android': ?>
                <h2 class="pp-heading">
                    <span id="android-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Android', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <input class="pp-settings-text-input-less-wide" type="text" id="android_url_<?php echo $id_tail; ?>" name="null[android_url]" placeholder="<?php echo __('Subscribe by Android not available', 'powerpress'); ?>" value="<?php echo esc_attr($android_url); ?>" maxlength="255" readonly />
                <?php
                break;
            case 'email': ?>
                <h2 class="pp-heading">
                    <span id="email-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Email', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <input class="pp-settings-text-input-less-wide" type="text" id="email_url_<?php echo $id_tail; ?>" name="null[iheart_url]" placeholder="<?php echo __('Susbcribe on Email not available', 'powerpress'); ?>" value="<?php echo esc_attr($email_url); ?>" maxlength="255" readonly />
                <?php
                break;
            case 'iheart': ?>

                <h2 class="pp-heading">
                    <span id="iheart-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('iHeartRadio', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-iheartradio/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to iHeartRadio', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="iheart_url<?php echo $id_tail; ?>" name="Feed[iheart_url]" placeholder="<?php echo __('iHeartRadio Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="iheart_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://www.iheart.com/podcast/abcdefghijklmopqrstu/'); ?></label>

                <?php
                break;
            case 'deezer': ?>

                <h2 class="pp-heading">
                    <span id="deezer-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Deezer', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/podcast-insider/2019/08/07/blubrry-podcasts-coming-deezer/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Deezer', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="deezer_url<?php echo $id_tail; ?>" name="Feed[deezer_url]" placeholder="<?php echo __('Deezer Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="deezer_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://www.deezer.com/us/show/1234'); ?></label>

                <?php
                break;
            case 'pandora': ?>

                <h2 class="pp-heading">
                    <span id="pandora-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Pandora', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-pandora/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Pandora', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="pandora_url<?php echo $id_tail; ?>" name="Feed[pandora_url]" placeholder="<?php echo __('Pandora Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="pandora_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://www.pandora.com/us/show/1234'); ?></label>

                <?php
                break;
            case 'amazon': ?>

                <h2 class="pp-heading">
                    <span id="amazon-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Amazon Music', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-amazon-music-podcasts/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Amazon Music', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="amazon_url<?php echo $id_tail; ?>" name="Feed[amazon_url]" placeholder="<?php echo __('Amazon Music Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="amazon_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://music.amazon.com/podcasts/x999xx99-9x99-9x99-x999-xx9xx999xxxx/Example-Podcast'); ?></label>

                <?php
                break;
            case 'pcindex': ?>

                <h2 class="pp-heading">
                    <span id="pcindex-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Podcast Index', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><?php echo __('Podcast Index is an independent podcast directory, free, available to anyone. Striving to protect podcasting as a platform for free speech.','powerpress'); ?></p>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-podcast-index/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Podcast Index', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="pcindex_url<?php echo $id_tail; ?>" name="Feed[pcindex_url]" placeholder="<?php echo __('Podcast Index Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="pcindex_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://podcastindex.org/podcast/xxxxxxx'); ?></label>

                <?php
                break;
            case 'jiosaavn': ?>

                <h2 class="pp-heading">
                    <span id="jiosaavn-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('JioSaavn', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-jiosaavn/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to JioSaavn', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="jiosaavn_url<?php echo $id_tail; ?>" name="Feed[jiosaavn_url]" placeholder="<?php echo __('JioSaavn Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="jiosaavn_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://www.jiosaavn.com/shows/xxxxxxxx'); ?></label>

                <?php
                break;
            case 'podchaser': ?>

                <h2 class="pp-heading">
                    <span id="podchaser-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Podchaser', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-podchaser/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Podchaser', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="podchaser_url<?php echo $id_tail; ?>" name="Feed[podchaser_url]" placeholder="<?php echo __('Podchaser Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="podchaser_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://podchaser.com/podcasts/xxxxxxx-123456'); ?></label>

                <?php
                break;
            case 'gaana': ?>

                <h2 class="pp-heading">
                    <span id="gaana-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Gaana', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-gaana/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Gaana', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="gaana_url<?php echo $id_tail; ?>" name="Feed[gaana_url]" placeholder="<?php echo __('Gaana Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="gaana_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://gaana.com/season/xxxxxxxx'); ?></label>

                <?php
                break;
            case 'anghami': ?>

                <h2 class="pp-heading">
                    <span id="anghami-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Anghami', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-podcast-to-anghami/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Anghami', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="anghami_url<?php echo $id_tail; ?>" name="Feed[anghami_url]" placeholder="<?php echo __('Anghami Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="anghami_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://www.anghami.com/artist/example'); ?></label>

                <?php
                break;
            case 'youtube': ?>

                <h2 class="pp-heading">
                    <span id="youtube-icon" class="destinations-side-icon"></span>
                    <span class="directory-summary-head"><?php echo __('Youtube Music', 'powerpress'); ?></span>
                    <?php powerpress_settings_save_button(); ?>
                </h2>
                <p class="pp-settings-text"><b><a href="https://blubrry.com/manual/podcast-promotion/submit-rss-to-youtube-podcasting/?podcast-feed=<?php echo urlencode($feed_url); ?>" target="_blank"><?php echo  __('How to submit a podcast to Youtube Music', 'powerpress'); ?></a></b></p>
                <input class="pp-settings-text-input-less-wide" type="text" id="youtube_url<?php echo $id_tail; ?>" name="Feed[youtube_url]" placeholder="<?php echo __('Youtube Music Listing URL', 'powerpress'); ?>" value="<?php echo esc_attr($listing_url); ?>" maxlength="255" />
                <label for="youtube_url" class="pp-settings-label-under"><?php echo sprintf(__('e.g. %s', 'powerpress'), 'https://music.youtube.com/playlist?list=fahsdjkaryvhijkaxalkjtg'); ?></label>

                <?php
                break;
            case 'default':
                break;
        }
    }
?>

<div class="pp-sidenav-toggle-container">
    <div id="destinations-toggle-sidenav" class="toggle-sidenav" title="Destinations Settings and Blubrry Services" onclick="powerpress_displaySideNav(this);">&lt;</div>
    <div class="pp-sidenav">
        <?php
        powerpressadmin_edit_blubrry_services($General);
        ?>
        <div class="pp-sidenav-extra"><a href="https://www.blubrry.com/support/" class="pp-sidenav-extra-text"><?php echo htmlspecialchars(__('POWERPRESS DOCUMENTATION', 'powerpress')); ?></a></div>
        <div class="pp-sidenav-extra"><a href="https://www.blubrry.com/podcast-insider/" class="pp-sidenav-extra-text"><?php echo htmlspecialchars(__('PODCAST INSIDER BLOG', 'powerpress')); ?></a></div>
        <div class="pp-sidenav-extra"><a href="https://blubrry.com/manual/" class="pp-sidenav-extra-text"><?php echo htmlspecialchars(__('PODCAST MANUAL', 'powerpress')); ?></a></div>
        <div class="pp-sidenav-extra"><a href="https://blubrry.com/services/" class="pp-sidenav-extra-text"><?php echo htmlspecialchars(__('BLUBRRY RESOURCES', 'powerpress')); ?></a></div>
        <div class="pp-sidenav-extra"><a href="https://blubrry.com/support/" class="pp-sidenav-extra-text"><?php echo htmlspecialchars(__('BLUBRRY SUPPORT', 'powerpress')); ?></a></div>
        <div class="pp-sidenav-extra"><a href="https://wordpress.org/support/plugin/powerpress/" class="pp-sidenav-extra-text"><?php echo htmlspecialchars(__('BLUBRRY POWERPRESS FORUM', 'powerpress')); ?></a></div>
    </div>
</div>
<?php

$atleastOneEpisode = $hasBlubrryHosting = false;
$episodeCount = powerpress_admin_episodes_per_feed($feed_slug);
if (!empty($episodeCount) && $episodeCount > 0) {
    $atleastOneEpisode = true;
}

if (!empty($General['blubrry_hosting']) && $General['blubrry_hosting'] > 0) {
    $hasBlubrryHosting = true;
}

?>
<div style="max-width: 75%;">
    <?php if ($atleastOneEpisode): ?>
        <div class="destinations-icon-links d-flex flex-wrap justify-content-between">
            <p class="mt-3 mb-4" style="width: 100%;">There are listeners everywhere, don’t miss out on a podcast platform. Submit and manage podcast listings here.</p>
            <p style="width: 100%; margin-bottom: 2em;"><b>Select Destination Point</b></p>
            <div id="destinations-default-open" onclick="sideNav(event, 'destinations-all')" class="active pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="subscribe-icons-larger rss"></i>
                <small>RSS</small>
            </div>
            <div id="destinations-apple-tab" onclick="sideNav(event, 'destinations-apple')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['itunes_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger apple-podcast"></i>
                <small>Apple Podcasts</small>
            </div>
            <div id="destinations-spotify-tab" onclick="sideNav(event, 'destinations-spotify')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['spotify_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger spotify"></i>
                <small>Spotify</small>
            </div>
            <div id="destinations-pcindex-tab" onclick="sideNav(event, 'destinations-pcindex')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['pcindex_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger pcindex"></i>
                <small>Podcast Index</small>
            </div>
            <div id="destinations-blubrry-tab" onclick="sideNav(event, 'destinations-blubrry')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['blubrry_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger blubrry"></i>
                <small>Blubrry Podcast Directory</small>
            </div>
            <div id="destinations-android-tab" onclick="sideNav(event, 'destinations-android')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="subscribe-icons-larger android"></i>
                <small>Subscribe on Android</small>
            </div>
            <div id="destinations-iheart-tab" onclick="sideNav(event, 'destinations-iheart')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['iheart_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger iheartradio"></i>
                <small>iHeartRadio</small>
            </div>
            <div id="destinations-pandora-tab" onclick="sideNav(event, 'destinations-pandora')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['pandora_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger pandora"></i>
                <small>Pandora</small>
            </div>
            <div id="destinations-podchaser-tab" onclick="sideNav(event, 'destinations-podchaser')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['podchaser_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger podchaser"></i>
                <small>Podchaser</small>
            </div>
            <div id="destinations-amazon-tab" onclick="sideNav(event, 'destinations-amazon')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['amazon_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger amazon_music"></i>
                <small>Amazon Music</small>
            </div>
            <div id="destinations-deezer-tab" onclick="sideNav(event, 'destinations-deezer')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['deezer_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger deezer"></i>
                <small>Deezer</small>
            </div>
            <div id="destinations-tunein-tab" onclick="sideNav(event, 'destinations-tunein')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['tunein_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger tunein"></i>
                <small>TuneIn</small>
            </div>
            <div id="destinations-gaana-tab" onclick="sideNav(event, 'destinations-gaana')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['gaana_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger gaana"></i>
                <small>Gaana</small>
            </div>
            <div id="destinations-jiosaavn-tab" onclick="sideNav(event, 'destinations-jiosaavn')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['jiosaavn_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger jiosaavn"></i>
                <small>JioSaavn</small>
            </div>
            <div  id="destinations-anghami-tab" onclick="sideNav(event, 'destinations-anghami')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['anghami_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger anghami"></i>
                <small>Anghami</small>
            </div>
            <div id="destinations-email-tab" onclick="sideNav(event, 'destinations-email')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="subscribe-icons-larger email"></i>
                <small>Subscribe by Email</small>
            </div>

            <div id="destinations-youtube-tab" onclick="sideNav(event, 'destinations-youtube')" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="<?php echo empty($FeedSettings['youtube_url']) ? 'grey-icon ' : ''; ?>subscribe-icons-larger youtube"></i>
                <small>Youtube Music</small>
            </div>
        </div>
        <div id="destinations-all" class="pp-sidenav-tab active">

            <h1 class="pp-heading">
                <?php echo __('Destinations', 'powerpress'); ?>
                <?php powerpress_settings_save_button(); ?>
            </h1>


            <div>
                <p style="line-height: 36px;" class="pp-settings-text"><?php echo __('Your podcast RSS feed: ', 'powerpress'); ?>
                    <a href="<?php echo esc_attr($feed_url); ?>"> <?php echo esc_attr($feed_url); ?> </a>
                    <br />
                    <?php echo __('Use this URL to submit your podcast to various directories.', 'powerpress'); ?>
                    <br />
                    <?php echo __('Directory listing URLs are used by player subscribe links, subscribe sidebar widgets, and subscribe to podcast page shortcodes.', 'powerpress'); ?>
                    <?php echo __('Showing this link is always recommended.', 'powerpress'); ?>
                </p>
            </div>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_rss_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_rss_sidebar" name="General[subscribe_feature_rss_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_rss_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_rss_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_rss_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_rss_shortcode" name="General[subscribe_feature_rss_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_rss_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_rss_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_rss]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_rss" name="General[subscribe_feature_rss]" value="1" <?php if( !empty($Settings['subscribe_feature_rss']) ) echo 'checked '; ?>/> <label for="subscribe_feature_rss"><?php echo __('Show link under player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-apple" class="pp-sidenav-tab">
            <?php subscribeSetting('apple', $feed_url, $FeedSettings['itunes_url'], $FeedSettings['apple_claim_token']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_apple_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_apple_sidebar" name="General[subscribe_feature_apple_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_apple_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_apple_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_apple_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_apple_shortcode" name="General[subscribe_feature_apple_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_apple_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_apple_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_apple]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_apple" name="General[subscribe_feature_apple]" value="1" <?php if( !empty($Settings['subscribe_feature_apple']) ) echo 'checked '; ?>/> <label for="subscribe_feature_apple"><?php echo __('Show link under player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-spotify" class="pp-sidenav-tab">
            <?php subscribeSetting('spotify', $feed_url, $FeedSettings['spotify_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_spotify_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_spotify_sidebar" name="General[subscribe_feature_spotify_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_spotify_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_spotify_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_spotify_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_spotify_shortcode" name="General[subscribe_feature_spotify_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_spotify_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_spotify_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_spotify]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_spotify" name="General[subscribe_feature_spotify]" value="1" <?php if( !empty($Settings['subscribe_feature_spotify']) ) echo 'checked '; ?>/> <label for="subscribe_feature_spotify"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-amazon" class="pp-sidenav-tab">
            <?php subscribeSetting('amazon', $feed_url, $FeedSettings['amazon_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_amazon_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_amazon_sidebar" name="General[subscribe_feature_amazon_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_amazon_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_amazon_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_amazon_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_amazon_shortcode" name="General[subscribe_feature_amazon_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_amazon_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_amazon_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_amazon]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_amazon" name="General[subscribe_feature_amazon]" value="1" <?php if( !empty($Settings['subscribe_feature_amazon']) ) echo 'checked '; ?>/> <label for="subscribe_feature_amazon"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-android" class="pp-sidenav-tab">
            <?php subscribeSetting('android', $feed_url, ''); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_android_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_android_sidebar" name="General[subscribe_feature_android_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_android_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_android_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_android_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_android_shortcode" name="General[subscribe_feature_android_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_android_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_android_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_android]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_android" name="General[subscribe_feature_android]" value="1" <?php if( !empty($Settings['subscribe_feature_android']) ) echo 'checked '; ?>/> <label for="subscribe_feature_android"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-pandora" class="pp-sidenav-tab">
            <?php subscribeSetting('pandora', $feed_url, $FeedSettings['pandora_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_pandora_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_pandora_sidebar" name="General[subscribe_feature_pandora_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_pandora_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_pandora_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_pandora_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_pandora_shortcode" name="General[subscribe_feature_pandora_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_pandora_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_pandora_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_pandora]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_pandora" name="General[subscribe_feature_pandora]" value="1" <?php if( !empty($Settings['subscribe_feature_pandora']) ) echo 'checked '; ?>/> <label for="subscribe_feature_pandora"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-iheart" class="pp-sidenav-tab">
            <?php subscribeSetting('iheart', $feed_url, $FeedSettings['iheart_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_iheart_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_iheart_sidebar" name="General[subscribe_feature_iheart_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_iheart_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_iheart_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_iheart_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_iheart_shortcode" name="General[subscribe_feature_iheart_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_iheart_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_iheart_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_iheart]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_iheart" name="General[subscribe_feature_iheart]" value="1" <?php if( !empty($Settings['subscribe_feature_iheart']) ) echo 'checked '; ?>/> <label for="subscribe_feature_iheart"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-blubrry" class="pp-sidenav-tab">
            <?php if ($hasBlubrryHosting): ?>
                <?php
                $blubrryDirectoryListingUrl = '';
                if (empty($FeedSettings['blubrry_url'])) {
                    if ($hasBlubrryHosting && !empty($General['blubrry_program_keyword'])) {
                        $blubrryDirectoryListingUrl = "https://blubrry.com/" . htmlspecialchars($General['blubrry_program_keyword']) . '/';
                    }
                } else {
                    $blubrryDirectoryListingUrl = $FeedSettings['blubrry_url'];
                }

                subscribeSetting('blubrry', $feed_url, $blubrryDirectoryListingUrl);
                ?>
                <div class="pp-show-subscribe">
                    <p class="pp-settings-text-smaller-margin">
                        <input type="hidden" name="General[subscribe_feature_blubrry_sidebar]" value="0"/>
                        <input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_blubrry_sidebar"
                               name="General[subscribe_feature_blubrry_sidebar]"
                               value="1" <?php if (!empty($Settings['subscribe_feature_blubrry_sidebar'])) echo 'checked '; ?>/>
                        <label for="subscribe_feature_blubrry_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label>
                    </p>
                    <p class="pp-settings-text-smaller-margin">
                        <input type="hidden" name="General[subscribe_feature_blubrry_shortcode]" value="0"/>
                        <input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_blubrry_shortcode"
                               name="General[subscribe_feature_blubrry_shortcode]"
                               value="1" <?php if (!empty($Settings['subscribe_feature_blubrry_shortcode'])) echo 'checked '; ?>/>
                        <label for="subscribe_feature_blubrry_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label>
                    </p>
                    <?php if (!empty($General['subscribe_links'])): ?>
                        <p class="pp-settings-text-smaller-margin">
                            <input type="hidden" name="General[subscribe_feature_blubrry]" value="0"/>
                            <input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_blubrry"
                                   name="General[subscribe_feature_blubrry]"
                                   value="1" <?php if (!empty($Settings['subscribe_feature_blubrry'])) echo 'checked '; ?>/>
                            <label for="subscribe_feature_blubrry"><?php echo __('Show link under media player', 'powerpress'); ?></label>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="pp-show-subscribe" style="margin-bottom: 3rem;">
                    <p>Directory listings are only available for hosting customers.</p>
                    <br>
                    <a rel="noopener noreferrer" href="https://blubrry.com/services/plans-pricing/">Learn more about
                        Blubrry Hosting Plans</a>
                </div>
            <?php endif; ?>
            <?php marketing_footer(); ?>

        </div>

        <div id="destinations-jiosaavn" class="pp-sidenav-tab">
            <?php subscribeSetting('jiosaavn', $feed_url, $FeedSettings['jiosaavn_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_jiosaavn_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_jiosaavn_sidebar" name="General[subscribe_feature_jiosaavn_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_jiosaavn_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_jiosaavn_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_jiosaavn_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_jiosaavn_shortcode" name="General[subscribe_feature_jiosaavn_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_jiosaavn_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_jiosaavn_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_jiosaavn]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_jiosaavn" name="General[subscribe_feature_jiosaavn]" value="1" <?php if( !empty($Settings['subscribe_feature_jiosaavn']) ) echo 'checked '; ?>/> <label for="subscribe_feature_jiosaavn"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-podchaser" class="pp-sidenav-tab">
            <?php subscribeSetting('podchaser', $feed_url, $FeedSettings['podchaser_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_podchaser_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_podchaser_sidebar" name="General[subscribe_feature_podchaser_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_podchaser_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_podchaser_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_podchaser_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_podchaser_shortcode" name="General[subscribe_feature_podchaser_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_podchaser_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_podchaser_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_podchaser]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_podchaser" name="General[subscribe_feature_podchaser]" value="1" <?php if( !empty($Settings['subscribe_feature_podchaser']) ) echo 'checked '; ?>/> <label for="subscribe_feature_podchaser"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-gaana" class="pp-sidenav-tab">
            <?php subscribeSetting('gaana', $feed_url, $FeedSettings['gaana_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_gaana_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_gaana_sidebar" name="General[subscribe_feature_gaana_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_gaana_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_gaana_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_gaana_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_gaana_shortcode" name="General[subscribe_feature_gaana_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_gaana_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_gaana_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_gaana]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_gaana" name="General[subscribe_feature_gaana]" value="1" <?php if( !empty($Settings['subscribe_feature_gaana']) ) echo 'checked '; ?>/> <label for="subscribe_feature_gaana"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-pcindex" class="pp-sidenav-tab">
            <?php subscribeSetting('pcindex', $feed_url, $FeedSettings['pcindex_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_pcindex_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_pcindex_sidebar" name="General[subscribe_feature_pcindex_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_pcindex_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_pcindex_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_pcindex_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_pcindex_shortcode" name="General[subscribe_feature_pcindex_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_pcindex_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_pcindex_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_pcindex]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_pcindex" name="General[subscribe_feature_pcindex]" value="1" <?php if( !empty($Settings['subscribe_feature_pcindex']) ) echo 'checked '; ?>/> <label for="subscribe_feature_pcindex"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-email" class="pp-sidenav-tab">
            <?php subscribeSetting('email', $feed_url, ''); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_email_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_email_sidebar" name="General[subscribe_feature_email_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_email_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_email_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_email_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_email_shortcode" name="General[subscribe_feature_email_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_email_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_email_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_email]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_email" name="General[subscribe_feature_email]" value="1" <?php if( !empty($Settings['subscribe_feature_email']) ) echo 'checked '; ?>/> <label for="subscribe_feature_email"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-tunein" class="pp-sidenav-tab">
            <?php subscribeSetting('tunein', $feed_url, $FeedSettings['tunein_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_tunein_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_tunein_sidebar" name="General[subscribe_feature_tunein_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_tunein_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_tunein_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_tunein_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_tunein_shortcode" name="General[subscribe_feature_tunein_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_tunein_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_tunein_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_tunein]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_tunein" name="General[subscribe_feature_tunein]" value="1" <?php if( !empty($Settings['subscribe_feature_tunein']) ) echo 'checked '; ?>/> <label for="subscribe_feature_tunein"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-deezer" class="pp-sidenav-tab">
            <?php subscribeSetting('deezer', $feed_url, $FeedSettings['deezer_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_deezer_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_deezer_sidebar" name="General[subscribe_feature_deezer_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_deezer_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_deezer_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_deezer_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_deezer_shortcode" name="General[subscribe_feature_deezer_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_deezer_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_deezer_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_deezer]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_deezer" name="General[subscribe_feature_deezer]" value="1" <?php if( !empty($Settings['subscribe_feature_deezer']) ) echo 'checked '; ?>/> <label for="subscribe_feature_deezer"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-anghami" class="pp-sidenav-tab">
            <?php subscribeSetting('anghami', $feed_url, $FeedSettings['anghami_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_anghami_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_anghami_sidebar" name="General[subscribe_feature_anghami_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_anghami_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_anghami_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_anghami_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_anghami_shortcode" name="General[subscribe_feature_anghami_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_anghami_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_anghami_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_anghami]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_anghami" name="General[subscribe_feature_anghami]" value="1" <?php if( !empty($Settings['subscribe_feature_anghami']) ) echo 'checked '; ?>/> <label for="subscribe_feature_anghami"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>

        <div id="destinations-youtube" class="pp-sidenav-tab">
            <?php subscribeSetting('youtube', $feed_url, $FeedSettings['youtube_url']); ?>
            <div class="pp-show-subscribe">
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_youtube_sidebar]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_youtube_sidebar" name="General[subscribe_feature_youtube_sidebar]" value="1" <?php if( !empty($Settings['subscribe_feature_youtube_sidebar']) ) echo 'checked '; ?>/> <label for="subscribe_feature_youtube_sidebar"><?php echo __('Show link in subscribe sidebar', 'powerpress'); ?></label></p>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_youtube_shortcode]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_youtube_shortcode" name="General[subscribe_feature_youtube_shortcode]" value="1" <?php if( !empty($Settings['subscribe_feature_youtube_shortcode']) ) echo 'checked '; ?>/> <label for="subscribe_feature_youtube_shortcode"><?php echo __('Show link on subscribe page', 'powerpress'); ?></label></p>
                <?php if( !empty($General['subscribe_links']) ) { ?>
                <p class="pp-settings-text-smaller-margin"><input type="hidden" name="General[subscribe_feature_youtube]" value="0" /><input class="pp-settings-checkbox" type="checkbox" id="subscribe_feature_youtube" name="General[subscribe_feature_youtube]" value="1" <?php if( !empty($Settings['subscribe_feature_youtube']) ) echo 'checked '; ?>/> <label for="subscribe_feature_youtube"><?php echo __('Show link under media player', 'powerpress'); ?></label></p>
                <?php } ?>
            </div>

        </div>
    <?php else: ?>
        <div class="destinations-icon-links d-flex flex-wrap justify-content-between">
            <div class="mb-4" style="border-bottom: 1px solid #444444; padding-bottom: 1.5rem;">
                <h1 class="mt-3">You haven’t published your first episode yet.</h1>
                <p class="mt-2">Before you can submit
                    your
                    podcast to
                    directories (examples below), you need to have published at least one episode (this can be a
                    trailer or pilot episode).</p>
                <ul style="clear: unset; list-style: unset; padding-inline-start: 40px;">
                    <li>Apple</li>
                    <li>Spotify</li>
                    <li>Amazon Music</li>
                    <li>Plus others!</li>
                </ul>
                <div style="text-align: center;">
                    <p><strong>All platforms require a single published episode</strong> in your RSS feed
                        in order to submit your show to a directory.</p>
                    <p>Your next step is to record and publish your first episode. Once it's live, you'll
                        be ready to start submitting to your chosen directories!</p>
                    <?php if ($hasBlubrryHosting): ?>
                        <p>With our partner <a href="https://publish.blubrry.com/resources/adobe/" target="_blank"
                                                        rel="noopener noreferrer">Adobe Podcasts</a> you can record your
                            first ever episode in minutes.</p>
                    <?php endif; ?>
                    <div id="destinations-all" class="pp-save-button-container" style="display: block; margin-top: 15px;">
                        <a class="powerpress_save_button mt-4 mb-4" href="/wp-admin/post-new.php" target="_blank"
                           rel="noopener noreferrer" style="text-decoration: none;">Publish An Episode Here</a>
                    </div>
                </div>
            </div>
            <div id="destinations-default-open" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger rss"></i>
                <small>RSS</small>
            </div>
            <div id="destinations-apple-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger apple-podcast"></i>
                <small>Apple Podcasts</small>
            </div>
            <div id="destinations-spotify-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger spotify"></i>
                <small>Spotify</small>
            </div>
            <div id="destinations-pcindex-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger pcindex"></i>
                <small>Podcast Index</small>
            </div>
            <div id="destinations-blubrry-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger blubrry"></i>
                <small>Blubrry Podcast Directory</small>
            </div>
            <div id="destinations-android-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger android"></i>
                <small>Subscribe on Android</small>
            </div>
            <div id="destinations-iheart-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger iheartradio"></i>
                <small>iHeartRadio</small>
            </div>
            <div id="destinations-pandora-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger pandora"></i>
                <small>Pandora</small>
            </div>
            <div id="destinations-podchaser-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger podchaser"></i>
                <small>Podchaser</small>
            </div>
            <div id="destinations-amazon-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger amazon_music"></i>
                <small>Amazon Music</small>
            </div>
            <div id="destinations-deezer-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger deezer"></i>
                <small>Deezer</small>
            </div>
            <div id="destinations-tunein-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger tunein"></i>
                <small>TuneIn</small>
            </div>
            <div id="destinations-gaana-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger gaana"></i>
                <small>Gaana</small>
            </div>
            <div id="destinations-jiosaavn-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger jiosaavn"></i>
                <small>JioSaavn</small>
            </div>
            <div id="destinations-anghami-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger anghami"></i>
                <small>Anghami</small>
            </div>
            <div id="destinations-email-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger email"></i>
                <small>Subscribe by Email</small>
            </div>
            <div id="destinations-youtube-tab" class="pp-sidenav-tablinks destinations-link text-center align-top">
                <i class="grey-icon subscribe-icons-larger youtube"></i>
                <small>Youtube Music</small>
            </div>
        </div>

    <?php endif; ?>
</div>
<br />