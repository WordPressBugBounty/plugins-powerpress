<?php
if (!is_numeric($props['rows']))
    $props['rows'] = 1;
if (!is_numeric($props['cols']))
    $props['cols'] = 4;

$limit = $props['rows'] * $props['cols'];
// clamp cols to actual program count so grid doesn't have empty gaps
// must stay in valid grid set (12-col divisors)
$validCols = [1, 2, 3, 4, 6];
$actualCount = count($props['results']);
$effectiveCols = min($props['cols'], $actualCount);
// round down to nearest valid grid column count
$numCols = [];
$numCols['large'] = 1;
foreach ($validCols as $v) {
    if ($v <= $effectiveCols) $numCols['large'] = $v;
}
if ($numCols['large'] == 6){
    $numCols['medium'] = 4;
    $numCols['small'] = 2;
} else if ($numCols['large'] == 4 || $numCols['large'] == 3){
    $numCols['medium'] = 2;
    $numCols['small'] = 2;
} else{
    $numCols['medium'] = $numCols['large'];
    $numCols['small'] = $numCols['large'];
}
$size = [];
foreach ($numCols as $key=>$value){
    $size[$key] = 12 / $value;
}
$gridHTML = '';
if ($limit > count($props['results'])){
    $limit = count($props['results']);
}
$pluginUrl = PowerPressNetwork::powerpress_network_plugin_url();
$hoverClass = $props['hover'] ? ' ppn-grid-cell--hover' : '';
$titleClass = $props['display-title'] ? ' ppn-centered-content--titled' : '';
$gridHTML .= "<div bp='grid' class='ppn-grid-header'>";
$count = 0;
foreach ($props['results'] as $program) {

    if($count >= $limit)
        break;

    // skip programs w/o a mapped post page
    $hasLink = !empty($program['link']) && $program['link'] !== '#';
    if (!$hasLink) {
        continue;
    }
    $count++;
    $linkUrl = esc_url($program['link']);
    $artworkSrc = esc_url($program['artwork_url']['300'] ?? '');
    $fallbackSrc = esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg');
    $titleText = esc_html($program['program_title']);

    $gridHTML .= "
        <div class='ppn-grid-rows' bp='{$size['large']}@lg {$size['medium']}@md {$size['small']}@sm'>
            <div class='ppn-grid-cell{$hoverClass}'>
                <div class='ppn-grid-img' id='{$program['program_id']}'>
                <div class='ppn-centered-content{$titleClass}'>";

    $gridHTML .= "
                    <div class='square'>
                        <a href='{$linkUrl}'>
                            <img class='ppn-img' bp='float-center' src='{$artworkSrc}' title='{$titleText}' onerror=\"this.onerror=null; this.src='{$fallbackSrc}';\">
                        </a>
                    </div>";

    if ($props['display-title']) {
        $gridHTML .= "
                    <div class='ppn-grid-title'>
                        <h3 bp='text-center' class='ppn-title'><a href='{$linkUrl}'>{$titleText}</a></h3>
                    </div>";
    }

    $gridHTML .=
            "        </div>
                </div>
            </div>
        </div>
                ";

}
$gridHTML .= "</div>";

// modifier classes now in ppn-frontend.css

// group description (specific list) or network description (all shows)
$description = $props['list_desc'] ?? $props['network_description'] ?? '';
$descHTML = '';
if (!empty($description)) {
    $desc = esc_html($description);
    $descHTML = "<p class=\"ppn-list-description\">{$desc}</p>";
}

echo $descHTML . $gridHTML;