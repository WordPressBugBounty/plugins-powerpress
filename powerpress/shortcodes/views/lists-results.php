<?php if (!empty($props['list_desc'])): ?>
    <p class="ppn-list-description"><?php echo esc_html($props['list_desc']); ?></p>
<?php endif; ?>

<?php
if ($props['style'] == 'simple'){
?>
    <div>
        <?php foreach ($props['results'] as $id => $program) {
            if (!$program['link'] || $program['link'] == "#")
                continue;
            ?>
            <ul>
                <li><a href="<?php echo esc_url(($program['link'])); ?>"><h3><?php echo esc_html(($program['program_title'])); ?></h3></a></li>
            </ul>
        <?php }
        ?>
    </div>
<?php
} else if ($props['style'] == 'detailed') {
    $programRowHTML = '';
    foreach ($props['results'] as $id => $program){

        if (!$program['link'] || $program['link'] == "#")
            continue;

        $programRowHTML.='<div bp="grid" class="ppn-program-row">';
        $programRowHTML.='    <div bp="3@lg 12@sm" class="ppn-program-artwork">
                                  <a href="'.esc_url(($program['link'])).'"><img class="ppn-program-artwork" src="'.esc_url(($program['artwork_url']['300'])).'" onerror="this.onerror=null; this.src=\''.esc_url(powerpress_get_root_url() . 'images/pts_cover.jpg').'\';"></a>
                              </div>';
        $programRowHTML.='    <div bp="9@lg 12@sm" class="ppn-program-detail">
                                  <h2 class="ppn-program-title"><a href="'.esc_url(($program['link'])).'">'.esc_html(($program['program_title'])).'</a></h2>
                                  <p class="ppn-program-description">'.(strlen(esc_html(($program['program_desc'])))<300  ? esc_html(($program['program_desc'])) : substr(esc_html(($program['program_desc'])),0,297)."...").'</p>
                              </div>';
        $programRowHTML.='</div>
                          <br>';
    }
    echo $programRowHTML;
}
    ?>
