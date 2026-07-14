<?php
// Update Frequency Template
// <podcast:updateFrequency>


// NORMALIZE LEGACY FORMAT
$uf_data = powerpress_normalize_update_frequency(
    $DataSource['update_frequency'] ?? null,
    $DataSource['update_frequency_week'] ?? null,
    $DataSource['update_frequency_month'] ?? null
);
$uf_data = $uf_data ?? [];

// EXTRACT BYDAY
$byday_string = $uf_data['byday'] ?? '';
$byday_codes = $byday_string !== '' ? array_map('trim', explode(',', $byday_string)) : [];
$common_codes = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
$checkbox_codes = array_values(array_intersect(array_map('strtoupper', $byday_codes), $common_codes));

// EXTRACT BYDAY ORDINAL DATA
$ordinal_pos = '';
$ordinal_day = '';
foreach ($byday_codes as $code) {
    if (preg_match('/^([+-]?\d+)([A-Z]{2})$/', strtoupper($code), $m)) {
        $ordinal_pos = $m[1];
        $ordinal_day = $m[2];
        break;
    }
}

// EXTRACT BYMONTHDAY
$bymonthday_string = $uf_data['bymonthday'] ?? '';
$bymonthday_selected = $bymonthday_string !== ''
    ? array_map('intval', array_filter(explode(',', $bymonthday_string), 'strlen'))
    : [];

// EXTRACT BYMONTH
$bymonth_string = $uf_data['bymonth'] ?? '';
$bymonth_selected = $bymonth_string !== ''
    ? array_map('intval', array_filter(explode(',', $bymonth_string), 'strlen'))
    : [];

$section_data = [
    'freq' => $uf_data['freq'] ?? '',
    'interval' => $uf_data['interval'] ?? '',
    'count' => $uf_data['count'] ?? '',
    'until' => $uf_data['until'] ?? '',
    'dtstart' => $DataSource['dtstart'] ?? '',
    'itunes_complete' => !empty($DataSource['itunes_complete']),
];

// EXTRACT CADENCE FROM SELECT DATA
$cadence_preset = $DataSource['cadence_preset'] ?? '';
if ($cadence_preset === '') {
    $interval_int = (int) ($section_data['interval'] ?: 1);
    if ($section_data['itunes_complete']) {
        $cadence_preset = 'complete';
    } elseif ($section_data['freq'] === 'DAILY' && $interval_int === 1) {
        $cadence_preset = 'daily';
    } elseif ($section_data['freq'] === 'WEEKLY' && $interval_int === 1 && empty($checkbox_codes) && $ordinal_pos === '') {
        $cadence_preset = 'weekly';
    } elseif ($section_data['freq'] === 'WEEKLY' && $interval_int === 2) {
        $cadence_preset = 'biweekly';
    } elseif ($section_data['freq'] === 'MONTHLY' && $interval_int === 1 && empty($bymonthday_selected) && $ordinal_pos === '') {
        $cadence_preset = 'monthly';
    } elseif ($section_data['freq'] === 'MONTHLY' && $interval_int === 2) {
        $cadence_preset = 'bimonthly';
    } elseif ($section_data['freq'] !== '') {
        $cadence_preset = 'custom';
    }
}

// NORMALIZE DATETIME -> YYYY-MM-DD
$to_date_input = function ($value) {
    if (empty($value)) return '';
    try {
        $dt = new DateTime($value);
        return $dt->format('Y-m-d');
    } catch (Exception $e) {
        return '';
    }
};
$dtstart_input = $to_date_input($section_data['dtstart']);
$until_input = $to_date_input($section_data['until']);

// END-CONDITION RADIO STATE
$ends_mode = 'never';
if (!empty($section_data['count']) && (int) $section_data['count'] > 0) $ends_mode = 'count';
elseif ($until_input !== '') $ends_mode = 'until';

// MONTHLY PATTERN RADIO STATE
$monthly_pattern = $ordinal_pos !== '' ? 'ordinal' : 'bymonthday';

// SETUP CADENCE OPTIONS
$cadence_options = [
    '' => __('— not set —', 'powerpress'),
    'daily' => __('Daily', 'powerpress'),
    'weekly' => __('Weekly', 'powerpress'),
    'semiweekly' => __('Semiweekly (twice a week)', 'powerpress'),
    'biweekly' => __('Biweekly (every 2 weeks)', 'powerpress'),
    'monthly' => __('Monthly', 'powerpress'),
    'semimonthly' => __('Semimonthly (twice a month)', 'powerpress'),
    'bimonthly' => __('Bimonthly (every 2 months)', 'powerpress'),
    'custom' => __('Custom', 'powerpress'),
    'complete' => __('Series complete (no more episodes)', 'powerpress'),
];

