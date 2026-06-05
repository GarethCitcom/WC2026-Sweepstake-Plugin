<?php

/**
 * Sweepstake hub template — tabbed wrapper for all sections.
 * Rendered by [wc_sweepstake] shortcode.
 *
 * @package WC2026_Sweepstake
 */

if (! defined('ABSPATH')) {
	exit;
}

$pts = WC2026_Leaderboard::get_point_values();

$tabs = array(
	'groups'      => __('Groups',      'wc2026-sweepstake'),
	'fixtures'    => __('Fixtures',    'wc2026-sweepstake'),
	'leaderboard' => __('Leaderboard', 'wc2026-sweepstake'),
	'wallchart'   => __('Wall Chart',  'wc2026-sweepstake'),
);
?>
<style>
	/* Hub-specific styles */
	.chatcom-widget {
		display: none !important;
	}
</style>
<div class="wc2026-hub" id="wc2026-hub">

	<nav class="wc2026-hub-nav" role="tablist" aria-label="<?php esc_attr_e('WC2026 Sweepstake', 'wc2026-sweepstake'); ?>">
		<?php foreach ($tabs as $key => $label) : ?>
			<button
				type="button"
				role="tab"
				class="wc2026-hub-tab"
				id="wc2026-tab-<?php echo esc_attr($key); ?>"
				data-tab="<?php echo esc_attr($key); ?>"
				aria-controls="wc2026-panel-<?php echo esc_attr($key); ?>"
				aria-selected="false"><?php echo esc_html($label); ?></button>
		<?php endforeach; ?>
	</nav>

	<div class="wc2026-hub-panels">

		<div id="wc2026-panel-groups" class="wc2026-hub-panel" role="tabpanel" aria-labelledby="wc2026-tab-groups" hidden>
			<div class="container-xxl">
				<?php echo do_shortcode('[wc_wall_chart]'); ?>
			</div>
		</div>

		<div id="wc2026-panel-fixtures" class="wc2026-hub-panel" role="tabpanel" aria-labelledby="wc2026-tab-fixtures" hidden>
			<div class="container-xxl">
				<?php echo do_shortcode('[wc_fixtures]'); ?>
			</div>
		</div>

		<div id="wc2026-panel-leaderboard" class="wc2026-hub-panel" role="tabpanel" aria-labelledby="wc2026-tab-leaderboard" hidden>

			<div class="container-xxl">
				<div class="row">
					<div class="col">
						<?php echo do_shortcode('[wc_leaderboard]'); ?>
					</div>
					<div class="col-12 col-md-2 col-lg-3 col-xl-4">
						<div class="wc2026-scoring-info">
							<h3 class="wc2026-scoring-title"><?php esc_html_e('How scoring works', 'wc2026-sweepstake'); ?></h3>
							<table class="wc2026-scoring-table">
								<tbody>
									<?php
									$scoring_rows = array(
										'group_win'   => __('Group stage win',    'wc2026-sweepstake'),
										'group_draw'  => __('Group stage draw',   'wc2026-sweepstake'),
										'reach_r32'   => __('Reach Round of 32', 'wc2026-sweepstake'),
										'reach_r16'   => __('Reach Round of 16', 'wc2026-sweepstake'),
										'reach_qf'    => __('Reach Quarter-final', 'wc2026-sweepstake'),
										'reach_sf'    => __('Reach Semi-final',  'wc2026-sweepstake'),
										'reach_final' => __('Reach Final',       'wc2026-sweepstake'),
										'winner'      => __('Win the tournament', 'wc2026-sweepstake'),
									);
									foreach ($scoring_rows as $key => $label) : ?>
										<tr>
											<td><?php echo esc_html($label); ?></td>
											<td class="wc2026-scoring-pts"><?php echo esc_html($pts[$key] ?? 0); ?> pts</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

		</div>

		<div id="wc2026-panel-wallchart" class="wc2026-hub-panel" role="tabpanel" aria-labelledby="wc2026-tab-wallchart" hidden>
			<?php echo do_shortcode('[wc_wall_chart_full]'); ?>
		</div>

	</div><!-- .wc2026-hub-panels -->
</div><!-- .wc2026-hub -->