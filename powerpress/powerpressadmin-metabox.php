<?php

function episode_box_top($EnclosureURL, $FeedSlug, $ExtraData, $GeneralSettings, $EnclosureLength, $DurationHH, $DurationMM, $DurationSS, $PCITranscriptURL)
{

    if ($EnclosureURL) {
        $style1 = "display: none";
        $style2 = "display: block";
        $style3 = "display: inline-block";
        $style4 = "display: none";
        $filename = $EnclosureURL;
        $style_attr = "";
        $padding = "";
    } else {
        $style1 = "display: inline-block";
        $style2 = "display: none";
        $style3 = "display: none";
        $style4 = "display: block";
        $padding = "style=\"margin-bottom: 2em;\"";
        $filename = "";
        if ($GeneralSettings['blubrry_hosting']) {
            $style_attr = "style=\"padding: 2em;\"";
        } else {
            $style_attr = "";
        }
    }
    if (!$DurationHH) {
        $DurationHH = '00';
    }
    if (!$DurationMM) {
        $DurationMM = '00';
    }
    if (!$DurationSS) {
        $DurationSS = '00';
    }
    if (empty($ExtraData)) {
        $ExtraData = array();
    }
    $FeedSettings = get_option('powerpress_feed_' . $FeedSlug);
    $language = $ExtraData['pci_transcript_language'] ?? '';
    if (empty($language) && !empty($FeedSettings['rss_language'])) {
        $language = $FeedSettings['rss_language'];
    }
    if (empty($language)) {
        $language = get_bloginfo("language");
    }
?>
    <div id="a-pp-selected-media-<?php echo $FeedSlug; ?>" <?php echo $padding; ?>>
        <h3 id="pp-pp-selected-media-head-<?php echo $FeedSlug; ?>">
            <?php echo esc_html(__('Media URL', 'powerpress')); ?>
            <a class="pp-ep-box-settings thickbox" title='Entry Box Settings' href="<?php echo admin_url(); ?>?action=powerpress-ep-box-options&amp;KeepThis=true&amp;TB_iframe=true&amp;width=600&amp;height=400&amp;modal=false">
                <img class="ep-box-settings-icon" src="<?php echo powerpress_get_root_url(); ?>images/outline_settings_24px.svg" alt="" />
            </a>
        </h3>
        <div id="pp-media-blubrry-container-<?php echo $FeedSlug; ?>" <?php echo $style_attr; ?>>
            <div id="pp-selected-media-text-<?php echo $FeedSlug; ?>">
                <div id="media-input-<?php echo $FeedSlug; ?>">
                    <div id="pp-url-input-container-<?php echo $FeedSlug; ?>" style="<?php echo $style1 ?>">
                        <div id="pp-url-input-label-container-<?php echo $FeedSlug; ?>">
                            <input type="hidden" id="powerpress_url_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__('File Media or URL')); ?>"
                                name="Powerpress[<?php echo $FeedSlug; ?>][url]" placeholder="https://example.com/path/to/media.mp3"
                                value="<?php echo esc_attr($EnclosureURL); ?>" />
                            <input type="text" id="powerpress_url_display_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__('File Media or URL')); ?>"
                                name="null" placeholder="https://example.com/path/to/media.mp3"
                                value="<?php echo esc_attr($EnclosureURL); ?>" onchange="powerpress_updateMediaInput(this); return false;" />
                        </div>
                        <div id="pp-change-media-file-<?php echo $FeedSlug; ?>" style="display: none;">
                            <div id="save-media-<?php echo $FeedSlug; ?>" class="pp-blue-button"
                                onclick="powerpress_saveMediaFile(this); return false;"><?php echo esc_html(__('VERIFY', 'powerpress')); ?></div>
                        </div>
                        <div id="select-media-file-<?php echo $FeedSlug; ?>" style="<?php echo $style1 ?>">
                            <div id="continue-to-episode-settings-<?php echo $FeedSlug; ?>" class="pp-blue-button"
                                onclick="powerpress_continueToEpisodeSettings(this); return false;"><?php echo esc_html(__('VERIFY', 'powerpress')); ?></div>
                        </div>
                    </div>
                    <div style="<?php echo $style3 ?>" title="<?php echo esc_attr($EnclosureURL); ?>"
                        id="powerpress_url_show_<?php echo $FeedSlug; ?>">
                        <div id="ep-box-filename-container-<?php echo $FeedSlug; ?>">
                            <p id="ep-box-filename-<?php echo $FeedSlug; ?>"><?php echo htmlspecialchars($filename); ?></p>
                        </div>
                        <img id="powerpress_success_<?php echo $FeedSlug; ?>"
                            src="/wp-content/plugins/powerpress/images/check.svg"
                            style="height: 24px; margin: 14px 1em 0 1em; vertical-align:top; display:none; float: right;" />
                        <img id="powerpress_fail_<?php echo $FeedSlug; ?>"
                            src="/wp-content/plugins/powerpress/images/redx.svg"
                            style="height: 24px; margin: 14px 1em 0 1em; vertical-align:top; display:none; float: right;" />
                        <img id="powerpress_check_<?php echo $FeedSlug; ?>"
                            src="<?php echo admin_url(); ?>images/loading.gif"
                            style="height: 24px; margin: 14px 1em 0 1em; vertical-align:top; display: none; float: right;"
                            alt="<?php echo esc_attr(__('Checking Media', 'powerpress')); ?>" />

                    </div>
                </div>
            </div>
            <div id="ep-box-blubrry-service-<?php echo $FeedSlug; ?>" style="<?php echo $style4; ?>">
                <?php if ($GeneralSettings['blubrry_hosting']) { ?>
                    <div id="ep-box-blubrry-connected-<?php echo $FeedSlug; ?>">
                        <img class="ep-box-blubrry-icon" src="<?php echo powerpress_get_root_url(); ?>images/blubrry_icon.png" alt="" />
                        <div class="ep-box-blubrry-info-container">
                            <h4 class="blubrry-connect-info"><?php echo __('Your Blubrry account is connected', 'powerpress'); ?></h4>
                            <p class="blubrry-connect-info"><?php echo __('Select or upload your media to your Blubrry hosting account.', 'powerpress'); ?></p>
                        </div>
                        <a id="pp-change-media-link-<?php echo $FeedSlug; ?>"
                            href="<?php echo admin_url(); ?>?action=powerpress-jquery-media&podcast-feed=<?php echo $FeedSlug; ?>&KeepThis=true&TB_iframe=true&modal=false"
                            class="thickbox">
                            <div id="change-media-button-<?php echo $FeedSlug; ?>"><?php echo esc_html(__('CHOOSE FILE', 'powerpress')); ?></div>
                        </a>
                    </div>
                <?php } else {
                    $pp_nonce = powerpress_login_create_nonce();
                ?>
                    <div id="ep-box-blubrry-connect-<?php echo $FeedSlug; ?>" style="<?php echo $style4; ?>">
                        <img class="ep-box-blubrry-icon" src="<?php echo powerpress_get_root_url(); ?>images/blubrry_icon.png" alt="" />
                        <div class="ep-box-blubrry-info-container">
                            <h4 class="blubrry-connect-info"><?php echo __('If you host with Blubrry', 'powerpress'); ?></h4>
                            <p class="blubrry-connect-info"><?php echo __('You can select a media file from your computer by connecting your hosting account.', 'powerpress'); ?></p>
                        </div>
                        <a class="button-blubrry" id="ep-box-connect-account-<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__('Blubrry Services Integration', 'powerpress')); ?>" href="<?php echo esc_attr(add_query_arg('_wpnonce', $pp_nonce, admin_url("admin.php?page=powerpressadmin_onboarding.php&step=blubrrySignin&from=new_post"))); ?>">
                            <div id="ep-box-connect-account-button-<?php echo $FeedSlug; ?>"><?php echo __('Connect to Blubrry', 'powerpress'); ?></div>
                        </a>
                    </div>
                    <div id="ep-box-min-blubrry-connect-<?php echo $FeedSlug; ?>" style="<?php echo $style2; ?>">
                        <div id="pp-connect-account-<?php echo $FeedSlug; ?>">
                            <a id="pp-connect-account-link-<?php echo $FeedSlug; ?>" class="pp-media-edit-details button-blubrry" title="<?php echo esc_attr(__("Blubrry Services Integration", "powerpress")); ?>" href="<?php echo esc_attr(add_query_arg('_wpnonce', $pp_nonce, admin_url("admin.php?page=powerpressadmin_onboarding.php&step=blubrrySignin&from=new_post"))); ?>">
                                <b><?php echo esc_html(__('Connect Blubrry Account', 'powerpress')); ?></b>
                            </a>
                        </div>
                        <div id="pp-cancel-container-<?php echo $FeedSlug; ?>">
                            <div id="pp-cancel-media-<?php echo $FeedSlug; ?>">
                                <button id="cancel-media-edit-<?php echo $FeedSlug; ?>" class="pp-media-edit-details"
                                    onclick="powerpress_cancelMediaEdit(this); return false;"><b><?php echo esc_html(__('CANCEL', 'powerpress')); ?></b></button>

                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div id="pp-warning-messages">
            <div id="file-select-warning-<?php echo $FeedSlug; ?>"
                style="background-color: white; box-shadow: none; margin-left: 0; padding-left: 3px; display:none; color: #dc3232;"><?php echo esc_html(__('You must have a media file selected to continue to episode settings.', 'powerpress')); ?></div>
            <div id="file-change-warning-<?php echo $FeedSlug; ?>"
                style="background-color: #f5f5f5; box-shadow: none; margin-left: 0; padding-left: 3px; display:none; color: #dc3232;"><?php echo esc_html(__('You must have a media file selected to save.', 'powerpress')); ?></div>
            <div id="powerpress_warning_<?php echo $FeedSlug; ?>"
                style="background-color: #f5f5f5; box-shadow: none; margin-left: 0; padding-left: 3px; display:none; color: #dc3232;"><?php echo esc_html(__('Error verifying media file.', 'powerpress')); ?></div>
            <input type="hidden" id="powerpress_hosting_<?php echo $FeedSlug; ?>"
                name="Powerpress[<?php echo $FeedSlug; ?>][hosting]"
                value="<?php echo (!empty($ExtraData['hosting']) ? '1' : '0'); ?>" />
            <input type="hidden" id="powerpress_has_media_<?php echo $FeedSlug; ?>"
                value="<?php echo !empty($EnclosureURL) ? '1' : '0'; ?>" />
            <input type="hidden" id="powerpress_skip_verification_<?php echo $FeedSlug; ?>"
                value="<?php echo !empty($GeneralSettings['skip_to_episode_settings']) ? '1' : '0'; ?>" />
            <div id="powerpress_hosting_note_<?php echo $FeedSlug; ?>"
                style="margin-left: 2px; padding-bottom: 2px; padding-top: 2px; display: <?php echo (!empty($ExtraData['hosting']) ? 'block' : 'none'); ?>">
                <em><?php echo esc_html(__('Media file hosted by blubrry.com.', 'powerpress')); ?>
                    (<a href="#" title="<?php echo esc_attr(__('Remove Blubrry.com hosted media file', 'powerpress')); ?>"
                        onclick="powerpress_remove_hosting('<?php echo $FeedSlug; ?>');return false;"><?php echo esc_html(__('remove', 'powerpress')); ?></a>)
                </em>
            </div>
            <input type="hidden" id="powerpress_program_keyword_<?php echo $FeedSlug; ?>"
                name="Powerpress[<?php echo $FeedSlug; ?>][program_keyword]"
                value="<?php echo !empty($ExtraData['program_keyword']) ? $ExtraData['program_keyword'] : ''; ?>" />
            <input type="hidden" id="powerpress_ep_id_<?php echo $FeedSlug; ?>"
                name="Powerpress[<?php echo $FeedSlug; ?>][podcast_id]"
                value="<?php echo !empty($ExtraData['podcast_id']) ? $ExtraData['podcast_id'] : ''; ?>" />

        </div>
        <div id="media-file-details-<?php echo $FeedSlug; ?>" style="<?php echo $style3; ?>">
            <div>
                <div id="edit-media-file-<?php echo $FeedSlug; ?>" style="<?php echo $style3 ?>">
                    <button id="pp-edit-media-button-<?php echo $FeedSlug; ?>" class="media-details"
                        onclick="powerpress_changeMediaFile(event, this); return false;"><?php echo esc_html(__('Edit Media File', 'powerpress')); ?></button>
                </div>
                <div id="show-hide-media-details-<?php echo $FeedSlug; ?>">
                    <!--<div class="ep-box-line-bold"></div>-->
                    <div id="media-details-container-<?php echo $FeedSlug; ?>">
                        <button id="show-details-link-<?php echo $FeedSlug; ?>" class="media-details" title="<?php echo esc_attr(__("Show file size and duration", "powerpress")); ?>"
                            onclick="powerpress_showHideMediaDetails(this); return false;"><?php echo esc_html(__('View File Size and Duration', 'powerpress')); ?> &#709;</button>
                        <!--<a id="hide-details-link-<?php //echo $FeedSlug; 
                                                        ?>" class="pp-hidden-settings"
                           onclick="showHideMediaDetails(this)"><?php //echo __('Hide File Size and Duration', 'powerpress'); 
                                                                ?>  &#708;</a>-->
                    </div>
                </div>
            </div>
            <?php
            $set_size = $GeneralSettings['set_size'];
            if (isset($ExtraData['set_size'])) {
                $set_size = $ExtraData['set_size'];
            }
            $set_duration = $GeneralSettings['set_duration'];
            if (isset($ExtraData['set_duration'])) {
                $set_duration = $ExtraData['set_duration'];
            }
            ?>
            <div id="hidden-media-details-<?php echo $FeedSlug; ?>" class="pp-hidden-settings" style="background-color: #d8dee9; border-radius: 10px;">
                <div class="powerpress_row">
                    <p class="media-details-head"><?php echo esc_html(__('File Size', 'powerpress')); ?></p>
                    <div class="pp-detail-section">
                        <div class="details-auto-detect">
                            <input class="media-details-radio" id="powerpress_set_size_0_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Auto detect file size", "powerpress")); ?>"
                                name="Powerpress[<?php echo $FeedSlug; ?>][set_size]" value="0"
                                type="radio" <?php echo ($set_size == 0 ? 'checked' : ''); ?> />
                            <?php echo esc_html(__('Auto detect file size', 'powerpress')); ?>
                        </div>
                        <div class="details-specify">
                            <input class="media-details-radio" id="powerpress_set_size_1_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Select specify file size", "powerpress")); ?>"
                                name="Powerpress[<?php echo $FeedSlug; ?>][set_size]" value="1"
                                type="radio" <?php echo ($set_size == 1 ? 'checked' : ''); ?> />
                            <?php echo esc_html(__('Specify', 'powerpress')) . ': '; ?>
                            <input class="pp-ep-box-input" type="text" id="powerpress_size_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("File size in bytes", "powerpress")); ?>"
                                name="Powerpress[<?php echo $FeedSlug; ?>][size]"
                                value="<?php echo esc_attr($EnclosureLength); ?>" style="width: 110px;height: auto;"
                                onchange="javascript:jQuery('#powerpress_set_size_1_<?php echo $FeedSlug; ?>').attr('checked', true);" />
                            <?php echo esc_html(__('in bytes', 'powerpress')); ?>
                        </div>
                    </div>
                </div>
                <div class="powerpress_row">
                    <p class="media-details-head" style="margin-bottom: 1ch;"><?php echo esc_html(__('Duration', 'powerpress')); ?></p>
                    <div class="pp-detail-section">
                        <div class="details-auto-detect">
                            <input class="media-details-radio" id="powerpress_set_duration_0_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Auto detect duration", "powerpress")); ?>"
                                name="Powerpress[<?php echo $FeedSlug; ?>][set_duration]" value="0"
                                type="radio" <?php echo ($set_duration == 0 ? 'checked' : ''); ?> />
                            <?php echo esc_html(__('Auto detect duration', 'powerpress')); ?>
                        </div>
                        <div class="details-specify">
                            <input class="media-details-radio" id="powerpress_set_duration_1_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Select specify duration", "powerpress")); ?>"
                                name="Powerpress[<?php echo $FeedSlug; ?>][set_duration]" value="1"
                                type="radio" <?php echo ($set_duration == 1 ? 'checked' : ''); ?> />
                            <?php echo esc_html(__('Specify', 'powerpress')) . ': '; ?>
                            <input type="text" class="pp-ep-box-input" id="powerpress_duration_hh_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Duration hours", "powerpress")); ?>"
                                placeholder="HH" name="Powerpress[<?php echo $FeedSlug; ?>][duration_hh]"
                                maxlength="2" value="<?php echo esc_attr($DurationHH); ?>"
                                onchange="javascript:jQuery('#powerpress_set_duration_1_<?php echo $FeedSlug; ?>').attr('checked', true);" /><strong
                                style="margin-left: 4px;">:</strong>
                            <input type="text" class="pp-ep-box-input" id="powerpress_duration_mm_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Duration minutes", "powerpress")); ?>"
                                placeholder="MM" name="Powerpress[<?php echo $FeedSlug; ?>][duration_mm]"
                                maxlength="2" value="<?php echo esc_attr($DurationMM); ?>"
                                onchange="javascript:jQuery('#powerpress_set_duration_1_<?php echo $FeedSlug; ?>').attr('checked', true);" /><strong
                                style="margin-left: 4px;">:</strong>
                            <input type="text" class="pp-ep-box-input" id="powerpress_duration_ss_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Duration seconds", "powerpress")); ?>"
                                placeholder="SS" name="Powerpress[<?php echo $FeedSlug; ?>][duration_ss]"
                                maxlength="10" value="<?php echo esc_attr($DurationSS); ?>"
                                onchange="javascript:jQuery('#powerpress_set_duration_1_<?php echo $FeedSlug; ?>').attr('checked', true);" />
                        </div>
                        <div class="details-not-specified">
                            <input class="media-details-radio" id="powerpress_set_duration_2_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Duration not specified", "powerpress")); ?>"
                                name="Powerpress[<?php echo $FeedSlug; ?>][set_duration]" value="-1"
                                type="radio" <?php echo ($set_duration == -1 ? 'checked' : ''); ?> />
                            <?php echo esc_html(__('Not specified', 'powerpress')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="transcript-box" style="margin-top: 25px; background-color: #f1f4f9; padding: 2ch;">
            <?php
            $transcript_box_style = '';
            if ($EnclosureURL) {
                $transcript_box_style = ' display: none;';
            ?>
                <p style="font-size: 14px; margin-top: 0; margin-bottom: 0;" class="pp-ep-box-text">
                    <input id="powerpress_transcript_edit" title="<?php echo esc_attr(__("Edit transcript", "powerpress")); ?>"
                        class="ep-box-checkbox"
                        name="Powerpress[<?php echo $FeedSlug; ?>][transcript][edit]" value="1"
                        type="checkbox"
                        onclick="showHideTranscriptBox('transcript', '<?php echo $FeedSlug; ?>');" />
                    <?php echo esc_html(__('Edit transcript', 'powerpress')); ?>
                    <?php if (!empty($PCITranscriptURL)) { ?>
                        - <a href="<?php echo htmlspecialchars($PCITranscriptURL); ?>" title="Transcript Link" target="_blank"><?php echo htmlspecialchars($PCITranscriptURL); ?></a>
                    <?php } ?>
                </p>
            <?php } else { ?>
                <input type="hidden" name="Powerpress[<?php echo $FeedSlug; ?>][transcript][edit]" value="1" />
            <?php } ?>
            <div id="transcript-box-options-<?php echo $FeedSlug; ?>" style="margin-top: 1.5em;<?php echo $transcript_box_style; ?>">

                <h3 style="margin-top: 0;">Transcription (optional)</h3>

                <?php
                $GeneralSettings = get_option('powerpress_general', array());
                if (!empty($GeneralSettings['blubrry_hosting'])) {
                    require_once(POWERPRESS_ABSPATH . '/powerpressadmin-auth.class.php');
                    $auth = new PowerPressAuth();

                    $accessToken = powerpress_getAccessToken();
                    $req_url = sprintf('/2/show/addons/?addon=transcript_plan&keyword=%s', urlencode($GeneralSettings['blubrry_program_keyword']));
                    $results = $auth->api($accessToken, $req_url);

                    $showAddonMessage = false;
                    if (!empty($results) && !isset($results['error'])) {
                        if ($results['transcript_plan'] === 'FREE') {
                            $showAddonMessage = true;
                        }
                    }

                    if ($showAddonMessage) { ?>
                        <div style="font-weight: bold; background-color: #FFFEF3; border-left: 4px solid #FFCA28; display: flex;">
                            <p style="font-size: 14px; margin: 8px;">
                                Free transcripts until July 31, 2024! Generate yours here.
                                <a target="_blank" href="https://blubrry.com/podcast-insider/2024/07/01/unlock-the-power-of-podcast-transcripts-with-blubrrys-one-month-free-trial/">Learn more</a>
                            </p>
                        </div>
                <?php }
                } ?>

                <p style="font-size: 14px;" class="pp-ep-box-text">
                    <input id="powerpress_transcript_none_<?php echo $FeedSlug ?>" title="<?php echo esc_attr(__("No transcript", "powerpress")); ?>"
                        class="media-details-radio"
                        name="Powerpress[<?php echo $FeedSlug; ?>][transcript][none]" value="1"
                        type="radio"
                        onclick="setTranscriptCheckboxes(this.id, '<?php echo $FeedSlug; ?>');" <?php echo empty($PCITranscriptURL) ? 'checked' : ''; ?> />
                    <?php echo esc_html(__('No transcript', 'powerpress')); ?>
                </p>

                <hr style="margin: 1em 0 1em 0;">

                <?php if (!empty($GeneralSettings['blubrry_hosting'])) { ?>
                    <p style="font-size: 14px; display: inline;" class="pp-ep-box-text">
                        <input id="powerpress_transcript_generate_<?php echo $FeedSlug ?>" title="<?php echo esc_attr(__("Generate transcript for me", "powerpress")); ?>"
                            class="media-details-radio"
                            name="Powerpress[<?php echo $FeedSlug; ?>][transcript][generate]" value="1"
                            type="radio"
                            onclick="setTranscriptCheckboxes(this.id, '<?php echo $FeedSlug; ?>');" />
                        <?php echo esc_html(__('Generate transcript for me', 'powerpress')); ?>
                    </p>

                    <div style="margin-left: 30px; margin-top: 5px; display: <?php echo ($GeneralSettings['blubrry_hosting'] ? 'inline-flex' : 'none'); ?>; background-color: #FFFEF3; border-left: 4px solid #FFCA28;">
                        <p style="font-size: 14px; margin: 8px;">Transcripts are used for displaying closed captions in the new Blubrry podcast player, as well as in podcast apps that support transcripts.</p>
                    </div>
                    <div class="powerpress_row" id="powerpress_generate_transcript_container_<?php echo $FeedSlug; ?>" style="display: none">

                        <select id="pp-generate-language-<?php echo $FeedSlug; ?>" class="pp-ep-box-input" name="Powerpress[<?php echo $FeedSlug; ?>][pci_transcript_language]">
                            <?php
                            $Languages = powerpress_revai_languages();

                            echo '<option value="">' . __('Select Language', 'powerpress') . '</option>';
                            foreach ($Languages as $value => $desc)
                                echo "\t<option value=\"$value\"" . (substr($language, 0, 2) == $value ? ' selected' : '') . ">" . esc_attr($desc) . "</option>\n";
                            ?>
                        </select>
                    </div>

                    <div style="margin-left: 5px; display: <?php echo (!$GeneralSettings['blubrry_hosting'] ? 'inline-flex' : 'none'); ?>; background-color: #FFFEF3; border-left: 4px solid #FFCA28;">
                        <p style="font-size: 14px; margin: 8px;">
                            Generated transcripts are only available for Blubrry Hosting customers.
                            Learn more about hosting with Blubrry <a href="">here</a>.
                        </p>
                    </div>

                    <hr style="margin: 1em 0 1em 0;">
                <?php } ?>

                <p style="font-size: 14px; display: inline-block;" class="pp-ep-box-text">
                    <input id="powerpress_pci_transcript_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Add a transcript", "powerpress")); ?>"
                        class="media-details-radio" onclick="setTranscriptCheckboxes(this.id, '<?php echo $FeedSlug; ?>');"
                        name="Powerpress[<?php echo $FeedSlug; ?>][transcript][upload]" value="1"
                        type="radio" <?php echo !empty($PCITranscriptURL) ? 'checked' : ''; ?> />
                    <?php echo esc_html(__('Add a transcript', 'powerpress')); ?>
                </p>
                <div class="pp-tooltip-right" style="margin: 1ch 0 0 1ch;">i
                    <span class="text-pp-tooltip" style="top: -50%; min-width: 200px;"><?php echo esc_html(__('Supported transcript types include .srt, .vtt, .json, and .html. An SRT or VTT transcript is required for generating closed captions in apps that support it.', 'powerpress')); ?></span>
                </div>

                <div class="powerpress_row" id="powerpress_pci_transcript_container_<?php echo $FeedSlug; ?>"
                    <?php if (empty($PCITranscriptURL)) {
                        echo "style=\"display: none;\"";
                    } ?>>

                    <input type="text" id="powerpress_transcript_url_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("URL to transcript file", "powerpress")); ?>"
                        class="pp-ep-box-input"
                        name="Powerpress[<?php echo $FeedSlug; ?>][pci_transcript_url]"
                        value="<?php echo esc_attr($PCITranscriptURL); ?>"
                        placeholder="<?php echo 'https://' . $_SERVER['SERVER_NAME'] . '/wp-content/uploads/' . date('Y') . '/' . date('m') . '/' . 'transcript.vtt'; ?>"
                        <?php echo !empty($PCITranscriptURL) ? 'checked' : ''; ?> />
                    <!--                    <label class="pp-ep-box-label-under">-->
                    <?php
                    //echo esc_html(__("Can be added later by editing this post", 'powerpress')); 
                    ?>
                    <!--</label>-->


                    <select id="pp-upload-language-<?php echo $FeedSlug; ?>" class="pp-ep-box-input" name="Powerpress[<?php echo $FeedSlug; ?>][pci_transcript_language]">
                        <?php
                        $Languages = powerpress_languages();

                        echo '<option value="">' . __('Select Language', 'powerpress') . '</option>';
                        foreach ($Languages as $value => $desc)
                            echo "\t<option value=\"$value\"" . ($language == $value ? ' selected' : '') . ">" . esc_attr($desc) . "</option>\n";
                        ?>
                    </select>
                </div>
            </div>

        </div>

        <?php
        if (!empty($GeneralSettings['cat_casting_strict']) && !empty($GeneralSettings['custom_cat_feeds'])) {
            // Get Podcast Categories...
            $cur_cat_id = intval(!empty($ExtraData['category']) ? $ExtraData['category'] : 0);
            if (count($GeneralSettings['custom_cat_feeds']) == 1) // Lets auto select the category
            {
                foreach ($GeneralSettings['custom_cat_feeds'] as $null => $cur_cat_id) {
                    break;
                }
                reset($GeneralSettings['custom_cat_feeds']);
            }

        ?>
            <div id="pp-category-dropdown-<?php echo $FeedSlug; ?>">
                <label for="Powerpress[<?php echo $FeedSlug; ?>][category]"><?php echo esc_html(__('Category', 'powerpress')); ?></label>
                <div class="powerpress_row_content"><?php
                                                    echo '<select id="powerpress_category_' . $FeedSlug . '" name="Powerpress[' . $FeedSlug . '][category]" class="pp-ep-box-input" title="Category">';
                                                    echo '<option value="0"';
                                                    echo '>' . esc_html(__('Select category', 'powerpress')) . '</option>' . "\n";

                                                    foreach ($GeneralSettings['custom_cat_feeds'] as $null => $cat_id) {
                                                        $catObj = get_category($cat_id);
                                                        if (empty($catObj->name))
                                                            continue; // Do not allow empty categories forward

                                                        $label = $catObj->name;
                                                        echo '<option value="' . esc_attr($cat_id) . '"';

                                                        // never pre-select a category for a new post
                                                        global $pagenow;
                                                        if ($cat_id == $cur_cat_id && strpos($pagenow, 'post-new.php') === false)
                                                            echo ' selected="selected"';
                                                        echo '>' . esc_html($label) . '</option>' . "\n";
                                                    }
                                                    echo '</select>';
                                                    ?>
                </div>
            </div>
        <?php
        }
        ?>
        <?php if ($EnclosureURL) { ?>
            <div class="ep-box-line"></div>
        <?php } ?>
    </div>
    <?php if ($EnclosureURL) { ?>
        <div class="powerpress_remove_container">
            <div class="powerpress_row_content">
                <input type="checkbox" class='ep-box-checkbox' name="Powerpress[<?php echo $FeedSlug; ?>][remove_podcast]" id="powerpress_remove_<?php echo $FeedSlug; ?>" value="1" onchange="javascript:document.getElementById('a-pp-selected-media-<?php echo $FeedSlug; ?>').style.display=(this.checked?'none':'block');javascript:document.getElementById('tab-container-<?php echo $FeedSlug; ?>').style.display=(this.checked?'none':'block');" />
                <b><?php echo esc_html(__('Remove Episode', 'powerpress')); ?></b><?php echo esc_html(__(' - Podcast episode will be removed from this post upon save', 'powerpress')); ?>
            </div>
        </div>
    <?php }
}

