<!-- 
Alternate Enclosure Template 
-->
<?php
// Normalilze Data
if (!empty($DataSource['alternate_enclosure']) && is_array($DataSource['alternate_enclosure'])) {
    $section_data = $DataSource['alternate_enclosure'];
}
$has_existing = !empty($section_data);

// State Settings
$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs Item Settings
$section_header = ($config['context'] === 'item') ? __('Alternate Enclosure', 'powerpress') : '';
$GeneralSettings = $config['GeneralSettings'] ?? get_option('powerpress_general', array());
$hosting = $GeneralSettings['blubrry_hosting'] ?? 0;
$program_keyword = $DataSource['program_keyword'] ?? $GeneralSettings['blubrry_program_keyword'] ?? '';
?>
<style>
    label[class*="pp-label"] {
        position: relative;
        top: 9px;
        padding: 0 6px 0 2px;
        left: 9px;
        font-size: 12px;
        border-radius: 5px;
    }
</style>
<div class="pp-section-content"
    id="alternate-enclosure-container-<?php echo $FeedSlug; ?>"
    data-has-hosting="<?php echo esc_html($hosting); ?>"
    data-admin-url="<?php echo esc_attr(admin_url()); ?>"
    style="margin-top: 1vh;">

    <div style="cursor: pointer;"
        data-action="collapse"
        onclick="toggleVisibility(this, 'alternate-enclosure-collapse-<?php echo $FeedSlug; ?>'); return false;">

        <!-- SECTION TITLE -->
        <div class="row justify-content-between">
            <div class="col-sm-9">
                <h4 class="pp-section-title-block" style="width: auto !important;">
                    <?php echo $section_header; ?>
                </h4>
            </div>

            <!-- SECTION COLLAPSE -->
            <div class="col-sm-1 d-flex justify-content-end">
                <button class="btn btn-primary"
                    type="button"
                    data-action="collapse"
                    title="<?php echo $default_title; ?>"
                    style="cursor: pointer; border: none; background: none; color: #1876d2; font-size: 16px;">

                    <?php echo $default_arrow; ?>

                </button>
            </div>
        </div>
        <!-- SECTION DESCRIPTION -->
        <div class="row">
            <div class="col">
                <p class="pp-ep-box-text">
                    <?php echo __('Provide multiple forms of media for your episode content.', 'powerpress'); ?>
                </p>
            </div>
        </div>
    </div>


    <!-- SECTION ADDT INFORMATION -->
    <div class="row justify-space-between">
        <!-- ACCEPTED MEDIA -->
        <div class="col-sm-6">
            <p class="pp-ep-box-text">
                <strong><?php echo __('Supported Link Types:', 'powerpress'); ?></strong><br>
            <ul>
                <li><?php echo __("&#8226 Media Files (mp3, m4a, mp4)", 'powerpress'); ?></li>
                <li><?php echo __("&#8226 Documents (pdf)", 'powerpress'); ?></li>
                <li><?php echo __("&#8226 Media Uploaded to your Blubrry Hosting account.", 'powerpress'); ?></li>
            </ul>
            </p>
        </div>
        <!-- HOSTING STATUS -->
        <div class="col-sm-6">
            <?php
            $hasHosting = $GeneralSettings['blubrry_hosting'] ?? 0;
            // HAS HOSTING W/ BLUBRRY
            if (!empty($hasHosting)) {
            ?>
                <div class="row" style="margin-top: 20px;">
                    <div id="ep-box-blubrry-connected-<?php echo esc_attr($FeedSlug); ?>"
                        class="d-flex align-items-start justify-content-end mr-2 pr-2">
                        <img class="ep-box-blubrry-icon"
                            src="<?php echo esc_url(powerpress_get_root_url()); ?>images/blubrry_icon.png"
                            style="align-items: start;"
                            alt="" />
                        <div class="ep-box-blubrry-info-container ml-2" style="min-width: 20vw;">
                            <h4 class="blubrry-connect-info mb-0" style="color: #28a745;"><?php echo __('Your Blubrry account is connected.', 'powerpress'); ?></h4>
                            <p class="blubrry-connect-info mb-0 text-muted small"><?php echo __('Select or upload media through your', 'powerpress'); ?></p>
                            <p class="blubrry-connect-info mb-0 text-muted small"><?php echo __('Blubrry hosting account.', 'powerpress'); ?></p>
                        </div>
                    </div>
                </div>
                <!-- NO HOSTING, CONNECT TO BLUBRRY? -->
            <?php } else {
                $pp_nonce = powerpress_login_create_nonce();
            ?>
                <div class="row" style="margin-top: 20px;">
                    <div class="d-flex align-items-start justify-content-evenly">
                        <img class="ep-box-blubrry-icon"
                            src="<?php echo esc_url(powerpress_get_root_url()); ?>images/blubrry_icon.png"
                            style="align-items: start;"
                            alt="" />
                        <div class="col-sm-6 ep-box-blubrry-info-container ml-2">
                            <h4 class="blubrry-connect-info mb-0" style="color: #6c757d;"><?php echo __('Blubrry hosting not connected.', 'powerpress'); ?></h4>
                            <p class="blubrry-connect-info mb-0 text-muted small"><?php echo __('Connect your account to upload files directly.', 'powerpress'); ?></p>
                        </div>
                        <a class="button-blubrry"
                            id="ep-box-connect-account-<?php echo $FeedSlug; ?>"
                            title="<?php echo esc_attr(__('Blubrry Services Integration', 'powerpress')); ?>"
                            href="<?php echo esc_attr(add_query_arg('_wpnonce', $pp_nonce, admin_url("admin.php?page=powerpressadmin_onboarding.php&step=blubrrySignin&from=new_post"))); ?>">
                            <div id="ep-box-connect-account-button-<?php echo $FeedSlug; ?>"><?php echo __('Connect to Blubrry', 'powerpress'); ?></div>
                        </a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div id="alternate-enclosure-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>;">
        <!-- FORM SECTION -->
        <div id="alternate-enclosure-form-container-<?php echo $FeedSlug; ?>">

            <!-- Url Form -->
            <div id="alt-enclosure-url-form-<?php echo $FeedSlug; ?>"
                style="margin-bottom: 2vh; margin-top: 2vh; padding: 20px; background-color: #e3f2fd; 
                        border-radius: 5px; border-left: 4px solid #1976d2; display: block;">

                <!-- URL Input -->
                <div class="row d-flex align-items-center">
                    <div class="col-lg-6">
                        <label class="pp-label">
                            <?php echo __('Media URL', 'powerpress'); ?>
                        </label>
                            <input id="alt-enclosure-url-input-<?php echo $FeedSlug; ?>"
                                class="pp-ep-box-input"
                                maxlength="255"
                                value=""
                                data-hosting="0"
                                data-program-keyword=""
                                style="width: 100%; font-size: 14px;" />
                        <small class="text-muted" style="display: block; margin-top: 4px;">
                            <?php echo __('Enter the primary URL for this alternate enclosure', 'powerpress'); ?>
                        </small>
                    </div>
                    <div class="col-lg-3 d-flex align-items-center"
                        style="padding-top: 10px;">
                        <!-- Choose File Button -->
                        <?php if (!empty($GeneralSettings['blubrry_hosting'])):
                            $choose_url_href = add_query_arg(array(
                                'action' => 'powerpress-jquery-media',
                                'podcast-feed' => $FeedSlug,
                                'target_field' => 'alt-enclosure-url-input-' . $FeedSlug,
                                'KeepThis' => 'true',
                                'TB_iframe' => 'true',
                                'modal' => 'false',
                            ), admin_url());
                        ?>
                            <a class="btn btn-primary thickbox"
                                href="<?php echo esc_attr($choose_url_href); ?>"
                                style="text-decoration: none; border: none; border-radius: 5px; background: #748ffc; color: white;
                                       padding: 10px 20px; width: 100%; font-size: 14px; cursor: pointer; text-align: center;">
                                <?php echo __('Choose File', 'powerpress'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <!-- Add Button -->
                    <div class=" col-lg-3 d-flex align-items-center"
                        style="padding-top: 10px;">
                        <button type="button"
                            data-action="check-alt-enclosure"
                            style="border: none; border-radius: 5px; background: #1976d2; color: white;
                                       padding: 10px 20px; width: 100%; font-size: 14px; cursor: pointer; text-align: center;">
                            <?php echo __('Continue', 'powerpress'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Detailed Form -->
            <div id="alt-enclosure-details-form-<?php echo $FeedSlug; ?>"
                data-hosting=""
                data-program-keyword=""
                style="display: none; margin-bottom: 2vh; margin-top: 2vh; padding: 20px; background-color: #e8f5e8; 
                        border-radius: 5px; border-left: 4px solid #28a745;">

                <h4 style="margin-top: 0;"><?php echo __('Advanced Alternate Enclosure Options', 'powerpress'); ?></h4>
                <p id="alt-enclosure-url-display-<?php echo $FeedSlug; ?>" style="margin-bottom: 15px; color: #666; font-family: monospace;"></p>

                <!-- Row 1 -->
                <div class="row d-flex align-items-center">
                    <div class="col-lg-6">
                        <label class="pp-label">
                            <?php echo __('Title', 'powerpress'); ?>
                        </label>
                            <input id="confirm-alt-title-<?php echo $FeedSlug; ?>" 
                                   class="pp-ep-box-input" 
                                   maxlength="32" 
                                   style="width: 100%; font-size: 14px;" />
                    </div>
                    <div class="col-lg-2">
                        <label class="pp-label">
                            <?php echo __('Bitrate', 'powerpress'); ?>
                        </label>
                            <span class="info-icon" 
                                title="<?php echo esc_attr(__('examples: 128, 192, 320 (kbps)', 'powerpress')); ?>"
                                style="display: inline-block; width: 16px; 
                                    height: 16px; line-height: 16px; 
                                    text-align: center; background: #1876d2;
                                    color: white; border-radius: 50%; 
                                    font-size: 12px; cursor: help; margin-left: 10px;">
                                                
                                    ?
                                                
                            </span>
                            <input id="confirm-alt-bitrate-<?php echo $FeedSlug; ?>" 
                                   class="pp-ep-box-input" 
                                   placeholder="192 (kbps)"
                                   style="width: 100%; font-size: 14px;" />
                    </div>
                    <div class="col-lg-2">
                        <label class="pp-label">
                            <?php echo __('Height', 'powerpress'); ?>
                        </label>
                            <span class="info-icon" 
                                title="<?php echo esc_attr(__('examples: 1080, 720, 480 (px)', 'powerpress')); ?>"
                                style="display: inline-block; width: 16px; 
                                    height: 16px; line-height: 16px; 
                                    text-align: center; background: #1876d2;
                                    color: white; border-radius: 50%; 
                                    font-size: 12px; cursor: help; margin-left: 10px;">
                                                
                                    ?

                            </span>
                            <input id="confirm-alt-height-<?php echo $FeedSlug; ?>"
                                   class="pp-ep-box-input" 
                                   placeholder="1080 (px)"
                                   style="width: 100%; font-size: 14px;" />
                    </div>
                    <div class="col-lg-2">
                        <label class="pp-label">
                            <?php echo __('Language', 'powerpress'); ?>
                        </label>
                            <select id="confirm-alt-lang-<?php echo $FeedSlug; ?>" 
                                    class="pp-ep-box-input" 
                                    style="width: 100%; font-size: 14px;">
                                <?php echo powerpress_print_select_options_lang_codes(); ?>
                            </select>
                    </div>
                </div>

                <!-- Row 2 -->
                <div class="row d-flex align-items-center" style="margin-top: 15px;">
                    <div class="col-lg-6">
                        <label class="pp-label">
                            <?php echo __('Rel', 'powerpress'); ?>
                        </label>
                            <span class="info-icon" 
                                title="<?php echo esc_attr(__('examples: alternative, default, captions, via', 'powerpress')); ?>"
                                style="display: inline-block; width: 16px; 
                                    height: 16px; line-height: 16px; 
                                    text-align: center; background: #1876d2;
                                    color: white; border-radius: 50%; 
                                    font-size: 12px; cursor: help; margin-left: 10px;">
                                                
                                    ?
                                                
                            </span>
                            <input id="confirm-alt-rel-<?php echo $FeedSlug; ?>" 
                                   class="pp-ep-box-input" 
                                   maxlength="127" 
                                   placeholder="Via"
                                   style="width: 100%; font-size: 14px;" />
                    </div>
                    <div class="col-lg-4">
                        <label class="pp-label">
                            <?php echo __('Codecs', 'powerpress'); ?>
                        </label>
                            <span class="info-icon" 
                                title="<?php echo esc_attr(__('examples: avc1.42E01E, mp4a.40.2, vp9', 'powerpress')); ?>"
                                style="display: inline-block; width: 16px; 
                                    height: 16px; line-height: 16px;
                                    text-align: center; background: #1876d2;
                                    color: white; border-radius: 50%; 
                                    font-size: 12px; cursor: help; margin-left: 10px;">
                                                
                                    ?
                                                
                            </span>
                            <input id="confirm-alt-codecs-<?php echo $FeedSlug; ?>" 
                                   class="pp-ep-box-input"
                                   placeholder="mp4a"
                                   style="width: 100%; font-size: 14px;" />
                        </label>
                    </div>
                    <div class="col-lg-2">
                        <label style="margin: 0; display: flex; align-items: center;">
                            <?php echo __('Is Default', 'powerpress'); ?>
                        </label>
                            <input id="confirm-alt-default-<?php echo $FeedSlug; ?>" 
                                   type="checkbox" 
                                   style="margin-right: 8px;" />
                        <span class="info-icon" 
                            title="<?php echo esc_attr(__('Indicates media is the same as the file from the enclosure', 'powerpress')); ?>"
                            style="display: inline-block; width: 16px; 
                                height: 16px; line-height: 16px; 
                                text-align: center; background: #1876d2;
                                color: white; border-radius: 50%; 
                                font-size: 12px; cursor: help; margin-left: 4px;">
                                                
                                ?

                        </span>
                    </div>
                </div>

                <!-- Additional URIs Section -->
                <div style="margin-top: 20px;">
                    <h5><?php echo __('Additional URIs', 'powerpress'); ?></h5>
                    <div id="uri-inputs-container-<?php echo $FeedSlug; ?>">
                        <?php
                        if (!empty($section_data)) {
                            foreach ($section_data as $enclosureId => $enclosureData) {
                                if (!empty($enclosureData['uris']) && is_array($enclosureData['uris'])) {
                                    foreach ($enclosureData['uris'] as $idx => $uri) {
                                        $uri_value = '';
                                        if (is_array($uri) && !empty($uri['uri'])) {
                                            $uri_value = $uri['uri'];
                                        } 

                                        if ($uri_value) {
                                            $encId = $enclosureId + 1;
                                            $index = $idx + 1;
                        ?>
                                            <div class="uri-input-row" style="margin-bottom: 10px;">
                                                <div class="row d-flex align-items-center">
                                                    <div class="col-lg-10">
                                                        <input type="text"
                                                            class="pp-ep-box-input"
                                                            data-field="alt-enc-uri-input"
                                                            data-alt-enc-index=<?php echo $encId; ?>
                                                            data-uri-index=<?php echo $index; ?>
                                                            maxlength="255"
                                                            style="width: 100%; font-size: 14px;"
                                                            value="<?php echo esc_attr($uri_value); ?>"
                                                            placeholder="<?php echo esc_attr(__('Additional URI / Mirror Link', 'powerpress')); ?>" />
                                                    </div>
                                                    <div class="col-lg-2">
                                                        <button type="button" data-action="remove-uri-input"
                                                            style="border: none; background: #dc3545; color: white; padding: 5px 10px; 
                                                                       border-radius: 3px; cursor: pointer;">
                                                            &times;
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                        <?php
                                        }
                                    }
                                }
                            }
                        }
                        ?>
                    </div>
                    <button type="button"
                        data-action="add-uri-input"
                        style="margin-top: 10px; border: none; background: inherit; color: #1976D2; font-size: 14px; cursor: pointer;">
                        <?php echo __('Add URI +', 'powerpress'); ?>
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="row align-items-center justify-content-between" style="margin-top: 20px;">
                    <div class="col-lg-3" style="margin-top: 20px;">
                        <button type="button"
                            data-action="confirm-alt-enclosure"
                            style="border: none; background: #28a745; color: white; padding: 10px 20px; 
                                       width: 100%; font-size: 14px; cursor: pointer; border-radius: 5px;">
                            <?php echo __('Save Enclosure', 'powerpress'); ?>
                        </button>
                    </div>
                    <div class="col-lg-3" style="margin-top: 20px;">
                        <button type="button"
                            data-action="cancel-alt-enclosure"
                            style="border: none; background: #dc3545; color: white; padding: 10px 20px; 
                                       width: 100%; font-size: 14px; cursor: pointer; border-radius: 5px;">
                            <?php echo __('Cancel', 'powerpress'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ERROR MESSAGE SECTION -->
        <div id="alternate-enclosure-error-<?php echo $FeedSlug; ?>" style="display: none; margin-top: 10px; margin-bottom: 10px; padding: 10px; 
             background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24">
        </div>

        <!-- TABLE SECTION -->
        <div id="alternate-enclosure-table-container-<?php echo $FeedSlug; ?>">
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table style="width: 99%; border-collapse: separate; border-spacing: 0; border: 1px solid #ddd; background: #fff;">
                    <thead>
                        <tr style="background: #e9ecef; border-bottom: 2px solid #ddd">
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 5%"><!-- <?php echo __('Expand', 'powerpress'); ?> --></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 30%"><?php echo __('URL', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 25%"><?php echo __('Title', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 5%"><?php echo __('Bitrate', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 5%"><?php echo __('Height', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 5%"><?php echo __('Lang', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 10%"><?php echo __('Default', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 15%"><?php echo __('Actions', 'powerpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($section_data)) 
                            foreach ($section_data as $index => $data) {
                                $id = $index + 1;
                                if (!empty($data['size'])) $data['length'] = $data['size']
                        ?>
                                <!-- Main row -->
                                <tr data-alt-enclosure-id="<?php echo (int)$id; ?>">
                                    <!-- Expand Button -->
                                    <td style="padding: 10px; text-align: center;">
                                        <button type="button"
                                            class="expand-enclosure-btn"
                                            data-action="toggle-enclosure-details"
                                            data-alt-enclosure-id="<?php echo (int)$id; ?>"
                                            style="border: none; background: #007cba; color: white; padding: 4px 8px; 
                                                   border-radius: 3px; cursor: pointer; font-size: 12px;">
                                            &#9660;
                                        </button>
                                    </td>

                                    <!-- URL -->
                                    <td style="padding: 9px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php
                                        // Check if URL exists and is valid
                                        $url = $data['url'] ?? '';
                                        if (!empty($url)) {
                                            // Extract only the filename from the URL
                                            $path = parse_url($url, PHP_URL_PATH);
                                            $filename = basename($path);

                                            if (empty($filename)) {
                                                $filename = $pathInfo || $url || 'unknown';
                                            }
                                            echo '<span style="font-size: clamp(10px, 2.5vw, 14px);" title="' . esc_attr($url) . '">' . esc_html('/' . $filename) . '</span>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>

                                    <!-- Title -->
                                    <td style="padding: 10px;"><?php echo esc_html(!empty($data['title']) ? $data['title'] : '—'); ?></td>

                                    <!-- Bitrate -->
                                    <td style="padding: 10px;"><?php echo esc_html(!empty($data['bitrate']) ? $data['bitrate'] : '—'); ?></td>

                                    <!-- Height -->
                                    <td style="padding: 10px;"><?php echo esc_html(!empty($data['height']) ? $data['height'] : '—'); ?></td>

                                    <!-- Lang -->
                                    <td style="padding: 10px;"><?php echo esc_html(!empty($data['lang']) ? $data['lang'] : '—'); ?></td>

                                    <!-- Default -->
                                    <td style="padding: 10px; text-align: center;"><?php echo (!empty($data['is_default']) ? '✓' : '—'); ?></td>

                                    <!-- Actions -->
                                    <td style="padding: 10px; text-align: center;">
                                        <button type="button"
                                            data-action="edit-alt-enclosure"
                                            data-alt-enclosure-id="<?php echo (int)$id; ?>"
                                            style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                                                   border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                                                   font-size: 12px;"
                                            title="<?php echo esc_attr(__('Edit this enclosure', 'powerpress')); ?>">
                                            <?php echo __('Edit', 'powerpress'); ?>
                                        </button>
                                        <button type="button"
                                            data-action="remove-alt-enclosure"
                                            data-alt-enclosure-id="<?php echo (int)$id; ?>"
                                            style="border: none; background: #dc3545; color: #fff; padding: 3px 8px; 
                                                   border-radius: 3px; cursor: pointer; font-size: 12px;"
                                            title="<?php echo esc_attr(__('Remove this enclosure', 'powerpress')); ?>">
                                            &times;
                                        </button>
                                    </td>

                                    <!-- Hidden inputs for form data -->
                                    <td data-field="save-data" style="display: none;">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][hosting]" value="<?php echo !empty($data['hosting']) ? esc_attr($data['hosting']) : ''; ?>" id="powerpress_hosting_<?php echo $FeedSlug; ?>_alternate_<?php echo $id; ?>">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][program_keyword]" value="<?php echo (!empty($program_keyword) ? $program_keyword: '') ?>" id="powerpress_program_keyword_<?php echo $FeedSlug; ?>_alternate_<?php echo $id; ?>" />
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][url]" value="<?php echo esc_attr($data['url'] ?? ''); ?>">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][title]" value="<?php echo esc_attr($data['title'] ?? ''); ?>">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][bitrate]" value="<?php echo esc_attr($data['bitrate'] ?? ''); ?>">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][height]" value="<?php echo esc_attr($data['height'] ?? ''); ?>">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][length]" value="<?php echo esc_attr($data['length'] ?? ''); ?>">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][rel]" value="<?php echo esc_attr($data['rel'] ?? ''); ?>">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][lang]" value="<?php echo esc_attr($data['lang'] ?? ''); ?>">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][codecs]" value="<?php echo esc_attr($data['codecs'] ?? ''); ?>">
                                        <?php if (!empty($data['is_default'])): ?>
                                            <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][default]" value="<?php echo (!empty($data['is_default']) ? '1' : ''); ?>">
                                        <?php endif; ?>
                                        <?php
                                        if (!empty($data['uris']) && is_array($data['uris'])) {
                                            foreach ($data['uris'] as $uindex => $uri_data) {
                                                $uriIndex = $uindex + 1;
                                                if (!empty($uri_data['uri'])) { ?>
                                                    <input type="hidden" data-field="alt-enc-save-uri"
                                                        data-alt-enc-idx="<?php echo $id; ?>" 
                                                        data-uri-index="<?php echo $uriIndex; ?>" 
                                                        name="<?php echo $namePrefix; ?>[alternate_enclosure][<?php echo $id; ?>][uris][<?php echo $uriIndex; ?>][uri]" 
                                                        value="<?php echo esc_attr($uri_data['uri']); ?>">
                                                <?php }
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>

                                <!-- Detail Row -->
                                <tr class="alt-enclosure-detail-row" 
                                    data-detail-for="<?php echo (int)$id; ?>"
                                    data-feed-slug="<?php echo $FeedSlug; ?>"
                                    style="display: none; background-color: #f8f9fa;">
                                    <td colspan="4" style="padding: 15px;">
                                        <div class="enclosure-details">
                                            <div><strong><?php echo esc_html__("URL:", 'powerpress'); ?></strong>
                                                <?php echo esc_html($data['url']); ?>
                                            </div>

                                            <h6><?php echo __('Additional Details', 'powerpress'); ?></h6>
                                            <strong><?php echo __('Rel:', 'powerpress'); ?></strong> <?php echo esc_html(!empty($data['rel']) ? $data['rel'] : __('N/A', 'powerpress')); ?><br>
                                            <strong><?php echo __('Codecs:', 'powerpress'); ?></strong> <?php echo esc_html(!empty($data['codecs']) ? $data['codecs'] : __('N/A', 'powerpress')); ?><br>
                                            <strong><?php echo __('Length:', 'powerpress'); ?></strong> <?php echo esc_html(!empty($data['length']) ? $data['length'] : __('N/A', 'powerpress')); ?>
                                        </div>
                                    </td>

                                    <td colspan="4" style="padding: 15px;">
                                        <div class="enclosure-details">
                                            <strong><?php echo __('Additional URIs:', 'powerpress'); ?></strong><br>
                                                <?php if (!empty($data['uris']) && is_array($data['uris'])):
                                                    foreach ($data['uris'] as $uri_data) {
                                                        // Handle both string and array formats
                                                        $uri_value = '';
                                                        if (is_array($uri_data) && !empty($uri_data['uri'])) {
                                                            $uri_value = $uri_data['uri'];
                                                        } elseif (is_string($uri_data) && $uri_data !== '') {
                                                            $uri_value = $uri_data;
                                                        }

                                                        if (empty($uri_value)) continue;
                                                ?>
                                                <div style="font-size: 12px; color: #666; margin-bottom: 4px;">
                                                    <div title="<?php echo esc_attr($uri_value); ?>"
                                                        style=" display: inline-block; max-width: 100%; overflow: hidden;
                                                        text-overflow: ellipsis; white-space: nowrap; font-size: clamp(10px, 2.5vw, 14px);">
                                                        <?php echo esc_html($uri_value); ?>
                                                    </div>
                                                </div>
                                                <?php
                                                    }
                                                else: ?>                                                <div style="font-size:12px; color:#666;">
                                                <em><?php echo __('No additional URIs', 'powerpress'); ?></em>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Empty Table Message -->
        <div id="alternate-enclosure-table-message-<?php echo $FeedSlug; ?>" style="display: none;">
            <div style="text-align: center; padding: 20px; color: #666;">
                <p><?php echo __('No alternate enclosures added yet. Use the form above to add new alternate enclosures.', 'powerpress'); ?></p>
            </div>
        </div>
    </div>

    <!-- TEMPLATE SECTION -->
    <template id="alt-enclosure-row-template-<?php echo $FeedSlug; ?>">
        <tr data-alt-enclosure-id="">
            <!-- Expand Button -->
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    class="expand-enclosure-btn"
                    data-action="toggle-enclosure-details"
                    data-alt-enclosure-id=""
                    style="border: none; background: #007cba; color: white; padding: 4px 8px; 
                               border-radius: 3px; cursor: pointer; font-size: 12px;">
                    &#9660;
                </button>
            </td>

            <!-- URL -->
            <td style="padding: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" data-cell="url"></td>

            <!-- Other cells -->
            <td style="padding: 10px;" data-cell="title"><?php echo __('—', 'powerpress'); ?></td>
            <td style="padding: 10px;" data-cell="bitrate"><?php echo __('—', 'powerpress'); ?></td>
            <td style="padding: 10px;" data-cell="height"><?php echo __('—', 'powerpress'); ?></td>
            <td style="padding: 10px;" data-cell="lang"><?php echo __('—', 'powerpress'); ?></td>
            <td style="padding: 10px; text-align: center;" data-cell="default"><?php echo __('—', 'powerpress'); ?></td>

            <!-- Actions -->
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    data-action="edit-alt-enclosure"
                    data-alt-enclosure-id=""
                    style="border: none; background: #28a745; color: #fff; padding: 3px 8px; 
                               border-radius: 3px; cursor: pointer; margin-right: 5px; font-size: 12px;">
                    <?php echo __('Edit', 'powerpress'); ?>
                </button>
                <button type="button"
                    data-action="remove-alt-enclosure"
                    data-alt-enclosure-id=""
                    style="border: none; background: #dc3545; color: #fff; padding: 3px 8px; 
                               border-radius: 3px; cursor: pointer; font-size: 12px;">
                    &times;
                </button>
            </td>

            <!-- Hidden inputs -->
            <td data-field="save-data" style="display: none;">
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][hosting]" value="" id="powerpress_hosting_<?php echo $FeedSlug; ?>_alternate___ID__" />
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][program_keyword]" value="<?php echo !empty($program_keyword) ? $program_keyword : ''; ?> id="powerpress_program_keyword_<?php echo $FeedSlug; ?>_alternate___ID__" />
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][url]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][title]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][bitrate]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][height]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][length]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][rel]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][lang]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[alternate_enclosure][__ID__][codecs]" value="">
            </td>
        </tr>
    </template>

    <!-- Detail Row Template -->
    <template id="alt-enclosure-detail-template-<?php echo $FeedSlug; ?>">
        <tr class="alt-enclosure-detail-row" 
            data-detail-for="" 
            data-feed-slug="<?php echo $FeedSlug; ?>" 
            style="display: none; background-color: #f8f9fa;">
            <td colspan="4" style="padding: 15px;">
                <div class="enclosure-details">
                    <h6><?php echo __('Additional Details', 'powerpress'); ?></h6>
                    <div><strong><?php echo esc_html__("URL:", 'powerpress'); ?></strong>
                    <div data-cell="url-display"></div>
                </div>
                    <strong><?php echo __('Rel:', 'powerpress'); ?></strong> <span data-cell="rel"><?php echo __('N/A', 'powerpress'); ?></span><br>
                    <strong><?php echo __('Codecs:', 'powerpress'); ?></strong> <span data-cell="codecs"><?php echo __('N/A', 'powerpress'); ?></span><br>
                    <strong><?php echo __('Length:', 'powerpress'); ?></strong> <span data-cell="length"><?php echo __('N/A', 'powerpress'); ?></span>
                </div>
            </td>
            <td colspan="4" style="padding: 15px;">
                <div class="enclosure-details">
                    <strong><?php echo __('Additional URIs:', 'powerpress'); ?></strong><br>
                    <div data-cell="uris"><em><?php echo __('No additional URIs', 'powerpress'); ?></em></div>
                </div>
            </td>
        </tr>
    </template>

    <!-- Uri Input -->
    <template id="uri-input-template-<?php echo $FeedSlug; ?>">
        <div class="uri-input-row" style="margin-bottom: 10px;">
            <div class="row d-flex align-items-center">
                <div class="col-lg-6">
                    <input type="text"
                        class="pp-ep-box-input"
                        data-field="alt-enc-uri-input"
                        maxlength="255"
                        style="width: 100%; font-size: 14px;"
                        placeholder="<?php echo esc_attr(__('Additional URI / Mirror Link', 'powerpress')); ?>" />
                    <input type="hidden" data-field="alt-enc-uri-hosting" value="" />
                    <input type="hidden" data-field="alt-enc-uri-program-keyword" value="" />
                </div>
                <div class="col-lg-4">
                    <a class="btn btn-primary thickbox"
                        data-action="pick-blubrry-uri"
                        href="#"
                        style="border: none; border-radius: 5px; background: #748ffc; color: white;
                               padding: 10px 20px; width: auto; font-size: 14px; cursor: pointer; text-align: center;">
                        <?php echo __('Choose file', 'powerpress'); ?>
                    </a>
                    <button type="button" data-action="remove-uri-input"
                        style="border: none; background: #dc3545; color: white; padding: 5px 10px; 
                                   border-radius: 3px; cursor: pointer;">
                        &times;
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
<br>