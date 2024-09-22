<?php
$AppleCategories = powerpress_apple_categories(true);
$FeedSettings = powerpress_get_settings('powerpress_feed_podcast');
$GeneralSettings = powerpress_get_settings('powerpress_general');
$blogEmail = powerpress_get_settings('admin_email');
$title = !empty($FeedSettings['title']) ? $FeedSettings['title'] : get_bloginfo_rss('name');
$talent_name = !empty($FeedSettings['itunes_talent_name']) ? $FeedSettings['itunes_talent_name'] : '';
$feed_desc = !empty($FeedSettings['description']) ? $FeedSettings['description'] : '';
$explicit = !empty($FeedSettings['itunes_explicit']) ? $FeedSettings['itunes_explicit'] : 0;
$category = !empty($FeedSettings['apple_cat_1']) ? $FeedSettings['apple_cat_1'] : '';
if (isset($_FILES['itunes_image_file'])) {
    $feed_info = explode(" ", $_POST['basic_details']);
    foreach ($feed_info as $i => $word) {
        switch($word) {
            case 'TITLE:':
                if ($feed_info[$i + 1] != 'CATEGORY:') {
                    $title = str_replace("_", " ", $feed_info[$i + 1]);
                }
                break;
            case 'CATEGORY:':
                if ($feed_info[$i + 1] != 'EXPLICIT:') {
                    $FeedSettings['apple_cat_1'] = $feed_info[$i + 1];
                }
                break;
            case 'EXPLICIT:':
                if ($feed_info[$i + 1] != 'undefined') {
                    $FeedSettings['itunes_explicit'] = intval($feed_info[$i + 1]);
                }
                break;
            case 'EMAIL:':
                if(strlen($feed_info[$i + 1]) > 3){
                    $FeedSettings['itunes_email'] = $feed_info[$i + 1];
                }
                break;
            case 'NAME:':
                if(strlen($feed_info[$i + 1]) > 0){
                    $FeedSettings['itunes_talent_name'] = $feed_info[$i + 1];
                }
                break;
            case 'DESCRIPTION:':
                if(strlen($feed_info[$i + 1]) > 3){
                    $FeedSettings['itunes_summary'] = str_replace('_', ' ', $feed_info[$i + 1]);
                    $FeedSettings['itunes_summary'] = str_replace('{underscore.pp}', '_', $FeedSettings['itunes_summary']);
                    $FeedSettings['description'] = $FeedSettings['itunes_summary'];
                }
                break;
            default:
                break;
        }
    }

    $temp = $_FILES['itunes_image_file']['tmp_name'];

    //Make sure the file extension is alright
    $acceptable_extensions = ['jpg', 'jpeg', 'png'];
    $name = basename($_FILES['itunes_image_file']['name']);
    $ext = substr($name, strrpos($name, '.') + 1);

    if (!in_array(strtolower($ext), $acceptable_extensions)) {
        powerpress_page_message_add_error(__('Image has an invalid file type: ' . htmlspecialchars($ext), 'powerpress') );
        $error = true;
    } else {
        // Check the image...
        if (file_exists($temp)) {
            $upload_result = wp_handle_upload($_FILES['itunes_image_file'], array('test_form' => false));
            if (is_array($upload_result) && isset($upload_result['error'])) {
                powerpress_page_message_add_error(__('Error saving Apple Podcasts image', 'powerpress') . ':	' . $upload_result['error']);
                $error = true;
            } elseif (is_array($upload_result) && isset($upload_result['url'])) {
                $previewImageURL = $upload_result['url'];
            } else {
                powerpress_page_message_add_error(__('Error saving Apple Podcasts image', 'powerpress'));
                $error = true;
            }
        }
    }
}
if (isset($_POST['pp_start']['title'])) {
    $SaveSettings = powerpress_stripslashes($_POST['pp_start']);
    if (isset($previewImageURL)) {
        unset($SaveSettings['itunes_image']);
        $SaveSettings['itunes_image'] = $previewImageURL;
    }
    powerpress_save_settings($SaveSettings, 'powerpress_feed_podcast');
    if (isset($GeneralSettings['blubrry_hosting']) && $GeneralSettings['blubrry_hosting'] != null) {
        echo '<script>window.location.href = "' . admin_url("admin.php?page=powerpressadmin_basic") . '";</script>';
    } else {
        echo '<script>window.location.href = "' . admin_url("admin.php?page=" . htmlspecialchars($_GET['page']) . "&step=nohost") . '";</script>';
    }
}