function seo_tab($FeedSlug, $ExtraData, $iTunesExplicit, $seo_feed_title, $GeneralSettings, $iTunesAuthor, $iTunesBlock, $object)
{
    ?>
    <!-- Tab content -->
    <div id="seo-<?php echo $FeedSlug; ?>" class="pp-tabcontent active">
        <?php //Apple Podcasts Settings
        // //Apple Podcasts Title
        if (empty($ExtraData)) {
            $ExtraData = array();
        }
        if (empty($ExtraData['episode_title'])) {
            $ExtraData['episode_title'] = '';
        }
        if (empty($ExtraData['episode_no'])) {
            $ExtraData['episode_no'] = '';
        }
        if (empty($ExtraData['season'])) {
            $ExtraData['season'] = '';
        }
        if (empty($ExtraData['episode_type'])) {
            $ExtraData['episode_type'] = '';
        }
        if (empty($ExtraData['feed_title'])) {
            $ExtraData['feed_title'] = '';
        }
        if (empty($ExtraData['episode_no_display'])) {
            $ExtraData['episode_no_display'] = '';
        }
        $AppleOpt = true;
        $AppleExtra = true;
        if (isset($GeneralSettings['new_episode_box_subtitle']) && isset($GeneralSettings['new_episode_box_summary']) && isset($GeneralSettings['new_episode_box_author']) && isset($GeneralSettings['new_episode_box_explicit']) && isset($GeneralSettings['new_episode_box_order']) && isset($GeneralSettings['new_episode_box_itunes_title']) && isset($GeneralSettings['new_episode_box_itunes_nst']) && isset($GeneralSettings['new_episode_box_feature_in_itunes']) && isset($GeneralSettings['new_episode_box_block'])) {
            if ($GeneralSettings['new_episode_box_subtitle'] == 2 && $GeneralSettings['new_episode_box_summary'] == 2 && $GeneralSettings['new_episode_box_author'] == 2 && $GeneralSettings['new_episode_box_explicit'] == 2 && $GeneralSettings['new_episode_box_order'] == 2 && $GeneralSettings['new_episode_box_itunes_title'] == 2 && $GeneralSettings['new_episode_box_itunes_nst'] == 2 && $GeneralSettings['new_episode_box_feature_in_itunes'] == 2 && $GeneralSettings['new_episode_box_block'] == 2) {
                $AppleOpt = false;
                $AppleExtra = false;
            } else {
                if ($GeneralSettings['new_episode_box_subtitle'] == 2 && $GeneralSettings['new_episode_box_summary'] == 2 && $GeneralSettings['new_episode_box_author'] == 2 && $GeneralSettings['new_episode_box_order'] == 2 && $GeneralSettings['new_episode_box_feature_in_itunes'] == 2 && $GeneralSettings['new_episode_box_block'] == 2) {
                    $AppleExtra = false;
                }
            }
        }

        // episode title
        ?>

        <!-- General Episode Information -->
        <div class="d-flex flex-row justify-content-evenly align-items-start">
            <div class="col-lg-12">
                <!-- Episode Title Information -->
                <div class="pp-section-container">
                    <!-- Open Title -->
                    <h4 class="pp-section-title"><?php echo esc_html(__("Episode Title", 'powerpress')); ?></h4>
                    <div class="pp-tooltip-right" style="margin: 1ch 0 0 1ch;">i
                        <span class="text-pp-tooltip" style="top: -50%; min-width: 200px;"><?php echo esc_html(__('Please enter your episode title in the space for post title, above the post description.', 'powerpress')); ?></span>
                    </div>
                    <?php if ($seo_feed_title) { ?>
                        <div class="powerpress_row">
                            <div class="powerpress_row_content">
                                <input type="text" id="powerpress_feed_title_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Episode title", "powerpress")); ?>"
                                    class="pp-ep-box-input"
                                    name="Powerpress[<?php echo $FeedSlug; ?>][feed_title]"
                                    value="<?php echo esc_attr($ExtraData['feed_title']); ?>"
                                    placeholder="<?php echo esc_attr(__('Custom episode title', 'powerpress')); ?>"
                                    style="width: 96%; margin-top: 1em;" />
                            </div>
                            <label class="pp-ep-box-label-under"><?php echo esc_html(__("Leave blank to use WordPress post title at the top of this page.", 'powerpress')); ?></label>
                        </div>
                    <?php } else { ?>
                        <p class="pp-ep-box-text"><?php echo esc_html(__("The episode title is pulled from your WordPress post title at the top of this page.", 'powerpress')); ?></p>
                    <?php } ?>
                </div> <!-- Close Title -->

                <div class="pp-section-container">
                    <!-- Open Description-->
                    <h4 class="pp-section-title"><?php echo esc_html(__("Episode Description", 'powerpress')); ?></h4>
                    <div class="pp-tooltip-right" style="margin: 1ch 0 0 1ch;">i
                        <span class="text-pp-tooltip"><?php echo esc_html(__('Please enter your description in the space above the episode box, underneath the post title.', 'powerpress')); ?></span>
                    </div>
                    <p class="pp-ep-box-text"><?php echo esc_html(__("The episode description is pulled from your WordPress post content, which can be edited above.", 'powerpress')); ?></p>
                </div> <!-- Close Description -->

                <div class="pp-section-container">
                    <!-- Open Episode number Display-->
                    <div class="row" style="margin: auto">
                        <h4 class="pp-section-title"><?php echo esc_html(__("Episode Number Display", 'powerpress')); ?></h4>
                        <div class="pp-tooltip-right" style="margin: 1ch 0 0 1ch;">i
                            <span class="text-pp-tooltip"><?php echo esc_html(__('How this episode number should be displayed in podcast apps. max characters: 32  e.g. S5E1, Season 5 Episode 1, Day 13', 'powerpress')); ?></span>
                        </div>
                    </div>
                    <!-- Episode Number Display Input-->
                    <div class="row" style="margin: auto">
                        <input class="pp-ep-box-input col-4" type="text" title="<?php echo esc_attr(__("Episode Number Display", "powerpress")); ?>"
                            id="powerpress_no_display_<?php echo $FeedSlug; ?>"
                            name="Powerpress[<?php echo $FeedSlug; ?>][episode_no_display]"
                            value="<?php echo esc_attr($ExtraData['episode_no_display']); ?>"
                            placeholder="e.g S5E12 , Day 5 , Episode 257"
                            maxlength="32" />
                    </div>
                </div> <!-- Close Episode number Display -->
            </div>


        </div> <!-- Section Container-->

        <!-- APPLE PODCAST OPTIMIZATION -->
        <?php if ($AppleOpt) { ?>
            <div id="apple-podcast-opt-<?php echo $FeedSlug; ?>" class="pp-section-container" style="background: #e3f2fd; border-left: 4px solid #1976d2; border-radius: 10px;">
                <div class="pp-section-container">
                    <h4 class="pp-section-title"><?php echo esc_html(__("Podcast Optimization (Optional)", 'powerpress')); ?></h4>
                    <div class="pp-tooltip-right">i
                        <span class="text-pp-tooltip"><?php echo esc_html(__('Fill this section out thoroughly to optimize the exposure that your podcast gets on Apple.', 'powerpress')); ?></span>
                    </div>

                    <?php
                    // FIELD VISIBILITY CONFIGURATION
                    $show_explicit = !isset($GeneralSettings['new_episode_box_explicit']) || $GeneralSettings['new_episode_box_explicit'] == 1;
                    ?>

                    <?php if ($show_explicit) { ?>
                        <div id="pp-explicit-container-<?php echo $FeedSlug; ?>">
                            <input type="number" style="display: none" id="powerpress_explicit_<?php echo $FeedSlug; ?>" name="Powerpress[<?php echo $FeedSlug; ?>][explicit]" value="<?php echo $iTunesExplicit ? $iTunesExplicit : 0; ?>">
                            <label class="pp-ep-box-label" style="padding-bottom: 10px;" for="explicit-switch-base-<?php echo $FeedSlug; ?>"><?php echo esc_html(__("Explicit Setting", "powerpress")); ?></label>
                            <div id="explicit-switch-base-<?php echo $FeedSlug; ?>">
                                <div id="clean-<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Clean content", "powerpress")); ?>" onclick="powerpress_changeExplicitSwitch(this)" 
                                    <?php echo $iTunesExplicit == 2 ? '' : ' style="border-right: 1px solid #b3b3b3;"' ?> class="<?php echo $iTunesExplicit == 2 || $iTunesExplicit == 0 ? 'explicit-selected' : 'pp-explicit-option' ?>">
                                        <?php echo esc_html(__('CLEAN', 'powerpress')); ?></div>
                                <div id="explicit-<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Explicit content", "powerpress")); ?>" onclick="powerpress_changeExplicitSwitch(this)" 
                                    <?php echo $iTunesExplicit == 2 ? ' style="border-left: 1px solid #b3b3b3;"' : '' ?> class="<?php echo $iTunesExplicit == 1 ? 'explicit-selected' : 'pp-explicit-option' ?>">
                                        <?php echo esc_html(__('EXPLICIT', 'powerpress')); ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <br>
                <div class="pp-section-container">
                    <?php
                    // FIELD VISIBILITY CONFIGURATION
                    $show_title = !isset($GeneralSettings['new_episode_box_itunes_title']) || $GeneralSettings['new_episode_box_itunes_title'] == 1;
                    $show_episode = !isset($GeneralSettings['new_episode_box_itunes_nst']) || $GeneralSettings['new_episode_box_itunes_nst'] == 1;
                    ?>

                    <!-- TITLE, EPISODE & SEASON ROW -->
                    <div class="row mt-2">
                        <?php if ($show_title) { ?>
                            <div class="col-12 col-md-6">
                                <label class="pp-ep-box-label-apple" for="powerpress_episode_apple_title_<?php echo $FeedSlug; ?>"><?php echo esc_html(__('Title', 'powerpress')); ?></label>
                                <input class="pp-ep-box-input" type="text" title="<?php echo esc_attr(__("Apple Podcasts episode title", "powerpress")); ?>" id="powerpress_episode_apple_title_<?php echo $FeedSlug; ?>" name="Powerpress[<?php echo $FeedSlug; ?>][episode_title]" value="<?php echo esc_attr($ExtraData['episode_title']); ?>" maxlength="255" />
                            </div>
                        <?php } ?>

                        <?php if ($show_episode) { ?>
                            <div class="col-6 col-md-3">
                                <label class="pp-ep-box-label-apple" for="powerpress_episode_episode_no_<?php echo $FeedSlug; ?>"><?php echo esc_html(__('Episode #', 'powerpress')); ?></label>
                                <input class="pp-ep-box-input" type="number" title="<?php echo esc_attr(__("Apple Podcasts episode number", "powerpress")); ?>" id="powerpress_episode_episode_no_<?php echo $FeedSlug; ?>" name="Powerpress[<?php echo $FeedSlug; ?>][episode_no]" value="<?php echo esc_attr($ExtraData['episode_no']); ?>" />
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="pp-ep-box-label-apple" for="powerpress_episode_season_<?php echo $FeedSlug; ?>"><?php echo esc_html(__('Season #', 'powerpress')); ?></label>
                                <input class="pp-ep-box-input" type="number" oninput="powerpress_setCurrentSeason(this)" id="powerpress_episode_season_<?php echo $FeedSlug; ?>" name="Powerpress[<?php echo $FeedSlug; ?>][season]" title="<?php echo esc_attr(__("Apple Podcasts season number", "powerpress")); ?>" style="width: 100%;" <?php if (isset($ExtraData['season']) && $ExtraData['season']) {
                                    echo " value=\"" . esc_attr($ExtraData['season']) . "\"";
                                } ?> />
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <script>
                    var show_settings = "<?php echo esc_js(__("See More Settings &#709;", "powerpress")); ?>";
                    var hide_settings = "<?php echo esc_js(__("Hide Settings &#708;", "powerpress")); ?>";
                </script>

                <?php if ($AppleExtra) { ?>
                    <div id="show-hide-apple-<?php echo $FeedSlug; ?>">
                        <div id="apple-advanced-container-<?php echo $FeedSlug; ?>">
                            <button id="show-apple-link-<?php echo $FeedSlug; ?>" class="apple-advanced" aria-pressed="false" title="<?php echo esc_attr(__("More settings button", "powerpress")); ?>"
                                onclick="powerpress_showHideAppleAdvanced(this); return false;"><?php echo esc_html(__('See More Settings &#709;', 'powerpress')); ?></button>
                        </div>
                    </div>
                <?php } ?>
                <div id="apple-advanced-settings-<?php echo $FeedSlug; ?>" class="pp-hidden-settings">
                    <div class="apple-opt-section-container">
                        <?php
                        // FIELD VISIBILITY CONFIGURATION
                        $show_author = !isset($GeneralSettings['new_episode_box_author']) || $GeneralSettings['new_episode_box_author'] == 1;
                        $show_type = !isset($GeneralSettings['new_episode_box_itunes_nst']) || $GeneralSettings['new_episode_box_itunes_nst'] == 1;
                        $show_block = !isset($GeneralSettings['new_episode_box_block']) || $GeneralSettings['new_episode_box_block'] == 1;

                        if ($show_type) {
                            // select options for episode type select
                            $type_array = [
                                '' => esc_html(__('Full (default)', 'powerpress')),
                                'full' => esc_html(__('Full Episode', 'powerpress')),
                                'trailer' => esc_html(__('Trailer', 'powerpress')),
                                'bonus' => esc_html(__('Bonus', 'powerpress'))
                            ];
                        }

                        if ($show_block) {
                            // select options for block select
                            $block_array = [
                                '' => esc_html(__('No', 'powerpress')),
                                1 => esc_html(__('Yes, Block episode from Apple Podcasts', 'powerpress'))
                            ];
                        }
                        ?>

                        <!-- AUTHOR, EPISODE TYPE, & BLOCK ROW -->
                        <div class="row">
                            <?php if ($show_author) { ?>
                                <div class="col-12 col-md-6">
                                    <label class="pp-ep-box-label" for="Powerpress[<?php echo $FeedSlug; ?>][author]"><?php echo esc_html(__('Author', 'powerpress')); ?></label>
                                    <input class="pp-ep-box-input" type="text" id="powerpress_author_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Apple Podcasts episode author", "powerpress")); ?>" name="Powerpress[<?php echo $FeedSlug; ?>][author]" value="<?php echo esc_attr($iTunesAuthor); ?>" />
                                    <label class="pp-ep-box-label-under"><?php echo esc_html(__('Leave blank to use default.', 'powerpress')); ?></label>
                                </div>
                            <?php } ?>

                            <?php if ($show_type) { ?>
                                <div class="col-6 col-md-3">
                                    <label class="pp-ep-box-label-apple" for="powerpress_episode_type_<?php echo $FeedSlug; ?>"><?php echo esc_html(__('Type', 'powerpress')); ?></label>
                                    <select style="font-size: 14px;" class="pp-ep-box-input" id="powerpress_episode_type_<?php echo $FeedSlug; ?>" name="Powerpress[<?php echo $FeedSlug; ?>][episode_type]" title="<?php echo esc_attr(__("Apple Podcasts episode type", "powerpress")); ?>">
                                        <?php
                                        foreach ($type_array as $value => $desc) {
                                            $selected = ($ExtraData['episode_type'] == $value) ? 'selected' : '';
                                            echo "\t<option value=\"$value\" $selected>$desc</option>\n";
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php } ?>

                            <?php if ($show_block) { ?>
                                <div class="col-6 col-md-3">
                                    <label class="pp-ep-box-label" for="Powerpress[<?php echo $FeedSlug; ?>][block]"><?php echo esc_html(__('Block', 'powerpress')); ?></label>
                                    <select class="pp-ep-box-input" id="powerpress_block_<?php echo $FeedSlug; ?>" name="Powerpress[<?php echo $FeedSlug; ?>][block]" title="<?php echo esc_attr(__("Apple Podcasts block episode", "powerpress")); ?>">
                                        <?php
                                        foreach ($block_array as $value => $desc) {
                                            $selected = ($iTunesBlock == $value) ? 'selected' : '';
                                            echo "\t<option value=\"$value\" $selected>$desc</option>\n";
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        </div>
    <?php
}

function artwork_tab($FeedSlug, $ExtraData, $object, $CoverImage, $GeneralSettings)
{
    ?>

        <div id="artwork-<?php echo $FeedSlug; ?>" class="pp-tabcontent">
            <?php
            $form_action_url = admin_url("media-upload.php?type=powerpress_image&tab=type&post_id={$object->ID}&powerpress_feed={$FeedSlug}&TB_iframe=true&width=450&height=200");

            if (empty($ExtraData)) {
                $ExtraData = array();
            }
            //Setting for itunes artwork
            if (!isset($ExtraData['itunes_image']) || !$ExtraData['itunes_image']) {
                $itunes_image = '';
                $itunes_image_preview = powerpress_get_root_url() . 'images/pts_cover.jpg';
            } else {
                $itunes_image = $ExtraData['itunes_image'];
                $itunes_image_preview = $itunes_image;
            }
            if (!$CoverImage) {
                $CoverImage_preview = powerpress_get_root_url() . 'images/pts_cover.jpg';
            } else {
                $CoverImage_preview = $CoverImage;
            }
            if (isset($GeneralSettings['new_episode_box_itunes_image']) && $GeneralSettings['new_episode_box_itunes_image'] == 2 && isset($GeneralSettings['new_episode_box_cover_image']) && $GeneralSettings['new_episode_box_cover_image'] == 2) {
                echo "<p class='pp-ep-box-text'>" . __('No artwork settings enabled', 'powerpress') . "</p></div>";
                return;
            }
            if (!isset($GeneralSettings['new_episode_box_itunes_image']) || $GeneralSettings['new_episode_box_itunes_image'] == 1) { ?>
                <div class="pp-section-container">
                    <div class="powerpress-art-text">
                        <h4 class="pp-section-title"
                            style="display: inline-block; margin-bottom: 1em;"><?php echo esc_html(__('Apple Podcast Episode Artwork', 'powerpress')); ?></h4>
                        <div class="pp-tooltip-right">i
                            <span class="text-pp-tooltip"
                                style="top: -150%;"><?php echo esc_html(__('Episode artwork should be square and have dimensions between 1400 x 1400 pixels and 3000 x 3000 pixels.', 'powerpress')); ?></span>
                        </div>
                        <br />
                        <input type="text" class="pp-ep-box-input" title="<?php echo esc_attr(__("Apple Image URL", "powerpress")); ?>"
                            id="powerpress_itunes_image_<?php echo $FeedSlug; ?>"
                            placeholder="<?php echo htmlspecialchars(__('e.g. http://example.com/path/to/image.jpg', 'powerpress')); ?>"
                            name="Powerpress[<?php echo $FeedSlug; ?>][itunes_image]"
                            value="<?php echo esc_attr($itunes_image); ?>"
                            style="font-size: 90%;" size="250" oninput="powerpress_insertArtIntoPreview(this)" />
                        <br />
                        <br />
                        <a href="<?php echo $form_action_url; ?>" class="thickbox powerpress-itunes-image-browser"
                            id="powerpress_itunes_image_browser_<?php echo $FeedSlug; ?>"
                            title="<?php echo esc_attr(__('Select Apple Image', 'powerpress')); ?>">
                            <button class="pp-gray-button"><?php echo esc_html(__('UPLOAD EPISODE ARTWORK', 'powerpress')); ?></button>
                        </a>
                    </div>
                    <div class="powerpress-art-preview">
                        <p class="pp-section-subtitle" style="font-weight: bold;"><?php echo esc_html(__('PREVIEW', 'powerpress')); ?></p>
                        <img id="pp-image-preview-<?php echo $FeedSlug; ?>"
                            src="<?php echo esc_attr($itunes_image_preview); ?>" alt="No artwork selected" />
                        <p id="pp-image-preview-caption-<?php echo $FeedSlug; ?>" class="pp-section-subtitle"
                            style="font-weight: bold;margin: 3px;">
                            <?php if ($itunes_image) {
                                echo get_filename_from_path(esc_attr($itunes_image));
                            } ?></p>
                    </div>
                </div>
                <div class="ep-box-line-margin"></div>
            <?php }
            if (isset($GeneralSettings['new_episode_box_cover_image']) && $GeneralSettings['new_episode_box_cover_image'] == 1) { ?>
                <div id="powerpress_thumbnail_container_<?php echo $FeedSlug; ?>" class="pp-section-container">
                    <div class="powerpress-art-text">
                        <h4 class="pp-section-title"><?php echo esc_html(__('Thumbnail Image', 'powerpress')); ?></h4>
                        <div class="pp-tooltip-right">i
                            <span class="text-pp-tooltip"><?php echo esc_html(__('This artwork only shows up if your podcast media is a video file.', 'powerpress')); ?></span>
                        </div>
                        <br /> <br />
                        <input type="text" class="pp-ep-box-input" id="powerpress_image_<?php echo $FeedSlug; ?>"
                            name="Powerpress[<?php echo $FeedSlug; ?>][image]" title="<?php echo esc_attr(__("Poster image URL", "powerpress")); ?>"
                            value="<?php echo esc_attr($CoverImage); ?>"
                            placeholder="<?php echo htmlspecialchars(__('e.g. http://example.com/path/to/image.jpg', 'powerpress')); ?>"
                            style="font-size: 90%;" size="250" oninput="powerpress_insertArtIntoPreview(this)" />
                        <br />
                        <label class="ep-box-caption"
                            for="powerpress_image_<?php echo $FeedSlug; ?>"><?php echo esc_html(__('Poster image for video (m4v, mp4, ogv, webm, etc..)', 'powerpress')); ?></label>
                        <br />
                        <a href="<?php echo $form_action_url; ?>" class="thickbox powerpress-image-browser"
                            id="powerpress_image_browser_<?php echo $FeedSlug; ?>"
                            title="<?php echo esc_attr(__('Select Poster Image', 'powerpress')); ?>">
                            <button class="pp-gray-button"><?php echo esc_html(__('UPLOAD THUMBNAIL', 'powerpress')); ?></button>
                        </a>
                    </div>
                    <div class="powerpress-art-preview">
                        <p class="pp-section-subtitle"
                            style="font-weight: bold;"><?php echo esc_html(__('PREVIEW', 'powerpress')); ?></p>
                        <img id="poster-pp-image-preview-<?php echo $FeedSlug; ?>"
                            src="<?php echo esc_attr($CoverImage_preview); ?>" alt="No thumbnail selected" />
                        <p id="poster-pp-image-preview-caption-<?php echo $FeedSlug; ?>" class="pp-section-subtitle"
                            style="font-weight: bold;margin: 3px;">
                            <?php if ($CoverImage) {
                                echo get_filename_from_path(esc_attr($CoverImage));
                            } ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>

    <?php
}

function print_table_body($chapter, $FeedSlug, $object, $row_num)
{
    $html = '';

    // determine styles / colors
    $card = 'card-c chapter-row card-left-border-c';
    $seconds = $chapter['startTime'];
    $startTime = sprintf('%02d:%02d:%02d', ($seconds / 3600), ($seconds / 60 % 60), $seconds % 60);

    $chapter['url'] = $chapter['url'] ?? '';
    $chapter['img'] = $chapter['img'] ?? '';

    $uploadDisp = $chapter['img'] == '' ? 'block' : 'none !important';
    $imDisp = $chapter['img'] == '' ? 'none !important' : 'block';

    // add episode data for each row
    $html .= "<div class='{$card} mb-0' style='width: 100%;border-left-color: #1976D2; border-bottom: 1px solid #eee; box-shadow: none;'>";
    $html .= "<div class='row card-body'>";
    $html .= "<div class='col d-flex align-items-center' style='width: 100px;' id='<?php echo $FeedSlug?>-time-col'><div class='time-container'><input required style='width: 100%;' pattern='([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1})' name='" . $FeedSlug . "-starts[]' class='time-input' type='text' value='{$startTime}' /></div></div>";
    $html .= "<div class='col-sm-3 align-self-center'><input style='width: 100%; height: 50.6px;' required name='" . $FeedSlug . "-titles[]' class='form-control b-border' type='text' value='" . esc_attr($chapter['title']) . "' placeholder='Chapter Title' /></div>";
    $html .= "<div class='col-sm-3 align-self-center'><input style='width: 100%; height: 50.6px;' name='" . $FeedSlug . "-urls[]' class='form-control b-border' type='text' value='" . esc_attr($chapter['url']) . "' placeholder='URL' /></div>";
    $html .= "<div class='col-sm-2 align-self-center' style='margin-top: -1.25rem; margin-bottom: -1.25rem;'>";

    $html .= "<div id='upload-im-container'>";
    $html .= "            <div id='pp-chapters-art-text-$FeedSlug-row-$row_num' style='display: $uploadDisp;'>";
    $html .= "                <input type='hidden' class='pp-ep-box-input' title='" . esc_attr(__("Chapter Image URL", "powerpress")) . "'";
    $html .= "                       id='powerpress_chapters_image_$FeedSlug-row-$row_num'";
    $html .= "                       placeholder='" . htmlspecialchars(__('e.g. http://example.com/path/to/image.jpg', 'powerpress')) . "'";
    $html .= "                       name='" . $FeedSlug . "-images[]'";
    $html .= "                       value=''";
    $html .= "                       style='font-size: 90%;' size='250' onchange='powerpress_chapters_art_preview(this)'/>";
    $html .= "                <a href='' class='powerpress-chapters-image-browser'";
    $html .= "                   id='powerpress_chapters_image_browser_$FeedSlug-row-$row_num' onclick='event.preventDefault();browseChapterImages(event);'";
    $html .= "                   title='" . esc_attr(__('Select Chapter Image', 'powerpress')) . "'>";
    $html .= "                    " . esc_html(__('UPLOAD', 'powerpress')) . "";
    $html .= "                </a>";
    $html .= "            </div>";
    $html .= "            <div id='preview-im-container-$FeedSlug-row-$row_num' style='display: $imDisp'>";
    $html .= "                <img id='pp-image-chapters-preview-$FeedSlug-row-$row_num' style='max-width: 100%; height: 55px; width: 55px; display: block;' ";
    $html .= "                     src='" . esc_attr($chapter['img']) . "' alt='No artwork selected'/>";
    $html .= "                <a id='remove-im-$FeedSlug-row-$row_num' class='ml-2' style='color: #1976D2; cursor: pointer;' onclick='removeIm(this);'>Remove</a>";
    $html .= "            </div>";
    $html .= "</div>";
    $html .= "<input type='hidden' name='" . $FeedSlug . "-existingIms[]' value='" . $chapter['img'] . "' />";
    $html .= "<input id='remove-existing-$FeedSlug-row-$row_num' type='hidden' name='" . $FeedSlug . "-removeExisting[]' value='0' />";
    $html .= "</div>";
    $html .= "<div class='col-sm-1 d-flex align-items-center'><div class='row d-flex justify-content-end align-items-center'>";
    $html .= "<a class='" . $FeedSlug . "-remove-chapter' style='cursor: pointer;'><img src='/wp-content/plugins/powerpress/images/trash.svg' alt='" . esc_attr(__('Delete Icon', 'powerpress')) . "'></a>";
    $html .= "</div></div>";
    // close icon and card divs
    $html .= "</div></div>";
    return $html;
}

function print_table_body_empty($FeedSlug, $object)
{
    $html = '';
    // determine styles / colors
    $card = 'card-c chapter-row card-left-border-c';

    // add episode data for each row
    $html .= "<div class='{$card} mb-0 ml-2' style='width: 100%;border-left-color: #1976D2; border-bottom: 1px solid #eee; box-shadow: none;'>";
    $html .= "<div class='row card-body'>";
    $html .= "<div class='col d-flex align-items-center' style='width: 100px;'><div class='time-container'><input required pattern='([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1})' style='width: 100%;' name='" . $FeedSlug . "-starts[]' class='time-input' type='text' value='00:00:00' /></div></div>";
    $html .= "<div class='col-sm-3 align-self-center'><input style='width: 100%; height: 50.6px;' required name='" . $FeedSlug . "-titles[]' class='form-control b-border' type='text' value='' placeholder='Chapter Title' /></div>";
    $html .= "<div class='col-sm-3 align-self-center'><input style='width: 100%; height: 50.6px;' name='" . $FeedSlug . "-urls[]' class='form-control b-border' type='text' value='' placeholder='URL' /></div>";
    $html .= "<div class='col-sm-2 align-self-center' style='margin-top: -1.25rem; margin-bottom: -1.25rem;'>";

    $html .= "<div id='upload-im-container'>";
    $html .= "            <div id='pp-chapters-art-text-$FeedSlug-row-0'>";

    $html .= "                <input type='hidden' class='pp-ep-box-input' title='" . esc_attr(__("Chapters Image URL", "powerpress")) . "'";
    $html .= "                       id='powerpress_chapters_image_$FeedSlug-row-0'";
    $html .= "                       placeholder='" . htmlspecialchars(__('e.g. http://example.com/path/to/image.jpg', 'powerpress')) . "'";
    $html .= "                       name='" . $FeedSlug . "-images[]'";
    $html .= "                       value=''";
    $html .= "                       style='font-size: 90%;' size='250' onchange='powerpress_chapters_art_preview(this)'/>";

    $html .= "                <a href='' class='powerpress-chapters-image-browser'";
    $html .= "                   id='powerpress_chapters_image_browser_$FeedSlug-row-0' onclick='event.preventDefault();browseChapterImages(event);'";
    $html .= "                   title='" . esc_attr(__('Select Chapters Image', 'powerpress')) . "'>";
    $html .= "                    " . esc_html(__('UPLOAD', 'powerpress')) . "";
    $html .= "                </a>";
    $html .= "            </div>";
    $html .= "            <div id='preview-im-container-$FeedSlug-row-0' style='display: none !important;'>";
    $html .= "                <img id='pp-image-chapters-preview-$FeedSlug-row-0'";
    $html .= "                     src='' alt='No artwork selected' style='max-width: 100%; height: 55px; width: 55px; display: block;' />";
    $html .= "                <a id='remove-im-$FeedSlug-row-0' class='ml-2' style='color: #1976D2; cursor: pointer;' onclick='removeIm(this);'>Remove</a>";

    $html .= "            </div>";
    $html .= "        </div>";
    $html .= "<input type='hidden' name='" . $FeedSlug . "-existingIms[]' value='' />";
    $html .= "<input id='remove-existing-$FeedSlug-row-0' type='hidden' name='" . $FeedSlug . "-removeExisting[]' value='0' />";
    $html .= "</div>";
    $html .= "<div class='col-sm-1 d-flex align-items-center'><div class='row d-flex justify-content-end'>";
    $html .= "<a class='" . $FeedSlug . "-remove-chapter' style='cursor: pointer;'><img src='/wp-content/plugins/powerpress/images/trash.svg' alt='" . esc_attr(__('Delete Icon', 'powerpress')) . "'></a>";
    $html .= "</div></div>";

    // close icon and card divs
    $html .= "</div></div>";
    return $html;
}

function chapters_tab($EnclosureURL, $FeedSlug, $object, $GeneralSettings, $PCITranscript, $PCITranscriptURL, $PCIChapters, $PCIChaptersManual, $PCIChaptersURL, $PCISoundbites, $ExtraData)
{
    $chaptersParsedJson = array();

    if (empty($ExtraData)) {
        $ExtraData = array();
    }
    if ($PCIChapters == 1) {
        // first, check if the chapters are hosted on this site. if so, access them directly rather than over http
        if (strpos($PCIChaptersURL, wp_upload_dir()['baseurl']) !== false) {
            $chapters_req_url = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $PCIChaptersURL);
        } else {
            $chapters_req_url = $PCIChaptersURL;
        }
        // phpstan: file_get_contents returns false on failure (doesn't throw), suppress warning instead
        $json = @file_get_contents($chapters_req_url);

        if ($json) {
            // phpstan: json_decode returns null on invalid JSON, check before accessing ['chapters']
            $decoded = json_decode($json, true);
            if (is_array($decoded) && isset($decoded['chapters'])) {
                $chaptersParsedJson = $decoded['chapters'];
            } else {
                $error = true;
                $statusMsg = "We were unable to parse the chapters file. Please make sure it contains a valid 'chapters' array.";
            }
        } else {
            $error = true;
            $statusMsg = "We were unable to parse the provided transcript file. Please make sure that it is publicly accessible and a well-formed .json file.";
        }
    }

    ?>
        <style>
            .card-c {
                position: relative;
                display: flex;
                flex-direction: column;
                min-width: 0;
                word-wrap: break-word;
                background-color: #fff;
                background-clip: border-box;
                border: 0px solid #ffffff;
                height: 82px;
                width: 80%;
            }

            .card-left-border-c {
                border-left-width: 5px;
                border-radius: 5px;
            }

            .card-body {
                flex: 1 1 auto;
                min-height: 1px;
                padding: 1.25rem;
            }

            .time-container {
                width: 100px;
            }

            .time-input {
                background-image: url('/wp-content/plugins/powerpress/images/time-stamps.png');
                background-repeat: no-repeat;
                background-position: 14px 29px;
                background-size: 65px;
                display: block;
                font-size: 18px !important;
                line-height: 18px;
                padding: 1px 12px 12px !important;
                border: 1px #1976D2 solid;
                border-radius: 5px;
            }

            .time-input:focus-visible {
                outline: none;
                border: 1px #1976D2 solid !important;
                border-radius: 5px;
            }

            .b-border {
                border: 1px solid #1976D2;
            }
        </style>
        <div id="chapters-<?php echo $FeedSlug; ?>" class="pp-tabcontent">
            <div class="pp-section-container">
                <h4 class="pp-section-title-block"> <?php echo esc_html(__('Podcasting 2.0 Chapters', 'powerpress')); ?> </h4>
                <?php
                $chapters_box_style = '';
                if ($EnclosureURL) {
                    $chapters_box_style = ' display: none;';
                ?>
                    <p style="font-size: 14px; margin-top: 0; margin-bottom: 0;" class="pp-ep-box-text">
                        <input id="powerpress_chapters_edit" title="<?php echo esc_attr(__("Edit chapters", "powerpress")); ?>"
                            class="ep-box-checkbox"
                            name="Powerpress[<?php echo $FeedSlug; ?>][chapters][edit]" value="1"
                            type="checkbox"
                            onclick="showHideTranscriptBox('chapters', '<?php echo $FeedSlug; ?>');" />
                        <?php echo esc_html(__('Edit chapters', 'powerpress')); ?>
                        <?php if (!empty($PCIChaptersURL)) { ?>
                            - <a href="<?php echo $PCIChaptersURL ?>" title="Chapters Link" target="_blank"><?php echo $PCIChaptersURL; ?></a>
                        <?php } ?>
                    </p>
                <?php } else { ?>
                    <input type="hidden" name="Powerpress[<?php echo $FeedSlug; ?>][chapters][edit]" value="1" />
                <?php } ?>
                <div id="chapters-box-options-<?php echo $FeedSlug; ?>" style="margin-top: 1.5em;<?php echo $chapters_box_style; ?>">

                    <p style="font-size: 14px;" class="pp-ep-box-text">
                        <input id="powerpress_chapters_none_<?php echo $FeedSlug ?>" title="<?php echo esc_attr(__("No chapters", "powerpress")); ?>"
                            class="media-details-radio"
                            name="Powerpress[<?php echo $FeedSlug; ?>][chapters][none]" value="1"
                            type="radio"
                            onclick="setChaptersCheckboxes(this.id, '<?php echo $FeedSlug; ?>');" <?php echo empty($PCIChaptersURL) ? 'checked' : ''; ?> />
                        <?php echo esc_html(__('No chapters', 'powerpress')); ?>
                    </p>

                    <hr style="margin: 1em 0 1em 0;">

                    <p style="font-size: 14px;" class="pp-ep-box-text">
                        <input id="powerpress_pci_chapters_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Add a chapters file", "powerpress")); ?>"
                            class="media-details-radio" onclick="setChaptersCheckboxes(this.id, '<?php echo $FeedSlug; ?>');"
                            name="Powerpress[<?php echo $FeedSlug; ?>][chapters][upload]" value="1"
                            type="radio" <?php echo !empty($PCIChaptersURL) && empty($PCIChaptersManual) ? 'checked' : ''; ?> />
                        <?php echo esc_html(__('Add a chapters file', 'powerpress')); ?>
                    </p>

                    <div class="powerpress_row" id="powerpress_pci_chapters_container_<?php echo $FeedSlug; ?>" <?php if (empty($PCIChapters) || !empty($PCIChaptersManual)) {
                                                                                                                    echo "style=\"display: none;\"";
                                                                                                                } ?>>
                        <div class="powerpress_row_content">
                            <input type="text" id="powerpress_chapters_url_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("URL to chapters file", "powerpress")); ?>"
                                class="pp-ep-box-input"
                                name="Powerpress[<?php echo $FeedSlug; ?>][pci_chapters_url]"
                                value="<?php echo esc_attr($PCIChaptersURL); ?>"
                                placeholder="<?php echo 'https://' . $_SERVER['SERVER_NAME'] . '/wp-content/uploads/' . date('Y') . '/' . date('m') . '/' . 'chapters.json'; ?>"
                                style="width: 96%; margin: 1em 4% 0 0;" />
                            <label class="pp-ep-box-label-under"><?php echo esc_html(__("Must be the format application/json+chapters", 'powerpress')); ?></label>
                        </div>
                    </div>
                    <hr style="margin: 1em 0 1em 0;">

                    <p style="font-size: 14px; display: inline;" class="pp-ep-box-text">
                        <input id="powerpress_chapters_manual_<?php echo $FeedSlug ?>" title="<?php echo esc_attr(__("Use the PowerPress Chapter Builder", "powerpress")); ?>"
                            class="media-details-radio"
                            name="Powerpress[<?php echo $FeedSlug; ?>][chapters][manual]" value="1"
                            type="radio" <?php echo !empty($PCIChaptersManual) ? 'checked' : ''; ?>
                            onclick="setChaptersCheckboxes(this.id, '<?php echo $FeedSlug; ?>');" />
                        <?php echo esc_html(__('Use the PowerPress Chapter Builder', 'powerpress')); ?>
                    </p>

                    <div class="pp-section-container chapter-builder" style="display: <?php echo $PCIChaptersManual == 1 ? "block" : "none" ?>" id="<?php echo $FeedSlug; ?>-chapter-builder-container">
                        <h3><?php echo esc_html(__("Chapter Builder", 'powerpress')) ?></h3>
                        <p><strong><?php echo esc_html(__("Note:", 'powerpress')) ?></strong> <?php echo esc_html(__("Modifying any entries below will overwrite the existing chapter file when you save it.", 'powerpress')) ?></p>
                        <p><?php echo esc_html(__("Don't worry about putting your chapters in order, we will take care of that for you!", 'powerpress')) ?></p>

                        <div class="table table-heading" id="<?php echo $FeedSlug; ?>-chapter-builder">
                            <div style="padding-left: 25px; padding-right: 15px;" class="row">
                                <div class="col" style="font-weight: bold; font-size: 115%; width: 100px;"><?php echo esc_html(__('Start Time', 'powerpress')); ?></div>
                                <div class="col-sm-3" style="font-weight: bold; font-size: 115%;"><?php echo esc_html(__('Title', 'powerpress')); ?></div>
                                <div class="col-sm-3" style="font-weight: bold; font-size: 115%;"><?php echo esc_html(__('URL', 'powerpress')); ?></div>
                                <div class="col-sm-2" style="font-weight: bold; font-size: 115%;"><?php echo esc_html(__('Image', 'powerpress')); ?></div>
                                <div class="col-sm-1"></div>
                            </div>
                            <?php
                            $chapters_count = 0;
                            if (!empty($chaptersParsedJson)) {
                                foreach ($chaptersParsedJson as $chapter) {
                                    echo print_table_body($chapter, $FeedSlug, $object, $chapters_count);
                                    $chapters_count++;
                                }
                            } ?>
                            <div id="<?php echo $FeedSlug ?>-chapter-end"></div>
                            <div style="padding-left: 25px; padding-right: 15px; width: 80%; padding-bottom: 50px;" class="row">
                                <div class="col-sm-3">
                                    <a class="btn btn-secondary mr-2" id="<?php echo $FeedSlug ?>-add-chapter" style="cursor: pointer;"><?php echo esc_html(__('Add Chapter', 'powerpress')); ?></a>
                                </div>
                                <div class="col-sm-3"></div>
                                <div class="col-sm-3"></div>
                                <div class="col-sm-2 "></div>
                                <div class="col-sm-1">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <?php if ($PCIChapters == 1 && is_array($chaptersParsedJson) && count($chaptersParsedJson) > 0) { ?>
                    <p><strong><?php echo esc_html(__("Note:", 'powerpress')) ?></strong> <?php echo esc_html(__("The Blubrry Podcast Player supports 'Skip To' functions which allow a listener to click on the time and skip to that position in the podcast. To leverage this feature, click the button below and paste the generated HTML in your show notes for this episode. Please save any changes before copying your HTML.", 'powerpress')) ?></p>
                    <div class="powerpress_row">
                        <div class="powerpress_row_content">
                            <textarea style="min-height: 150px; width: 100%; background-color: #f1f1f1; color: crimson;" wrap="off" id="<?php echo $FeedSlug; ?>-html-to-copy" readonly>
                                <?php
                                $html = "";
                                if (count($chaptersParsedJson) > 0) {
                                    $html .= "<ul>" . PHP_EOL;
                                    foreach ($chaptersParsedJson as $chapter) {
                                        $seconds = $chapter['startTime'];
                                        $startTime = sprintf('%02d:%02d:%02d', (int)($seconds / 3600), (int)($seconds / 60) % 60, $seconds % 60);

                                        $chapter['url'] = $chapter['url'] ?? '';
                                        $chapter['img'] = $chapter['img'] ?? '';
                                        $html .= "\t" . '<li>[skipto time="' . $startTime . '"] - <a href="' . esc_attr($chapter['url']) . '">' . esc_html($chapter['title']) . '</a></li>' . PHP_EOL;
                                    }
                                    $html .= "</ul>";
                                    echo $html;
                                } ?>
                            </textarea>
                            <button onclick="copyElement('<?php echo $FeedSlug; ?>-html-to-copy', '<?php echo $FeedSlug; ?>-copy-btn-embed')" class="btn btn-primary float-right" id="<?php echo $FeedSlug; ?>-copy-btn-embed" title="Copy to clipboard">
                                Copy to Clipboard
                            </button>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <script>
            function copyElement(link, btn) {
                let linkBox = document.getElementById(link);
                let copyBtn = document.getElementById(btn);
                linkBox.select();
                document.execCommand("copy");
                copyBtn.innerHTML = 'Copied to Clipboard';
                setTimeout(function() {
                    copyBtn.innerHTML = 'Copy to Clipboard';
                }, 5000);
            }
            var chapters_row_count = jQuery('.chapter-row').length;

            let <?php echo str_replace('-', '_', $FeedSlug); ?>_chapterCount = <?php echo 0; ?>;
            jQuery(document).ready(function() {
                jQuery("#<?php echo $FeedSlug ?>-add-chapter").on('click', function() {
                    <?php echo str_replace('-', '_', $FeedSlug); ?>_chapterCount += 1;

                    let newHtml = "<?php echo print_table_body_empty($FeedSlug, $object); ?>";
                    if (chapters_row_count != 0) {
                        while (newHtml.includes('-row-0')) {
                            newHtml = newHtml.replace("-row-0", '-row-' + chapters_row_count.toString());
                        }
                    }
                    let prevId = '#<?php echo $FeedSlug; ?>-chapter-end';
                    jQuery(newHtml).insertBefore(prevId);
                    chapters_row_count += 1;
                });

                jQuery(document).on('click', ".<?php echo $FeedSlug ?>-remove-chapter", function(e) {
                    <?php echo str_replace('-', '_', $FeedSlug); ?>_chapterCount -= 1;
                    jQuery(this).parent().parent().parent().parent().remove();
                });
            });
        </script>

    <?php
}

function print_table_body_vts($timeSplit, $FeedSlug, $vts_id, $post_id)
{
    $html = '';

    // determine styles / colors
    $card = 'card-c card-left-border-c';
    $seconds = $timeSplit['start_time'];
    $startTime = sprintf('%02d:%02d:%02d', intval($seconds / 3600), intval($seconds / 60) % 60, $seconds % 60);

    $seconds = $timeSplit['duration'];
    $duration = sprintf('%02d:%02d:%02d', intval($seconds / 3600), intval($seconds / 60) % 60, $seconds % 60);

    $title = (!empty($timeSplit['recipient']) && $timeSplit['recipient'] == 1) ? 'Value Recipient(s)' : ($timeSplit['remote_item']['item_title'] ?? '');

    $html = "<div class='card-c card-left-border-c mb-0 ml-2' style='border-left-color: #1976D2; border-bottom: 1px solid #eee; box-shadow: none;'>";
    $html .= "<input type='hidden' name='Powerpress[$FeedSlug][vts][$vts_id][post_id]' value='$post_id' />";
    $html .= "<div class='row card-body'>";
    $html .= "<div class='col-sm-2 d-flex align-items-center' style='width: 100px;'><div class='time-container'><input required pattern='([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1})' style='width: 100%;' name='Powerpress[$FeedSlug][vts][$vts_id][start_time]' class='time-input' type='text' value='$startTime' /></div></div>";
    $html .= "<div class='col-sm-2 d-flex align-items-center' style='width: 100px;'><div class='time-container'><input required pattern='([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1})' style='width: 100%;' name='Powerpress[$FeedSlug][vts][$vts_id][duration]' class='time-input' type='text' value='$duration' /></div></div>";
    $html .= "<div class='col-sm-4 align-self-center recipient-title' id='recipient-title-$vts_id'>$title</div>";
    $html .= "<div class='col-sm-3 align-self-center'><a style='display: block;' class='thickbox' id='add-edit-recipient-$vts_id' title='" . esc_attr(__('Edit Recipient', 'powerpress')) . "' href='" . admin_url() . wp_nonce_url("admin.php?action=powerpress-jquery-vts-add-edit-recipient", 'powerpress-jquery-vts-add-edit-recipient') . "&amp;KeepThis=true&amp;feed_slug=$FeedSlug&amp;post_id=$post_id&amp;vts_id=$vts_id&amp;TB_iframe=true&amp;width=600&amp;height=400&amp;modal=true' target='_blank'>" . __('Edit Recipient', 'powerpress') . "</a></div>";
    $html .= "<div class='col-sm-1 d-flex align-items-center'><div class='row d-flex justify-content-end'>";
    $html .= "<a class='remove-vts' style='cursor: pointer;'><img src='/wp-content/plugins/powerpress/images/trash.svg' alt='" . esc_attr(__('Delete Icon', 'powerpress')) . "'></a>";
    $html .= "</div></div>";

    // close icon and card divs
    $html .= "</div></div>";

    return $html;
}

function vts_tab($FeedSlug, $object, $GeneralSettings, $PCITranscript, $PCITranscriptURL, $PCIChapters, $PCIChaptersManual, $PCIChaptersURL, $PCISoundbites, $ExtraData)
{
    if (empty($ExtraData)) {
        $ExtraData = array();
    }
    $existingTimeSplits = get_option('vts_' . $FeedSlug . '_' . $object->ID, array());
    $existingVTSOrder = $ExtraData['vts_order'] ?? array();

    ?>
        <style>
            .card-c {
                position: relative;
                display: flex;
                flex-direction: column;
                min-width: 0;
                word-wrap: break-word;
                background-color: #fff;
                background-clip: border-box;
                border: 0px solid #ffffff;
                height: 82px;
                width: 80%;
            }

            .card-left-border-c {
                border-left-width: 5px;
                border-radius: 5px;
            }

            .card-body {
                flex: 1 1 auto;
                min-height: 1px;
                padding: 1.25rem;
            }

            .time-container {
                width: 100px;
            }

            .time-input {
                background-image: url('/wp-content/plugins/powerpress/images/time-stamps.png');
                background-repeat: no-repeat;
                background-position: 14px 29px;
                background-size: 65px;
                display: block;
                font-size: 18px !important;
                line-height: 18px;
                padding: 1px 12px 12px !important;
                border: 1px #1976D2 solid;
                border-radius: 5px;
            }

            .time-input:focus-visible {
                outline: none;
                border: 1px #1976D2 solid !important;
                border-radius: 5px;
            }

            .b-border {
                border: 1px solid #1976D2;
            }
        </style>
        <div id="vts-<?php echo $FeedSlug; ?>" class="pp-tabcontent">
            <div class="pp-section-container">
                <h4 class="pp-section-title-block"> <?php echo esc_html(__('Value Time Split (VTS)', 'powerpress')); ?> </h4>
                <p style="font-size: 14px;" class="pp-ep-box-text">
                    <?php echo esc_html(__('This enables alternate payments to recipients during a specific segment of the podcast episode this could be an artist and V4V-enabled song or a special guest. The VTS substitutes the payment to those designated for the period of time set.', 'powerpress')); ?>
                </p>
            </div>
            <div class="pp-section-container" style="background-color: #eee; border-radius: 5px; padding: 25px;" id="<?php echo $FeedSlug; ?>-vts-builder-container">
                <h3><?php echo esc_html(__("VTS Builder", 'powerpress')) ?></h3>
                <p><?php echo esc_html(__("Don't worry about putting your time splits in order, we will take care of that for you!", 'powerpress')) ?></p>
                <p><span style="font-weight: bold;"><?php echo esc_html(__("Note:", 'powerpress')) ?></span> <?php echo esc_html(__("Time splits without a set recipient will not be saved.", 'powerpress')) ?></p>
                <div class="table table-heading" id="<?php echo $FeedSlug; ?>-vts-builder">
                    <div style="padding-left: 25px; padding-right: 15px; width: 80%;" class="row">
                        <div class="col" style="font-weight: bold; font-size: 115%; width: 100px;"><?php echo esc_html(__('Start Time', 'powerpress')); ?></div>
                        <div class="col" style="font-weight: bold; font-size: 115%; width: 100px;"><?php echo esc_html(__('Duration', 'powerpress')); ?></div>
                        <div class="col-sm-4" style="font-weight: bold; font-size: 115%;"><?php echo esc_html(__('Recipient Title', 'powerpress')); ?></div>
                        <div class="col-sm-3" style="font-weight: bold; font-size: 115%;"><?php echo esc_html(__('Edit', 'powerpress')); ?></div>
                        <div class="col-sm-1"></div>
                    </div>
                    <?php
                    foreach ($existingVTSOrder as $vts_key) {
                        $timeSplit = $existingTimeSplits[$vts_key];
                        echo print_table_body_vts($timeSplit, $FeedSlug, $timeSplit['vts_id'], $object->ID);
                    } ?>
                    <div id="<?php echo $FeedSlug ?>-vts-end"></div>
                    <div style="padding-left: 25px; padding-right: 15px; width: 80%; padding-bottom: 50px;" class="row">
                        <div class="col-sm-3">
                            <a class="btn btn-secondary mr-2" id="<?php echo $FeedSlug ?>-add-vts" style="cursor: pointer;"><?php echo esc_html(__('Add Time Split', 'powerpress')); ?></a>
                        </div>
                        <div class="col-sm-3"></div>
                        <div class="col-sm-3"></div>
                        <div class="col-sm-2 "></div>
                        <div class="col-sm-1">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            var old_tb_remove = window.tb_remove;

            var tb_remove_new = function() {
                old_tb_remove(); // calls the tb_remove() of the Thickbox plugin
                alert('ohai');
            };

            let <?php echo str_replace('-', '_', $FeedSlug); ?>_vts_count = <?php echo 0; ?>;

            function uuidv4() {
                return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
                    (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
                );
            }

            jQuery(document).ready(function() {
                jQuery("#<?php echo $FeedSlug ?>-add-vts").on('click', function() {
                    <?php echo str_replace('-', '_', $FeedSlug); ?>_vts_count += 1;
                    let newVTSId = Math.random().toString(16).slice(2);

                    let newHtml = '';

                    // add episode data for each row
                    newHtml += "<div class='card-c card-left-border-c mb-0 ml-2' style='border-left-color: #1976D2; border-bottom: 1px solid #eee; box-shadow: none;'>";
                    newHtml += "<input type='hidden' name='Powerpress[<?php echo $FeedSlug; ?>][vts][" + newVTSId + "][post_id]' value='<?php echo $object->ID; ?>' />";
                    newHtml += "<div class='row card-body'>";
                    newHtml += "<div class='col d-flex align-items-center' style='width: 100px;'><div class='time-container'><input required pattern='([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1})' style='width: 100%;' name='Powerpress[<?php echo $FeedSlug; ?>][vts][" + newVTSId + "][start_time]' class='time-input' type='text' value='00:00:00' /></div></div>";
                    newHtml += "<div class='col d-flex align-items-center' style='width: 100px;'><div class='time-container'><input required pattern='([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1}):([0-9]{2}|[0-9]{1})' style='width: 100%;' name='Powerpress[<?php echo $FeedSlug; ?>][vts][" + newVTSId + "][duration]' class='time-input' type='text' value='00:00:00' /></div></div>";
                    newHtml += "<div class='col-sm-4 align-self-center recipient-title' id='recipient-title-" + newVTSId + "'></div>";
                    newHtml += "<div class='col-sm-3 align-self-center'><a style='display: block;' class='thickbox' id='add-edit-recipient-" + newVTSId + "' title='<?php echo esc_attr(__('Add Recipient', 'powerpress')); ?>' href='<?php echo admin_url() . wp_nonce_url("admin.php?action=powerpress-jquery-vts-add-edit-recipient", 'powerpress-jquery-vts-add-edit-recipient'); ?>&amp;KeepThis=true&amp;feed_slug=<?php echo $FeedSlug; ?>&amp;post_id=<?php echo $object->ID; ?>&amp;vts_id=" + newVTSId + "&amp;TB_iframe=true&amp;width=600&amp;height=400&amp;modal=true' target='_blank'><?php echo __('Add Recipient', 'powerpress'); ?></a></div>";
                    newHtml += "<div class='col-sm-1 d-flex align-items-center'><div class='row d-flex justify-content-end'>";
                    newHtml += "<a class='remove-vts' style='cursor: pointer;'><img src='/wp-content/plugins/powerpress/images/trash.svg' alt='<?php echo esc_attr(__('Delete Icon', 'powerpress')); ?>'></a>";
                    newHtml += "</div></div>";

                    // close icon and card divs
                    newHtml += "</div></div>";

                    let prevId = '#<?php echo $FeedSlug ?>-vts-end';
                    jQuery(newHtml).insertBefore(prevId)
                });

                jQuery(document).on('click', ".remove-vts", function(e) {
                    <?php echo str_replace('-', '_', $FeedSlug); ?>_vts_count -= 1;
                    jQuery(this).parent().parent().parent().parent().remove();
                });
            });
        </script>

    <?php
}

function display_tab($FeedSlug, $IsVideo, $NoPlayer, $NoLinks, $Width, $Height, $Embed, $GeneralSettings)
{
    ?>

        <div id="display-<?php echo $FeedSlug; ?>" class="pp-tabcontent">
            <?php if ((!isset($GeneralSettings['new_episode_box_no_player']) || $GeneralSettings['new_episode_box_no_player'] == 1) || (!isset($GeneralSettings['new_episode_box_no_links']) || $GeneralSettings['new_episode_box_no_links'] == 1)) { ?>
                <div id="pp-display-player-<?php echo $FeedSlug; ?>" class="pp-section-container">
                    <h4 class="pp-section-title-block"><?php echo esc_html(__('Episode Player', 'powerpress')); ?></h4>
                    <?php if (!isset($GeneralSettings['new_episode_box_no_player']) || $GeneralSettings['new_episode_box_no_player'] == 1) { ?>
                        <p style="font-size: 14px;" class="pp-ep-box-text">
                            <input id="powerpress_no_player_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Do not display player", "powerpress")); ?>"
                                class="ep-box-checkbox"
                                name="Powerpress[<?php echo $FeedSlug; ?>][no_player]" value="1"
                                type="checkbox" <?php echo ($NoPlayer == 1 ? 'checked' : ''); ?> />
                            <?php echo esc_html(__('Do not display player', 'powerpress')); ?>
                        </p>
                        <br /><?php }
                            if (!isset($GeneralSettings['new_episode_box_no_links']) || $GeneralSettings['new_episode_box_no_links'] == 1) { ?>
                        <p style="font-size: 14px; margin-top: 1ch;" class="pp-ep-box-text">
                            <input id="powerpress_no_links_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Do not display media links", "powerpress")); ?>"
                                class="ep-box-checkbox"
                                name="Powerpress[<?php echo $FeedSlug; ?>][no_links]" value="1"
                                type="checkbox" <?php echo ($NoLinks == 1 ? 'checked' : ''); ?> />
                            <?php echo esc_html(__('Do not display media links', 'powerpress')); ?>
                        </p>
                    <?php } ?>
                </div>

                <div id="line-above-player-size-<?php echo $FeedSlug; ?>" class="ep-box-line"></div>
            <?php } //Setting for audio player size

            if (!isset($GeneralSettings['new_episode_box_player_size']) || $GeneralSettings['new_episode_box_player_size'] == 1) { ?>
                <div id="pp-player-size-<?php echo $FeedSlug; ?>" class="pp-section-container">
                    <h4 class="pp-section-title" style="width: 100%;"><?php echo esc_html(__('Video Player Size', 'powerpress')); ?></h4>
                    <div class="powerpress-label-container">
                        <input type="text" id="powerpress_episode_player_width_<?php echo $FeedSlug; ?>" title="<?php echo esc_attr(__("Player width", "powerpress")); ?>"
                            class="pp-ep-box-input" placeholder="<?php echo htmlspecialchars(__('Width', 'powerpress')); ?>"
                            name="Powerpress[<?php echo $FeedSlug; ?>][width]" value="<?php echo esc_attr($Width); ?>"
                            style="width: 40%;font-size: 90%;" size="5" />
                        x
                        <input type="text" id="powerpress_episode_player_height_<?php echo $FeedSlug; ?>"
                            class="pp-ep-box-input"
                            placeholder="<?php echo htmlspecialchars(__('Height', 'powerpress')); ?>" title="<?php echo esc_attr(__("Player height", "powerpress")); ?>"
                            name="Powerpress[<?php echo $FeedSlug; ?>][height]" value="<?php echo esc_attr($Height); ?>"
                            style="width: 40%; font-size: 90%;" size="5" />
                    </div>
                </div>
                <div class="ep-box-line"></div>
            <?php } ?>
            <div id="player-shortcode-<?php echo $FeedSlug; ?>" class="pp-section-container">
                <h4 class="pp-section-title-block"><?php echo esc_html(__('Display Player Anywhere on Page', 'powerpress')); ?></h4>
                <div style="display:inline-block">
                    <p class="pp-shortcode-example" style="font-weight: bold;">
                        [<?php echo __('powerpress'); ?>]</p>
                </div>
                <p class="pp-section-subtitle"><?php echo esc_html(__('Just copy and paste this shortcode anywhere in your page content. ', 'powerpress')); ?>
                    <a href="https://support.wordpress.com/shortcodes/"
                        target="_blank"><?php echo esc_html(__('Learn more about shortcodes here.', 'powerpress')); ?></a>
                </p>
            </div>
            <div class="ep-box-line"></div>
            <?php //Setting for media embed
            if (!isset($GeneralSettings['new_episode_box_embed']) || $GeneralSettings['new_episode_box_embed'] == 1) {
            ?>
                <div class="pp-section-container">
                    <h4 class="pp-section-title"><?php echo esc_html(__('Media Embed', 'powerpress')); ?></h4>
                    <div class="pp-tooltip-right">i
                        <span class="text-pp-tooltip"
                            style="top: -50%;"><?php echo esc_html(__('Here, you can enter a link to embed a media player.', 'powerpress')); ?></span>
                    </div>
                    <div class="powerpress_row_content">
                        <textarea class="pp-ep-box-input" id="powerpress_embed_<?php echo $FeedSlug; ?>"
                            name="Powerpress[<?php echo $FeedSlug; ?>][embed]" title="<?php echo esc_attr(__("Media Embed", "powerpress")); ?>"
                            style="font-size: 14px; width: 95%; height: 80px;"
                            onfocus="this.select();"><?php echo esc_textarea($Embed); ?></textarea>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php
}

