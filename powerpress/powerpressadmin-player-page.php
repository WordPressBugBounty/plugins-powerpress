<?php
// PowerPress Player settings page

require_once( POWERPRESS_ABSPATH. '/powerpress-player.php'); // Include, if not included already

function powerpressplayer_mediaelement_info($full_info = true)
{
?>
	<p>
		<?php echo __('MediaElement.js is an open source HTML5 audio and video player that supports both audio (mp3, m4a and oga) and video (mp4, m4v, webm, ogv and flv) media files. It includes all the necessary features for playback including a play/pause button, scroll-able position bar, elapsed time, total time, mute button and volume control.', 'powerpress'); ?>
	</p>
	<?php
	if( $full_info )
	{ 
	?>
	<p>
		<?php echo __('MediaElement.js is the default player in Blubrry PowerPress because it is HTML and CSS based, meets accessibility standards including WebVTT, and will play in any browser using either HTML5, Flash or Silverlight for playback.', 'powerpress'); ?>
	</p>
<?php
	}
}

function powerpressplayer_videojs_info()
{
	$plugin_link = '';
	
	if( !function_exists('add_videojs_header') && file_exists( WP_PLUGIN_DIR . '/' . 'videojs-html5-video-player-for-wordpress' ) ) // plugin downloaded but not activated...
	{
		$plugin_file = 'videojs-html5-video-player-for-wordpress' . '/' . 'video-js.php';
		$plugin_link = '<a href="' . esc_url(wp_nonce_url(admin_url('plugins.php?plugin_status=active&action=activate&plugin=' . $plugin_file ), 'activate-plugin_' . $plugin_file)) .
										'"title="' . esc_attr__('Activate Plugin') . '"">' . __('VideoJS - HTML5 Video Player for WordPress plugin', 'powerpress') . '</a>';
	
	
	} else {
		$plugin_link = '<a href="'. esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . 'videojs-html5-video-player-for-wordpress' .
									'&TB_iframe=true&width=600&height=550' ) ) .'" class="thickbox" title="' .
									esc_attr__('Install Plugin') . '">'. __('VideoJS - HTML5 Video Player for WordPress plugin', 'powerpress') . '</a>';
	}
?>
	<p style="margin-bottom: 20px;">
		<?php echo __('VideoJS is a HTML5 JavaScript and CSS video player with fallback to Flash. ', 'powerpress'); ?>
	</p>
	
	<?php if( $plugin_link ) { ?>
	<div <?php echo ( function_exists('add_videojs_header') ?'':' styleX="background-color: #FFFFE0; border: 1px solid #E6DB55; padding: 8px 12px; line-height: 29px; font-weight: bold; font-size: 14px; display:inline;"'); ?>><p>
		<?php echo sprintf(__('The %s must be installed and activated in order to enable this feature.', 'powerpress'), $plugin_link ); ?>
	</p></div>
	<?php } ?>
<?php
}

function defaultPlayerSettings(){
    $settings = array('mode' => 'Light', 'border' => '#000000', 'progress' => '#000000');
    return json_encode($settings);
}