?>

<div class="wrap">
    <div class="pp_container" style="padding-bottom: 0">
        <div class="onboarding_header">
            <h4 style="margin: 0;"><?php echo __('Create your Podcast', 'powerpress'); ?></h4>
            <h5 style="margin: 0;"><?php echo __('Enter your new podcast\'s information and contact email.', 'powerpress'); ?></h5>
        </div>
        <hr class="pp_align-center" />

            <section id="one" class="pp_wrapper">

                <div class="pp_flex-grid">
                    <div class="pp_col" style="margin-left: 0;">
                        <form id="basic-feed" enctype="multipart/form-data" action="" method="post">
                            <h4 style="margin-bottom: 2ch;"><?php echo __('Podcaster Information', 'powerpress'); ?></h4>
                            <div style="width: 200%;">
                                <div class="pp_form-group" style="display: inline-block; width: 40%;">
                                    <div class="pp_input-field-thirds">
                                        <input id="input-name" style="width: 90%;" type="text" name="pp_start[name]" class="pp_outlined" value="<?php echo htmlspecialchars($talent_name); ?>" placeholder="<?php echo __('Enter the name of the podcast host', 'powerpress'); ?>">
                                        <label for="input-name" id="name-label" style="left: 4px;"><?php echo __('Name', 'powerpress'); ?></label>
                                        <script>
                                            jQuery("#input-name").on("input", function(el) {
                                                jQuery("#name-label").css("display", "inline-block");
                                                jQuery("#input-name").attr("placeholder", "");
                                            });
                                        </script>
                                    </div>
                                </div>
                                <div class="pp_form-group" style="display: inline-block; width: 40%;">
                                    <div class="pp_input-field-thirds">
                                        <input id="input-email" style="width: 90%;" type="text" name="pp_start[email]" class="pp_outlined" value="<?php echo htmlspecialchars($blogEmail); ?>" placeholder="<?php echo __('Enter the email for your podcast', 'powerpress'); ?>">
                                        <label for="input-email" id="email-label" style="left: 4px;"><?php echo __('Email', 'powerpress'); ?></label>
                                        <script>
                                            jQuery("#input-email").on("input", function(el) {
                                                jQuery("#email-label").css("display", "inline-block");
                                                jQuery("#input-email").attr("placeholder", "");
                                            });
                                        </script>
                                    </div>
                                </div>
                            </div>
                            <h4 style="margin-bottom: 2em;"><?php echo __('Podcast Information', 'powerpress'); ?></h4>
                            <div class="">
                                <div class="pp_form-group">
                                    <div class="pp_input-field-thirds">
                                        <input id="input-title" type="text" name="pp_start[title]" class="pp_outlined" value="<?php echo esc_attr($title); ?>" placeholder="<?php echo __('Enter the title of your podcast', 'powerpress'); ?>">
                                        <label for="input-title" id="title-label"><?php echo __('Show Name', 'powerpress'); ?></label>
                                        <script>
                                            jQuery("#input-title").on("input", function(el) {
                                                jQuery("#title-label").css("display", "inline-block");
                                                jQuery("#input-title").attr("placeholder", "");
                                            });
                                        </script>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div>
                                    <div class="pp_input-field-thirds">
                                        <textarea id="podcast-description" type="text" name="pp_start[description]" class="pp_outlined" placeholder="<?php echo __('Enter a brief description of your show.', 'powerpress'); ?>"><?php echo htmlspecialchars($feed_desc); ?></textarea>
                                        <label style="left: -3px;" for="podcast-description" id="description-label"><?php echo __('Show Description', 'powerpress'); ?></label>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="pp_form-group" style="display: inline-block;">
                                    <div class="pp_input-field-thirds" style="width: ">
                                        <select id="apple_cat" name="pp_start[apple_cat_1]" class="pp_outlined">
                                            <?php

                                            echo '<option value="">'. __('Select Category', 'powerpress') .'</option>';

                                            foreach( $AppleCategories as $value=> $desc ) {
                                                echo "\t<option value=\"$value\"" . ($category == $value ? ' selected' : '') . ">" . htmlspecialchars($desc) . "</option>\n";
                                            }
                                            reset($AppleCategories);
                                            ?>
                                        </select>
                                        <label for="apple_cat" style="left: 3px;"><?php echo __('Category','powerpress'); ?></label>
                                    </div>
                                </div>
                                <div class="pp_form-group" style="display: inline-block; margin-left: 2em;">
                                    <p class="label"><?php echo __('Explicit Content','powerpress'); ?></p>
                                    <label><input type="radio" class="explicit-radio" name="pp_start[itunes_explicit]" value="1" <?php echo $explicit == 1 ? 'checked': '' ?> /> <?php echo __('Yes', 'powerpress'); ?></label>
                                    <label style="margin-left:2em;"><input type="radio" class="explicit-radio" name="pp_start[itunes_explicit]" value="2" <?php echo $explicit == 2 ? 'checked': '' ?> /> <?php echo __('No', 'powerpress'); ?></label>
                                </div>
                            </div>
                            <button type="submit" name="basic-feed-submit" class="pp_button" style="visibility: hidden;"><span><?php echo __('Continue', 'powerpress'); ?></span></button>
                        </form>
                    </div>

                    <div id="artwork-onboarding-container" class="pp_col" style="">
                        <form id="artwork" enctype="multipart/form-data" action="" method="post">
                            <div style="margin-left: 3em;">
                                <div id="error-container" style="display: none;"><h5 style="font-weight: bold;color: red;"><img style="vertical-align: middle;margin: 0 5px 3px 0;" src="<?php echo powerpress_get_root_url(); ?>images/onboarding/cancel.svg"><?php echo __('Your image is not valid.', 'powerpress'); ?></h5></div>

                                <div style="margin-bottom: 1ch;">
                                    <p class="label" style="margin-bottom: 0;"><?php echo __('Podcast Artwork','powerpress'); ?></p>
                                    <p style="display: inline-block; font-size: 12px; margin: 0 2em 0 0;"><?php echo __('Do you have Podcast Artwork?', 'powerpress'); ?></p>
                                    <label><input type="radio" class="artwork-radio" name="pp_start[itunes_explicit]" value="1" <?php echo !empty($FeedSettings['itunes_image']) ? 'checked': '' ?> /> <?php echo __('Yes', 'powerpress'); ?></label>
                                    <label style="margin-left:2em;"><input type="radio" class="artwork-radio" name="pp_start[itunes_explicit]" value="0" <?php echo empty($FeedSettings['itunes_image']) ? 'checked': '' ?> /> <?php echo __('No', 'powerpress'); ?></label>
                                </div>
                                <div id="pp-artwork-onboarding-message"<?php echo !empty($FeedSettings['itunes_image']) ? ' style="display: none;"' : ''; ?>>
                                    <p style="font-size: 11px;"><?php echo __('You don’t need artwork to start a podcast, but you will need it to submit your show to distributors like Apple Podcasts.', 'powerpress'); ?>
                                        <a href="https://blubrry.com/support/powerpress-documentation/artwork-2/"><?php echo __('Learn more', 'powerpress'); ?></a>
                                    </p>
                                </div>
                                <div id="pp-artwork-upload-container"<?php echo empty($FeedSettings['itunes_image']) ? ' style="display: none;"' : ''; ?>>
                                    <div>
                                        <div id="upload-artwork-button" onclick="document.getElementById('FileAttachment').click();">
                                            <img style="color: #3c434a; vertical-align: middle; height: 20px; margin-right: 6px;" src="<?php echo powerpress_get_root_url(); ?>images/onboarding/image.svg" />
                                            <span style="vertical-align: middle; line-height: 24px;"><?php echo __('Upload Artwork', 'powerpress'); ?></span>
                                            <input type="file" id="FileAttachment" name="itunes_image_file" accept="image/*" class="pp_file_upload" style="display: none;" />
                                        </div>
                                        <input type="text" id="filePath" readonly class="pp_outlined" style="margin: 0 0 1ch 0; display: inline-block;" placeholder="Upload your show artwork" <?php echo empty($FeedSettings['itunes_image']) ? '' : "value='" . esc_attr($FeedSettings['itunes_image']) . "'"  ?>>
                                    </div>
                                    <div class="pp_flex-grid" id="showbasics_artwork">
                                        <div class="pp_col" id="showbasics_artwork_upload" style="margin-left: 0; flex: 0.5;">
                                            <input id="itunes_image" type="hidden" name="pp_start[itunes_image]" <?php echo !empty($FeedSettings['itunes_image']) ? "value='" . esc_attr($FeedSettings['itunes_image']) . "'" : ""  ?>>
                                            <?php
                                            if (!isset($previewImageURL)) {
                                                $previewImageURL = !empty($FeedSettings['itunes_image']) ? $FeedSettings['itunes_image'] : powerpress_get_root_url() . "itunes_default.jpg";
                                            } ?>
                                            <img id="preview_image" class="image_wrapper" src="<?php echo htmlspecialchars($previewImageURL); ?>" alt="Podcast Artwork Preview">
                                            <input type="hidden" name="basic_details" id="basic-details">
                                        </div>
                                        <div class="pp_col" style="margin: 0;">
                                            <div id="artwork-spec">
                                                <strong><?php echo __('Artwork Requirements', 'powerpress'); ?></strong><br />
                                                <img style="display: none;" src="<?php echo powerpress_get_root_url(); ?>images/onboarding/checkmark.svg" id="size-icon" class="success-fail-icon">
                                                <p class="pp-smaller-text"><?php echo __('Minimum size: 1400px x 1400px', 'powerpress'); ?></p> <br />
                                                <img style="display: none;" src="<?php echo powerpress_get_root_url(); ?>images/onboarding/checkmark.svg" id="type-icon" class="success-fail-icon">
                                                <p class="pp-smaller-text"><?php echo __('.jpg or .png', 'powerpress'); ?></p> <br />
                                                <img style="display: none;" src="<?php echo powerpress_get_root_url(); ?>images/onboarding/checkmark.svg" id="colorspace-icon" class="success-fail-icon">
                                                <p class="pp-smaller-text"><?php echo __('RGB color space', 'powerpress'); ?></p> <br />
                                                <a href="https://blubrry.com/support/powerpress-documentation/artwork-2/"><?php echo __('Learn more about Podcast Artwork', 'powerpress'); ?></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="artwork-submit" class="pp_button" style="padding: 0; visibility: hidden;"><span><?php echo __('Continue', 'powerpress'); ?></span></button>
                        </form>
                        <div class="pp_col">
                            <div class="pp_button-container" id="show_basics_continue" style="">
                                <button id="continue-button" type="button" name="submit" class="pp_button"><span><?php echo __('Continue', 'powerpress'); ?></span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
    </div>
