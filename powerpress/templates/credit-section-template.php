<!-- 
Credit Template

USED BY: powerpress_render_section($config)
-->
<?php

// nested
if (!empty($DataSource['credits'])) {
    foreach ($DataSource['credits'] as $credit) {
        if (!empty($credit['name'])) {
            $section_data[] = [
                'name' => $credit['name'],
                'role' => $credit['role'] ?? 'Guest',
                'person_url' => $credit['person_url'] ?? '',
                'link_url' => $credit['link_url'] ?? ''
            ];
        }
    }
} 
// paralellized (legacy)
else {
    $personNames = $DataSource['person_names'] ?? [];
    $personRoles = $DataSource['person_roles'] ?? [];
    $personURLs = $DataSource['person_urls'] ?? [];
    $linkURLs = $DataSource['link_urls'] ?? [];

    foreach ($personNames as $i => $name) {
        if ($name === '') continue;
        $section_data[] = [
            'name' => $name,
            'role' => $personRoles[$i] ?? 'Guest',
            'person_url' => $personURLs[$i] ?? '',
            'link_url' => $linkURLs[$i] ?? '',
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
$section_header = ($config['context'] === 'item') ? __('Credit', 'powerpress') : '';
$inherit_visibility = ($config['context'] === 'item') ? '' : 'none';
?>
<style>

input[class="pp-box-input"], textarea[class="pp-box-input"], select[class="pp-box-input"], div[class="pp-box-input"] {
    border-radius: 4px;
    background-color: white;
    border: 1px solid #B1B1B1;
    font-size: 14px;
    height: 56px;
    width: 100%;
}

input[class="pp-box-input"]:focus, textarea[class="pp-box-input"]:focus, select[class="pp-box-input"]:focus, div[class="pp-box-input"]:focus {
    border-radius: 4px;
    background-color: white;
    border: 1px solid #B1B1B1;
    font-size: 14px;
    height: 56px;
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

<!-- CREDIT TEMPLATE -->
<div class="pp-section-content" id="credit-role-container-<?php echo $FeedSlug; ?>">
                
    <div style="cursor: pointer"
        data-action="collapse"
        onclick="toggleVisibility(this, 'credit-collapse-<?php echo $FeedSlug; ?>'); return false;">

        <!-- SECTION TITLE -->
        <div class="row justify-content-between">
            <div class="col-sm-9">
                <div class="d-flex justify-content-start align-items-center">
                    <h4 class="pp-section-title-block" style="width: auto !important;">
                        <?php echo __($section_header, 'powerpress'); ?>
                    </h4>
                </div>
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
        <!-- Section Description -->
        <div class="row">
            <div class="col">
                <p class="pp-ep-box-text">
                    <?php echo __('Credit the people that made this episode happen (guests, editors, musicians, production).', 'powerpress'); ?>
                </p>
            </div>
        </div>

        <!-- Section Warning -->
        <div class="row">
            <div class="col">
                <p class="pp-ep-box-text" style="display: <?php echo $inherit_visibility; ?>">
                    <strong><?php echo __('WARNING:', 'powerpress'); ?></strong><em><?php echo __('Filling this section will override ALL show level credit recipients for this particular episode unless the "Inherit Channel Credits" Checkbox is marked.', 'powerpress'); ?></em>
                </p>
            </div>
        </div>
    </div>

    <!-- MAIN FORM -->
    <div id="credit-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>">
        
        <div id="credit-form-container-<?php echo $FeedSlug; ?>">
            <div data-component="credit-form"
                style="margin-bottom: 10px; margin-top: 10px; padding: 20px; background: #e3f2fd; 
                    border-radius: 5px; border-left: 4px solid #1976d2;">
                <div class="row d-flex align-items-center">
                    <!-- NAME INPUT -->
                    <div class="col-lg-6">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Name *', 'powerpress'); ?>
                        </label>
                            <input id="credit-name-input-<?php echo $FeedSlug; ?>"
                                class="pp-box-input"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                    </div>
                    <!-- ROLE INPUT -->
                    <div class="col-lg-3">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Role', 'powerpress'); ?>
                        </label>
                            <select id="credit-role-select-<?php echo $FeedSlug; ?>"
                                class="pp-box-input"
                                style="width: 100%; font-size: 14px;">
                                <?php
                                powerpress_print_select_options_roles();
                                ?>
                            </select>
                    </div>
                    <!-- INSERT BUTTON -->
                    <div class="col-lg-3 d-flex align-items-center">
                        <button type="button"
                            class="btn"
                            data-action="add-credit"
                            style="border: none; background: #1876d2; color: #fff; padding: 10px 20px;
                           width: 100%; font-size: 14px; cursor: pointer; border-radius: 5px; margin-top: 15px;">
                            <?php echo __('Insert Credit', 'powerpress'); ?>
                        </button>
                    </div>
                </div>

                <div class="row d-flex align-items-center">
                    <div class="col-lg-6">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Image URL', 'powerpress'); ?>
                        </label>
                            <input id="credit-person-url-input-<?php echo $FeedSlug; ?>"
                                class="pp-box-input"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                    </div>
                    <div class="col-lg-6">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Website URL', 'powerpress'); ?>
                        </label>
                            <input id="credit-link-url-input-<?php echo $FeedSlug; ?>"
                                class="pp-box-input"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Inherit Channel Check -->
        <div class="form-check row align-items-start"
            style="padding: 10px 20px; display: <?php echo $inherit_visibility; ?>;">
            <?php
            $inherit_channel_credits = 0;
            if (!empty($DataSource['inherit_channel_credits'])) {
                $inherit_channel_credits = $DataSource['inherit_channel_credits'];
            }
            ?>
            <div>
                <input class="form-check-input me-3" type="checkbox"
                    name="<?php echo $namePrefix; ?>[inherit_channel_credits]"
                    id="inherit-channel-credits-<?php echo $FeedSlug; ?>"
                    data-action="inherit-credits"
                    style="width: 1rem; height: 1rem;" <?php checked($inherit_channel_credits); ?>>
            </div>
            <div>
                <label class="form-check-label"
                    style="font-size: 110%; font-weight: bold; cursor: pointer;"
                    for="inherit-channel-credits">
                    <strong><?php echo __('Inherit Channel Credits? ', 'powerpress'); ?></strong><small style="font-weight: normal;"><?php echo __("Check this to keep your regular hosts/crew listed when adding episode-specific guests or credits.", 'powerpress'); ?></small>
                </label>
            </div>
        </div>

        <!-- ERROR MESSAGE SECTION -->
        <div id="credit-error-<?php echo $FeedSlug; ?>"
            class="credit-error"
            style="display: none; margin-top: 10px; padding: 10px; background: #f8d7da; 
            border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
        </div>

        <!-- Inherit Recipient Table Display -->
        <?php
        if ($config['context'] === 'item') {
            // Get channel-level credits data
            $FeedSettings = get_option('powerpress_feed');
            $channel_credits_data = [];

            // nested
            if (!empty($FeedSettings['credits'])) {
                $channel_credits_data = $FeedSettings['credits'];
            } 
            // paralellized (legacy)
            else {
                $channelPersonNames = isset($FeedSettings['person_names']) ? $FeedSettings['person_names'] : array("");
                $channelPersonRoles = isset($FeedSettings['person_roles']) ? $FeedSettings['person_roles'] : array("Host");
                $channelPersonURLs = isset($FeedSettings['person_urls']) ? $FeedSettings['person_urls'] : array("");
                $channelLinkURLs = isset($FeedSettings['link_urls']) ? $FeedSettings['link_urls'] : array("");

                // Build channel credits data array
                $channel_credits_data = [];
                foreach ($channelPersonNames as $i => $name) {
                    if ($name === '') continue;
                    $channel_credits_data[] = [
                        'name' => $name,
                        'role' => $channelPersonRoles[$i] ?? 'Host',
                        'person_url' => $channelPersonURLs[$i] ?? '',
                        'link_url' => $channelLinkURLs[$i] ?? '',
                    ];
                }

            }

        }
        ?>

        <!-- Inherited Credits Table -->
        <div id="inherited-credits-preview-<?php echo $FeedSlug; ?>"
            style="display: <?php echo $inherit_channel_credits ? 'block' : 'none'; ?>; 
                    margin: 15px 0; padding: 15px; 
                    background: #f8f9fa; border-radius: 5px; 
                    border-left: 4px solid #28a745;">

            <h5 style="margin: 0 0 10px 0; color: #28a745;">
                <?php echo __('Inherited Channel Credits', 'powerpress'); ?>
            </h5>

            <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
                <?php echo __('These credits from your channel settings will be included with this episode:', 'powerpress'); ?>
            </p>

            <?php if (!empty($channel_credits_data)): ?>
                <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table style="width: 99%; border-collapse: separate; border-spacing: 0; 
                                  border: 1px solid #ddd; background: #fff; font-size: 13px;">
                        <thead>
                            <tr style="background: #e9ecef; border-bottom: 1px solid #ddd">
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 25%">
                                    <?php echo __('Name', 'powerpress'); ?>
                                </th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 15%">
                                    <?php echo __('Role', 'powerpress'); ?>
                                </th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 30%">
                                    <?php echo __('Image URL', 'powerpress'); ?>
                                </th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: 600; width: 30%">
                                    <?php echo __('Website URL', 'powerpress'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($channel_credits_data as $credit): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px 10px;"><?php echo esc_html($credit['name']); ?></td>
                                    <td style="padding: 8px 10px;"><?php echo esc_html($credit['role']); ?></td>
                                    <td style="padding: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php if (!empty($credit['person_url'])) {
                                            // strip http(s) from url and display shortened version
                                            $displaypurl = preg_replace('/^https?:\/\//', '', $credit['person_url']);
                                            if (strlen($displaypurl) > 32) {
                                                $displaypurl = substr($displaypurl, 0, 29) . '...';
                                            } ?>
                                            <div style="max-width: 100%;font-size: clamp(10px, 2.5vw, 14px);"
                                                title="<?php echo esc_attr($credit['person_url']); ?>">
                                                <?php echo esc_html($displaypurl); ?>
                                            </div>
                                        <?php
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <!-- linkUrl -->
                                    <td style="padding: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php if (!empty($credit['link_url'])) {
                                            // strip http(s) from url and display shortened version
                                            $displaylurl = preg_replace('/^https?:\/\//', '', $credit['link_url']);
                                            if (strlen($displaylurl) > 32) {
                                                $displaylurl = substr($displaylurl, 0, 29) . '...';
                                            } ?>
                                            <div style="max-width: 100%; font-size: clamp(10px, 2.5vw, 14px);"
                                                title="<?php echo esc_attr($credit['link_url']); ?>">
                                                <?php echo esc_html($displaylurl); ?>
                                            </div>
                                        <?php
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #666; font-style: italic; margin: 0;">
                    <?php echo __('No channel credits configured. You can add them in your channel settings.', 'powerpress'); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Table Section -->
        <div id="credit-table-container-<?php echo $FeedSlug; ?>">
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="credits-table"
                    style="width: 99%; border-collapse: separate; border-spacing: 0; border: 1px solid #ddd; background: #fff;">
                    <thead>
                        <tr style="background: #e9ecef; border-bottom: 2px solid #ddd">
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 30%"><?php echo __('Name', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%"><?php echo __('Role', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%"><?php echo __('Image URL', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%"><?php echo __('Website URL', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 10%"><?php echo __('Actions', 'powerpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($section_data as $index => $row) {
                            $id = $index + 1; // 1-based
                            $purl = $row['person_url'];
                            $lurl = $row['link_url'];
                        ?>
                            <tr data-credit-id="<?php echo (int)$id; ?>">
                                <!-- Name -->
                                <td style="padding: 10px;"><?php echo esc_html($row['name']); ?></td>

                                <!-- Role -->
                                <td style="padding: 10px;"><?php echo esc_html($row['role']); ?></td>

                                <!-- personUrl -->
                                <td style="padding: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php if (!empty($purl)) {
                                        // strip http(s) from url and display shortened version
                                        $displaypurl = preg_replace('/^https?:\/\//', '', $purl);
                                        if (strlen($displaypurl) > 32) {
                                            $displaypurl = substr($displaypurl, 0, 29) . '...';
                                        } ?>
                                        <div style="max-width: 100%;font-size: clamp(10px, 2.5vw, 14px);"
                                            title="<?php echo esc_attr($purl); ?>">
                                            <?php echo esc_html($displaypurl); ?>
                                        </div>
                                    <?php
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <!-- linkUrl -->
                                <td style="padding: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php if (!empty($lurl)) {
                                        // strip http(s) from url and display shortened version
                                        $displaylurl = preg_replace('/^https?:\/\//', '', $lurl);
                                        if (strlen($displaylurl) > 32) {
                                            $displaylurl = substr($displaylurl, 0, 29) . '...';
                                        } ?>
                                        <div style="max-width: 100%; font-size: clamp(10px, 2.5vw, 14px);"
                                            title="<?php echo esc_attr($lurl); ?>">
                                            <?php echo esc_html($displaylurl); ?>
                                        </div>
                                    <?php
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>

                                <!-- Remove Button -->
                                <td style="padding: 10px; text-align: center;"> 
                                    <button type="button"
                                        data-action="edit-credit"
                                        data-credit-id="<?php echo (int)$id; ?>"
                                        style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                                               border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                                               font-size: 12px;"
                                        title="<?php echo esc_attr(__('Edit this credit', 'powerpress')); ?>">
                                        <?php echo __('Edit', 'powerpress'); ?>
                                    </button>
                                    <button type="button"
                                        class="credit-remove-btn"
                                        data-action="remove-credit"
                                        data-credit-id="<?php echo (int)$id; ?>"
                                        style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                                        title="Remove this credit">
                                        &times;
                                    </button>
                                </td>

                                <!-- Hidden fields for saving -->
                                <td style="display: none;">
                                    <input type="hidden" maxlength="128" name="<?php echo $namePrefix; ?>[credits][<?php echo $id; ?>][name]" value="<?php echo esc_attr($row['name']); ?>">
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[credits][<?php echo $id; ?>][role]" value="<?php echo esc_attr($row['role']); ?>">
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[credits][<?php echo $id; ?>][person_url]" value="<?php echo esc_attr($row['person_url']); ?>">
                                    <input type="hidden" name="<?php echo $namePrefix; ?>[credits][<?php echo $id; ?>][link_url]" value="<?php echo esc_attr($row['link_url']); ?>">
                                </td>

                            </tr>
                        <?php
                        } // end foreach
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Table Message -->
        <div id="credit-table-message-<?php echo $FeedSlug; ?>" style="display:none;">
            <div class="no-credits-message" style="text-align: center; padding: 20px; color: #666;">
                <p><?php echo __('No credits added for this episode yet. Use the form above to add credits.', 'powerpress'); ?></p>
            </div>
        </div>

        <!-- END COLLAPSE -->
    </div>

    <!-- Credit Row Template -->
    <template id="credit-row-template-<?php echo $FeedSlug; ?>">
        <tr data-credit-id="">
            <!-- Name -->
            <td style="padding: 10px;" data-cell="name"></td>
            <!-- Role -->
            <td style="padding: 10px;" data-cell="role"></td>
            <!-- Person URL -->
            <td style="padding: 10px;" data-cell="personUrl">-</td>
            <!-- Link URL -->
            <td style="padding: 10px;" data-cell="linkUrl">-</td>
            <!-- Remove Button -->
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    data-action="edit-credit"
                    data-credit-id=""
                    style="border: none; background: #28a745; color: #fff; padding: 5px 10px; 
                           border-radius: 3px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; 
                           font-size: 12px;"
                    title="<?php echo __('Edit this credit', 'powerpress'); ?>">
                    <?php echo __('Edit', 'powerpress'); ?>
                </button>
                <button type="button"
                    class="credit-remove-btn"
                    data-action="remove-credit"
                    data-credit-id=""
                    style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                    title="Remove this credit">
                    &times;
                </button>
            </td>
            <!-- Section Save Information -->
            <td style="display:none;">
                <input type="hidden" maxlength="128" name="<?php echo $namePrefix; ?>[credits][__ID__][name]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[credits][__ID__][role]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[credits][__ID__][person_url]" value="">
                <input type="hidden" name="<?php echo $namePrefix; ?>[credits][__ID__][link_url]" value="">
            </td>                                             
        </tr>
    </template>

</div>
<br>