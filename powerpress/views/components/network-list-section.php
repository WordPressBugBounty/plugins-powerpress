<?php
/**
 * List section w/ optional collapsible wrapper.
 *
 * pass label to get collapsible divider header + toggle body;
 * pass false to render rows directly (flat, no wrapper).
 */
function powerpress_render_list_section($section_id, $label, $items, $render_row, $collapsed = false, $empty_text = '') {
	$count = count($items);

	// 1) OPEN COLLAPSIBLE WRAPPER
	if ($label) {
		$cls = 'ppn-list__divider ppn-list__row ppn-toggle';
		if ($collapsed) $cls .= ' ppn-toggle--collapsed';
		$chevron = $collapsed ? 'expand_more' : 'expand_less';
		?>
		<div class="<?php echo esc_attr($cls); ?>" data-ppn-section="<?php echo esc_attr($section_id); ?>">
			<?php echo esc_html($label); ?> (<?php echo (int)$count; ?>)
			<i class="material-icons-outlined ppn-toggle__chevron"><?php echo esc_html($chevron); ?></i>
		</div>
		<div class="ppn-toggle__body" data-ppn-section="<?php echo esc_attr($section_id); ?>">
		<div class="ppn-toggle__inner">
		<?php
	}

	// 2) RENDER ROWS
	if ($count === 0 && $empty_text) {
		?>
		<div class="ppn-list__row ppn-list__empty">
			<span class="pp-text-muted"><?php echo esc_html($empty_text); ?></span>
		</div>
		<?php
	} else {
		foreach ($items as $item) {
			$render_row($item);
		}
	}

	// 3) CLOSE WRAPPER
	if ($label) {
		?>
		</div>
		</div>
		<?php
	}
}
