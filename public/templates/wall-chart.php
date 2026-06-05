<?php
/**
 * Wall chart template — rendered by [wc_wall_chart] shortcode.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$groups = range( 'A', 'L' );
?>
<div class="wc2026-wall-chart">

	<h2 class="wc2026-section-title"><?php esc_html_e( 'Group Stage', 'wc2026-sweepstake' ); ?></h2>

	<div class="wc2026-groups-grid">
	<?php foreach ( $groups as $letter ) :
		$standings = WC2026_Matches::get_group_standings( $letter );
		if ( empty( $standings ) ) {
			continue;
		}
		?>
		<div class="wc2026-group-card">
			<div class="wc2026-group-header">
				<?php
				/* translators: %s: group letter */
				printf( esc_html__( 'Group %s', 'wc2026-sweepstake' ), esc_html( $letter ) );
				?>
			</div>
			<table class="wc2026-group-table">
				<thead>
					<tr>
						<th class="col-pos">#</th>
						<th class="col-flag"></th>
						<th class="col-team"><?php esc_html_e( 'Team', 'wc2026-sweepstake' ); ?></th>
						<th class="col-owner"><?php esc_html_e( 'Staff', 'wc2026-sweepstake' ); ?></th>
						<th title="<?php esc_attr_e( 'Played', 'wc2026-sweepstake' ); ?>">P</th>
						<th title="<?php esc_attr_e( 'Wins', 'wc2026-sweepstake' ); ?>">W</th>
						<th title="<?php esc_attr_e( 'Draws', 'wc2026-sweepstake' ); ?>">D</th>
						<th title="<?php esc_attr_e( 'Losses', 'wc2026-sweepstake' ); ?>">L</th>
						<th title="<?php esc_attr_e( 'Goal difference', 'wc2026-sweepstake' ); ?>">GD</th>
						<th title="<?php esc_attr_e( 'Points', 'wc2026-sweepstake' ); ?>">Pts</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $standings as $pos => $row ) :
					$country    = $row['country'];
					$staff_obj  = (object) array(
						'name'   => $country->staff_name,
						'slug'   => $country->staff_slug,
						'colour' => $country->staff_colour,
						'photo'  => $country->staff_photo,
					);
					$avatar_url = WC2026_Staff::get_avatar_url( $staff_obj, 'thumbnail' );
					$qualify    = $pos < 2 ? ' qualify' : ( 2 === $pos ? ' third' : '' );
					?>
					<tr class="wc2026-team-row<?php echo esc_attr( $qualify ); ?>"
						style="--staff-colour: <?php echo esc_attr( $country->staff_colour ?? '#ccc' ); ?>">
						<td class="col-pos"><?php echo esc_html( $pos + 1 ); ?></td>
						<td class="col-flag">
							<?php if ( $country->flag_url ) : ?>
								<img
									src="<?php echo esc_url( $country->flag_url ); ?>"
									alt="<?php echo esc_attr( $country->name ); ?>"
									class="wc2026-flag"
									loading="lazy"
									width="30"
									height="20"
								>
							<?php endif; ?>
						</td>
						<td class="col-team">
							<?php echo esc_html( $country->name ); ?>
							<?php if ( $country->fifa_code ) : ?>
								<span class="wc2026-fifa-code"><?php echo esc_html( $country->fifa_code ); ?></span>
							<?php endif; ?>
						</td>
						<td class="col-owner">
							<?php if ( $country->staff_name && $country->staff_slug ) : ?>
								<button type="button" class="wc2026-staff-trigger" data-staff-slug="<?php echo esc_attr( $country->staff_slug ); ?>" title="<?php echo esc_attr( $country->staff_name ); ?>">
									<img
										src="<?php echo esc_url( $avatar_url ); ?>"
										alt="<?php echo esc_attr( $country->staff_name ); ?>"
										class="wc2026-avatar wc2026-avatar-sm"
										loading="lazy"
									>
									<span class="wc2026-staff-name"><?php echo esc_html( $country->staff_name ); ?></span>
								</button>
							<?php else : ?>
								<span class="wc2026-unowned">—</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $row['played'] ); ?></td>
						<td><?php echo esc_html( $row['wins'] ); ?></td>
						<td><?php echo esc_html( $row['draws'] ); ?></td>
						<td><?php echo esc_html( $row['losses'] ); ?></td>
						<td><?php echo esc_html( $row['gd'] > 0 ? '+' . $row['gd'] : $row['gd'] ); ?></td>
						<td class="col-pts"><strong><?php echo esc_html( $row['points'] ); ?></strong></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?>
	</div><!-- .wc2026-groups-grid -->

</div><!-- .wc2026-wall-chart -->