$custom_display = $cadence_preset === 'custom' ? 'block' : 'none';
?>
<div class="pp-section-content pp-update-frequency">

    <h2><?php esc_html_e('Episode Frequency', 'powerpress'); ?></h2>

    <!-- ==========
         START DATE
         ========== -->
    <div class="row mb-3">
        <div class="form-group col-lg-6">
            <label for="<?php echo $namePrefix; ?>_dtstart" class="pp-settings-label">
                <?php esc_html_e('Start Date', 'powerpress'); ?>
            </label>
            <input class="pp-settings-text-input" type="date"
                   name="<?php echo $namePrefix; ?>[dtstart]"
                   id="<?php echo $namePrefix; ?>_dtstart"
                   value="<?php echo esc_attr($dtstart_input); ?>">
        </div>
    </div>

    <!-- ========================
         TIER 1: CADENCE DROPDOWN
         ======================== -->
    <div class="row mb-3">
        <div class="form-group col-lg-6">
            <label for="<?php echo $namePrefix; ?>_cadence_preset" class="pp-settings-label">
                <?php esc_html_e('Release Cadence', 'powerpress'); ?>
            </label>
            <select class="pp-settings-text-input"
                    name="<?php echo $namePrefix; ?>[cadence_preset]"
                    id="<?php echo $namePrefix; ?>_cadence_preset"
                    data-uf-cadence>
                <?php foreach ($cadence_options as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>"
                            <?php echo $cadence_preset === $value ? 'selected' : ''; ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- ====================
         TIER 2: CUSTOM PANEL
         ==================== -->
    <div id="<?php echo $namePrefix; ?>_uf_custom_panel"
         style="display: <?php echo $custom_display; ?>;"
         data-uf-custom-panel>

        <!-- REPEATS FREQ + INTERVAL -->
        <div class="row mb-3">
            <div class="form-group col-lg-4">
                <label class="pp-settings-label" for="<?php echo $namePrefix; ?>_freq">
                    <?php esc_html_e('Repeats', 'powerpress'); ?>
                </label>
                <select class="pp-settings-text-input"
                        name="<?php echo $namePrefix; ?>[update_frequency][freq]"
                        id="<?php echo $namePrefix; ?>_freq"
                        data-uf-freq>
                    <option value="WEEKLY" <?php echo $section_data['freq'] === 'WEEKLY' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="MONTHLY" <?php echo $section_data['freq'] === 'MONTHLY' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="YEARLY" <?php echo $section_data['freq'] === 'YEARLY' ? 'selected' : ''; ?>>Yearly</option>
                </select>
            </div>

            <div class="form-group col-lg-4">
                <label class="pp-settings-label" for="<?php echo $namePrefix; ?>_interval">
                    <?php esc_html_e('Every', 'powerpress'); ?>
                </label>
                <input class="pp-settings-text-input" type="number" min="1" max="999" step="1"
                       name="<?php echo $namePrefix; ?>[update_frequency][interval]"
                       id="<?php echo $namePrefix; ?>_interval"
                       value="<?php echo esc_attr($section_data['interval']); ?>"
                       placeholder="1">
            </div>
        </div>

        <!-- WEEKLY: BYDAY -->
        <div class="row mb-3" data-uf-show-for="WEEKLY">
            <div class="form-group col-12">
                <label class="pp-settings-label"><?php esc_html_e('On these days', 'powerpress'); ?></label>
                <div class="pp-uf-day-chips">
                    <?php
                    $day_letters = ['SU' => 'S', 'MO' => 'M', 'TU' => 'T', 'WE' => 'W', 'TH' => 'T', 'FR' => 'F', 'SA' => 'S'];
                    foreach ($day_letters as $code => $letter):
                        $checked = in_array($code, $checkbox_codes, true);
                    ?>
                        <label class="pp-uf-day-chip" style="background: <?php echo $checked ? '#1876d2' : '#fff'; ?>; color: <?php echo $checked ? '#fff' : '#333'; ?>;">
                            <input type="checkbox"
                                   name="<?php echo $namePrefix; ?>[update_frequency][byday][]"
                                   value="<?php echo $code; ?>"
                                   <?php echo $checked ? 'checked' : ''; ?>
                                   data-uf-day-chip>
                            <span><?php echo $letter; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- MONTHLY / YEARLY -->
        <div class="row mb-3" data-uf-show-for="MONTHLY YEARLY">
            <div class="form-group col-12">
                <h5 class="mt-3 mb-2"><?php esc_html_e('Recurrence pattern', 'powerpress'); ?></h5>

                <div class="form-check mt-2">
                    <input type="radio" class="form-check-input"
                           id="<?php echo $namePrefix; ?>_mp_bymonthday"
                           name="<?php echo $namePrefix; ?>[update_frequency][monthly_pattern]"
                           value="bymonthday"
                           <?php echo $monthly_pattern === 'bymonthday' ? 'checked' : ''; ?>
                           data-uf-monthly-pattern>
                    <label class="form-check-label" for="<?php echo $namePrefix; ?>_mp_bymonthday">
                        <?php esc_html_e('On specific days of the month', 'powerpress'); ?>
                    </label>
                </div>

                <div class="form-check mt-2">
                    <input type="radio" class="form-check-input"
                           id="<?php echo $namePrefix; ?>_mp_ordinal"
                           name="<?php echo $namePrefix; ?>[update_frequency][monthly_pattern]"
                           value="ordinal"
                           <?php echo $monthly_pattern === 'ordinal' ? 'checked' : ''; ?>
                           data-uf-monthly-pattern>
                    <label class="form-check-label" for="<?php echo $namePrefix; ?>_mp_ordinal">
                        <?php esc_html_e('On the Nth day-of-week of the month', 'powerpress'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- MONTHLY/YEARLY BYMONTHDAY -->
        <div class="row mb-3" data-uf-show-monthly-pattern="bymonthday">
            <div class="form-group col-12">
                <label class="pp-settings-label"><?php esc_html_e('Days of the month', 'powerpress'); ?></label>
                <div class="pp-uf-day-of-month-grid">
                    <?php for ($d = 1; $d <= 31; $d++):
                        $checked = in_array($d, $bymonthday_selected, true);
                    ?>
                        <label class="pp-uf-day-of-month-cell" style="background: <?php echo $checked ? '#1876d2' : '#fff'; ?>; color: <?php echo $checked ? '#fff' : '#333'; ?>;">
                            <input type="checkbox"
                                   name="<?php echo $namePrefix; ?>[update_frequency][bymonthday][]"
                                   value="<?php echo $d; ?>"
                                   <?php echo $checked ? 'checked' : ''; ?>
                                   data-uf-day-cell>
                            <span><?php echo $d; ?></span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- MONTHLY/YEARLY ORDINAL -->
        <div class="row mb-3" data-uf-show-monthly-pattern="ordinal">
            <div class="form-group col-lg-3">
                <label class="pp-settings-label" for="<?php echo $namePrefix; ?>_ordinal_pos">
                    <?php esc_html_e('On the', 'powerpress'); ?>
                </label>
                <select class="pp-settings-text-input"
                        name="<?php echo $namePrefix; ?>[update_frequency][ordinal_pos]"
                        id="<?php echo $namePrefix; ?>_ordinal_pos">
                    <?php
                    $ordinal_labels = ['+1' => 'First', '+2' => 'Second', '+3' => 'Third', '+4' => 'Fourth', '-1' => 'Last'];
                    foreach ($ordinal_labels as $value => $label):
                        $selected = ($ordinal_pos === $value) || ($ordinal_pos === ltrim($value, '+'));
                    ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php echo $selected ? 'selected' : ''; ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-lg-4">
                <label class="pp-settings-label" for="<?php echo $namePrefix; ?>_ordinal_day">
                    <?php esc_html_e('Day of week', 'powerpress'); ?>
                </label>
                <select class="pp-settings-text-input"
                        name="<?php echo $namePrefix; ?>[update_frequency][ordinal_day]"
                        id="<?php echo $namePrefix; ?>_ordinal_day">
                    <?php
                    $day_options = ['SU' => 'Sunday', 'MO' => 'Monday', 'TU' => 'Tuesday', 'WE' => 'Wednesday', 'TH' => 'Thursday', 'FR' => 'Friday', 'SA' => 'Saturday'];
                    foreach ($day_options as $code => $label):
                    ?>
                        <option value="<?php echo $code; ?>" <?php echo $ordinal_day === $code ? 'selected' : ''; ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- YEARLY BYMONTH -->
        <div class="row mb-3" data-uf-show-for="YEARLY">
            <div class="form-group col-12">
                <label class="pp-settings-label"><?php esc_html_e('In these months', 'powerpress'); ?></label>
                <div class="pp-uf-month-grid">
                    <?php
                    $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
                    foreach ($months as $num => $name):
                        $checked = in_array($num, $bymonth_selected, true);
                    ?>
                        <label class="pp-uf-month-cell" style="background: <?php echo $checked ? '#1876d2' : '#fff'; ?>; color: <?php echo $checked ? '#fff' : '#333'; ?>;">
                            <input type="checkbox"
                                   name="<?php echo $namePrefix; ?>[update_frequency][bymonth][]"
                                   value="<?php echo $num; ?>"
                                   <?php echo $checked ? 'checked' : ''; ?>
                                   data-uf-month-cell>
                            <span><?php echo $name; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ENDS RADIO GROUP -->
        <div class="row mb-3 pp-uf-ends-group">
            <div class="form-group col-12">
                <h5 class="mt-3 mb-2"><?php esc_html_e('Ends', 'powerpress'); ?></h5>

                <div class="form-check mt-2">
                    <input type="radio" class="form-check-input"
                           id="<?php echo $namePrefix; ?>_ends_never"
                           name="<?php echo $namePrefix; ?>[update_frequency][ends_mode]"
                           value="never"
                           <?php echo $ends_mode === 'never' ? 'checked' : ''; ?>
                           data-uf-ends>
                    <label class="form-check-label" for="<?php echo $namePrefix; ?>_ends_never">
                        <?php esc_html_e('Never', 'powerpress'); ?>
                    </label>
                </div>

                <div class="form-check mt-2 d-flex align-items-center">
                    <input type="radio" class="form-check-input mr-2"
                           id="<?php echo $namePrefix; ?>_ends_count"
                           name="<?php echo $namePrefix; ?>[update_frequency][ends_mode]"
                           value="count"
                           <?php echo $ends_mode === 'count' ? 'checked' : ''; ?>
                           data-uf-ends>
                    <label class="form-check-label mr-2" for="<?php echo $namePrefix; ?>_ends_count">
                        <?php esc_html_e('After', 'powerpress'); ?>
                    </label>
                    <input class="pp-settings-text-input pp-uf-count-input" type="number" min="1" step="1"
                           name="<?php echo $namePrefix; ?>[update_frequency][count]"
                           id="<?php echo $namePrefix; ?>_count"
                           value="<?php echo esc_attr($section_data['count']); ?>"
                           <?php echo $ends_mode !== 'count' ? 'disabled' : ''; ?>
                           data-uf-ends-count>
                    <span class="ml-2"><?php esc_html_e('occurrences', 'powerpress'); ?></span>
                </div>

                <div class="form-check mt-2 d-flex align-items-center">
                    <input type="radio" class="form-check-input mr-2"
                           id="<?php echo $namePrefix; ?>_ends_until"
                           name="<?php echo $namePrefix; ?>[update_frequency][ends_mode]"
                           value="until"
                           <?php echo $ends_mode === 'until' ? 'checked' : ''; ?>
                           data-uf-ends>
                    <label class="form-check-label mr-2" for="<?php echo $namePrefix; ?>_ends_until">
                        <?php esc_html_e('On', 'powerpress'); ?>
                    </label>
                    <input class="pp-settings-text-input pp-uf-until-input" type="date"
                           name="<?php echo $namePrefix; ?>[update_frequency][until]"
                           id="<?php echo $namePrefix; ?>_until"
                           value="<?php echo esc_attr($until_input); ?>"
                           <?php echo $ends_mode !== 'until' ? 'disabled' : ''; ?>
                           data-uf-ends-until>
                </div>
            </div>
        </div>

    </div>

    <!-- ALIGN <itunes:complete> WITH UPDATE FREQUENCY COMPLETE STATE -->
    <input type="hidden"
           name="<?php echo $namePrefix; ?>[itunes_complete]"
           value="<?php echo $section_data['itunes_complete'] ? '1' : ''; ?>"
           data-uf-itunes-complete>
</div>
