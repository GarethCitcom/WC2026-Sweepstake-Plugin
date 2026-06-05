<?php
/**
 * Register all public shortcodes and the staff-popup AJAX handler.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Staff popup modal ──────────────────────────────────────────────────────
// Output the modal HTML once in wp_footer whenever any sweepstake shortcode
// is used on the page.
function wc2026_register_popup_footer() {
	static $registered = false;
	if ( $registered ) {
		return;
	}
	$registered = true;
	add_action( 'wp_footer', 'wc2026_output_staff_popup_modal' );
}

function wc2026_output_staff_popup_modal() {
	?>
	<div id="wc2026-staff-popup-overlay" class="wc2026-popup-overlay" aria-hidden="true">
		<div class="wc2026-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="wc2026-popup-name">
			<button type="button" class="wc2026-popup-close" aria-label="<?php esc_attr_e( 'Close', 'wc2026-sweepstake' ); ?>">&times;</button>
			<div id="wc2026-popup-content" class="wc2026-popup-body">
				<div class="wc2026-popup-loading">
					<span class="wc2026-spinner"></span>
				</div>
			</div>
		</div>
	</div>
	<?php
}

// AJAX: return staff profile HTML for the popup.
function wc2026_ajax_staff_popup() {
	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	if ( empty( $slug ) ) {
		wp_send_json_error( array( 'message' => 'Missing slug.' ), 400 );
	}
	$GLOBALS['wc2026_profile_slug'] = $slug;
	ob_start();
	include WC2026_PLUGIN_DIR . 'public/templates/staff-profile.php';
	$html = ob_get_clean();
	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_wc2026_staff_popup',        'wc2026_ajax_staff_popup' );
add_action( 'wp_ajax_nopriv_wc2026_staff_popup', 'wc2026_ajax_staff_popup' );

// ── Shortcodes ─────────────────────────────────────────────────────────────

/**
 * [wc_sweepstake] — Tabbed hub wrapping all sections.
 */
function wc2026_shortcode_sweepstake( $atts ) {
	wc2026_register_popup_footer();
	ob_start();
	include WC2026_PLUGIN_DIR . 'public/templates/sweepstake-hub.php';
	return ob_get_clean();
}
add_shortcode( 'wc_sweepstake', 'wc2026_shortcode_sweepstake' );

/**
 * [wc_wall_chart] — Group stage wall chart with standings, flags, and staff avatars.
 */
function wc2026_shortcode_wall_chart( $atts ) {
	wc2026_register_popup_footer();
	ob_start();
	include WC2026_PLUGIN_DIR . 'public/templates/wall-chart.php';
	return ob_get_clean();
}
add_shortcode( 'wc_wall_chart', 'wc2026_shortcode_wall_chart' );

/**
 * [wc_fixtures] — Full fixture list with filters.
 */
function wc2026_shortcode_fixtures( $atts ) {
	wc2026_register_popup_footer();
	ob_start();
	include WC2026_PLUGIN_DIR . 'public/templates/fixtures.php';
	return ob_get_clean();
}
add_shortcode( 'wc_fixtures', 'wc2026_shortcode_fixtures' );

/**
 * [wc_leaderboard] — Staff leaderboard sorted by points.
 */
function wc2026_shortcode_leaderboard( $atts ) {
	wc2026_register_popup_footer();
	ob_start();
	include WC2026_PLUGIN_DIR . 'public/templates/leaderboard.php';
	return ob_get_clean();
}
add_shortcode( 'wc_leaderboard', 'wc2026_shortcode_leaderboard' );

/**
 * [wc_staff_profile slug="gaz"] — Individual staff profile page.
 */
function wc2026_shortcode_staff_profile( $atts ) {
	$atts = shortcode_atts( array( 'slug' => '' ), $atts, 'wc_staff_profile' );
	if ( empty( $atts['slug'] ) ) {
		return '';
	}
	$GLOBALS['wc2026_profile_slug'] = sanitize_key( $atts['slug'] );
	ob_start();
	include WC2026_PLUGIN_DIR . 'public/templates/staff-profile.php';
	return ob_get_clean();
}
add_shortcode( 'wc_staff_profile', 'wc2026_shortcode_staff_profile' );

/**
 * [wc_wall_chart_full] — Full wall chart with bracket, groups, flags, and staff photos.
 */
function wc2026_shortcode_wall_chart_full( $atts ) {
	wp_enqueue_style(
		'wc2026-montserrat',
		'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&display=swap',
		array(),
		null
	);
	$wc_data = wc2026_wallchart_data();
	ob_start();
	include WC2026_PLUGIN_DIR . 'public/templates/wall-chart-full.php';
	return ob_get_clean();
}
add_shortcode( 'wc_wall_chart_full', 'wc2026_shortcode_wall_chart_full' );
