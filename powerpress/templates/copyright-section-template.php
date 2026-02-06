<!--
Copyright Template
 -->
<?php

// Data Normalization
$section_data = [
    'copyright' => isset($DataSource['copyright']) ? $DataSource['copyright'] : '',
    'copyright_url' => isset($DataSource['copyright_url']) ? $DataSource['copyright_url'] : ''
];
$has_existing = !empty($section_data['copyright']);
// State Setting
$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs. Item Settings
$section_header = ($config['context'] === 'item') ? __('Copyright', 'powerpress') : '';__('Expand / Collapse', 'powerpress')
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
    font-size: 12px;
}

</style>
<!-- Copyright Template -->
<div class="pp-section-content">              
    <div style="cursor: pointer; display: <?php echo (!empty($config['context']) && $config['context'] === 'channel') ? 'none' : ''; ?>;"
        data-action="collapse"
        onclick="toggleVisibility(this, 'license-collapse-<?php echo $FeedSlug; ?>'); return false;">

        <!-- SECTION TITLE -->
        <div class="row d-flex justify-content-between"
            style="margin-top: 10px;">
            <div class="col-sm-9">
                <h4 class="pp-section-title-block" style="width: auto;"><?php echo $section_header?></h4>
            </div>

            <!-- COLLAPSE BUTTON -->
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
        <div class="row"
            style="margin-top: 10px;">
            <div class="col">
                <p class="pp-box-text" style="margin-top: 0;">
                    <?php echo __('Specify a license for this podcast episode if you need to override your show-level license.', 'powerpress'); ?>
                </p>
            </div>
        </div>
                    
        <!-- Section Warning -->
        <div class="row" style="padding: 15px; display: <?php echo (!empty($config['context']) && $config['context'] === 'channel') ? 'none' : ''; ?>;">
            <p class="pp-box-text">
                <strong><?php echo __('WARNING:', 'powerpress'); ?></strong><em><?php echo __('Filling this section will override show level licensing/copyright information for this episode.', 'powerpress'); ?></em>
            </p>
        </div>
    </div>

    <div id="license-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo (!empty($config['context']) && $config['context'] === 'channel') ? 'visible' : $default_state; ?>"
        style="display: <?php echo (!empty($config['context']) && $config['context'] === 'channel') ? '' : $default_display; ?>;">

        <!-- Copyright Input-->
        <div class="col-12 justify-content-center align-items-start"
            style="margin-top: 10px;">
            <div class="row">
                <label for="<?php echo $namePrefix; ?>[copyright]" class="pp-label"><?php echo __('Copyright', 'powerpress'); ?></label>
                    <input class="pp-settings-text-input"
                        type="text"
                        name="<?php echo $namePrefix; ?>[copyright]"
                        value="<?php echo isset($section_data['copyright']) ? esc_attr($section_data['copyright']) : ""; ?>"
                        style="width: 100%;"
                        maxlength="128" />
            </div>
            <div class="row">
                <label for="<?php echo $namePrefix; ?>[copyright_url]" class="pp-label"><?php echo __('Copyright URL', 'powerpress'); ?></label>
                    <input class="pp-settings-text-input"
                        type="text"
                        name="<?php echo $namePrefix; ?>[copyright_url]"
                        value="<?php echo isset($section_data['copyright_url']) ? esc_attr($section_data['copyright_url']) : ""; ?>"
                        style="width: 100%;"
                        maxlength="128" />
            </div>
        </div>
    </div>
</div>
<br>