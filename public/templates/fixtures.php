<?php
/**
 * Fixtures list template — rendered by [wc_fixtures] shortcode.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$filter_round = isset( $_GET['wc_round'] ) ? sanitize_text_field( wp_unslash( $_GET['wc_round'] ) ) : ''; // phpcs:ignore WordPress.Security
$filter_group = isset( $_GET['wc_group'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['wc_group'] ) ) ) : ''; // phpcs:ignore WordPress.Security
$filter_staff = isset( $_GET['wc_staff'] ) ? (int) $_GET['wc_staff'] : 0; // phpcs:ignore WordPress.Security

$args = array();
if ( $filter_round ) {
	$args['round'] = $filter_round;
}
if ( $filter_group && ( ! $filter_round || 'Group' === $filter_round ) ) {
	$args['group_letter'] = $filter_group;
}

$all_matches = WC2026_Matches::get_all( $args );
$all_staff   = WC2026_Staff::get_all();

// Staff filter.
if ( $filter_staff ) {
	$staff_country_ids = array_map(
		function ( $c ) { return (int) $c->id; },
		WC2026_Countries::get_by_staff( $filter_staff )
	);
	$all_matches = array_filter(
		$all_matches,
		function ( $m ) use ( $staff_country_ids ) {
			return in_array( (int) $m->home_team_id, $staff_country_ids, true )
				|| in_array( (int) $m->away_team_id, $staff_country_ids, true );
		}
	);
}

// Group matches by date.
$by_date = array();
foreach ( $all_matches as $match ) {
	$by_date[ $match->match_date ][] = $match;
}
ksort( $by_date );

$round_labels = array(
	'Group' => 'Group Stage',
	'R32'   => 'Round of 32',
	'R16'   => 'Round of 16',
	'QF'    => 'Quarter-finals',
	'SF'    => 'Semi-finals',
	'3rd'   => 'Third Place',
	'Final' => 'Final',
);
?>
<div class="wc2026-fixtures">

	<form class="wc2026-fixtures-filter" method="get">
		<label>
			<?php esc_html_e( 'Round:', 'wc2026-sweepstake' ); ?>
			<select name="wc_round">
				<option value=""><?php esc_html_e( 'All Rounds', 'wc2026-sweepstake' ); ?></option>
				<?php foreach ( $round_labels as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $filter_round, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<?php if ( ! $filter_round || 'Group' === $filter_round ) : ?>
		<label>
			<?php esc_html_e( 'Group:', 'wc2026-sweepstake' ); ?>
			<select name="wc_group">
				<option value=""><?php esc_html_e( 'All Groups', 'wc2026-sweepstake' ); ?></option>
				<?php foreach ( range( 'A', 'L' ) as $g ) : ?>
					<option value="<?php echo esc_attr( $g ); ?>" <?php selected( $filter_group, $g ); ?>>
						<?php echo esc_html( 'Group ' . $g ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php endif; ?>

		<label>
			<?php esc_html_e( 'Staff:', 'wc2026-sweepstake' ); ?>
			<select name="wc_staff">
				<option value=""><?php esc_html_e( 'All Staff', 'wc2026-sweepstake' ); ?></option>
				<?php foreach ( $all_staff as $s ) : ?>
					<option value="<?php echo esc_attr( $s->id ); ?>" <?php selected( $filter_staff, $s->id ); ?>>
						<?php echo esc_html( $s->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<button type="submit" class="wc2026-btn"><?php esc_html_e( 'Filter', 'wc2026-sweepstake' ); ?></button>
		<a href="?" class="wc2026-btn wc2026-btn-secondary"><?php esc_html_e( 'Reset', 'wc2026-sweepstake' ); ?></a>
	</form>

	<?php if ( empty( $by_date ) ) : ?>
		<p><?php esc_html_e( 'No fixtures found.', 'wc2026-sweepstake' ); ?></p>
	<?php else : ?>

	<div class="wc2026-fixtures-grid">
		<?php foreach ( $by_date as $date => $day_matches ) : ?>

		<div class="wc2026-fix-date-row">
			<?php echo esc_html( date_i18n( 'l j F Y', strtotime( $date ) ) ); ?>
		</div>

		<?php foreach ( $day_matches as $match ) :
			$home_staff = null;
			$away_staff = null;
			if ( $match->home_staff_slug ) {
				$home_staff = (object) array(
					'name'   => $match->home_staff_name,
					'slug'   => $match->home_staff_slug,
					'colour' => $match->home_staff_colour,
					'photo'  => $match->home_staff_photo,
				);
			}
			if ( $match->away_staff_slug ) {
				$away_staff = (object) array(
					'name'   => $match->away_staff_name,
					'slug'   => $match->away_staff_slug,
					'colour' => $match->away_staff_colour,
					'photo'  => $match->away_staff_photo,
				);
			}
			$round_label = $round_labels[ $match->round ] ?? $match->round;
			$is_live     = 'live' === $match->status;
		?>
		<div class="wc2026-fix-row status-<?php echo esc_attr( $match->status ); ?><?php echo $is_live ? ' is-live' : ''; ?>">

			<div class="wc2026-fix-time">
				<?php echo esc_html( substr( $match->kick_off_time ?? '', 0, 5 ) ); ?>
			</div>

			<div class="wc2026-fix-round">
				<span class="fix-round-label"><?php echo esc_html( $round_label ); ?></span>
				<?php if ( $match->group_letter ) : ?>
					<span class="wc2026-badge"><?php echo esc_html( $match->group_letter ); ?></span>
				<?php endif; ?>
			</div>

			<div class="wc2026-fix-home">
				<span class="wc2026-fix-team-name"><?php echo esc_html( $match->home_team_name ?? 'TBD' ); ?></span>
				<?php if ( $match->home_flag ) : ?>
					<img src="<?php echo esc_url( $match->home_flag ); ?>"
						alt="" class="wc2026-flag" loading="lazy" width="28" height="18">
				<?php endif; ?>
				<?php if ( $home_staff ) : ?>
					<button type="button" class="wc2026-staff-trigger" data-staff-slug="<?php echo esc_attr( $home_staff->slug ); ?>" title="<?php echo esc_attr( $home_staff->name ); ?>">
						<img src="<?php echo esc_url( WC2026_Staff::get_avatar_url( $home_staff, 'thumbnail' ) ); ?>"
							alt="<?php echo esc_attr( $home_staff->name ); ?>"
							class="wc2026-avatar wc2026-avatar-xs"
							loading="lazy">
					</button>
				<?php endif; ?>
			</div>

			<div class="wc2026-fix-score">
				<?php if ( null !== $match->home_score ) : ?>
					<strong class="wc2026-score-result"><?php echo esc_html( $match->home_score . ' – ' . $match->away_score ); ?></strong>
				<?php elseif ( $is_live ) : ?>
					<span class="wc2026-live-dot"></span>
				<?php else : ?>
					<span class="wc2026-vs">vs</span>
				<?php endif; ?>
			</div>

			<div class="wc2026-fix-away">
				<?php if ( $away_staff ) : ?>
					<button type="button" class="wc2026-staff-trigger" data-staff-slug="<?php echo esc_attr( $away_staff->slug ); ?>" title="<?php echo esc_attr( $away_staff->name ); ?>">
						<img src="<?php echo esc_url( WC2026_Staff::get_avatar_url( $away_staff, 'thumbnail' ) ); ?>"
							alt="<?php echo esc_attr( $away_staff->name ); ?>"
							class="wc2026-avatar wc2026-avatar-xs"
							loading="lazy">
					</button>
				<?php endif; ?>
				<?php if ( $match->away_flag ) : ?>
					<img src="<?php echo esc_url( $match->away_flag ); ?>"
						alt="" class="wc2026-flag" loading="lazy" width="28" height="18">
				<?php endif; ?>
				<span class="wc2026-fix-team-name"><?php echo esc_html( $match->away_team_name ?? 'TBD' ); ?></span>
			</div>

			<div class="wc2026-fix-status">
				<span class="wc2026-status-badge wc2026-status-<?php echo esc_attr( $match->status ); ?>">
					<?php echo esc_html( ucfirst( $match->status ) ); ?>
				</span>
			</div>

		</div><!-- .wc2026-fix-row -->
		<?php endforeach; // day_matches ?>

		<?php endforeach; // by_date ?>
	</div><!-- .wc2026-fixtures-grid -->

	<?php endif; ?>

</div><!-- .wc2026-fixtures -->
