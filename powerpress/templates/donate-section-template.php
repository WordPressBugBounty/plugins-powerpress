<!-- 
    Donate Section Template
-->
<?php

$section_data = [
    'donate_url' => $DataSource['donate_url'] ?? $DataSource['funding_url'] ?? $DatSource['donate_link'] ?? '',
    'donate_label' => $DataSource['donate_label'] ?? $DataSource['funding_label'] ?? '',
];
$has_existing = !empty($section_data['donate_url']);

// State Settings 
$default_state = $has_existing ? 'visible' : 'hidden';
$default_display = $has_existing ? 'block' : 'none';
$default_arrow = $has_existing ? '▲' : '▼';
$default_title = $has_existing ? __('Collapse Form', 'powerpress') : __('Expand Form', 'powerpress');

// Channel vs. Item Settings
$section_header = ($config['context'] === 'item') ? __('Donate', 'powerpress') : '';
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
<div class="pp-section-content">

    <div style="cursor: pointer; display: <?php echo (!empty($config['context']) && $config['context'] === 'channel') ? 'none' : ''; ?>;"
        data-action="collapse"
        onclick='toggleVisibility(this, "donation-collapse-<?php echo $FeedSlug; ?>"); return false;'>

        <!-- SECTION TITLE -->
        <div class="row justify-content-between"
            style="margin-top: 10px;">
            <!-- Donate Link Container-->
            <div class="col-sm-9">
                <h4 class="pp-section-title-block" style="width: auto;"><?php echo $section_header; ?></h4>
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
        <!-- SECTION DESCRIPTION -->
        <div class="row"
            style="margin-top: 10px;">
            <div class="col"
                style="display: <?php echo (!empty($config['context']) && $config['context'] === 'channel') ? 'none' : '';?>;">
                <p class="pp-box-text" style="margin-top: 0;">
                    <?php echo __('Syndicate a donate link with your podcast. Create your own crowdfunding page with PayPal donate buttons, or link to a service such as Patreon.', 'powerpress'); ?>
                </p>
            </div>
        </div>
    </div>


    <div id="donation-collapse-<?php echo $FeedSlug; ?>"
        data-state="<?php echo $default_state; ?>"
        style="display: <?php echo $default_display; ?>;">
        <!-- MAIN INPUTS -->
        <div class="col-12 justify-content-center align-items-start"
            style="margin-top: 10px; margin-bottom: 10px;">
            <!-- DONATION URL -->
            <div class="row">
                <label for="<?php echo $namePrefix; ?>[donate_url]"
                    class="pp-label"><?php echo __('Donate URL', 'powerpress'); ?>   
                </label>
                <input class="pp-settings-text-input"
                    type="text"
                    name="<?php echo $namePrefix; ?>[donate_url]"
                    value="<?php echo !empty($section_data['donate_url']) ? esc_attr($section_data['donate_url']) : ""; ?>" />
            </div>

            <!-- DONATION LABEL -->
            <div class="row">
                <label for="<?php echo $namePrefix; ?>[donate_label]"
                    class="pp-label"><?php echo __('Donate Label', 'powerpress'); ?>
                </label>
                <input class="pp-settings-text-input"
                    type="text"
                    name="<?php echo $namePrefix; ?>[donate_label]"
                    value="<?php echo !empty($section_data['donate_label']) ? esc_attr($section_data['donate_label']) : ""; ?>" />
            </div>
        </div>
    </div>
</div>
<br>
