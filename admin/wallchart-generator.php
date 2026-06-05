<?php
/**
 * Wall chart generator — admin-post action for the printable standalone page.
 * Data preparation and the [wc_wall_chart_full] shortcode share the same template.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assemble all data needed by the wall chart template.
 *
 * @return array
 */
function wc2026_wallchart_data() {
	$group_letters = range( 'A', 'L' );
	$standings     = array();
	foreach ( $group_letters as $g ) {
		$standings[ $g ] = WC2026_Matches::get_group_standings( $g );
	}

	$r32_all = WC2026_Matches::get_all( array( 'round' => 'R32' ) );
	$r16_all = WC2026_Matches::get_all( array( 'round' => 'R16' ) );
	$qf_all  = WC2026_Matches::get_all( array( 'round' => 'QF' ) );
	$sf_all  = WC2026_Matches::get_all( array( 'round' => 'SF' ) );
	$final   = WC2026_Matches::get_all( array( 'round' => 'Final' ) );
	$third   = WC2026_Matches::get_all( array( 'round' => '3rd' ) );

	return array(
		'standings' => $standings,
		'r32_l'     => array_slice( $r32_all, 0, 8 ),
		'r32_r'     => array_slice( $r32_all, 8, 8 ),
		'r16_l'     => array_slice( $r16_all, 0, 4 ),
		'r16_r'     => array_slice( $r16_all, 4, 4 ),
		'qf_l'      => array_slice( $qf_all,  0, 2 ),
		'qf_r'      => array_slice( $qf_all,  2, 2 ),
		'sf_l'      => $sf_all[0]  ?? null,
		'sf_r'      => $sf_all[1]  ?? null,
		'fin'       => $final[0]   ?? null,
		'trd'       => $third[0]   ?? null,
		'all_staff' => WC2026_Staff::get_all(),
	);
}

/**
 * Handle the admin-post action: output a complete standalone HTML document
 * wrapping the shared wall chart template.
 */
function wc2026_handle_wallchart_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions.' );
	}
	check_admin_referer( 'wc2026_wallchart' );

	$wc_data = wc2026_wallchart_data();

	// Flush any existing output buffers so we own the full response.
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'Cache-Control: no-store, no-cache' );
	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>WC2026 Sweepstake — Wall Chart</title>
<link rel="preconnect" href="https://fonts.googleapis.com">

<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
html, body { margin: 0; padding: 0; background: #edf0f4; }
@media print { html, body { background: #fff; } }
</style>
</head>
<body>
	<?php include WC2026_PLUGIN_DIR . 'public/templates/wall-chart-full.php'; ?>
</body>
</html>
	<?php
	exit;
}
add_action( 'admin_post_wc2026_wallchart', 'wc2026_handle_wallchart_action' );
