<!-- 
CONTENT LINK TEMPALTE 
-->
<?php
// Data Normalization
$section_data = $DataSource['content_link'] ?? [];
$has_existing = !empty($section_data);

// State Settings
$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs. Item Settings
$section_header = ($config['context'] === 'item') ? __('Content Link', 'powerpress') : '';
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

label[class*="pp-label"] {
    position: relative;
    top: 9px;
    padding: 0 9px 0 2px;
    left: 9px;
    font-size: 12px;
    border-radius: 5px;
}

</style>
<div class="pp-section-content" id="content-link-container-<?php echo $FeedSlug; ?>" style="padding-top: 1vh;">

    <div style="cursor: pointer;"
        data-action="collapse"
        onclick="toggleVisibility(this, 'content-link-collapse-<?php echo $FeedSlug; ?>'); return false;">

        <!-- SECTION TITLE -->
        <div class="row justify-content-between">
            <div class="col-sm-9">
                <h4 class="pp-section-title-block" style="width: auto !important;">
                    <?php echo esc_html(__('Content Link', 'powerpress')); ?>
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
                    <?php echo __("Share an external link, i.e YouTube, Twitch, or another platform where your content can be found. This ensures audiences can access your content on the app or platform of their choice.", 'powerpress'); ?>
                </p>
            </div>
        </div>
    </div>

    <div id="content-link-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>;">
        <!-- FORM SECTION -->
        <div id="content-link-form-container-<?php echo $FeedSlug; ?>">
            <div data-component="content-link-form"
                style="margin-bottom: 2vh; margin-top: 2vh; padding: 20px; background-color: #e3f2fd;
                        border-radius: 5px; border-left: 4px solid #1976d2;">

                <div class="row d-flex align-items-center">
                    <!-- URL Input -->
                    <div class="col-lg-5">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('URL', 'powerpress'); ?>
                        </label>
                            <input id="content-link-url-input-<?php echo $FeedSlug; ?>"
                                class="pp-ep-box-input"
                                type="url"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                        <small class="text-muted" style="display: block; margin-top: 4px;">
                            <?php echo __('e.g. https://www.youtube.com/watch?v=MIeNG64NezY', 'powerpress'); ?>
                        </small>
                    </div>

                    <!-- Label Input -->
                    <div class="col-lg-5">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Label', 'powerpress'); ?>
                        </label>
                            <input id="content-link-label-input-<?php echo $FeedSlug; ?>"
                                class="pp-ep-box-input"
                                type="text"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                        <small class="text-muted" style="display: block; margin-top: 4px;">
                            <?php echo __('e.g. Watch on YouTube', 'powerpress'); ?>
                        </small>
                    </div>

                   <!-- Add Button -->
                    <div class="col-lg-2 d-flex align-items-center">
                        <button type="button"
                            data-action="add-content-link"
                            style="border: none; background: #1876d2; color: white;
                                       padding: 10px 20px; width: 100%; font-size: 14px;
                                       cursor: pointer; border-radius: 5px; margin-top: 10px;"
                            title="Add content link">
                            <?php echo __('Insert Link', 'powerpress'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ERROR MESSAGE SECTION -->
        <div id="content-link-error-<?php echo $FeedSlug; ?>"
            class="content-link-error"
            style="display: none; margin-top: 10px; margin-bottom: 10px; padding: 10px; background-color: #f8d7da;
                    border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24">
            <!-- Error Messages -->
        </div>

        <!-- TABLE SECTION -->
        <div id="content-link-table-container-<?php echo $FeedSlug; ?>">
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="content-link-table"
                    style="width: 99%; border-collapse: separate; border-spacing: 0; border: 1px solid #ddd; background: #fff;">
                    <thead>
                        <tr style="background: #e9ecef; border-bottom: 2px solid #ddd">
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 50%;"><?php echo __('URL', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 40%;"><?php echo __('Label', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 10%;"><?php echo __('Actions', 'powerpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($section_data as $index => $link) {
                            $linkId = $index + 1;
                            if (!empty($link['url'])) {
                        ?>
                                <!-- pre-gen url -->
                                <tr data-content-link-id="<?php echo (int)$linkId; ?>">
                                    <td style="padding:10px;" data-cell="url">
                                        <?php
                                        $raw = trim((string)($link['url'] ?? ''));
                                        if ($raw !== '') {
                                            // strip http(s):// for display
                                            $display = preg_replace('/^https?:\/\//i', '', $raw);
                                            // truncate to 64 chars (61 + '...')
                                            if (mb_strlen($display) > 32) {
                                                $display = mb_substr($display, 0, 29) . '...';
                                            }
                                        ?>
                                            <div
                                                title="<?php echo esc_attr($raw); ?>"
                                                style="display: inline-block; max-width: 100%;
                                                    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                                                    font-size: clamp(10px, 2.5vw, 14px);">
                                                <?php echo esc_html($display); ?>
                                            </div>
                                        <?php
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <!-- pre-gen label -->
                                    <td style="padding: 10px;">
                                        <?php echo esc_html($link['label'] ?? '–'); ?>
                                    </td>
                                    <td style="padding: 10px; text-align: center;">
                                        <button type="button"
                                            data-action="edit-content-link"
                                            data-content-link-id="<?php echo (int)$linkId; ?>"
                                            style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                                                   border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                                                   font-size: 12px;"
                                            title="<?php echo esc_attr(__('Edit this content link', 'powerpress')); ?>">
                                            <?php echo __('Edit', 'powerpress'); ?>
                                        </button>
                                        <button type="button"
                                            class="content-link-remove-btn"
                                            data-action="remove-content-link"
                                            data-content-link-id="<?php echo (int)$linkId; ?>"
                                            style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                                            title="Remove this content link">
                                            &times;
                                        </button>
                                    </td>

                                    <!-- Hidden fields for saving -->
                                    <td style="display: none;">
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[content_link][<?php echo (int)$linkId; ?>][url]" value="<?php echo esc_attr($link['url']); ?>" />
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[content_link][<?php echo (int)$linkId; ?>][label]" value="<?php echo esc_attr($link['label'] ?? ''); ?>" />
                                    </td>
                                </tr>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty Table Message -->
        <div id="content-link-table-message-<?php echo $FeedSlug; ?>" style="display: none;">
            <div class="no-content-links-message" style="text-align: center; padding: 20px; color: #666;">
                <p><?php echo __('No content links added yet. Use the form above to add external links.', 'powerpress'); ?></p>
            </div>
        </div>
    </div>

    <!-- TEMPLATE SECTION -->
    <template id="content-link-row-template-<?php echo $FeedSlug; ?>">
        <tr data-content-link-id="">
            <td style="padding: 10px;" data-cell="url">-</td>
            <td style="padding: 10px;" data-cell="label">–</td>
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    data-action="edit-content-link"
                    data-content-link-id=""
                    style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                           border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                           font-size: 12px;"
                    title="<?php echo __('Edit this content link', 'powerpress'); ?>">
                    <?php echo __('Edit', 'powerpress'); ?>
                </button>
                <button type="button"
                    class="content-link-remove-btn"
                    data-action="remove-content-link"
                    data-content-link-id=""
                    style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                    title="<?php echo __('Remove this content link', 'powerpress'); ?>">
                    &times;
                </button>
            </td>
            <td style="display: none;">
                <input type="hidden" name="<?php echo $namePrefix; ?>[content_link][__ID__][url]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[content_link][__ID__][label]" value="">
            </td>
        </tr>
    </template>

</div>
<br>