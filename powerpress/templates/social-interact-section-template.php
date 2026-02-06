<!-- 
Social Interact Template 
-->
<?php
// Nested
if (isset($DataSource['social_interact']) && is_array($DataSource['social_interact'])) {

    $section_data = $DataSource['social_interact'];
}
// Paralellized (Legacy)
else if (isset($DataSource['social_interact_uri']) && !empty($DataSource['social_interact_uri'])) {
    $section_data[] = [
        'uri' => $DataSource['social_interact_uri'],
        'protocol' => $DataSource['social_interact_protocol'] ?? 'disabled',
        'account_id' => $DataSource['social_interact_account_id'] ?? '',
        'accountUrl' => $DataSource['social_interact_accountUrl'] ?? '',
        'priority' => $DataSource['social_interact_priority'] ?? '1'
    ];
}
$has_existing = !empty($section_data);

// State Settings 
$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs. Item Setting
$section_header = ($config['context'] === 'item') ? __('Social Interact', 'powerpress') : '';
$inherit_visibility = ($config['context'] === 'item') ? '' : 'none';
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
<div class="pp-section-content" id="social-interact-container-<?php echo $FeedSlug; ?>" style="padding-top: 1vh;">

    <div style="cursor: pointer;"
        data-action="collapse"
        onclick="toggleVisibility(this, 'social-interact-collapse-<?php echo $FeedSlug; ?>'); return false;">

        <!-- SECTION TITLE -->
        <div class="row justify-content-between">
            <div class="col-sm-9">
                <h4 class="pp-section-title-block" style="width: auto !important;">
                    <?php echo esc_html(__('Social Interact', 'powerpress')); ?>
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
                    <?php echo __("The Social Interact feature lets podcasters add links to the main post which link to discussion threads for their episode.
                                    The posts are considered the main hubs for all discussions and comments related to the episode.", 'powerpress') ?>
                    <br><br>
                </p>
            </div>
        </div>
    </div>



    <div id="social-interact-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>;">
        <!-- FORM SECTION -->
        <div id="social-interact-form-container-<?php echo $FeedSlug; ?>">
            <div data-component="social-interact-form"
                style="margin-bottom: 10px; margin-top: 2opx; padding: 20px; background-color: #e3f2fd; 
                        border-radius: 5px; border-left: 4px solid #1976d2;">

                <div class="row d-flex align-items-center">
                    <!-- Social Media Post URI -->
                    <div class="col-lg-10">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Social Media Post URI', 'powerpress'); ?>
                        </label>
                            <input id="social-interact-uri-input-<?php echo $FeedSlug; ?>"
                                type="url"
                                class="pp-ep-box-input"
                                style="width: 100%; font-size: 14px;" />
                        <small class="text-muted" style="display: block; margin-top: 4px;">
                            <?php echo __('Enter the URL of a social media post where people can discuss this episode', 'powerpress'); ?>
                        </small>
                    </div>

                    <!-- Insert URL Button -->
                    <div class="col-lg-2">
                        <button type="button"
                            data-action="check-uri"
                            style="border: none; background: #1876d2; color: white; 
                                    padding: 10px 20px; width: 100%; font-size: 14px;
                                    cursor: pointer; border-radius: 5px; margin-top: 10px;"
                            title="Check URL and insert to table">
                            <?php echo __('Insert URL', 'powerpress'); ?>
                        </button>
                    </div>
                </div>

                <!-- Disable Episode Comments -->
                <div class="form-check row align-items-start" style="padding: 10px 20px;">
                    <?php
                    $comments_disabled = !empty($section_data['disable_episode_comments']);
                    ?>
                    <div>
                        <input class="form-check-input me-3 align-items-start" type="checkbox"
                            name="<?php echo $namePrefix; ?>[disable_episode_comments]"
                            id="disable-episode-comments-<?php echo $FeedSlug; ?>"
                            style="width: 1rem; height: 1rem; margin-left: 15px;" <?php checked($comments_disabled); ?>>
                    </div>
                    <div>
                        <label class="form-check-label text-danger" style="font-size: 110%; font-weight: bold;" for="disable-episode-comments-<?php echo $FeedSlug; ?>">
                            <strong><?php echo __('Disable Comments / Interactions for this Episode', 'powerpress'); ?></strong>
                            <div class="text-muted small" style="font-weight: normal;">
                                <?php echo __("Check to signal to podcasting apps that you don't want public comments.", 'powerpress'); ?>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ERROR MESSAGE SECTION -->
        <div id="social-interact-error-<?php echo $FeedSlug; ?>"
            class="social-interact-error"
            style="display: none; margin-top: 10px; padding: 10px; background-color: #f8d7da; 
                   border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24">
            <!-- Error Messages -->
        </div>

        <!-- Manual Protocol Selection (auto-detect failed) -->
        <div id="social-interact-manual-protocol-<?php echo $FeedSlug; ?>"
              style="display: none; margin-top: 10px; padding: 15px; background-color: #fff3cd;
                     border: 1px solid #ffc107; border-radius: 4px; color: #856404">
            <p style="margin-bottom: 10px; font-weight: bold;">
                <?php echo __('Could not auto-detect protocol. Please select manually: ', 'powerpress'); ?>
            </p>
            <div class="align-items-center" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <select id="manual-protocol-select-<?php echo $FeedSlug; ?>"
                        data-field="manual-protocol"
                        style="flex: 1; min-width: 200px; padding: 6px; font-size: 14px; 
                        border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">-- Select Protocol --</option>
                    <option value="activitypub">ActivityPub</option>
                    <option value="twitter">Twitter/X</option>
                    <option value="atproto">AT Protocol (Bluesky)</option>
                    <option value="lightning">Lightning</option>
                    <option value="matrix">Matrix</option>
                    <option value="nostr">Nostr</option>
                    <option value="hive">Hive</option>
                </select>
                <button type="button"
                        data-action="confirm-manual-protocol"
                        style="padding: 8px 20px; background: #1876d2; color: white; border: none;
                               border-radius: 4px; cursor: pointer; font-size: 14px; white-space: nowrap;">
                    <?php echo __('Confirm', 'powerpress'); ?>
                </button>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div id="social-interact-table-container-<?php echo $FeedSlug; ?>"
            style="opacity: 1; pointer-events: auto">
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="social-interact-table"
                    style="width: 99%; margin-top: 10px; border-collapse: separate; border-spacing: 0; border: 1px solid #ddd; background: #fff;">
                    <thead>
                        <tr style="background: #e9ecef; border-bottom: 2px solid #ddd">
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 10%;"><?php echo __('Priority', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%;"><?php echo __('URI', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 10%;"><?php echo __('Protocol', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%;"><?php echo __('Account ID', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 30%;"><?php echo __('Account URL', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 10%;"><?php echo __('Actions', 'powerpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($section_data as $index => $data) {
                            $socialInteractId = $index + 1;
                        ?>
                            <tr data-social-interact-id="<?php echo (int)$socialInteractId; ?>">
                                <!-- Priority -->
                                <td style="padding: 10px;">
                                    <input type="number"
                                        class="pp-settings-text-input"
                                        data-field="priority"
                                        value="<?php echo esc_attr($data['priority'] ?? '1'); ?>"
                                        name="<?php echo $namePrefix; ?>[social_interact][<?php echo (int)$socialInteractId; ?>][priority]"
                                        style="width: 100%; font-size: 12px; padding: 4px;"
                                        min="1" max="999" />
                                </td>

                                <!-- Uri -->
                                <td style="padding: 9px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <div data-field="uri-display">
                                        <?php
                                        // Check if URI exists and is valid
                                        $uri = $data['uri'] ?? '';
                                        if (!empty($uri)) {
                                            // Strip http:// or https:// from display text and truncate 
                                            $displayUrl = preg_replace('/^https?:\/\//', '', $uri);
                                            if (strlen($displayUrl) > 32) {
                                                $displayUrl = substr($displayUrl, 0, 29) . '...';
                                            }
                                            echo '    <span style="font-size: clamp(10px, 2.5vw, 14px);" title="' . esc_attr($uri) . '">' . esc_html($displayUrl) . '</span>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                </div>
                                    <!-- URI Hidden Save -->
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[social_interact][<?php echo (int)$socialInteractId; ?>][uri]" value="<?php echo esc_attr($data['uri']); ?>" />
                                </td>

                                <!-- Protocol Select -->
                                <td style="padding: 10px;">
                                    <select data-field="protocol"
                                        class="pp-settings-text-input"
                                        data-social-interact-id="<?php echo (int)$socialInteractId; ?>"
                                        name="<?php echo $namePrefix; ?>[social_interact][<?php echo (int)$socialInteractId; ?>][protocol]"
                                        style="width: 100%; font-size: 12px;">
                                        <option value="disabled" <?php selected($data['protocol'], 'disabled'); ?>><?php echo __('Disabled', 'powerpress'); ?></option>
                                        <option value="activitypub" <?php selected($data['protocol'], 'activitypub'); ?>><?php echo __('ActivityPub', 'powerpress'); ?></option>
                                        <option value="twitter" <?php selected($data['protocol'], 'twitter'); ?>><?php echo __('Twitter/X', 'powerpress'); ?></option>
                                        <option value="atproto" <?php selected($data['protocol'], 'atproto'); ?>><?php echo __('AT Protocol (Bluesky)', 'powerpress'); ?></option>
                                        <option value="lightning" <?php selected($data['protocol'], 'lightning'); ?>><?php echo __('Lightning', 'powerpress'); ?></option>
                                        <option value="matrix" <?php selected($data['protocol'], 'matrix'); ?>><?php echo __('Matrix', 'powerpress'); ?></option>
                                        <option value="nostr" <?php selected($data['protocol'], 'nostr'); ?>><?php echo __('Nostr', 'powerpress'); ?></option>
                                        <option value="hive" <?php selected($data['protocol'], 'hive'); ?>><?php echo __('Hive', 'powerpress'); ?></option>
                                    </select>
                                </td>

                                <!-- Account Id -->
                                <td style="padding: 10px;">
                                    <input type="text"
                                        class="pp-settings-text-input"
                                        data-field="account_id"
                                        value="<?php echo esc_attr($data['account_id'] ?? ''); ?>"
                                        name="<?php echo $namePrefix; ?>[social_interact][<?php echo (int)$socialInteractId; ?>][account_id]"
                                        style="width: 100%; font-size: 12px; padding: 4px;" />
                                </td>

                                <!-- Account URL -->
                                <td style="padding: 10px;">
                                    <input type="text"
                                        class="pp-settings-text-input"
                                        data-field="accountUrl"
                                        value="<?php echo esc_attr($data['accountUrl'] ?? ''); ?>"
                                        name="<?php echo $namePrefix; ?>[social_interact][<?php echo (int)$socialInteractId; ?>][accountUrl]"
                                        style="width: 100%; font-size: 12px; padding: 4px;" />
                                </td>

                                <!-- Remove Button -->
                                <td style="padding: 10px; text-align: center;">
                                    <button type="button"
                                        class="pp-settings-text-input"
                                        data-action="remove-social-interact"
                                        data-social-interact-id="<?php echo (int)$socialInteractId; ?>"
                                        style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                                        title="Remove this social interaction">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="social-interact-loading-<?php echo $FeedSlug; ?>"
            class="social-interact-loading"
            style="display: none; margin-top: 10px; padding: 10px; text-align: center; color: #007cba;">
            <?php echo __('Validating social media URL...', 'powerpress'); ?>
        </div>

        <!-- Empty Table Message -->
        <div id="social-interact-table-message-<?php echo $FeedSlug; ?>" style="display:none;">
            <div class="no-social-interactions-message" style="text-align:center;padding:20px;color:#666;">
                <p><?php echo __('No social interactions added yet. Use the form above to add social media links for episode discussions.', 'powerpress'); ?></p>
            </div>
        </div>
    </div>

    <!-- TEMPLATE SECTION -->
    <template id="social-interact-row-template-<?php echo $FeedSlug; ?>">
        <tr data-social-interact-id="">
            <!-- Priority -->
            <td style="padding: 10px;" data-cell="priority">
                <input type="number"
                       class="pp-settings-text-input"
                       data-field="priority" 
                       name="<?php echo $namePrefix; ?>[social_interact][__ID__][priority]"
                       value="1" min="1" max="999" 
                       style="width: 100%; font-size: 12px; padding: 4px;" />
            </td>

            <!-- URI -->
            <td style="padding: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" data-cell="uri">
                <div data-field="uri-display"></div>
                <input type="hidden" name="<?php echo $namePrefix; ?>[social_interact][__ID__][uri]" value="">
            </td>

            <!-- Protocol -->
            <td style="padding: 10px;" data-cell="protocol">
                <select data-field="protocol" class="pp-settings-text-input"
                    name="<?php echo $namePrefix; ?>[social_interact][__ID__][protocol]"
                    style="width: 100%; font-size: 12px;">
                    <option value="disabled"><?php echo __('Disabled', 'powerpress'); ?></option>
                    <option value="activitypub"><?php echo __('ActivityPub', 'powerpress'); ?></option>
                    <option value="twitter"><?php echo __('Twitter/X', 'powerpress'); ?></option>
                    <option value="atproto"><?php echo __('AT Protocol (Bluesky)', 'powerpress'); ?></option>
                    <option value="lightning"><?php echo __('Lightning', 'powerpress'); ?></option>
                    <option value="matrix"><?php echo __('Matrix', 'powerpress'); ?></option>
                    <option value="nostr"><?php echo __('Nostr', 'powerpress'); ?></option>
                    <option value="hive"><?php echo __('Hive', 'powerpress'); ?></option>
                </select>
            </td>
            <!-- Account ID -->
            <td style="padding: 10px;" data-cell="accountId">
                <input type="text" 
                       class="pp-settings-text-input"
                       data-field="account_id"
                       name="<?php echo $namePrefix; ?>[social_interact][__ID__][account_id]"
                       style="width: 100%; font-size: 12px; padding: 4px;"
                       value="" />
            </td>
            <!-- Account URL -->
            <td style="padding: 10px;" data-cell="accountUrl">
                <input type="text" 
                       class="pp-settings-text-input"
                       data-field="accountUrl" 
                       name="<?php echo $namePrefix; ?>[social_interact][__ID__][accountUrl]"
                       style="width: 100%; font-size: 12px; padding: 4px;" 
                       value="" />
            </td>

            <!-- Remove Button -->
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    class="social-interact-remove-btn"
                    data-action="remove-social-interact"
                    data-social-interact-id=""
                    style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                    title="Remove this social interaction">
                    &times;
                </button>
            </td>
        </tr>
    </template>

</div>
<br>