//
// Epiosde: Advanced Tab
//
function notes_tab($FeedSlug, $object, $GeneralSettings, $ExtraData)
{
    if (empty($ExtraData)) {
        $ExtraData = array();
    }
    ?>
        <div id="notes-<?php echo $FeedSlug; ?>" class="pp-tabcontent">
            <?php $MetaRecords = powerpress_metamarks_get($object->ID, $FeedSlug); ?>
            <script language="javascript">
                //<!--

                function powerpress_metamarks_addrow(FeedSlug) {
                    var NextRow = 0;
                    if (jQuery('#powerpress_metamarks_counter_' + FeedSlug).length > 0) {
                        NextRow = jQuery('#powerpress_metamarks_counter_' + FeedSlug).val();
                    } else {
                        alert('<?php echo __('An error occurred.', 'powerpress'); ?>');
                        return;
                    }
                    NextRow++;
                    jQuery('#powerpress_metamarks_counter_' + FeedSlug).val(NextRow);

                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url(); ?>admin-ajax.php',
                        data: {
                            action: 'powerpress_metamarks_addrow',
                            next_row: NextRow,
                            feed_slug: encodeURIComponent(FeedSlug),
                            nonce: '<?php echo wp_create_nonce('powerpress-metamarks-addrow'); ?>'
                        },
                        timeout: (10 * 1000),
                        success: function(response) {
                            <?php
                            if (defined('POWERPRESS_AJAX_DEBUG'))
                                echo "\t\t\t\talert(response);\n";
                            ?>
                            jQuery('#powerpress_metamarks_block_' + FeedSlug).append(response);
                        },
                        error: function(objAJAXRequest, strError) {

                            var errorMsg = "HTTP " + objAJAXRequest.statusText;
                            if (objAJAXRequest.responseText) {
                                errorMsg += ', ' + objAJAXRequest.responseText.replace(/<.[^<>]*?>/g, '');
                            }

                            jQuery('#powerpress_check_' + FeedSlug).css("display", 'none');
                            if (strError == 'timeout')
                                jQuery('#powerpress_warning_' + FeedSlug).text('<?php echo esc_js(__('Operation timed out.', 'powerpress')); ?>');
                            else if (errorMsg)
                                jQuery('#powerpress_warning_' + FeedSlug).text('<?php echo esc_js(__('AJAX Error', 'powerpress')) . ': '; ?>' + errorMsg);
                            else if (strError != null)
                                jQuery('#powerpress_warning_' + FeedSlug).text('<?php echo esc_js(__('AJAX Error', 'powerpress')) . ': '; ?>' + strError);
                            else
                                jQuery('#powerpress_warning_' + FeedSlug).text('<?php echo esc_js(__('AJAX Error', 'powerpress')) . ': ' . esc_html(__('Unknown', 'powerpress')); ?>');
                            jQuery('#powerpress_warning_' + FeedSlug).css('display', 'block');
                        }
                    });
                }

                function powerpress_metamarks_deleterow(div) {
                    if (confirm('<?php echo __('Delete row, are you sure?', 'powerpress'); ?>')) {
                        jQuery('#' + div).remove();
                    }
                    return false;
                }

                jQuery(document).ready(function() {

                });

                // -->
            </script>
            <style>
                .pp-settings-subsection {
                    margin-left: 1em;
                    padding: 1em 0 1em 1em;
                    border-bottom: 1px solid #EFF0F5;
                    display: inline-block;
                    width: 90%;
                }
            </style>

            <!-- Advanced Tab Rendering -->
            <?php 
            // uncomment and check php-error.log to see data structures
            // Data Source debug
            // if (defined('WP_DEBUG') && WP_DEBUG) {
            //     error_log(print_r($FeedSlug, true) . ' -> PRENORMALIZE DATASOURCE -> ' . print_r($ExtraData, true));
            // }

            $advanced_tab_tags = [
                'location',
                'copyright',
                'credit',
                'v4v',
                'soundbites',
                'social_interact',
                'donate',
                'txt_tag',
                'alternate_enclosure',
                'content_link'
            ];

            // Form Render Loop
            foreach($advanced_tab_tags as $tag) {
                powerpress_render_template([
                    'type' => $tag,
                    'context' => 'item',
                    'FeedSlug' => $FeedSlug,
                    'Data' => $ExtraData,
                    'NamePrefix' => "Powerpress[$FeedSlug]",
                ]);
                ?>
                <hr>
                <?php 
            }
            ?>
        </div>
        <script defer>
            // Initializer for section/tag managers
            document.addEventListener('DOMContentLoaded', function() {
                initLocationManager('<?php echo $FeedSlug; ?>');
                initCreditsManager( '<?php echo $FeedSlug; ?>');
                initValueRecipientManager('<?php echo $FeedSlug; ?>');
                initSoundbitesManager('<?php echo $FeedSlug; ?>');
                initSocialInteractManager('<?php echo $FeedSlug; ?>');
                initTxtTagManager('<?php echo $FeedSlug; ?>');
                initAlternateEnclosureManager('<?php echo $FeedSlug; ?>');
                initContentLinksManager('<?php echo $FeedSlug; ?>')
            });
        </script>
    <?php
}
//
//      End Of Advanced Tab
//