</div>
<script>
    function verifyImage() {
        var img = new Image();
        img.onload = function() {
            let url = jQuery('#preview_image').attr("src");
            jQuery("#itunes_image").val(url);

            let width = this.naturalWidth;
            let height = this.naturalHeight;
            if (width != height || width > 3000 || width < 1400) {
                jQuery("#size-icon").removeAttr('src');
                jQuery("#size-icon").attr('src', '<?php echo powerpress_get_root_url(); ?>images/onboarding/cancel.svg');
            }
            jQuery("#size-icon").removeAttr('style');
            jQuery("#size-icon").attr('style', 'display: inline-block');
            if (!url.toLowerCase().includes('.jpg') && !url.toLowerCase().includes('.png')) {
                jQuery("#type-icon").removeAttr('src');
                jQuery("#type-icon").attr('src', '<?php echo powerpress_get_root_url(); ?>images/onboarding/cancel.svg')
            }
            jQuery("#type-icon").removeAttr('style');
            jQuery("#type-icon").attr('style', 'display: inline-block');

            let validate_url = 'https://castfeedvalidator.com/validate_colorspace?artwork-url=' + encodeURIComponent(url);
            jQuery("#colorspace-icon").removeAttr('src');
            jQuery("#colorspace-icon").attr('src', validate_url);
            jQuery("#colorspace-icon").removeAttr('style');
            jQuery("#colorspace-icon").attr('style', 'display: inline-block');

        };
        let url = jQuery('#preview_image').attr("src");
        img.src = url;
    }
    jQuery(document).ready(function() {
        jQuery("#filePath").val(jQuery("#preview_image").attr('src').replace(/https?:\/\/.*\/uploads\/powerpress\//i, ''));
        let title = jQuery("#input-title").val().replace(new RegExp(' ', 'g'), "_");
        let category = jQuery("#apple_cat").val();
        let explicit = jQuery("input[type=radio]:checked").val();
        let email = jQuery("#input-email").val();
        let description = jQuery("#podcast-description").val().replace(new RegExp("_", 'g'), "{underscore.pp}").replace(new RegExp(' ', 'g'), '_');
        jQuery("#basic-details").val("TITLE: " + title + " CATEGORY: " + category + " EXPLICIT: " + explicit + " EMAIL: " + email + " DESCRIPTION: " + description);
        jQuery("#input-title").on("input", function() {
            refreshDetails();
        });
        jQuery("#podcast-description").on("input", function() {
            refreshDetails();
        });
        jQuery("#apple_cat").on("change", function() {
            refreshDetails();
        });
        jQuery(".explicit-radio").on("change", function() {
            refreshDetails();
        });
        jQuery("#continue-button").on("click", function () {
            let valid_image = true;
            <?php if (!empty($FeedSettings['itunes_image'])) { ?>
            jQuery(".success-fail-icon").each(function (index) {
                if (jQuery(this).attr("src").includes("cancel.svg")) {
                    valid_image = false;
                }
            });
            <?php } ?>
            if (valid_image) {
                jQuery('#artwork :input').not(':submit').clone().hide().appendTo('#basic-feed');
                jQuery("#basic-feed").submit();
            } else {
                jQuery("#error-container").removeAttr('style');
            }
        });
        jQuery('.artwork-radio').on("change", function(e) {
            if (e.currentTarget.value == 1) { // selected yes
                jQuery('#pp-artwork-upload-container').removeAttr('style');
                jQuery('#pp-artwork-onboarding-message').attr('style', 'display: none');
            } else { // selected no
                jQuery('#pp-artwork-onboarding-message').removeAttr('style');
                jQuery('#pp-artwork-upload-container').attr('style', 'display: none');
            }
        });
        verifyImage();
    });

    function refreshDetails() {
        let title = jQuery("#input-title").val().replace(new RegExp(' ', 'g'), "_");
        let category = jQuery("#apple_cat").val();
        let explicit = jQuery(".explicit-radio:checked").val();
        let email = jQuery("#input-email").val();
        let name = jQuery("#input-name").val();
        let description = jQuery("#podcast-description").val().replace(new RegExp("_", 'g'), "{underscore.pp}").replace(new RegExp(' ', 'g'), '_');
        jQuery("#basic-details").val("TITLE: " + title + " CATEGORY: " + category + " EXPLICIT: " + explicit + " EMAIL: " + email + " DESCRIPTION: " + description + " NAME: " + name);
    }

    document.getElementById("FileAttachment").onchange = function () {
        jQuery("#artwork").submit();
    };
</script>
