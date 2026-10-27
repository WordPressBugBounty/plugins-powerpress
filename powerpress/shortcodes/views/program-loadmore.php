<?php if (!empty($props['has_more'])) { ?>
    <div class="ppn-loadmore-row" bp="12">
        <button type="button"
                class="ppn-loadmore"
                data-ppn-loadmore
                data-program="<?php echo esc_attr($props['program_id']); ?>"
                data-style="<?php echo esc_attr($props['style']); ?>"
                data-limit="<?php echo esc_attr($props['limit']); ?>"
                data-offset="<?php echo esc_attr(count($props['episodes'])); ?>"
                data-selected="<?php echo esc_attr($props['selected'] ?? ''); ?>"
                data-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"><?php esc_html_e('Load more', 'powerpress'); ?></button>
    </div>
<?php } ?>
