<!--
Location Template
-->
<?php
// Data Normalization
if (!empty($DataSource['location'])) {
    // standalone address string (Hamburg, Germany)
    if (is_string($DataSource['location'])) {
        $section_data[] = [
            'address' => $DataSource['location'],
            'pci_rel' => '1',
            'pci_country' => '',
            'pci_geo' => '',
            'pci_osm' => ''
        ];
    } elseif (is_array($DataSource['location'])) {
        $first_location = reset($DataSource['location']);

        // nested
        if (is_array($first_location) && (isset($first_location['address']) || isset($first_location['location']))) {
            foreach ($DataSource['location'] as $location) {
                if (empty($location) || !is_array($location)) continue;

                $address = $location['address'] ?? $location['location'] ?? '';
                if ($address === '') continue;

                $section_data[] = [
                    'address' => $address,
                    'pci_rel' => $location['pci_rel'] ?? $location['rel'] ?? '1',
                    'pci_country' => $location['pci_country'] ?? $location['country'] ?? '',
                    'pci_geo' => $location['pci_geo'] ?? $location['geo'] ?? '',
                    'pci_osm' => $location['pci_osm'] ?? $location['osm'] ?? ''
                ];
            }

        // paralellized (legacy)
        } else {
            foreach ($DataSource['location'] as $i => $address) {
                if ($address === '' || $address === null) continue;

                $section_data[] = [
                    'address' => $address,
                    'pci_rel' => $DataSource['pci_rel'][$i] ?? '1',
                    'pci_country' => $DataSource['pci_country'][$i] ?? '',
                    'pci_geo' => $DataSource['pci_geo'][$i] ?? '',
                    'pci_osm' => $DataSource['pci_osm'][$i] ?? ''
                ];
            }
        }
    }
}
$has_existing = !empty($section_data);

// State Settings 
$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs Item Settings
$section_header = ($config['context'] === 'item') ? __('Location', 'powerpress') : '';
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

