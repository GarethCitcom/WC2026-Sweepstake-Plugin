<?php
/**
 * Leaderboard template — rendered by [wc_leaderboard] shortcode.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$leaderboard = WC2026_Leaderboard::get_leaderboard();
?>
<div class="wc2026-leaderboard">
	<h2 class="wc2026-section-title"><?php esc_html_e( 'Leaderboard', 'wc2026-sweepstake' ); ?></h2>

	<table class="wc2026-leaderboard-table">
		<thead>
			<tr>
				<th class="col-rank">#</th>
				<th class="col-avatar"></th>
				<th class="col-name"><?php esc_html_e( 'Staff', 'wc2026-sweepstake' ); ?></th>
				<th class="col-pts"><?php esc_html_e( 'Points', 'wc2026-sweepstake' ); ?></th>
				<th class="col-countries"><?php esc_html_e( 'Countries', 'wc2026-sweepstake' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $leaderboard as $rank => $staff ) :
			$avatar_url = WC2026_Staff::get_avatar_url( $staff, 'medium' );
			$countries  = WC2026_Countries::get_by_staff( $staff->id );
			$rank_class = 0 === $rank ? ' rank-gold' : ( 1 === $rank ? ' rank-silver' : ( 2 === $rank ? ' rank-bronze' : '' ) );
			?>
			<tr class="wc2026-leaderboard-row<?php echo esc_attr( $rank_class ); ?>"
				style="--staff-colour: <?php echo esc_attr( $staff->colour ); ?>">
				<td class="col-rank">
					<?php if ( $rank < 3 ) : ?>
						<span class="wc2026-medal wc2026-medal-<?php echo esc_attr( $rank ); ?>">
							<?php echo esc_html( $rank + 1 ); ?>
						</span>
					<?php else : ?>
						<?php echo esc_html( $rank + 1 ); ?>
					<?php endif; ?>
				</td>
				<td class="col-avatar">
					<button type="button" class="wc2026-staff-trigger" data-staff-slug="<?php echo esc_attr( $staff->slug ); ?>">
						<img
							src="<?php echo esc_url( $avatar_url ); ?>"
							alt="<?php echo esc_attr( $staff->name ); ?>"
							class="wc2026-avatar wc2026-avatar-md"
							loading="lazy"
						>
					</button>
				</td>
				<td class="col-name">
					<button type="button" class="wc2026-staff-trigger wc2026-staff-name-link" data-staff-slug="<?php echo esc_attr( $staff->slug ); ?>">
						<strong><?php echo esc_html( $staff->name ); ?></strong>
					</button>
				</td>
				<td class="col-pts">
					<span class="wc2026-points"><?php echo esc_html( (int) $staff->total_points ); ?></span>
				</td>
				<td class="col-countries">
					<div class="wc2026-country-flags">
					<?php foreach ( $countries as $country ) : ?>
						<?php if ( $country->flag_url ) : ?>
							<img
								src="<?php echo esc_url( $country->flag_url ); ?>"
								alt="<?php echo esc_attr( $country->name ); ?>"
								class="wc2026-flag wc2026-flag-xs"
								title="<?php echo esc_attr( $country->name ); ?>"
								loading="lazy"
								width="24"
								height="16"
							>
						<?php else : ?>
							<span class="wc2026-country-name-sm"><?php echo esc_html( $country->name ); ?></span>
						<?php endif; ?>
					<?php endforeach; ?>
					</div>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div><!-- .wc2026-leaderboard -->