function powerpress_admin_players($type = 'audio'){
    if(defined('WP_DEBUG')){
        if(WP_DEBUG){
            wp_register_style('powerpress_settings_style',  powerpress_get_root_url() . 'css/settings.css', array(), POWERPRESS_VERSION);
        } else {
            wp_register_style('powerpress_settings_style',  powerpress_get_root_url() . 'css/settings.min.css', array(), POWERPRESS_VERSION);
        }
    } else {
        wp_register_style('powerpress_settings_style',  powerpress_get_root_url() . 'css/settings.min.css', array(), POWERPRESS_VERSION);
    }

    wp_enqueue_style("powerpress_settings_style");

    // colorpicker library
    wp_register_script('powerpress_colorpicker_new_js', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js');
    wp_enqueue_script('powerpress_colorpicker_new_js');
    wp_register_style('powerpress_colorpicker_new_css', 'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/monolith.min.css');
    wp_enqueue_style('powerpress_colorpicker_new_css');

	$General = powerpress_get_settings('powerpress_general');
    $playerSettings = powerpress_get_settings('powerpress_bplayer');
    if(!empty($playerSettings) && is_string($playerSettings)){
        $playerSettings = json_decode(($playerSettings));
    } else {
        $playerSettings = false;
    }

    if(empty($playerSettings)){
        // Insert default record
        $defaultPlayerSettings = defaultPlayerSettings();
        update_option('powerpress_bplayer',  $defaultPlayerSettings);

    } elseif($playerSettings && !isset($playerSettings->mode) || !isset($playerSettings->border) || !isset($playerSettings->progress)){
        // If one of these settings is not present, then they have not been updated to the current/new settings
        // Update to default values
        $defaultPlayerSettings = defaultPlayerSettings();
        update_option('powerpress_bplayer',  $defaultPlayerSettings);
    }

    // refresh player settings array
    if(is_string(powerpress_get_settings('powerpress_bplayer'))){
        $playerSettings = json_decode(powerpress_get_settings('powerpress_bplayer'));
        $playerMode = $playerSettings->mode;
        $playerBorderColor = $playerSettings->border;
        $playerProgressColor = $playerSettings->progress;
    } else {
        $playerMode = 'Light';
        $playerBorderColor = '#000000';
        $playerProgressColor = '#000000';
    }


	$select_player = true;
	if(isset($_REQUEST['ep'])){
		$select_player = false;
	}
	
	if(isset($_GET['sp'])){
		$select_player = true;

	} elseif($type == 'video'){
		if(empty($General['video_player'])){
			$select_player = true;

		} else {
			switch($General['video_player']){
				case 'mediaelement-video':
				case 'videojs-html5-video-player-for-wordpress':
				case 'html5video':
                    break;
				default: {
					$select_player = true;
				}
			}
		}
	} else {
		if(empty($General['player'])){
			$select_player = true;
		} else {
			switch($General['player']){
                case 'blubrrymodern':
				case 'blubrryaudio':
				case 'mediaelement-audio':
				case 'html5audio':
                    break;
				default: {
					$select_player = true;
				}
			}
		}
	}

    // This makes it so when the modern player is activated, the POST leads to the select page rather than the customize page
    if(isset($_POST['Player'])){
        if($_POST['Player']['player'] == 'blubrrymodern'){
            $select_player = true;
        }
    }

    // If player is not set, then assign the Modern player as the default
	if(empty($General['player'])){
        $General['player'] = 'mediaelement-audio';
    }

	if(empty($General['player'])){
        $General['video_player'] = 'mediaelement-video';
    }

	if(empty($General['audio_custom_play_button'])){
        $General['audio_custom_play_button'] = '';
    }

    // If we have powerpress credentials, check if the account has been verified
    $GeneralSettings = get_option('powerpress_general');
    if(!empty($GeneralSettings['blubrry_program_keyword'])){
        $program_keyword = $GeneralSettings['blubrry_program_keyword'];
    } else {
        $program_keyword = '';
    }

	$Audio = array();
	$Audio['html5audio'] = 'http://media.blubrry.com/blubrry/content.blubrry.com/blubrry/html5.mp3';
	$Audio['mediaelement-audio'] = 'http://media.blubrry.com/blubrry/content.blubrry.com/blubrry/MediaElement_audio.mp3';
	$Audio['blubrryaudio'] = ''; // Set hardcoded by ID

	$Video = array();
	$Video['html5video'] = 'http://media.blubrry.com/blubrry/content.blubrry.com/blubrry/html5.mp4';
	$Video['videojs-html5-video-player-for-wordpress'] = 'http://media.blubrry.com/blubrry/content.blubrry.com/blubrry/videojs.mp4';
	$Video['mediaelement-video'] = 'http://media.blubrry.com/blubrry/content.blubrry.com/blubrry/MediaElement_video.mp4';
		
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker');

	if($type == 'video' && function_exists('add_videojs_header')){
        add_videojs_header();
    }
    ?>

    <link rel="stylesheet" href="<?php echo powerpress_get_root_url(); ?>3rdparty/colorpicker/css/colorpicker.css" type="text/css" />
    <script type="text/javascript" src="<?php echo powerpress_get_root_url(); ?>3rdparty/colorpicker/js/colorpicker.js"></script>
    <script type="text/javascript" src="<?php echo powerpress_get_root_url(); ?>player.min.js"></script>

    <script type="text/javascript">

        let playerColors = <?php echo json_encode($playerSettings); ?>;

        function rgb2hex(rgb){
            rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            function hex(x){
                hexDigits = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
                return isNaN(x) ? '00' : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
            }

            if(rgb){
                return '#' + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
            }
            return '';
        }

        jQuery(document).ready(function($){
            jQuery(window).keydown(function(event){
                if(event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });

            $('input[name=mode]').click(function(){
                updatePlayerIframe();
                updateLatestPlayerEmbed();
                updatePlaylistPlayer();
            });

            $('input[name=scroll]').click(function(){
                updatePlaylistPlayer();
            });

            $('input[name=num_episodes]').on('focusout', function () {
                updatePlaylistPlayer();
            });

            $('#restore-default-settings').click(function(){
                let playerMode = $('#mode-light');
                playerMode.click();

                let playerBorderColor = $('#border-color-hex');
                let playerProgressColor = $('#progress-color-hex');

                playerBorderColor.val('#000000');
                playerProgressColor.val('#000000');
                updateProgressColor('#000000');
                updateBorderColor('#000000');
                updatePlayerIframe();
                updateLatestPlayerEmbed();
                updatePlaylistPlayer();
            })

            function calculatePlayerHeight(numEpisodes, scrollBar){
                if(numEpisodes > 50){
                    numEpisodes = 50;
                }

                let calcPlayerHeight = 230 + 40 * (numEpisodes - 1) + 5 * numEpisodes;
                return numEpisodes >= 5 && scrollBar ? 530 : calcPlayerHeight;
            }

            function updateProgressColor(color){
                $('#color-picker-progress').css('background-color', color);
                $('#progress-color-hex').val(color);
                updatePlayerIframe();
                updateLatestPlayerEmbed();
                updatePlaylistPlayer();
            }

            function updateBorderColor(color){
                $('#color-picker-border').css('background-color', color);
                $('#border-color-hex').val(color);
                updatePlayerIframe();
                updateLatestPlayerEmbed();
                updatePlaylistPlayer();
            }

            function updatePlayerIframe(){
                let playerIframe = $('#modern_player_iframe_div');
                let playerIframeValue = playerIframe[0].innerHTML;
                let newPlayerIframeValue = '';
                let playerMode = $('#mode-light').prop('checked');
                let playerBorderColor = $('#border-color-hex').val();
                let playerProgressColor = $('#progress-color-hex').val();

                if(playerMode === true){
                    playerMode = 'Light';
                } else {
                    playerMode = 'Dark';
                }

                if(playerIframeValue.includes('player.blubrry.local')){
                    newPlayerIframeValue = 'src="http://player.blubrry.local/?id=12559710&preview=1&cache=' + <?php echo time(); ?>;
                } else {
                    newPlayerIframeValue = 'src="https://player.blubrry.com/?id=80155910&preview=1&cache=' + <?php echo time(); ?>;
                }

                newPlayerIframeValue += '#mode-' + playerMode + '&border-' + playerBorderColor.replace('#', '') + '&progress-' + playerProgressColor.replace('#', '') + '"';

                newPlayerIframeValue += ' id="playeriframe" class="" scrolling="yes" width="100%" height="165px" frameborder="0" title="Modern Blubrry Player"';

                newPlayerIframeValue = '<iframe ' + newPlayerIframeValue + '</iframe>';

                playerIframe[0].innerHTML = newPlayerIframeValue;
            }

            function updateLatestPlayerEmbed(){
                let latestPlayerEmbed = $('#link-to-embed-latest');
                let latestPlayerEmbedValue = latestPlayerEmbed[0].value;
                let newLatestPlayerEmbed = '';
                let playerMode = $('#mode-light').prop('checked');
                let playerBorderColor = $('#border-color-hex').val();
                let playerProgressColor = $('#progress-color-hex').val();

                if(playerMode === true){
                    playerMode = 'Light';
                } else {
                    playerMode = 'Dark';
                }

                if(latestPlayerEmbedValue.includes('player.blubrry.local')){
                    newLatestPlayerEmbed = 'src="http://player.blubrry.local/';
                } else {
                    newLatestPlayerEmbed = 'src="https://player.blubrry.com/';
                }

                newLatestPlayerEmbed += '<?php echo $program_keyword; ?>' + '/latest/';

                newLatestPlayerEmbed += '#mode-' + playerMode + '&border-' + playerBorderColor.replace('#', '') + '&progress-' + playerProgressColor.replace('#', '') + '"';

                newLatestPlayerEmbed += ' title="Blubrry Podcast Player" scrolling="no" width="100%" height="165px" frameborder="0"';

                newLatestPlayerEmbed = '<iframe ' + newLatestPlayerEmbed + '></iframe>';

                latestPlayerEmbed[0].value = newLatestPlayerEmbed;
            }

            function updatePlaylistPlayer(){
                let playlistPlayerIframe = $('#playlist-player-iframe');
                let playlistPlayerEmbed = $('#link-to-embed');
                let playlistPlayerEmbedValue = playlistPlayerEmbed[0].value;
                let newPlaylistPlayerValue = '';
                let playerMode = $('#mode-light').prop('checked');
                let playerBorderColor = $('#border-color-hex').val();
                let playerProgressColor = $('#progress-color-hex').val();

                if(playerMode === true){
                    playerMode = 'Light';
                } else {
                    playerMode = 'Dark';
                }

                let scrollBar = $('#scroll-check').prop('checked');
                let numEpisodes = $('#num-episodes').val();
                let playerHeight = calculatePlayerHeight(numEpisodes, scrollBar);

                if(playlistPlayerEmbedValue.includes('player.blubrry.local')){
                    newPlaylistPlayerValue = 'src="http://player.blubrry.local/';
                } else {
                    newPlaylistPlayerValue = 'src="https://player.blubrry.com/';
                }

                newPlaylistPlayerValue += '<?php echo $program_keyword; ?>' + '/playlist/?episodes=' + numEpisodes;

                if(scrollBar === true){
                    newPlaylistPlayerValue += '&scroll=1';
                }

                newPlaylistPlayerValue += '#mode-' + playerMode + '&border-' + playerBorderColor.replace('#', '') + '&progress-' + playerProgressColor.replace('#', '') + '"';

                newPlaylistPlayerValue += ' title="Blubrry Playlist Player" scrolling="no" width="100%" height="' + playerHeight + 'px" frameborder="0" ';

                newPlaylistPlayerValue = '<iframe ' + newPlaylistPlayerValue + '></iframe>';

                playlistPlayerEmbed[0].value = newPlaylistPlayerValue;
                playlistPlayerIframe[0].innerHTML = newPlaylistPlayerValue;
            }

            let colorPickerProgress = Pickr.create({
                el: '#color-picker-progress',
                theme: 'monolith',
                useAsButton: true,
                default: '<?php echo $playerProgressColor; ?>',
                defaultRepresentation: 'HEX',
                components: {
                    // Main components
                    preview: true,
                    opacity: true,
                    hue: true,

                    // Input / output Options
                    interaction: {
                        input: true
                    }
                }
            });

            colorPickerProgress.on('change', (color, source, instance) => {
                updateProgressColor(color.toHEXA().toString());
            });

            let colorPickerBorder = Pickr.create({
                el: '#color-picker-border',
                theme: 'monolith',
                useAsButton: true,
                default: '<?php echo $playerBorderColor; ?>',
                defaultRepresentation: 'HEX',
                components: {
                    // Main components
                    preview: true,
                    opacity: true,
                    hue: true,

                    // Input / output Options
                    interaction: {
                        input: true
                    }
                }
            });

            colorPickerBorder.on('change', (color, source, instance) => {
                updateBorderColor(color.toHEXA().toString());
            });
        });

        function copyText(textElem) {
            textElem.select();
            document.execCommand("copy");
        }
    </script>


    <!-- special page styling goes here -->
    <style>
        div.color_control input { display: inline; float: left; }
        div.color_control div.color_picker { display: inline; float: left; margin-top: 3px; }
        #player_preview { margin-bottom: 0px; height: 50px; margin-top: 8px;}
        input#colorpicker-value-input {
            width: 60px;
            height: 16px;
            padding: 0;
            margin: 0;
            font-size: 12px;
            border-spacing: 0;
            border-width: 0;
        }
        table.html5formats {
            width: 600px;
            margin: 0;
            padding: 0;
        }
        table.html5formats tr {
            margin: 0;
            padding: 0;
        }
        table.html5formats tr th {
            font-weight: bold;
            border-bottom: 1px solid #000000;
            margin: 0;
            padding: 0 5px;
            width: 25%;
        }
        table.html5formats tr td {

            border-right: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin: 0;
            padding: 0 10px;
        }
        table.html5formats tr > td:first-child {
            border-left: 1px solid #000000;
        }
    </style>
    <?php
    $creds = get_option('powerpress_creds');
    powerpress_check_credentials($creds);
    wp_enqueue_script('powerpress-admin', powerpress_get_root_url() . 'js/admin.js', array(), POWERPRESS_VERSION );

	// mainly 2 pages, first page selects a player, second configures the player, if there are options to configure for that player. If the user is on the second page,
	// a link should be provided to select a different player.
	if($select_player){ ?>
        <input type="hidden" name="action" value="powerpress-select-player" />
        <h2 style="margin-bottom: 0;"><?php echo __('PowerPress Podcast Player', 'powerpress'); ?></h2>
        <p style="margin-top: 0;"><?php echo __('Select the media player you would like to use.', 'powerpress'); ?></p>

        <?php
        if($type == 'video'){ // Video player
            if(empty($General['video_player'])){
                $General['video_player'] = '';
            }
            ?>
            <input type="hidden" name="ep" value="1" />
            <div class="pp-sidenav-tab active" style="display: block;width: auto;border-radius: 8px;">
                <div class="player-options video-player-options">
                    <div>
                        <div class="pp-settings-subsection">
                            <div class="video-player-text">
                            <?php if( $General['video_player'] == 'mediaelement-video' ) { ?>
                                <input type="hidden" name="VideoPlayer[video_player]" id="player_mediaelement_video" value="mediaelement-video" class="player-type-input" />
                            <?php } else { ?>
                                <input type="hidden" name="VideoPlayer[video_player]" id="player_mediaelement_video" value="mediaelement-video" class="player-type-input" disabled />

                            <?php } ?>
                            <h1><?php echo __('MediaElement.js Media Player (default)', 'powerpress'); ?></h1>
                            <div>
                                <?php powerpressplayer_mediaelement_info(); ?>
                            </div>
                            </div>
                            <div class="video-player-sample">
                            <?php if( $General['video_player'] == 'mediaelement-video' ) { ?>
                                <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_videoplayer.php&amp;ep=1"); ?>" id="activate_mediaelement_video" class="activate-player activated"><?php echo __('Customize', 'powerpress'); ?></a>
                            <?php } else { ?>
                                <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_videoplayer.php&amp;ep=1"); ?>" id="activate_mediaelement_video" class="activate-player"><?php echo __('Activate and Customize Now', 'powerpress'); ?></a>
                            <?php } ?>
                            <div>
                                <div class="powerpressadmin-mejs-video">
                                <?php echo powerpressplayer_build_mediaelementvideo( $Video['mediaelement-video'] ); ?>
                                </div>
                            </div>
                            </div>
                        </div>
                        <div class="pp-settings-subsection">
                            <div class="video-player-text html5" style="">
                                <?php if( $General['video_player'] == 'html5video' ) { ?>
                                    <input type="hidden" name="VideoPlayer[video_player]" id="player_html5video" value="html5video" class="player-type-input" />
                                <?php } else { ?>
                                    <input type="hidden" name="VideoPlayer[video_player]" id="player_html5video" value="html5video" class="player-type-input" disabled />
                                <?php } ?>
                                <h3><?php echo __('HTML5 Video Player', 'powerpress'); ?>  </h3>
                                <p><?php echo __('HTML5 Video is an element introduced in the latest HTML specification (HTML5) for the purpose of playing videos.', 'powerpress'); ?></p>

                            </div>
                            <div class="video-player-sample" style="min-width: 400px;">
                                <?php if( $General['video_player'] == 'html5video' ) { ?>
                                    <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_videoplayer.php&amp;ep=1"); ?>" id="activate_html5video" class="activate-player activated"><?php echo __('Customize', 'powerpress'); ?></a>
                                <?php } else { ?>
                                    <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_videoplayer.php&amp;ep=1"); ?>" id="activate_html5video" class="activate-player"><?php echo __('Activate and Configure Now', 'powerpress'); ?></a>
                                <?php } ?>
                                <div style="float: right;"><?php echo powerpressplayer_build_html5video($Video['html5video']); ?></div>
                            </div>
                        </div>
                        <div class="pp-settings-subsection-no-border">
                            <!-- videojs-html5-video-player-for-wordpress -->
                            <div class="video-player-text">
                                <?php if( $General['video_player'] == 'videojs-html5-video-player-for-wordpress' ) { ?>
                                    <input type="hidden" name="VideoPlayer[video_player]" id="player_videojs_html5_video_player_for_wordpress" value="videojs-html5-video-player-for-wordpress" class="player-type-input" />
                                <?php } else { ?>
                                    <input type="hidden" name="VideoPlayer[video_player]" id="player_videojs_html5_video_player_for_wordpress" value="videojs-html5-video-player-for-wordpress" class="player-type-input" disabled />
                                <?php } ?>
                                <h3><?php echo __('VideoJS', 'powerpress'); ?></h3>
                                <?php powerpressplayer_videojs_info(); ?>
                            </div>
                            <div class="video-player-sample">
                                <?php if ( function_exists('add_videojs_header') ) { ?>
                                    <?php if( $General['video_player'] == 'videojs-html5-video-player-for-wordpress' ) { ?>
                                        <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_videoplayer.php&amp;ep=1"); ?>" id="activate_videojs_html5_video_player_for_wordpress" class="activate-player activated"><?php echo __('Customize', 'powerpress'); ?></a>
                                    <?php } else { ?>
                                        <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_videoplayer.php&amp;ep=1"); ?>" id="activate_videojs_html5_video_player_for_wordpress" class="activate-player"><?php echo __('Activate and Configure Now', 'powerpress'); ?></a>
                                    <?php } ?>
                                <?php } ?>
                                <p>
                                    <?php
                                    if ( function_exists('add_videojs_header') ) {
                                        echo powerpressplayer_build_videojs( $Video['videojs-html5-video-player-for-wordpress'] );
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else { // audio player ?>
            <input type="hidden" name="ep" value="1" />
            <div class="pp-sidenav-tab active" style="display: block;width: auto;border-radius: 8px;">
                <div class="player-options">
                    <div class="pp-settings-subsection">
                        <?php if($General['player'] == 'blubrrymodern' ){ ?>
                            <input type="hidden" name="Player[player]" id="player_blubrrymodern" value="blubrrymodern" class="player-type-input" />

                            <h1><?php echo __('Blubrry Player', 'powerpress'); ?></h1>

                            <?php powerpress_settings_save_button(true); ?>

                            <h3 style="display: inline-block; vertical-align: top; margin-top: 1ch; float: right; margin-right: 20px;">
                                <a href="#" id="restore-default-settings" style="text-decoration: none; font-weight: normal;"><?php echo __('Restore Defaults', 'powerpress'); ?></a>
                            </h3>
                        <?php } else { ?>
                            <input type="hidden" name="Player[player]" id="player_blubrrymodern" value="blubrrymodern" class="player-type-input" disabled />

                            <h1>
                                <?php echo __('Blubrry Player', 'powerpress'); ?>
                                <a href="<?php echo admin_url('admin.php?page=powerpress/powerpressadmin_player.php&sp=1'); ?>" id="activate_blubrrymodern" class="activate-player" style="float: right;"><?php echo __('Activate', 'powerpress'); ?></a>
                            </h1>
                        <?php } ?>

                        <h3 style="font-weight: normal;"><?php echo __('The Blubrry Player is only available to Blubrry Hosting Customers.', 'powerpress'); ?></h3>

                        <div id="modern_player_iframe_div" style="margin: 25px 0;">
                            <?php print_modern_player_demo($playerSettings); ?>
                        </div>

                        <h3><?php echo __('Customize Player', 'powerpress'); ?></h3>
                        <h3 style="font-weight: normal; margin-bottom: 1em;">
                            Don't forget to <a target="_blank" href="https://webaim.org/resources/contrastchecker/">check the contrast</a>
                            of your color scheme to ensure it is accessible for all users.
                        </h3>

                        <div class="player-customize-container" style="">
                            <div class="customize-box-section" style="width: 100%">
                                <h3><?php echo __('Player Contents', 'powerpress'); ?></h3>

                                <?php

                                ?>

                                <div>
                                    <div class="" style="">
                                        <div style="display: flex; justify-content: flex-start; align-items: center;">
                                            <div class="" style="margin-right: 40px;">
                                                <h4 style="font-weight: bold;">Mode</h4>
                                            </div>
                                            <div class="" style="display: flex; align-items: center;">
                                                <input <?php echo ($playerMode == 'Light' ? 'checked' : ''); ?> type="radio" name="mode" id="mode-light" aria-label="Player Mode Light" value="Light">
                                                <label for="mode-light" class="" style="font-size: 14px; margin-right: 20px;">Light</label>

                                                <input <?php echo ($playerMode == 'Dark' ? 'checked' : ''); ?> type="radio" name="mode" id="mode-dark" aria-label="Player Mode Dark" value="Dark">
                                                <label for="mode-dark" class="" style="font-size: 14px;">Dark</label>
                                            </div>

                                            <div class="" style="margin-left: 80px; margin-right: 10px;">
                                                <h4 style="font-weight: bold;">Border</h4>
                                            </div>

                                            <div style="display: flex;">
                                                <input type="text" style="" id="border-color-hex" name="ModernPlayer[border]"
                                                    class="new-color-field"
                                                    value="<?php echo esc_attr($playerBorderColor); ?>"
                                                    maxlength="20"
                                                    aria-label="Player Border Color"
                                                />
                                                <button type="button" id="color-picker-border" class="new-color-field-button" style="background-color: <?php echo $playerBorderColor; ?>"></button>
                                            </div>

                                            <div class="" style="margin-left: 80px; margin-right: 10px;">
                                                <h4 style="font-weight: bold;">Progress</h4>
                                            </div>

                                            <div style="display: flex;">
                                                <input type="text" style="" id="progress-color-hex" name="ModernPlayer[progress]"
                                                    class="new-color-field"
                                                    value="<?php echo esc_attr($playerProgressColor); ?>"
                                                    maxlength="20"
                                                    aria-label="Player Progress Color"
                                                />
                                                <button type="button" id="color-picker-progress" class="new-color-field-button" style="background-color: <?php echo $playerProgressColor; ?>"></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <?php // Latest Episode ?>
                        <div class="player-customize-container" style=" margin-top: 15px;">
                            <div class="customize-box-section" style="width: 100%;">
                                <h3><?php echo __('Latest Episode Player', 'powerpress'); ?></h3>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px;">
                                    <h4 style="font-weight: bold; width: 10%;">Embed Code</h4>
                                    <div style="width: 90%;">
                                        <?php
                                        if(preg_match('/blubrry.local/i', $_SERVER['HTTP_HOST'])){
                                            $playerUrlLatest = 'http://player.blubrry.local/';
                                        } else {
                                            $playerUrlLatest = 'https://player.blubrry.com/';
                                        }
                                        $playerUrlLatest .= $program_keyword . '/latest/';
                                        $playerUrlLatest .= '#mode-' . $playerMode . '&border=' . str_replace('#', '', $playerBorderColor) . '&progress-' . str_replace('#', '', $playerProgressColor);
                                        $playerHeightLatest = "165px";
                                        ?>

                                        <input type="text" onclick="copyText(this)"
                                            id="link-to-embed-latest"
                                            class="form-control"
                                            title="Copy to clipboard"
                                            value='<iframe src="<?php echo $playerUrlLatest; ?>" title="Blubrry Podcast Player" scrolling="no" width="100%" height="<?php echo $playerHeightLatest; ?>" frameborder="0"></iframe>'
                                            readonly/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php // Playlist Settings ?>
                        <div class="player-customize-container" style=" margin-top: 15px;">
                            <div class="customize-box-section" style="width: 100%;">
                                <h3><?php echo __('Player with Playlist', 'powerpress'); ?></h3>
                                <p>
                                    Set the number of episodes you want to feature in your playlist. Copy and paste the embed code to place your playlist on your website.
                                </p>
                                <p>
                                    <span style="font-weight: bold;">NOTE:</span> The below settings do not save. Once they are set the iframe automatically updates for you to copy and paste into your post.
                                </p>
                                <div style="display: flex; align-items: center; margin-top: 25px;">
                                    <h4 style="font-weight: bold; margin-right: 15px;">Do you want a scroll bar?</h4>
                                    <input name="scroll" type="checkbox" value="" id="scroll-check">
                                    <label for="scrollCheck" style="color: black; margin-right: 80px;">
                                        <strong>Yes</strong>
                                    </label>

                                    <div style="display: flex; align-items: center; flex-direction: row; width: 40%;">
                                        <h4 style="font-weight: bold; margin-right: 15px;">Number of Episodes</h4>
                                        <input class="form-control" style="width: 60px; margin-right: 15px;" id="num-episodes" name="num_episodes" type="number" value="5">
                                        <p style="margin: 0; color: #9F6E00 !important; font-weight: bold;">Max 50</p>
                                    </div>
                                </div>

                                <div style="margin-top: 25px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px;">
                                        <h4 style="font-weight: bold; ">Embed Code</h4>
                                        <div style="width: 90%;">
                                            <?php
                                            if(preg_match('/blubrry.local/i', $_SERVER['HTTP_HOST'])){
                                                $playerUrl = 'http://player.blubrry.local/';
                                            } else {
                                                $playerUrl = 'https://player.blubrry.com/';
                                            }

                                            $playerUrl .= $program_keyword . '/playlist/?episodes=5';
                                            $playerUrl .= '#mode-' . $playerMode . '&border=' . str_replace('#', '', $playerBorderColor) . '&progress-' . str_replace('#', '', $playerProgressColor);
                                            $playerHeight = "415px";
                                            ?>
                                            <input type="text" onclick="copyText(this)"
                                                   id="link-to-embed"
                                                   class="form-control"
                                                   title="Copy to clipboard"
                                                   value='<iframe src="<?php echo $playerUrl; ?>" title="Blubrry Podcast Player" scrolling="no" width="100%" height="<?php echo $playerHeight; ?>" frameborder="0"></iframe>'
                                                   readonly/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h3 style="margin-bottom: 1em; margin-top: 2em;"><?php echo __('Playlist Player Preview', 'powerpress'); ?></h3>

                        <div id="playlist-player-iframe">
                            <iframe id="player" src="<?php echo $playerUrl; ?>" title="Blubrry Podcast Player" scrolling="no" width="100%" height="<?php echo $playerHeight; ?>" frameborder="0"></iframe>
                        </div>
                    </div>

                    <?php // Media Player ?>
                    <div class="pp-settings-subsection">
                        <?php if( $General['player'] == 'mediaelement-audio' ) { ?>
                            <input type="hidden" name="Player[player]" id="player_mediaelement_audio" value="mediaelement-audio" class="player-type-input" />
                            <h1><?php echo __('MediaElement.js Media Player (default)', 'powerpress'); ?>
                            <a href="<?php echo admin_url('admin.php?page=powerpress/powerpressadmin_player.php&ep=1'); ?>" id="activate_mediaelement_audio" class="activate-player activated" style="float: right;"><?php echo __('Customize', 'powerpress'); ?></a></h1>
                        <?php } else { ?>
                            <input type="hidden" name="Player[player]" id="player_mediaelement_audio" value="mediaelement-audio" class="player-type-input" disabled />
                            <h1><?php echo __('MediaElement.js Media Player (default)', 'powerpress'); ?>
                            <a href="<?php echo admin_url('admin.php?page=powerpress/powerpressadmin_player.php&ep=1'); ?>" id="activate_mediaelement_audio" class="activate-player" style="float: right;"><?php echo __('Activate', 'powerpress'); ?></a></h1>
                        <?php } ?>
                        <?php powerpressplayer_mediaelement_info(); ?>
                        <p>
                        <div style="width: 90%;">
                        <?php echo powerpressplayer_build_mediaelementaudio( $Audio['mediaelement-audio'] ); ?>
                        </div>
                        </p>
                    </div>

                    <?php // Audio Player ?>
                    <div class="pp-settings-subsection-no-border">
                        <?php if($General['player'] == 'html5audio'){ ?>
                            <input type="hidden" name="Player[player]" id="player_html5audio" value="html5audio" class="player-type-input" />
                            <h1>
                                <?php echo __('HTML5 Audio Player', 'powerpress'); ?>
                                <a href="<?php echo admin_url('admin.php?page=powerpress/powerpressadmin_player.php&ep=1'); ?>" id="activate_html5audio" class="activate-player activated" style="float: right;"><?php echo __('Customize', 'powerpress'); ?></a>
                            </h1>
                        <?php } else { ?>
                            <input type="hidden" name="Player[player]" id="player_html5audio" value="html5audio" class="player-type-input" disabled />
                            <h1>
                                <?php echo __('HTML5 Audio Player', 'powerpress'); ?>
                                <a href="<?php echo admin_url('admin.php?page=powerpress/powerpressadmin_player.php&ep=1'); ?>" id="activate_html5audio" class="activate-player" style="float: right;"><?php echo __('Activate', 'powerpress'); ?></a>
                            </h1>
                        <?php } ?>
                        <p><?php echo __('HTML5 audio is an element introduced in the latest HTML specification (HTML5) for the purpose of playing audio.', 'powerpress'); ?></p>

                        <div style="width: 90%;">
                            <?php echo powerpressplayer_build_html5audio($Audio['html5audio']); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php }
    } else { ?>
        <input type="hidden" name="ep" value="1"/>
        <h2><?php echo __('Configure Player', 'powerpress'); ?></h2>

        <?php if($type == 'audio'){ ?>
            <p style="margin-bottom: 20px;"><strong>&#8592;  <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_player.php&amp;sp=1"); ?>"><?php echo __('Select a different audio player', 'powerpress'); ?></a></strong></p>
        <?php } elseif($type == 'video'){ ?>
            <p style="margin-bottom: 20px;"><strong>&#8592;  <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_videoplayer.php&amp;sp=1"); ?>"><?php echo __('Select a different video player', 'powerpress'); ?></a></strong></p>
        <?php } else { ?>
            <p style="margin-bottom: 20px;"><strong>&#8592;  <a href="<?php echo admin_url("admin.php?page=powerpress/powerpressadmin_mobileplayer.php&amp;sp=1"); ?>"><?php echo __('Select a different mobile player', 'powerpress'); ?></a></strong></p>
        <?php } ?>

        <div class="player-options pp-sidenav-tab active" style="display: block;width: auto;border-radius: 8px;">
            <?php
            // Start adding logic here to display options based on the player selected...
            if($type == 'audio'){
                if(empty($General['player'])){
                    $General['player'] = '';
                }

                switch($General['player']){
                    case 'html5audio': {
                        $SupportUploads = powerpressadmin_support_uploads();
                        ?>

                        <p><?php echo __('Configure HTML5 Audio Player', 'powerpress'); ?></p>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php echo __('Preview of Player', 'powerpress'); ?>
                                </th>
                                <td>
                                    <p>
                        <?php
                                    echo powerpressplayer_build_html5audio( $Audio['html5audio'] );
                        ?>
                                    </p>
                                </td>
                            </tr>


                            <tr>
                            <th scope="row">
                            <?php echo __('Play Icon', 'powerpress'); ?></th>
                            <td>

                            <input type="text" id="audio_custom_play_button" name="General[audio_custom_play_button]" style="width: 60%;" value="<?php echo esc_attr($General['audio_custom_play_button']); ?>" maxlength="255" />
                            <a href="#" onclick="javascript: window.open( document.getElementById('audio_custom_play_button').value ); return false;"><?php echo __('preview', 'powerpress'); ?></a>

                            <p><?php echo __('Place the URL to the play icon above.', 'powerpress'); ?> <?php echo __('Example', 'powerpress'); ?>: http://example.com/images/audio_play_icon.jpg<br /><br />
                            <?php echo __('Leave blank to use default play icon image.', 'powerpress'); ?></p>

                            <?php if( $SupportUploads ) { ?>
                            <p><input name="audio_custom_play_button_checkbox" type="checkbox" onchange="powerpress_show_field('audio_custom_play_button_upload', this.checked)" value="1" /> <?php echo __('Upload new image', 'powerpress'); ?> </p>
                            <div style="display:none" id="audio_custom_play_button_upload">
                                <label for="audio_custom_play_button_file"><?php echo __('Choose file', 'powerpress'); ?>:</label><input type="file" name="audio_custom_play_button_file"  />
                            </div>
                            <?php } ?>
                            </td>
                            </tr>
                        </table>

                    <?php } break;

                    case 'blubrryaudio' : {   //TODO
                        break; // not displaying any of the old-player settings
                        $BBplayerSettings = powerpress_get_settings('powerpress_bplayer');
                        if (empty($BBplayerSettings)) {
                            $BBplayerSettings = array(
                                'showbg' => '#444444',
                                'showtext' => '#ffffff',
                                'downloadbgcolor' => '#003366',
                                'downloadcolortext' => '#ffffff',
                                'subscribebg' => '#fb8c00',
                                'textsubscribe' => '#ffffff',
                                'bgshare' => '#1976d2',
                                'textshare' => '#ffffff',
                                'playerstyle' => 'light'
                            );
                        }
                        ?>

                        <div id="tab_play" class="powerpress_tab bbplayer_settings" style="padding-left: 3%; padding-right: 3%">
                            <h2 style="font-size: 2em;"> <?php echo __('Blubrry Player', 'powerpress'); ?> </h2>
                            <p>
                                <?php echo __('Note: The Blubrry Audio Player is only available to Blubrry Hosting Customers.', 'powerpress'); ?>
                            </p>
                            <p>
                                <?php echo __('Shownotes and Download options are not displayed initially.', 'powerpress'); ?>
                            </p>
                            <div id="player_iframe_div"
                                 style="border: 1px solid #000000; height: 138px; box-shadow: inset 0 0 10px black, 0 0 6px black; margin: 20px 0;">
                                <?php  //print_blubrry_player_demo(); ?>
                            </div>
                            <br>

                            <table class="form-table">
                                <tr>
                                    <td>
                                        <a href="#" id="previewButton"
                                           style="font-weight: bold; color: #1976d2; font-size: 12px"><?php echo __('Preview Changes', 'powerpress'); ?></a>

                                    </td>
                                    <td>
                                        <a href="#" id="restoreDefaultsButton"
                                           style="font-weight: bold; color: #1976d2; font-size: 12px"><?php echo __('Restore Default Colors', 'powerpress'); ?></a>

                                    </td>
                                </tr>
                                <h3 style="font-size: 2em; margin-bottom: 5px; color: #23282d; font-weight: 500"><?php echo __('Player Customization Settings', 'powerpress'); ?></h3>

                                <tr valign="top" style="margin-bottom: -15px;">
                                    <th scope="row">
                                        <h3>   <?php echo __('Buttons', 'powerpress'); ?> </h3>
                                    </th>

                                    <th scope="row">
                                        <h3>  <?php echo __('Background Color', 'powerpress'); ?> </h3>
                                    </th>

                                    <th scope="row">
                                        <h3>   <?php echo __('Font Color', 'powerpress'); ?> </h3>
                                    </th>

                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php echo __('Shownotes/Embed Button', 'powerpress'); ?>
                                    </th>
                                    <td>

                                        <div class="color_control">
                                            <input type="text" style="width: 100px;" id="shownotesbg" name="BBPlayer[showbg]"
                                                   class="color-field"
                                                   value="<?php echo esc_attr($BBplayerSettings['showbg']); ?>"
                                                   maxlength="20"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="color_control">
                                            <input type="text" style="width: 100px;" id="showtext"
                                                   name="BBPlayer[showtext]" class="color-field"
                                                   value="<?php echo esc_attr($BBplayerSettings['showtext']); ?>"
                                                   maxlength="20"/>
                                        </div>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php echo __('Download Button', 'powerpress'); ?>
                                    </th>
                                    <td>
                                        <div class="color_control">
                                            <input type="text" style="width: 100px;" id="downloadbgcolor"
                                                   name="BBPlayer[downloadbgcolor]"
                                                   class="color-field"
                                                   value="<?php echo esc_attr($BBplayerSettings['downloadbgcolor']); ?>"
                                                   maxlength="20"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="color_control">
                                            <input type="text" style="width: 100px;" id="downloadcolortext"
                                                   name="BBPlayer[downloadcolortext]"
                                                   class="color-field"
                                                   value="<?php echo esc_attr($BBplayerSettings['downloadcolortext']); ?>"
                                                   maxlength="20"/>
                                        </div>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php echo __('Subscribe Button', 'powerpress'); ?>
                                    </th>
                                    <td>
                                        <div class="color_control">
                                            <input type="text" style="width: 100px;" id="subscribebg" name="BBPlayer[subscribebg]"
                                                   class="color-field"
                                                   value="<?php echo esc_attr($BBplayerSettings['subscribebg']); ?>"
                                                   maxlength="20"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="color_control">
                                            <input type="text" style="width: 100px;" id="textsubscribe"
                                                   name="BBPlayer[textsubscribe]" class="color-field"
                                                   value="<?php echo esc_attr($BBplayerSettings['textsubscribe']); ?>"
                                                   maxlength="20"/>
                                        </div>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php echo __('Share Button', 'powerpress'); ?>
                                    </th>
                                    <td>
                                        <div class="color_control">
                                            <input type="text" style="width: 100px;" id="bgshare" name="BBPlayer[bgshare]"
                                                   class="color-field"
                                                   value="<?php echo esc_attr($BBplayerSettings['bgshare']); ?>"
                                                   maxlength="20"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="color_control">
                                            <input type="text" style="width: 100px;" id="textshare" name="BBPlayer[textshare]"
                                                   class="color-field"
                                                   value="<?php echo esc_attr($BBplayerSettings['textshare']); ?>"
                                                   maxlength="20"/>
                                        </div>
                                    </td>

                                </tr>

                                <tr valign="top">
                                    <br>

                                    <th scope="row">
                                        <h3><?php echo __('Player Style', 'powerpress'); ?> </h3>
                                    </th>
                                    <td>
                                        <input type="radio" name="BBPlayer[playerstyle]" id="selectlight"
                                               value="light" <?php echo ($BBplayerSettings['playerstyle'] == 'light') ? 'checked = true' : ''; ?>>
                                        <label for="selectlight" style="font-size:14px;">Light (default)</label>
                                    </td>
                                    <td>
                                        <input type="radio" name="BBPlayer[playerstyle]" id="selectdark"
                                               value="dark" <?php echo ($BBplayerSettings['playerstyle'] == 'dark') ? 'checked = true' : ''; ?> >
                                        <label for="selectdark" style="font-size:14px;">Dark</label>
                                    </td>
                                </tr>

                                <!-- <p><input name="General[itunes_image_audio]" type="hidden" value="0"/><input name="General[itunes_image_audio]" type="checkbox" value="1" <?php echo(!empty($General['itunes_image_audio']) ? 'checked' : ''); ?> /> <?php echo __('Use episode iTunes image if set', 'powerpress'); ?> </p> -->
                            </table>
                            <h3><?php echo __('Episode Image', 'powerpress'); ?></h3>

                            <p><input name="General[new_episode_box_itunes_image]" type="hidden" value="0"/><input
                                        name="General[new_episode_box_itunes_image]" type="checkbox"
                                        value="1" <?php echo((empty($General['new_episode_box_itunes_image']) || $General['new_episode_box_itunes_image'] == 1) ? 'checked' : ''); ?> /> <?php echo __('Display field for entering iTunes episode image ', 'powerpress'); ?>
                            </p>
                            <p><input name="General[bp_episode_image]" type="hidden" value="0"/><input
                                        name="General[bp_episode_image]" type="checkbox"
                                        value="1" <?php echo(!empty($General['bp_episode_image']) ? 'checked' : ''); ?> /> <?php echo __('Use iTunes episode image with player', 'powerpress'); ?>
                            </p>
                            <input type="hidden" name="General[powerpress_bplayer_settings]" value="1" />
                        </div>
                        <input type="hidden" name="action" value="powerpress_bplayer"/>
                    <?php } break;

                    case 'mediaelement-audio': {
                        $SupportUploads = powerpressadmin_support_uploads();

                        if(!isset($General['audio_player_max_width'])){
                            $General['audio_player_max_width'] = '';
                        } ?>
                        <p><?php echo __('Configure MediaElement.js Audio Player', 'powerpress'); ?></p>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php echo __('Preview of Player', 'powerpress'); ?>
                                </th>
                                <td><p>
                                <?php
                                // TODO
                                    echo powerpressplayer_build_mediaelementaudio($Audio['mediaelement-audio']);
                                ?>
                                    </p>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <?php echo __('Max Width', 'powerpress'); ?>
                                </th>
                                <td valign="top">
                                        <input type="text" style="width: 50px;" id="audio_player_max_width" name="General[audio_player_max_width]" class="player-width" value="<?php echo esc_attr($General['audio_player_max_width']); ?>" maxlength="4" />
                                    <?php echo __('Width of Audio mp3 player (leave blank for max width)', 'powerpress'); ?>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    &nbsp;
                                </th>
                                <td>
                                    <p><?php echo __('MediaElement.js Player has no additional settings at this time.', 'powerpress'); ?></p>
                                </td>
                            </tr>
                        </table>

                    <?php } break;

                    // TODO:
                    default: {
                        if(empty($General['player_width_audio'])){
                            $General['player_width_audio'] = '';
                        }
                        ?>

                        <h2><?php echo __('General Settings', 'powerpress'); ?></h2>
                        <table class="form-table">
                            <tr valign="top">
                            <th scope="row">
                                <?php echo __('Width', 'powerpress'); ?>
                            </th>
                            <td valign="top">
                                    <input type="text" style="width: 50px;" id="player_width" name="General[player_width_audio]" class="player-width" value="<?php echo esc_attr($General['player_width_audio']); ?>" maxlength="4" />
                                <?php echo __('Width of Audio mp3 player (leave blank for 320 default)', 'powerpress'); ?>
                            </td>
                        </tr>
                    </table>
                    <?php } break;
                }

            } elseif($type == 'video'){
                $player_to_configure = (!empty($General['video_player'])?$General['video_player']:'');
                switch($player_to_configure){
                    case 'html5':
                    case 'html5video': {
                        echo '<p>'. __('Configure HTML5 Video Player', 'powerpress') . '</p>';
                        ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php echo __('Preview of Player', 'powerpress'); ?>
                                </th>
                                <td>
                                    <?php
                                    if($type == 'mobile'){
                                        echo '<p>' . __('Audio:', 'powerpress') .' ';
                                        echo powerpressplayer_build_html5audio( $Audio['html5audio'] );
                                        echo '</p>';
                                    }
                                    ?>
                                    <p>
                                        <?php
                                        if($type == 'mobile'){
                                            echo  __('Video:', 'powerpress') .' ';
                                        }
                                        echo powerpressplayer_build_html5video( $Video['html5video'] );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <?php
                    } break;
                    case 'videojs-html5-video-player-for-wordpress': { ?>
                        <p><?php echo __('Configure VideoJS', 'powerpress'); ?></p>

                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php echo __('Preview of Player', 'powerpress'); ?>
                                </th>
                                <td>
                                    <p>
                                        <?php
                                        echo powerpressplayer_build_videojs( $Video['videojs-html5-video-player-for-wordpress'] );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <h3><?php echo __('VideoJS Settings', 'powerpress'); ?></h3>

                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php echo __('VideoJS CSS Class', 'powerpress'); ?>
                                </th>
                                <td>
                                    <p>
                                    <input type="text" name="General[videojs_css_class]" style="width: 150px;" value="<?php echo ( empty($General['videojs_css_class']) ?'':esc_attr($General['videojs_css_class']) ); ?>" />
                                    <?php echo __('Apply specific CSS styling to your Video JS player.', 'powerpress'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <?php
                    } break;
                    case 'mejs': // $player_to_configure
                    case 'mediaelement-video':
                    default: { ?>
                        <p><?php echo __('Configure MediaElement.js Player', 'powerpress'); ?></p>

                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php echo __('Preview of Player', 'powerpress'); ?>
                                </th>
                                <td>
                                    <p>
                                        <?php
                                        if($type == 'mobile'){
                                            echo '<p>' . __('Audio:', 'powerpress') .' ';
                                            echo powerpressplayer_build_mediaelementaudio( $Audio['mediaelement-audio'] );
                                            echo '</p>';
                                        } ?>
                                    </p>
                                    <div style="max-width: 70%;">
                                        <div class="powerpressadmin-mejs-video">
                                            <?php
                                            if($type == 'mobile'){
                                                echo  __('Video:', 'powerpress') .' ';
                                            }
                                            echo powerpressplayer_build_mediaelementvideo( $Video['mediaelement-video'] );
                                            ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    <?php } break;
                }

                if(!isset($General['poster_play_image'])){
                    $General['poster_play_image'] = 1;
                }
                if(!isset($General['poster_image_audio'])){
                    $General['poster_image_audio'] = 0;
                }
                if(!isset($General['player_width'])){
                    $General['player_width'] = '';
                }
                if(!isset($General['player_height'])){
                    $General['player_height'] = '';
                }
                if(!isset($General['poster_image'])){
                    $General['poster_image'] = '';
                }
                if(!isset($General['video_player_max_width'])){
                    $General['video_player_max_width'] = '';
                }
                if(!isset($General['video_player_max_height'])){
                    $General['video_player_max_height'] = '';
                }
                if(!isset($General['video_custom_play_button'])){
                    $General['video_custom_play_button'] = '';
                }
                ?>

                <!-- Global Video Player settings (Apply to all video players) -->
                <input type="hidden" name="action" value="powerpress-save-videocommon"/>
                <h3><?php echo __('Common Video Settings', 'powerpress'); ?></h3>

                <p><?php echo __('The following video settings apply to the video player above as well as to classic video &lt;embed&gt; formats such as Microsoft Windows Media (.wmv), QuickTime (.mov) and RealPlayer.', 'powerpress'); ?></p>
                <table class="form-table">
                    <?php if($player_to_configure == 'mediaelement-video' || $player_to_configure == 'mejs'){ ?>
                        <tr valign="top">
                            <th scope="row">
                                <?php echo __('Player Width', 'powerpress'); ?>
                            </th>
                            <td>
                                <input type="text" name="General[player_width]" style="width: 50px;" onkeyup="javascript:this.value=this.value.replace(/[^0-9%]/g, '');" value="<?php echo esc_attr($General['player_width']); ?>" maxlength="4" />
                                <?php echo __('Width of player (leave blank for default width)', 'powerpress'); ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <?php echo __('Player Height', 'powerpress'); ?>
                            </th>
                            <td>
                                <input type="text" name="General[player_height]" style="width: 50px;" onkeyup="javascript:this.value=this.value.replace(/[^0-9%]/g, '');" value="<?php echo esc_attr($General['player_height']); ?>" maxlength="4" />
                                <?php echo __('Height of player (leave blank for default height)', 'powerpress'); ?>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <tr valign="top">
                            <th scope="row">
                                <?php echo __('Player Width', 'powerpress'); ?>
                            </th>
                            <td>
                                <input type="text" name="General[player_width]" style="width: 50px;" onkeyup="javascript:this.value=this.value.replace(/[^0-9%]/g, '');" value="<?php echo esc_attr($General['player_width']); ?>" maxlength="4" />
                                <?php echo __('Width of player (leave blank for 400 default)', 'powerpress'); ?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <?php echo __('Player Height', 'powerpress'); ?>
                            </th>
                            <td>
                                <input type="text" name="General[player_height]" style="width: 50px;" onkeyup="javascript:this.value=this.value.replace(/[^0-9%]/g, '');" value="<?php echo esc_attr($General['player_height']); ?>" maxlength="4" />
                                <?php echo __('Height of player (leave blank for 225 default)', 'powerpress'); ?>
                            </td>
                        </tr>
                    <?php }

                    $SupportUploads = powerpressadmin_support_uploads();
                    ?>

                    <tr>
                        <th scope="row">
                        <?php echo __('Default Poster Image', 'powerpress'); ?></th>
                        <td>
                            <input type="text" id="poster_image" name="General[poster_image]" style="width: 60%;" value="<?php echo esc_attr($General['poster_image']); ?>" maxlength="255" />
                            <a href="#" onclick="javascript: window.open( document.getElementById('poster_image').value ); return false;"><?php echo __('preview', 'powerpress'); ?></a>

                            <p><?php echo __('Place the URL to the poster image above.', 'powerpress'); ?> <?php echo __('Example', 'powerpress'); ?>: http://example.com/images/poster.jpg<br /><br />
                            <?php echo __('Image should be at minimum the same width/height as the player above. Leave blank to use default black background image.', 'powerpress'); ?></p>

                            <?php if( $SupportUploads ) { ?>
                                <p><input name="poster_image_checkbox" type="checkbox" onchange="powerpress_show_field('poster_image_upload', this.checked)" value="1" /> <?php echo __('Upload new image', 'powerpress'); ?> </p>
                                <div style="display:none" id="poster_image_upload">
                                    <label for="poster_image_file"><?php echo __('Choose file', 'powerpress'); ?>:</label><input type="file" name="poster_image_file"  />
                                </div>
                            <?php }

                            if(in_array($General['video_player'], array('html5video'))){ ?>
                                <p><input name="General[poster_play_image]" type="checkbox" value="1" <?php echo ((!isset($General['poster_play_image']) || $General['poster_play_image'])?'checked':''); ?> /> <?php echo __('Include play icon over poster image when applicable', 'powerpress'); ?> </p>
                                <?php if($type == 'video'){ ?>
                                    <p><input name="General[poster_image_audio]" type="checkbox" value="1" <?php echo ($General['poster_image_audio']?'checked':''); ?> /> <?php echo __('Use poster image, player width and height above for audio (Flow Player only)', 'powerpress'); ?> </p>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>

                    <?php
                    // Play icon, only applicable to HTML5/FlowPlayerClassic
                    if(in_array($General['video_player'], array('html5video'))){ ?>
                        <tr>
                            <th scope="row">
                            <?php echo __('Video Play Icon', 'powerpress'); ?></th>
                            <td>
                                <input type="text" id="video_custom_play_button" name="General[video_custom_play_button]" style="width: 60%;" value="<?php echo esc_attr($General['video_custom_play_button']); ?>" maxlength="255" />
                                <a href="#" onclick="javascript: window.open( document.getElementById('video_custom_play_button').value ); return false;"><?php echo __('preview', 'powerpress'); ?></a>

                                <p><?php echo __('Place the URL to the play icon above.', 'powerpress'); ?> <?php echo __('Example', 'powerpress'); ?>: http://example.com/images/video_play_icon.jpg<br /><br />
                                <?php echo __('Image should 60 pixels by 60 pixels. Leave blank to use default play icon image.', 'powerpress'); ?></p>

                                <?php if( $SupportUploads ) { ?>
                                    <p><input name="video_custom_play_button_checkbox" type="checkbox" onchange="powerpress_show_field('video_custom_play_button_upload', this.checked)" value="1" /> <?php echo __('Upload new image', 'powerpress'); ?> </p>
                                    <div style="display:none" id="video_custom_play_button_upload">
                                        <label for="video_custom_play_button_file"><?php echo __('Choose file', 'powerpress'); ?>:</label><input type="file" name="video_custom_play_button_file"  />
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </div>

        <?php
        if($General['player'] != 'blubrrymodern' || $type == 'video'){
            powerpress_settings_save_button(true);
        }
	}
}

function print_blubrry_player_demo(){ ?>
    <p>
        <?php echo __('The Blubrry Audio Player is only available to Blubrry Hosting Customers.', 'powerpress'); ?>
    </p>
    <p style="font-weight: bold;">
        <?php echo __('The Legacy player will be discontinued on December 31, 2023.', 'powerpress'); ?>
    </p>
    <div style="border: 1px solid #000000; height: 138px; box-shadow: inset 0 0 10px black, 0 0 6px black; margin: 20px 0;">
        <?php
        echo powerpressplayer_build_blubrryaudio_by_id(); // Special episode where we talk about the new player
        ?>
    </div>
<?php }

function print_modern_player_demo($playerSettings){ ?>
    <?php echo powerpressplayer_build_blubrryaudio_by_id($playerSettings); // Special episode where we talk about the new player ?>
<?php } ?>

<?php // eof ?>