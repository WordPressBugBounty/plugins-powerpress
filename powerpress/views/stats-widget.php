<?php
/**
 * Stats Widget Template
 * shared template for dashboard and admin settings stats display
 *
 * Expected variables:
 * @var string $nonce           security nonce
 * @var string $ajax_url        admin ajax url
 * @var array  $chart_data      chart data array (optional, for non-deferred)
 * @var bool   $deferred        whether to render skeleton for lazy loading
 * @var bool   $stacked         whether to use stacked single-column layout
 * @var array  $stats_content   stats content from API
 * @var object $program_card    PowerPressProgramCard instance for rendering sub-components
 */

if (!defined('ABSPATH')) exit;

$data_attr = !empty($chart_data) ? " data-chart='" . esc_attr(json_encode($chart_data)) . "'" : "";
$stacked_attr = $stacked ? ' data-stacked="1"' : '';
$deferred_attr = $deferred ? ' data-deferred="1"' : '';
$program_keyword = $program_card->get_program_keyword();
?>

<div id="pp-stats-widget"
     data-nonce="<?php esc_attr_e($nonce); ?>"
     data-ajax-url="<?php esc_attr_e($ajax_url); ?>"
     data-program-keyword="<?php esc_attr_e($program_keyword); ?>"
     <?php echo "$data_attr$stacked_attr$deferred_attr"; ?>>

    <!-- live region for screen reader announcements -->
    <div id="pp-stats-live-region" role="status" aria-live="polite" class="pp-screen-reader-text"></div>

    <?php $program_card->render_stats_header(); ?>

    <?php if ($deferred) : ?>
        <!-- SKELETON FOR LAZY LOADING -->
        <div class="pp-stats-skeleton">
            <?php if ($stacked) : ?>
            <div class="pp-stats-stacked">
                <div class="pp-stats-chart">
                    <div class="pp-skeleton-chart"></div>
                </div>
                <div class="pp-stats-summary">
                    <div class="pp-skeleton-line"></div>
                    <div class="pp-skeleton-line"></div>
                    <div class="pp-skeleton-line"></div>
                </div>
            </div>
            <?php else : ?>
            <div class="row pp-stats-row">
                <div class="col-md-8">
                    <div class="pp-stats-chart">
                        <div class="pp-skeleton-chart"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pp-stats-summary">
                        <div class="pp-skeleton-line"></div>
                        <div class="pp-skeleton-line"></div>
                        <div class="pp-skeleton-line"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

    <?php elseif (isset($stats_content['error'])) : ?>
        <!-- ERROR STATE -->
        <div class="pp-stats-error-message"><?php echo $stats_content['error']; ?></div>

    <?php elseif (empty($stats_content)) : ?>
        <!-- EMPTY STATE -->
        <?php
        echo "Unknown error occurred getting stats";
        delete_option('powerpress_stats');
        ?>

    <?php elseif ($stacked) : ?>
        <!-- STACKED LAYOUT (dashboard) -->
        <div class="pp-stats-stacked">
            <?php $program_card->render_stats_chart(); ?>
            <?php $program_card->render_stats_summary(); ?>
        </div>

    <?php else : ?>
        <!-- TWO-COLUMN LAYOUT (settings) -->
        <div class="row pp-stats-row">
            <div class="col-md-8">
                <?php $program_card->render_stats_chart(); ?>
            </div>
            <div class="col-md-4">
                <?php $program_card->render_stats_summary(); ?>
            </div>
        </div>
    <?php endif; ?>

</div>
