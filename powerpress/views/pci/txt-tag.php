<!--
TXT tag
-->
<?php
// Data Normalization
if (!empty($DataSource['txt_tag'])) {
    $first = is_array($DataSource['txt_tag']) ? reset($DataSource['txt_tag']) : null;
    
    // nested
    if (is_array($first) && isset($first['tag'])) {
        $section_data = $DataSource['txt_tag'];
    }
    // string (legacy)
    else {
        $section_data = [
            1 => [
                'tag' => $DataSource['txt_tag'],
                'purpose' => ''
            ]
        ];
    }
}
$has_existing = !empty($section_data);

// State Settings
$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs Item Settings
$section_header = ($config['context'] === 'item') ? __('Optional Text Tag', 'powerpress') : '';
?>
<style>
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

label[class^="pp-label"] {
    position: relative;
    top: 9px;
    padding: 0 9px 0 2px;
    left: 9px;
    background-color: white;
    border-radius: 5px;
    font-size: 12px;
}

</style>


<div class="pp-section-content" 
     id="txt-tag-container-<?php echo $FeedSlug; ?>"
     style="padding-top: 1vh;">

    <div style="cursor: pointer;"
        data-action="collapse"
        onclick="toggleVisibility(this, 'txt-tag-collapse-<?php echo $FeedSlug; ?>'); return false;">

        <!-- SECTION TITLE -->
        <div class="row justify-content-between">
            <div class="col-sm-9">
                <h4 class="pp-section-title">
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
                    <?php echo __('Add custom metadata for niche use cases, modeled after DNS TXT records for maximum flexibility.', 'powerpress'); ?>
                </p>
            </div>
        </div>
    </div>



    <div id="txt-tag-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>;">
        <!-- FORM SECTION -->
        <div id="txt-tag-form-container-<?php echo $FeedSlug; ?>">
            <div data-component="txt-tag-form"
                style="margin-bottom: 10px; margin-top: 20px; padding: 20px; background-color: #e3f2fd; 
                   border-radius: 5px; border-left: 4px solid #1976d2;">

                <div class="row d-flex align-items-center">
                    <!-- Tag Content Input -->
                    <div class="col-lg-5">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Tag Content', 'powerpress'); ?>
                        </label>
                            <textarea value=""
                                id="txt-tag-content-input-<?php echo $FeedSlug; ?>"
                                class="pp-settings-text-input"
                                maxlength="3999"
                                rows="3"
                                style="width: 100%; font-size: 14px; resize: vertical;"></textarea>
                        <small class="text-muted" style="display: block; margin-top: 4px; margin-bottom: 0;">
                            <?php echo __('Free-form text content (max 4000 characters)', 'powerpress'); ?>
                        </small>
                    </div>

                    <!-- Purpose Input -->
                    <div class="col-lg-5">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Purpose (Optional)', 'powerpress'); ?>
                        </label>
                            <input value=""
                                id="txt-tag-purpose-input-<?php echo $FeedSlug; ?>"
                                class="pp-settings-text-input"
                                type="text"
                                maxlength="127"
                                style="width: 100%; font-size: 14px;" />
                        <small class="text-muted" style="display: block; margin-top: 4px; margin-bottom: 0;">
                            <?php echo __('Optional purpose description (max 128 characters)', 'powerpress'); ?>
                        </small>
                    </div>

                    <!-- Add Button -->
                    <div class="col-lg-2 d-flex align-items-center">
                        <button type="button"
                            data-action="add-txt-tag"
                            style="border: none; background: #1876d2; color: white;
                                       padding: 10px 20px; width: 100%; font-size: 14px;
                                       cursor: pointer; border-radius: 5px; margin-top: 10px;"
                            title="Add text tag">
                            <?php echo __('Add Tag', 'powerpress'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ERROR MESSAGE SECTION -->
        <div id="txt-tag-error-<?php echo $FeedSlug; ?>"
            class="txt-tag-error"
            style="display: none; margin-bottom: 10px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24">
            <!-- Error Messages -->
        </div>

        <!-- TABLE SECTION -->
        <div id="txt-tag-table-container-<?php echo $FeedSlug; ?>">
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="txt-tag-table"
                    style="width: 99%; border-collapse: separate; border-spacing: 0; border: 1px solid #ddd; background: #fff;">
                    <thead>
                        <tr style="background: #e9ecef; border-bottom: 2px solid #ddd">
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 50%;"><?php echo __('Tag Content', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 30%;"><?php echo __('Purpose', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 20%;"><?php echo __('Actions', 'powerpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($section_data as $index => $txt_tag) {
                                $tagId = $index + 1;
                        ?>
                                <tr data-txt-tag-id="<?php echo (int)$tagId; ?>">
                                    <!-- Tag -->
                                    <td style="padding: 10px;">
                                        <?php
                                            // truncate oversized display
                                            $tag_content = $txt_tag['tag'];
                                            $display_text = (mb_strlen($tag_content) > 64) ? mb_substr($tag_content, 0, 61) . '...' : $tag_content;
                                        ?>
                                        <div style="font-size: 14px; line-height: 1.4; max-height: 60px; overflow-y: auto;" title="<?php echo esc_attr($tag_content); ?>">
                                            <?php echo esc_html($display_text); ?>
                                        </div>
                                    </td>

                                    <!-- Purpose -->
                                    <td style="padding: 10px;">
                                        <?php
                                            // truncate oversized display
                                            $tag_purpose = $txt_tag['purpose'] ?? '-';
                                            $display_purpose = (mb_strlen($tag_purpose) > 32) ? mb_substr($tag_purpose, 0, 29) . '...' : $tag_purpose;
                                        ?>
                                        <span title="<?php echo esc_attr($tag_purpose); ?>"><?php echo esc_html($display_purpose); ?></span>
                                    </td>

                                    <!-- Edit + Remove Button -->
                                    <td style="padding: 10px; text-align: center;">
                                        <button type="button"
                                            data-action="edit-txt-tag"
                                            data-txt-tag-id="<?php echo (int)$tagId; ?>"
                                            style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                                                   border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                                                   font-size: 12px;"
                                            title="<?php echo esc_attr(__('Edit this txt tag', 'powerpress')); ?>">
                                            <?php echo __('Edit', 'powerpress'); ?>
                                        </button>
                                        <button type="button"
                                            class="txt-tag-remove-btn"
                                            data-action="remove-txt-tag"
                                            data-txt-tag-id="<?php echo (int)$tagId; ?>"
                                            style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                                            title="Remove this text tag">
                                            &times;
                                        </button>
                                    </td>

                                    <!-- Hidden input for saving -->
                                    <td style="display: none;">
                                        <input type="hidden" name="<?php echo $namePrefix?>[txt_tag][<?php echo (int)$tagId; ?>][tag]" value="<?php echo esc_attr($txt_tag['tag']); ?>" />
                                        <input type="hidden" name="<?php echo $namePrefix?>[txt_tag][<?php echo (int)$tagId; ?>][purpose]" value="<?php echo esc_attr($txt_tag['purpose'] ?? ''); ?>" />
                                    </td>
                                </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty Table Message -->
        <div id="txt-tag-table-message-<?php echo $FeedSlug; ?>" style="display: none;">
            <div class="no-txt-tags-message" 
                 style="text-align: center; padding :20px; color: #666;">
                <p><?php echo __('No text tags added yet. Use the form above to add custom text tags.', 'powerpress'); ?></p>
            </div>
        </div>
    </div>

    <!-- TEMPLATE SECTION -->
    <template id="txt-tag-row-template-<?php echo $FeedSlug; ?>">
        <tr data-txt-tag-id="">
            <!-- Tag -->
            <td style="padding: 10px;" data-cell="tag">
                <div style="font-size: 14px; line-height: 1.4; max-height: 60px; overflow-y: auto;"></div>
            </td>
            <!-- Purpose -->
            <td style="padding: 10px;" data-cell="purpose">–</td>
            <!-- Actions -->
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    data-action="edit-txt-tag"
                    data-txt-tag-id=""
                    style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                            border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                            font-size: 12px;"
                    title="<?php echo __('Edit this txt tag', 'powerpress'); ?>">
                    <?php echo __('Edit', 'powerpress'); ?>
                </button>
                <button type="button"
                    class="txt-tag-remove-btn"
                    data-action="remove-txt-tag"
                    data-txt-tag-id=""
                    style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                    title="<?php echo __('Remove this txt tag', 'powerpress'); ?>">
                    &times;
                </button>
            </td>
            <!-- Hidden input save fields -->
            <td style="display:none;">
                <input type="hidden" name="<?php echo $namePrefix; ?>[txt_tag][__ID__][tag]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[txt_tag][__ID__][purpose]" value="">
            </td>
        </tr>
    </template>

</div>
<br>