function get_filename_from_path($path)
{
    if (strpos($path, '/') !== false) {
        $pieces = explode('/', $path);
    } else {
        $pieces = explode('\\', $path);
    }
    return end($pieces);
}

/**
 * {@internal Missing Short Description}}
 *
 * @since unknown
 *
 * @return unknown
 */
function media_upload_powerpress_image()
{
    $errors = array();
    $id = 0;

    if (isset($_POST['html-upload']) && !empty($_FILES)) {
        // Upload File button was clicked
        $post_id = intval($_REQUEST['post_id']); // precautionary, make sure we're always working with an integer
        $id = media_handle_upload('async-upload', $post_id);
        unset($_FILES);
        if (is_wp_error($id)) {
            $errors['upload_error'] = $id;
            $id = false;
        }
    }

    return wp_iframe('powerpress_media_upload_type_form', 'powerpress_image', $errors, $id);
}

add_action('media_upload_powerpress_image', 'media_upload_powerpress_image');

/**
 * {@internal Missing Short Description}}
 *
 * @since unknown
 *
 * @param unknown_type $html
 */
function powerpress_send_to_episode_entry_box($url)
{
    ?>
        <script type="text/javascript">
            /* <![CDATA[ */
            var win = window.dialogArguments || opener || parent || top;
            if (win.powerpress_send_to_poster_image)
                win.powerpress_send_to_poster_image('<?php echo addslashes($url); ?>');
            /* ]]> */
        </script>
    <?php
    exit;
}


