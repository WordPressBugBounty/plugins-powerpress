<!-- 
SOUNDBITES TEMPLATE 
-->
<?php
// Data Normalization
// nested
if (!empty($DataSource['soundbites']) && is_array($DataSource['soundbites'])) {
    foreach ($DataSource['soundbites'] as $id => $soundbite) {
        if ((empty($soundbite)) || !is_array($soundbite)) continue;
        // check required fields
        if (empty($soundbite['start']) || empty($soundbite['duration'])) continue;

        $section_data[] = [
            'start' => $soundbite['start'],
            'duration' => $soundbite['duration'],
            'title' => $soundbite['title'] ?? ''
        ];
    }
}
// paralellized (legacy)
else if (!empty($DataSource['soundbite_starts'])) {
    foreach($DataSource['soundbite_starts'] as $idx => $soundbite) {
        // check required fields
        if (!empty($soundbite) && !empty($soundbite['duration'])) {
            $section_data[] = [
                'start' => $soundbite,
                'duration' => $DataSource['soundbite_durations'][$idx],
                'title' => $DataSource['soundbite_titles'][$idx] ?? ''
            ];
        }
    }
}
$has_existing = !empty($section_data);

// State Settings
$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs. Item Settings
$section_header = ($config['context'] === 'item') ? __('Soundbites', 'powerpress') : '';
$inherit_visibility = ($config['context'] === 'item') ? '' : 'none';
?>
<style>
    .time-container {
        width: 100px;
    }

    .time-input {
        background-image: url("wp-content/plugins/powerpress/images/time-stamps.png");
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

    .pp-box-text {
        font-size: 14px;
        margin-top: 2ch;
        margin-bottom: 0;
        font-family: Roboto, sans-serif;
        display: block;
    }

    input.pp-settings-text-input, textarea.pp-settings-text-input, input.pp-settings-text-input:focus, textarea.pp-settings-text-input:focus {
        border-radius: 4px;
        background-color: white;
        border: 1px solid #B1B1B1;
        font-size: 14px;
        padding: 2ch 1em 2ch 1em;
        width: 100%;
    }

    label[class*="pp-label"] {
        position: relative;
        top: 9px;
        padding: 0 9px 0 2px;
        left: 9px;
        font-size: 12px;
        border-radius: 5px;
    }
</style>                

<!-- Soundbite Template -->
<div class="pp-section-content" id="soundbite-container-<?php echo $FeedSlug; ?>">

    <div style="cursor: pointer;"
        data-action="collapse"
        onclick="toggleVisibility(this, 'soundbite-collapse-<?php echo $FeedSlug; ?>'); return false;">
                    

        <!-- SECTION TITLE -->
        <div class="row justify-content-between">
            <div class="col-sm-9">
                <h4 class="pp-section-title-block" style="width: auto !important;">
                    <?php echo esc_html(__( $section_header, 'powerpress')); ?>
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
                    <?php echo __("Highlight specific parts of your podcast episode with a soundbite. 
                                    You can use this feature to create episode previews, generate audiograms,
                                    and highlight important parts of your episode. When using this feature, 
                                    please note that the soundbite is linked to the audio/video source of the episode.", 'powerpress'); ?>
                    <br><br>
                </p>
            </div>
        </div>
    </div>


    <div id="soundbite-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>;">
        <!-- FORM SECTION -->
        <div id="soundbite-form-container-<?php echo $FeedSlug; ?>">
            <div data-component="soundbite-form"
                style="margin-bottom: 10px; padding: 20px; background-color: #e3f2fd;
                        border-radius: 5px; border-left: 4px solid #1976d2;">

                <div class="row d-flex align-items-center justify-content-start">
                    <!-- Title -->
                    <div class="col-lg-4">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Title', 'powerpress'); ?>
                        </label>
                            <input id="soundbite-title-input-<?php echo $FeedSlug; ?>"
                                maxlength="128"
                                class="pp-settings-text-input"
                                style="width: 100%; font-size: 14px;" />
                    </div>

                    <!-- Start Time Input-->
                    <div class="col-lg-3">
                        <label style="margin: 0;">
                            <?php echo __('Start Time', 'powerpress'); ?>
                            <span><small>(HH:MM:SS)</small></span>
                            <div class="time-container">
                                <input id="soundbite-start-input-<?php echo $FeedSlug; ?>"
                                    pattern="([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})"
                                    maxlength="8"
                                    class="time-input"
                                    value="00:00:00"
                                    placeholder="00:00:00"
                                    style="width: 100%; font-size: 14px;" />
                            </div>
                        </label>
                    </div>

                    <!-- Duration -->
                    <div class="col-lg-3">
                        <label style="margin: 0;">
                            <?php echo __('Duration', 'powerpress'); ?>
                            <span><small>(HH:MM:SS)</small></span>
                            <div class="time-container">
                                <input id="soundbite-duration-input-<?php echo $FeedSlug; ?>"
                                    pattern="([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})"
                                    maxlength="8"
                                    class="time-input"
                                    value="00:00:00"
                                    placeholder="00:00:00"
                                    style="width: 100%; font-size: 14px" />
                            </div>
                        </label>
                    </div>

                    <!-- Add Button -->
                    <div class="col-lg-2 d-flex justify-content-end align-items-center">
                        <button type="button"
                            data-action="add-soundbite"
                            style="border: none; background: #1876d2; color: white; 
                                       padding: 10px 20px; width: 100%; font-size: 14px;
                                       cursor: pointer; border-radius: 5px; margin-top: 10px;"
                            title="Add soundbite">
                            <?php echo __('Insert Soundbite', 'powerpress'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ERROR MESSAGE SECTION -->
        <div id="soundbite-error-<?php echo $FeedSlug; ?>"
            class="soundbite-error"
            style="display: none; margin-bottom: 10px; margin-top: 10px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24">
            <!-- Error Messages -->
        </div>

        <!-- TABLE SECTION -->
        <div id="soundbite-table-container-<?php echo $FeedSlug; ?>">
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="soundbite-table"
                    style="width: 99%; border-collapse: separate; border-spacing: 0; border: 1px solid #ddd; background: #fff;">
                    <thead>
                        <tr style="background: #e9ecef; border-bottom: 2px solid #ddd">
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 40%;"><?php echo __('Title/Description', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%;"><?php echo __('Start Time', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%;"><?php echo __('Duration', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 20%;"><?php echo __('Actions', 'powerpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Pre-Generation -->
                        <?php
                        foreach ($section_data as $index => $data) {
                            $soundbiteId = $index + 1;
                        ?>
                            <tr data-soundbite-id="<?php echo (int)$soundbiteId; ?>">
                                <!-- Title -->
                                <td style="padding: 10px;"><?php echo esc_html($data['title']); ?></td>

                                <!-- Start Time -->
                                <td style="padding: 10px;"><?php echo esc_html(powerpress_seconds_to_hms($data['start'])); ?></td>

                                <!-- Duration -->
                                <td style="padding: 10px;"><?php echo esc_html(powerpress_seconds_to_hms($data['duration'])); ?></td>

                                <!-- Remove Button -->
                                <td style="padding: 10px; text-align: center;">
                                    <button type="button"
                                        data-action="edit-soundbite"
                                        data-soundbite-id="<?php echo (int)$soundbiteId; ?>"
                                        style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                                               border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                                               font-size: 12px;"
                                        title="<?php echo esc_attr(__('Edit this soundbite', 'powerpress')); ?>">
                                        <?php echo __('Edit', 'powerpress'); ?>
                                    </button>
                                    <button type="button"
                                        class="soundbite-remove-btn"
                                        data-action="remove-soundbite"
                                        data-soundbite-id="<?php echo (int)$soundbiteId; ?>"
                                        style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                                        title="Remove this soundbite">
                                        &times;
                                    </button>
                                </td>

                                <!-- Hidden fields for saving -->
                                <td style="display: none;">
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[soundbites][<?php echo $soundbiteId; ?>][start]" value="<?php echo esc_attr(powerpress_seconds_to_hms($data['start'])); ?>" />
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[soundbites][<?php echo $soundbiteId; ?>][duration]" value="<?php echo esc_attr(powerpress_seconds_to_hms($data['duration'])); ?>" />
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[soundbites][<?php echo $soundbiteId; ?>][title]" value="<?php echo esc_attr($data['title']); ?>" />
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty Table Message -->
        <div id="soundbite-table-message-<?php echo $FeedSlug; ?>"
             style="display:none;">
            <div class="no-soundbites-message"
                style="text-align: center; padding: 20px; color: #666;">
                <p><?php echo __('No soundbites added yet. Use the form above to add soundbites.', 'powerpress'); ?></p>
            </div>
        </div>
    </div>

    <!-- TEMPLATE SECTION -->
    <template id="soundbite-row-template-<?php echo $FeedSlug; ?>">
        <tr data-soundbite-id="">
            <!-- Title -->
            <td style="padding: 10px;" data-cell="title"></td>
            <!-- Start Time -->
            <td style="padding: 10px;" data-cell="startTime"></td>
            <!-- Duration -->
            <td style="padding: 10px;" data-cell="duration"></td>
            <!-- Remove Button -->
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    data-action="edit-soundbite"
                    data-alt-enclosure-id=""
                    style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                           border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                           font-size: 12px;"
                    title="<?php echo esc_attr(__('Edit this enclosure', 'powerpress')); ?>">
                    <?php echo __('Edit', 'powerpress'); ?>
                </button>
                <button type="button"
                    class="soundbite-remove-btn"
                    data-action="remove-soundbite"
                    data-soundbite-id=""
                    style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                    title="Remove this soundbite">
                    &times;
                </button>
            </td>
            <!-- Hidden Save inputs -->
            <td style="display:none;">
                <input type="hidden" name="<?php echo $namePrefix; ?>[soundbites][__ID__][title]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[soundbites][__ID__][start]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[soundbites][__ID__][duration]" value="">
            </td>
        </tr>
    </template>

</div>
<br>