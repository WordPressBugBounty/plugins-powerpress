<!--  
Value4Value Template

USED BY: powerpress_render_section($config)
-->
<?php
// Normalilze Data
// nested
if (!empty($DataSource['value_recipients']) && is_array($DataSource['value_recipients'])) {
    foreach ($DataSource['value_recipients'] as $recipient) {
        $pubkey = trim($recipient['pubkey'] ?? '');
        if ($pubkey === '') continue;
        $section_data[] = [
            'lightning' => ($recipient['lightning'] ?? $recipient['name'] ?? ''),
            'pubkey' => $pubkey,
            'custom_key' => ($recipient['custom_key'] ?? $recipient['customKey'] ?? ''),
            'custom_value' => $recipient['custom_value'] ?? $recipient['customValue'] ?? '',
            'split' => $recipient['split'] ?? '0',
            'fee' => (isset($recipient['fee']) && $recipient['fee'] === 'true') ? 'true' : 'false',
        ];
    }
}

// paralellized (channel level legacy)
else if (!empty($DataSource['value_pubkey']) && is_array($DataSource['value_pubkey'])) {
    $pubKeys = $DataSource['value_pubkey'];
    $splits = $DataSource['value_split'] ?? [];
    $lightnings = $DataSource['value_lightning'] ?? [];
    $customKeys = $DataSource['value_custom_key'] ?? [];
    $customValues = $DataSource['value_custom_value'] ?? [];
    $fees = $DataSource['value_is_fee'] ?? $DataSource['value_fee'] ?? [];

    foreach ($pubKeys as $i => $pubkey) {
        $pubkey = trim((string)$pubkey);
        if ($pubkey === '') continue;

        $split = (string)($splits[$i] ?? '0');
        $feeRaw = $fees[$i] ?? '';
        $fee = ($feeRaw === 'true' || $feeRaw === 1 || $feeRaw === '1' || $feeRaw === true) ? 'true' : 'false';

        $section_data[] = [
            'lightning' => $lightnings[$i] ?? '',
            'pubkey' => $pubkey,
            'custom_key' => $customKeys[$i] ?? '',
            'custom_value' => $customValues[$i] ?? '',
            'split' => $split,
            'fee' => $fee,
        ];
    }
}

// paralellized (expisode level legacy)
else {
    $keys = array_keys($DataSource);
    $matches = preg_grep('/^ep-person-\d+-pubkey$/', $keys);
    if (!empty($matches)) {
        foreach ($matches as $key) {
            $pubkey = trim((string)($DataSource[$key] ?? ''));
            if ($pubkey === '') continue;

            preg_match('/^ep-person-(\d+)-pubkey$/', $key, $m);
            $id = isset($m[1]) ? (int)$m[1] : null;
            if ($id === null) continue;

            $split = (string)($DataSource["ep-person-$id-split"] ?? ($DataSource['value_split'][$id] ?? '0'));
            $feeRaw = ($DataSource["ep-person-$id-fee"] ?? ($DataSource['value_fee'][$id] ?? $DataSource['value_is_fee'][$id] ?? ''));
            $fee = ($feeRaw === 'true' || $feeRaw === 1 || $feeRaw === '1' || $feeRaw === true) ? 'true' : 'false';

            $section_data[] = [
                'lightning' => (string)($DataSource["ep-person-$id-lightning"] ?? ($DataSource['value_lightning'][$id] ?? '')),
                'pubkey' => $pubkey,
                'custom_key' => (string)($DataSource["ep-person-$id-customkey"] ?? ($DataSource['value_custom_key'][$id] ?? '')),
                'custom_value' => (string)($DataSource["ep-person-$id-customvalue"] ?? ($DataSource['value_custom_value'][$id] ?? '')),
                'split' => $split,
                'fee' => $fee,
            ];
        }
    }
}
$has_existing = !empty($section_data);

$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs Item settings
$section_header = ($config['context'] === 'item') ? __('Value4Value (V4V)', 'powerpress') : __('Expand / Collapse', 'powerpress');
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