/**
 * {@internal Missing Short Description}}
 *
 * @since unknown
 *
 * @param unknown_type $tabs
 * @return unknown
 */
function powerpress_update_media_upload_tabs($tabs)
{

    if (!empty($_GET['type'])) {
        if ($_GET['type'] == 'powerpress_image') // We only want to allow uploads
        {
            unset($tabs['type_url']);
            unset($tabs['gallery']);
            unset($tabs['library']);
        }
    }
    return $tabs;
}
add_filter('media_upload_tabs', 'powerpress_update_media_upload_tabs', 100);

/**
 * {@internal Missing Short Description}}
 *
 * @since unknown
 *
 * @param unknown_type $type
 * @param unknown_type $errors
 * @param unknown_type $id
 */
function powerpress_media_upload_type_form($type = 'file', $errors = null, $id = null)
{
    media_upload_header();

    $post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;

    $form_action_url = admin_url("media-upload.php?type=$type&tab=type&post_id=$post_id");
    $form_action_url = apply_filters('media_upload_form_url', $form_action_url, $type);

    if ($id && !is_wp_error($id)) {
        $image_url = wp_get_attachment_url($id);
        powerpress_send_to_episode_entry_box($image_url);
    }

    ?>

        <form enctype="multipart/form-data" method="post" action="<?php echo esc_attr($form_action_url); ?>" class="media-upload-form type-form validate" id="<?php echo $type; ?>-form">
            <input type="submit" class="hidden" name="save" value="" />
            <input type="hidden" name="post_id" id="post_id" value="<?php echo (int) $post_id; ?>" />
            <?php wp_nonce_field('media-form'); ?>

            <h3 class="media-title"><?php echo esc_html(__('Select poster image from your computer.', 'powerpress')); ?></h3>

            <?php media_upload_form($errors); ?>

            <script type="text/javascript">
                //<![CDATA[         <-- DEPRECATED
                jQuery(document).ready(function() {
                    jQuery('#sidemenu').css('display', 'none');
                    jQuery('body').css('margin', '0px 20px');
                    jQuery('body').css('height', 'auto');
                    jQuery('html').css('height', 'auto'); // Elimate the weird scroll bar
                });
                //]]>
            </script>
            <div id="media-items">
                <?php
                if ($id && is_wp_error($id)) {
                    echo '<div id="media-upload-error">' . esc_html($id->get_error_message()) . '</div>';
                }
                ?>
            </div>
        </form>
    <?php
}


function powerpress_media_upload_use_flash($flash)
{
    if (!empty($_GET['type']) && $_GET['type'] == 'powerpress_image') {
        return false;
    }
    return $flash;
}

add_filter('flash_uploader', 'powerpress_media_upload_use_flash');