.pp-box-text {
    font-size: 14px;
    margin-top: 2ch;
    margin-bottom: 0;
    font-family: Roboto, sans-serif;
    display: block;
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
<div class="pp-section-content" id="location-container-<?php echo $FeedSlug; ?>">

    <div style="cursor: pointer;"
         data-action="collapse"
         onclick="toggleVisibility(this, 'location-collapse-<?php echo $FeedSlug; ?>'); return false;">

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
        <!-- SECTION DESCRIPTION  -->
        <div class="row">
            <div class="col">
                <p class="pp-box-text" style="margin-top: 10px;">
                    <?php echo __('This tag is intended to describe the location of editorial focus for a podcast\'s content (i.e. what place is this podcast about?).', 'powerpress'); ?>
                </p>
            </div>
        </div>
    </div>


    <div id="location-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>;">
        <!-- FORM SECTION -->
        <div id="location-form-container-<?php echo $FeedSlug; ?>">
            <div data-component="location-form"
                style="margin-bottom: 10px; margin-top: 10px; padding: 20px; background-color: #e3f2fd;
                        border-radius: 5px; border-left: 4px solid #1976d2;">
                <div class="row d-flex align-items-center">
                    <!-- Address Input -->
                    <div class="col-lg-6">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Address Information', 'powerpress'); ?>
                        </label>
                            <input id="location-search-input-<?php echo $FeedSlug; ?>"
                                class="pp-box-input"
                                data-purpose="search-input"
                                maxlength="128"
                                style="width: 100%; font-size: 14px;" />
                        <small class="text-muted" style="display: block; margin-top: 4px; margin-bottom: 0;">
                            <?php echo __('Enter an address to search and verify', 'powerpress'); ?>
                        </small>
                    </div>

                    <!-- Country Select -->
                    <div class="col-lg-3">
                        <label style="margin: 0;" class="pp-label">
                            <?php echo __('Country', 'powerpress'); ?>
                        </label>
                        <select id="location-search-country-<?php echo $FeedSlug; ?>"
                            class="pp-box-input"
                            style="width: 100%; font-size: 14px;">
                            <?php powerpress_print_select_options_country(); ?>
                        </select>
                        <small class="text-muted" style="display: block; margin-top: 4px; margin-bottom: 0;">
                            <?php echo __('Optional search enhancement', 'powerpress'); ?>
                        </small>
                    </div>

                    <!-- Add Button -->
                    <div class="col-lg-3 d-flex align-items-center"
                         style="margin-top: 10px;">
                        <button type="button"
                            data-action="add-location"
                            style="border: none; background: #1876d2; color: white;
                                        padding: 10px 20px; width: 100%; font-size: 14px;
                                        cursor: pointer; border-radius: 5px;"
                            title="Add new location">
                            <?php echo __('Add Location', 'powerpress'); ?>
                        </button>
                    </div>
                </div>
                <br>

                <!-- Custom Location Indicator-->
                <div class="form-check row align-items-start"
                    style=" padding: 10px 20px;">
                    <div class="col-lg-6" style="margin-left: 15px;">
                        <div class="row justify-content-start">
                            <div>
                                <input class="form-check-input me-3" type="checkbox"
                                    data-field="custom-location"
                                    style="width: 1rem; height: 1rem;">
                            </div>
                            <div class="col-sm-10">
                                <label class="form-check-label"
                                    style="font-size: 110%; font-weight: bold; cursor: pointer;">
                                    <strong><?php echo __('Custom Location? ', 'powerpress'); ?></strong><small style="font-weight: normal"><?php echo __("Check this to bypass location search.", 'powerpress'); ?></small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <!-- Nominatim/OSM API acknowledgement-->
                        <small class="text-muted">
                            <?php echo __('Geolocation provided by: ', 'powerpress'); ?>
                            <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">
                                <?php echo __('© OpenStreetMap', 'powerpress'); ?></a>
                            <?php echo __(', data available under the Open Database License.', 'powerpress'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ERROR MESSAGE SECTION -->
        <div id="location-error-<?php echo $FeedSlug; ?>"
            class="location-error"
            style="display: none; margin-bottom: 10px; margin-top: 10px; padding: 10px; 
                    background-color: #f8d7da; border: 1px solid #f5c6cb; 
                    border-radius: 5px; color: #721c24">
            <!-- Error Messages -->
        </div>

        <!-- TABLE SECTION -->
        <div id="location-table-container-<?php echo $FeedSlug; ?>">
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="locations-table"
                    style="width: 99%; border-collapse: separate; border-spacing: 0; border: 1px solid #ddd; background: #fff;">
                    <thead>
                        <tr style="background: #e9ecef; border-bottom: 2px solid #ddd">
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 15%;"><?php echo __('Rel', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 40%;"><?php echo __('Address', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 20%;"><?php echo __('Geo Data', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: left; font-weight: 600; width: 15%;"><?php echo __('OSM Data', 'powerpress'); ?></th>
                            <th style="padding: 12px 10px; text-align: center; font-weight: 600; width: 10%;"><?php echo __('Actions', 'powerpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($section_data as $index => $data) {
                            $locationId = (int)$index + 1;
                        ?>
                            <tr data-location-id="<?php echo (int)$locationId; ?>">
                                <!-- Rel (Subject / Creator) -->
                                <td style="padding: 10px;">
                                    <select class="pp-box-input"
                                        name="<?php echo $namePrefix; ?>[location][<?php echo (int)$locationId; ?>][pci_rel]"
                                        data-field="rel"
                                        style="width: 100%; font-size: 12px;">
                                        <option value="1" <?php selected($data['pci_rel'], '1'); ?>><?php echo __('Subject', 'powerpress'); ?></option>
                                        <option value="2" <?php selected($data['pci_rel'], '2'); ?>><?php echo __('Creator', 'powerpress'); ?></option>
                                    </select>
                                </td>

                                <!-- Address -->
                                <td style="padding: 10px;">
                                    <div style="font-size: 14px; line-height: 1.4;">
                                        <?php echo esc_html($data['address']) ?: 'Unknown location'; ?>
                                    </div>
                                </td>

                                <!-- GEO populated indicated -->
                                <td style="padding: 10px;">
                                    <code style="font-size: 16px; background: #f5f5f5; padding: 2px 4px; border-radius: 3px;">
                                        <?php echo !empty($data['pci_geo']) ? '✓' : '×'; ?>
                                    </code>
                                </td>

                                <!-- OSM populated indicator -->
                                <td style="padding: 10px;">
                                    <code style="font-size: 16px; background: #f5f5f5; padding: 2px 4px; border-radius: 3px;">
                                        <?php echo !empty($data['pci_osm']) ? '✓' : '×'; ?>
                                    </code>
                                </td>

                                <!-- Remove Button -->
                                <td style="padding: 10px; text-align: center;">
                                    <button type="button"
                                        class="location-remove-btn"
                                        data-action="remove-location"
                                        data-location-id="<?php echo (int)$locationId; ?>"
                                        style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                                        title="Remove this location">
                                        &times;
                                    </button>
                                </td>

                                <!-- Hidden fields for saving -->
                                <td style="display: none;">
                                    <input maxlength="128" type="hidden" data-field="address" name="<?php echo $namePrefix; ?>[location][<?php echo (int)$locationId; ?>][address]" value="<?php echo esc_attr($data['address']); ?>" />
                                    <input type="hidden" data-field="country" name="<?php echo $namePrefix; ?>[location][<?php echo (int)$locationId; ?>][pci_country]" value="<?php echo esc_attr($data['pci_country']); ?>" />
                                    <input type="hidden" data-field="geo" name="<?php echo $namePrefix; ?>[location][<?php echo (int)$locationId; ?>][pci_geo]" value="<?php echo esc_attr($data['pci_geo']); ?>" />
                                    <input type="hidden" data-field="osm" name="<?php echo $namePrefix; ?>[location][<?php echo (int)$locationId; ?>][pci_osm]" value="<?php echo esc_attr($data['pci_osm']); ?>" />
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="location-loading-<?php echo $FeedSlug; ?>"
            class="location-loading"
            style="display: none; margin-top: 10px; padding: 10px; text-align: center; color: #007cba;">
            <?php echo __('Validating location...', 'powerpress'); ?>
        </div>

        <!-- Empty Table Message -->
        <div id="location-table-message-<?php echo $FeedSlug; ?>" style="display: none;">
            <div class="no-locations-message" style="text-align: center; padding: 20px; color: #666;">
                <p><?php echo __('No locations added yet. Use the form above to add locations.', 'powerpress'); ?></p>
            </div>
        </div>
    </div>

    <!-- TEMPLATE SECTION -->
    <template id="location-row-template-<?php echo $FeedSlug; ?>">
        <tr data-location-id="">
            <!-- Rel -->
            <td style="padding: 10px;" data-cell="rel">
                <select class="pp-box-input"
                    name="<?php echo $namePrefix; ?>[location][__ID__][pci_rel]"
                    data-field="rel"
                    style="width: 100%; font-size: 12px;">
                    <option value="1"><?php echo __('Subject', 'powerpress'); ?></option>
                    <option value="2"><?php echo __('Creator', 'powerpress'); ?></option>
                </select>
            </td>
            <!-- Address -->
            <td style="padding: 10px;" data-cell="address">
                <div style="font-size: 14px; line-height: 1.4;"></div>
            </td>
            <!-- GEO indicator -->
            <td style="padding: 10px;" data-cell="geo">
                <code style="font-size: 16px; background: #f5f5f5; padding: 2px 4px; border-radius: 3px;">×</code>
            </td>
            <!-- OSM indicator -->
            <td style="padding: 10px;" data-cell="osm">
                <code style="font-size: 16px; background: #f5f5f5; padding: 2px 4px; border-radius: 3px;">×</code>
            </td>
            <!-- Remove button -->
            <td style="padding: 10px; text-align: center;">
                <button type="button"
                    class="location-remove-btn"
                    data-action="remove-location"
                    data-location-id=""
                    style="border: none; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 3px; cursor: pointer;"
                    title="Remove this location">
                    &times;
                </button>
            </td>
            <!-- Hidden Save Inputs -->
            <td style="display:none;">
                <input type="hidden" maxlength="128" data-field="address" name="<?php echo $namePrefix; ?>[location][__ID__][address]" value="">
                <input type="hidden" data-field="country" name="<?php echo $namePrefix; ?>[location][__ID__][pci_country]" value="">
                <input type="hidden" data-field="geo" name="<?php echo $namePrefix; ?>[location][__ID__][pci_geo]" value="">
                <input type="hidden" data-field="osm" name="<?php echo $namePrefix; ?>[location][__ID__][pci_osm]" value="">
            </td>
        </tr>
    </template>
</div>
<br>