<div class="pp-section-content" id="value-recipient-container-<?php echo $FeedSlug; ?>">
    <div style="cursor: pointer;"
        data-action="collapse"
        onclick="toggleVisibility(this, 'value-recipient-collapse-<?php echo $FeedSlug; ?>'); return false;">

        <!-- SECTION TITLE -->
        <div class="row justify-content-between">
            <div class="col-sm-9">
                <h4 class="pp-section-title-block" style="width: auto;"><?php echo $section_header; ?></h4>
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
        <div class="row"
            style="display: <?php echo $inherit_visibility; ?>">
            <div class="col">
                <p class="pp-box-text">
                    <!-- Development Note -->
                    <?php echo __("The V4V uses crypto as a method to receive donations to your show. It can be supporting your show with a donation by the minute as the audience listens to the episode, or a show-level donation with comment known as a boost.", 'powerpress'); ?>
                </p>
            </div>
        </div>

        <!-- SECTION NOTE -->
        <div class="row"
            style="display: <?php echo $inherit_visibility; ?>">
            <div class="col">
                <p class="pp-box-text">
                    <!-- Development Note -->
                    <strong>
                        <?php echo esc_html(__('PowerPress Development Support', 'powerpress')); ?>
                    </strong>
                    <?php echo esc_html(__('To help us continue improving this plugin, PowerPress automatically includes a small 3% development split. This helps fund new features, security updates, and ongoing support for the podcasting community.', 'powerpress')); ?>
                </p>
            </div>
        </div>

        <!-- SECTION WARNING -->
        <div class="row"
            style="display: <?php echo $inherit_visibility; ?>">
            <div class="col">
                <p class="pp-box-text">
                    <strong><?php echo __('WARNING:', 'powerpress'); ?></strong><em><?php echo __('When you input the value tag at the episode level, show-level V4V settings are ignored for that episode.', 'powerpress'); ?></em>
                </p>
            </div>
        </div>
    </div>


    <!-- HIDDEN MAIN CONTENT -->
    <div id="value-recipient-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>;">
        <!-- FORM SECTION -->
        <div id="value-recipient-form-container-<?php echo $FeedSlug; ?>">

            <!-- Lightning Address Form -->
            <div id="lightning-address-form-<?php echo $FeedSlug; ?>"
                data-component="lightning-address-form"
                style="margin-bottom: 10px; margin-top: 20px; padding: 20px; background-color: #e3f2fd; 
                    border-radius: 5px; border-left: 4px solid #1976d2; display: block;">

                <div class="row d-flex align-items-center">
                    <!-- Lightning Address Input -->
                    <div class="col-lg-9">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Lightning Address or Name', 'powerpress'); ?>
                        </label>
                            <input id="lightning-address-input-<?php echo $FeedSlug; ?>"
                                class="pp-settings-text-input"
                                data-field="lightningAddress"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                        <small class="text-muted" style="display: block; margin-top: 4px; margin-bottom: 0;">
                            <?php echo __('Enter a Lightning address to search and verify', 'powerpress'); ?>
                        </small>
                    </div>

                    <!-- Validate Button -->
                    <div class="col-lg-3 d-flex align-items-center">
                        <button type="button"
                            data-action="check-lightning"
                            style="border: none; border-radius: 5px; background: #1976d2; color: white;
                            padding: 10px 20px; width: 100%; font-size: 14px; cursor: pointer;"
                            title="Add this recipient">
                            <?php echo __('Continue', 'powerpress'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Detailed Recipient Form -->
            <div id="recipient-confirmation-form-<?php echo $FeedSlug; ?>"
                data-component="recipient-confirmation-form"
                style="display: none; margin-bottom: 2vh; margin-top: 2vh; padding: 20px; background-color: #e8f5e8; border-radius: 5px; border-left: 4px solid #28a745;">

                <div class="row d-flex align-items-center">
                    <!-- Lightning Address (readonly) -->
                    <div class="col-lg-6">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Lightning Address', 'powerpress'); ?>
                        </label>
                            <input id="confirm-lightning-address-<?php echo $FeedSlug; ?>"
                                class="pp-box-input"
                                readonly
                                style="width: 100%; font-size: 14px; background-color: #f8f9fa;" />
                    </div>

                    <!-- PubKey -->
                    <div class="col-lg-6">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('PubKey *', 'powerpress'); ?>
                        </label>
                            <input id="confirm-pubkey-<?php echo $FeedSlug; ?>"
                                class="pp-settings-text-input"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                    </div>
                </div>

                <div class="row d-flex align-items-center" style="margin-top: 15px;">
                    <!-- Custom Key -->
                    <div class="col-lg-3">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Custom Key', 'powerpress'); ?>
                        </label>
                            <input id="confirm-custom-key-<?php echo $FeedSlug; ?>"
                                class="pp-settings-text-input"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                    </div>

                    <!-- Custom Value -->
                    <div class="col-lg-3">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Custom Value', 'powerpress'); ?>
                        </label>
                            <input id="confirm-custom-value-<?php echo $FeedSlug; ?>"
                                class="pp-settings-text-input"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                    </div>

                    <!-- Split % -->
                    <div class="col-lg-3">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Split %', 'powerpress'); ?>
                        </label>
                            <input id="confirm-split-<?php echo $FeedSlug; ?>"
                                class="pp-settings-text-input"
                                type="number"
                                min="0" max="100" step="0.1"
                                value="0"
                                style="width: 100%; font-size: 14px;" />
                    </div>

                    <!-- Fee -->
                    <div class="col-lg-3">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Fee', 'powerpress'); ?>
                        </label>
                            <select id="confirm-fee-<?php echo $FeedSlug; ?>"
                                class="pp-settings-text-input"
                                style="width: 100%; font-size: 14px;">
                                <option value="false"><?php echo __('No', 'powerpress'); ?></option>
                                <option value="true"><?php echo __('Yes', 'powerpress'); ?></option>
                            </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row align-items-center justify-content-between" style="margin-top: 20px;">
                    <div class="col-lg-3">
                        <button type="button"
                            data-action="confirm-recipient"
                            style="border: none; background: #28a745; color: white; 
                                 padding: 10px 20px; width: 100%; font-size: 14px;
                                 cursor: pointer; border-radius: 5px;"
                            class="btn btn-success">
                            <?php echo __('Insert Recipient', 'powerpress'); ?>
                        </button>
                    </div>
                    <div class="col-lg-3">
                        <button type="button"
                            data-action="cancel-recipient"
                            style="border: none; background: #dc3545; color: white; 
                                 padding: 10px 20px; width: 100%; font-size: 14px;
                                 cursor: pointer; border-radius: 5px;"
                            class="btn btn-secondary">
                            <?php echo __('Cancel', 'powerpress'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Inherit Channel Check -->
            <div class="form-check row align-items-start"
                style="padding: 10px 20px; display: <?php echo $inherit_visibility; ?>;">
                <?php
                $inherit_channel_recipients = 0;
                if (!empty($DataSource['inherit_channel_recipients'])) {
                    $inherit_channel_recipients = $DataSource['inherit_channel_recipients'];
                }
                ?>
                <div>
                    <input class="form-check-input me-3" type="checkbox"
                        name="<?php echo $namePrefix; ?>[inherit_channel_recipients]"
                        id="inherit-channel-recipients-<?php echo $FeedSlug; ?>"
                        data-action="inherit-recipients"
                        style="width: 1rem; height: 1rem;" <?php checked(!empty($inherit_channel_recipients)); ?>>
                </div>
                <div>
                    <label class="form-check-label"
                        style="font-size: 110%; font-weight: bold; cursor: pointer;"
                        for="inherit-channel-recipients-<?php echo $FeedSlug; ?>">
                        <strong><?php echo __('Inherit Channel Recipients? ', 'powerpress'); ?></strong><small style="font-weight: normal;"><?php echo __("Check this to keep your regular recipients when adding episode-specific recipients.", 'powerpress'); ?></small>
                    </label>
                </div>
            </div>

            <!-- ERROR MESSAGE SECTION -->
            <div id="value-recipient-error-<?php echo $FeedSlug; ?>"
                class="value-recipient-error"
                style="display: none; margin-bottom: 10px; margin-top: 10px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24">
                <!-- Error Messages -->
            </div>
        </div>
        <?php
        // Get Channel v4v info
        if (!$inherit_visibility) {
            $FeedSettings = get_option('powerpress_feed');
            $channel_recipients_data = [];

            if (!empty($FeedSettings['value_recipients'])) {
                $channel_recipients_data = $FeedSettings['value_recipients'];
            } 
            // handle legacy
            else if (!empty($FeedSettings['pubkey'])) {
                foreach ($FeedSettings['pubkey'] as $i => $pubkey) {
                    if (empty($pubkey)) continue;
                    $channel_recipients_data[] = [
                        'lightning' => $FeedSettings['lightning'][$i] ?? '',
                        'pubkey' => $pubkey,
                        'custom_key' => $FeedSettings['custom_key'][$i] ?? '',
                        'custom_value' => $FeedSettings['custom_value'][$i] ?? '',
                        'split' => $FeedSettings['split'][$i] ?? '0',
                        'fee' => $FeedSettings['is_fee'][$i] ?? 'false'
                    ];
                }
            }
        }
        ?>

        <!-- Inherited Recipients Table -->
        <div id="inherited-recipients-preview-<?php echo $FeedSlug; ?>"
            style="display: <?php echo !empty($inherit_channel_recipients) ? 'block' : 'none'; ?>; 
                    margin: 15px 0; padding: 15px; 
                    background: #f8f9fa; border-radius: 5px; 
                    border-left: 4px solid #28a745;">

            <h5 style="margin: 0 0 10px 0; color: #28a745;">
                <?php echo __('Inherited Channel Recipients', 'powerpress'); ?>
            </h5>

            <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
                <?php echo __('These recipients from your channel settings will be included with this episode:', 'powerpress'); ?>
            </p>

            <?php if (!empty($channel_recipients_data)): ?>
                <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table style="width: 99%; border-collapse: separate; border-spacing: 0; 
                                  border: 1px solid #ddd; background: #fff; font-size: 13px;">
                        <thead>
                            <tr style="background: #e9ecef; border-bottom: 1px solid #ddd">
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 20%">
                                    <?php echo __('Lightning Address', 'powerpress'); ?>
                                </th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 25%">
                                    <?php echo __('PubKey', 'powerpress'); ?>
                                </th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 15%">
                                    <?php echo __('Custom Key', 'powerpress'); ?>
                                </th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 15%">
                                    <?php echo __('Custom Value', 'powerpress'); ?>
                                </th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 10%">
                                    <?php echo __('Split %', 'powerpress'); ?>
                                </th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 5%">
                                    <?php echo __('Fee', 'powerpress'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($channel_recipients_data as $recipient): ?>
                                <tr data-origin="inherited" style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px 10px;"><?php echo esc_html($recipient['lightning']); ?></td>
                                    <td style="padding: 8px 10px; font-family: monospace; font-size: 11px;">
                                        <?php echo esc_html(substr($recipient['pubkey'], 0, 16) . '...'); ?>
                                    </td>
                                    <td style="padding: 8px 10px;">
                                        <?php echo esc_html($recipient['custom_key'] ?: '-'); ?>
                                    </td>
                                    <td style="padding: 8px 10px;">
                                        <?php echo esc_html($recipient['custom_value'] ?: '-'); ?>
                                    </td>
                                    
                                    <td style="padding: 8px 10px;">
                                        <input type="number"
                                            class="pp-box-input"
                                            data-field="split-inherited"
                                            min="0" max="100" step="0.1"
                                            value="<?php echo esc_attr($recipient['split']); ?>"
                                            <?php echo $inherit_channel_recipients ? '' : 'disabled'; ?>
                                            style="width: auto; font-size: 12px; padding: 4px;">
                                    </td>

                                    <td style="padding: 8px 10px;">
                                        <?php echo ($recipient['fee'] === 'true') ? 'Yes' : 'No'; ?>
                                    </td>

                                    <td style="display: none;">
                                        <?php
                                        $inherited_channel_count = 0;
                                        foreach ($channel_recipients_data as $recipient):
                                            $inherited_channel_count++;
                                        ?>
                                        <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][inherit-<?php echo $inherited_channel_count; ?>][lightning]"
                                            value="<?php echo esc_attr($recipient['lightning']); ?>"
                                            <?php echo $inherit_channel_recipients ? '' : 'disabled'; ?>>

                                        <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][inherit-<?php echo $inherited_channel_count; ?>][pubkey]"
                                            value="<?php echo esc_attr($recipient['pubkey']); ?>"
                                            <?php echo $inherit_channel_recipients ? '' : 'disabled'; ?>>

                                        <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][inherit-<?php echo $inherited_channel_count; ?>][custom_key]"
                                            value="<?php echo esc_attr($recipient['custom_key']); ?>"
                                            <?php echo $inherit_channel_recipients ? '' : 'disabled'; ?>>

                                        <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][inherit-<?php echo $inherited_channel_count; ?>][custom_value]"
                                            value="<?php echo esc_attr($recipient['custom_value']); ?>"
                                            <?php echo $inherit_channel_recipients ? '' : 'disabled'; ?>>

                                        <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][inherit-<?php echo $inherited_channel_count; ?>][split]"
                                            value="<?php echo esc_attr($recipient['split']); ?>"
                                            <?php echo $inherit_channel_recipients ? '' : 'disabled'; ?>>

                                        <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][inherit-<?php echo $inherited_channel_count; ?>][fee]"
                                            value="<?php echo ($recipient['fee'] === 'true') ? 'true' : 'false'; ?>" 
                                            <?php echo $inherit_channel_recipients ? '' : 'disabled'; ?>>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #666; font-style: italic; margin: 0;">
                    <?php echo __('No channel recipients configured.', 'powerpress'); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- TABLE SECTION -->
        <div id="value-recipient-table-container-<?php echo $FeedSlug; ?>">
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="recipients-table"
                    style="width: 99%; border-collapse: separate; border-spacing: 0; border: 1px solid #ddd; background: #fff;">
                    <thead>
                        <tr style="background: #e9ecef; border-bottom: 2px solid #ddd">
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%"><?php echo __('Lightning Address', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 25%"><?php echo __('PubKey', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 15%"><?php echo __('Custom Key', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 15%"><?php echo __('Custom Value', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 10%"><?php echo __('Split %', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 5%"><?php echo __('Fee', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 10%"><?php echo __('Actions', 'powerpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($section_data as $idx => $data) {
                            $recipientId = $idx + 1;
                        ?>
                            <!-- Table Row Pre-Gen -->
                            <tr data-recipient-id="<?php echo (int)$recipientId; ?>">
                                <!-- Lightning Address -->
                                <td style="padding: 10px;"><?php echo esc_html($data['lightning']); ?></td>

                                <!-- Pubkey -->
                                <td style="padding: 10px; font-family: monospace; font-size: 12px;">
                                    <?php echo esc_html(substr($data['pubkey'], 0, 20) . '...'); ?>
                                </td>

                                <!-- Custom Key -->
                                <td style="padding: 10px;"><?php echo esc_html($data['custom_key'] ?: '–'); ?></td>

                                <!-- Custom Value -->
                                <td style="padding: 10px;"><?php echo esc_html($data['custom_value'] ?: '–'); ?></td>

                                <!-- Split -->
                                <td style="padding: 10px;">
                                    <input type="number"
                                        class="pp-box-input"
                                        data-field="split"
                                        data-recipient-id="<?php echo (int)$recipientId; ?>"
                                        value="<?php echo esc_attr($data['split']); ?>"
                                        min="0" max="100" step="0.1"
                                        style="width: auto; font-size: 12px; padding: 4px;">
                                </td>

                                <!-- Fee -->
                                <td style="padding: 10px;"><?php echo $data['fee'] === 'true' ? 'Yes' : 'No'; ?></td>

                                <!-- Remove Button -->
                                <td style="padding: 10px; text-align: center;">
                                    <button type="button"
                                        class="recipient-remove-btn"
                                        data-action="remove-recipient"
                                        data-recipient-id="<?php echo (int)$recipientId; ?>"
                                        style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                                        title="Remove this recipient">
                                        &times;
                                    </button>
                                </td>

                                <!-- Hidden fields for saving -->
                                <td style="display: none;">
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][<?php echo (int)$recipientId; ?>][lightning]" value="<?php echo esc_attr($data['lightning']); ?>">
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][<?php echo (int)$recipientId; ?>][custom_key]" value="<?php echo esc_attr($data['custom_key']); ?>" />
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][<?php echo (int)$recipientId; ?>][custom_value]" value="<?php echo esc_attr($data['custom_value']); ?>" />
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][<?php echo (int)$recipientId; ?>][pubkey]" value="<?php echo esc_attr($data['pubkey']); ?>" />
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][<?php echo (int)$recipientId; ?>][split]" value="<?php echo esc_attr($data['split']); ?>" />
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[value_recipients][<?php echo (int)$recipientId; ?>][fee]" value="<?php echo esc_attr($data['fee']); ?>" />
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Split total indicator -->
            <div class="row" style="margin-top: 10px">
                <div class="col-lg-12">
                    <small class="text-muted split-total-indicator">
                        <?php echo __('Current total: ', 'powerpress'); ?><span class="total-percentage"><?php echo __('0%', 'powerpress'); ?></span>
                        <span class="total-status" style="margin-left: 10px;"></span>
                    </small>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="value-recipient-loading-<?php echo $FeedSlug; ?>"
            class="value-recipient-loading"
            style="display: none; margin-top: 10px; padding: 10px; text-align: center; color: #007cba;">
            <?php echo __('Validating wallet address..', 'powerpress'); ?>
        </div>

        <!-- Empty Table Message -->
        <div id="value-recipient-table-message-<?php echo $FeedSlug; ?>" style="display:none;">
            <div class="no-recipients-message" style="text-align:center;padding:20px;color:#666;">
                <p><?php echo __('No value recipients added for this episode yet. Use the form above to add recipients.', 'powerpress'); ?></p>
            </div>
        </div>
    </div>

    <!-- TEMPLATE SECTION -->
    <template id="recipient-row-template-<?php echo $FeedSlug; ?>">
        <tr data-recipient-id="">
            <!-- Lightning Address -->
            <td style="padding: 10px;" data-cell="lightningAddress"></td>
            <!-- Pubkey -->
            <td style="padding: 10px; font-family: monospace; font-size: 12px;" data-cell="pubkey"></td>
            <!-- Custom Key -->
            <td style="padding: 10px;" data-cell="customKey"><?php echo __('-', 'powerpress'); ?></td>
            <!-- Custom Value -->
            <td style="padding: 10px;" data-cell="customValue"><?php echo __('-', 'powerpress'); ?></td>
            <!-- Split -->
            <td style="padding: 10px;" data-cell="split">
                <input type="number"
                    class="pp-box-input"
                    data-field="split"
                    value="0"
                    min="0" max="100" step="0.1"
                    style="width: auto; font-size: 12px; padding: 4px;">
            </td>
            <!-- Fee -->
            <td style="padding: 10px;" data-cell="fee"><?php echo __('No', 'powerpress'); ?></td>
            <!-- Remove Button -->
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    class="recipient-remove-btn"
                    data-action="remove-recipient"
                    data-recipient-id=""
                    style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                    title="Remove this recipient">
                    &times;
                </button>
            </td>
            <td style="display:none;">
                <input type="hidden" name="<?php echo $namePrefix?>[value_recipients][__ID__][lightning]" value="">
                <input type="hidden" name="<?php echo $namePrefix?>[value_recipients][__ID__][custom_key]" value="">
                <input type="hidden" name="<?php echo $namePrefix?>[value_recipients][__ID__][custom_value]" value="">
                <input type="hidden" name="<?php echo $namePrefix?>[value_recipients][__ID__][pubkey]" value="">
                <input type="hidden" name="<?php echo $namePrefix?>[value_recipients][__ID__][split]" value="">
                <input type="hidden" name="<?php echo $namePrefix?>[value_recipients][__ID__][fee]" value="">
            </td>
        </tr>
    </template>

</div>
<br>