<?php
/**
 * Staff profile template — rendered by [wc_staff_profile slug="gaz"] shortcode.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug  = isset( $GLOBALS['wc2026_profile_slug'] ) ? $GLOBALS['wc2026_profile_slug'] : '';
$staff = WC2026_Staff::get_by_slug( $slug );

if ( ! $staff ) {
	echo '<p>' . esc_html__( 'Staff member not found.', 'wc2026-sweepstake' ) . '</p>';
	return;
}

$avatar_url = WC2026_Staff::get_avatar_url( $staff, 'large' );
$countries  = WC2026_Countries::get_by_staff( $staff->id );
?>
<div class="wc2026-staff-profile" style="--staff-colour: <?php echo esc_attr( $staff->colour ); ?>">

	<div class="wc2026-profile-hero">
		<img
			src="<?php echo esc_url( $avatar_url ); ?>"
			alt="<?php echo esc_attr( $staff->name ); ?>"
			class="wc2026-avatar wc2026-avatar-hero"
		>
		<div class="wc2026-profile-info">
			<h2 class="wc2026-profile-name"><?php echo esc_html( $staff->name ); ?></h2>
			<p class="wc2026-profile-country-count">
				<?php
				printf(
					/* translators: %d: number of countries */
					esc_html( _n( '%d country', '%d countries', count( $countries ), 'wc2026-sweepstake' ) ),
					count( $countries )
				);
				?>
			</p>
		</div>
	</div>

	<div class="wc2026-profile-countries">
		<h3><?php esc_html_e( 'Countries', 'wc2026-sweepstake' ); ?></h3>
		<ul class="wc2026-country-list">
		<?php foreach ( $countries as $country ) : ?>
			<li class="wc2026-country-item">
				<?php if ( $country->flag_url ) : ?>
					<img
						src="<?php echo esc_url( $country->flag_url ); ?>"
						alt="<?php echo esc_attr( $country->name ); ?>"
						class="wc2026-flag wc2026-flag-md"
						loading="lazy"
						width="40"
						height="27"
					>
				<?php endif; ?>
				<span class="wc2026-country-label">
					<?php echo esc_html( $country->name ); ?>
					<?php if ( $country->group_letter ) : ?>
						<span class="wc2026-badge">
							<?php
							printf(
								/* translators: %s: group letter */
								esc_html__( 'Group %s', 'wc2026-sweepstake' ),
								esc_html( $country->group_letter )
							);
							?>
						</span>
					<?php endif; ?>
				</span>
			</li>
		<?php endforeach; ?>
		</ul>
	</div>

</div><!-- .wc2026-staff-profile -->
