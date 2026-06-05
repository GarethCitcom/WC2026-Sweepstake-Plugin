<?php

/**
 * Plugin Name: WC2026 Sweepstake
 * Plugin URI:  https://citcom.co.uk/wp-plugins/wc2026-sweepstake
 * Description: Internal World Cup 2026 sweepstake — wall chart, fixtures, scores, and leaderboard.
 * Version:     1.0.0
 * Author:      CitCom.
 * Author URI:  https://citcom.co.uk
 * License:     GPL-2.0-or-later
 * Text Domain: wc2026-sweepstake
 */

if (! defined('ABSPATH')) {
	exit;
}

define('WC2026_VERSION', '1.2.1');
define('WC2026_PLUGIN_FILE', __FILE__);
define('WC2026_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC2026_PLUGIN_URL', plugin_dir_url(__FILE__));

// Core includes.
require_once WC2026_PLUGIN_DIR . 'includes/class-db.php';
require_once WC2026_PLUGIN_DIR . 'includes/class-staff.php';
require_once WC2026_PLUGIN_DIR . 'includes/class-countries.php';
require_once WC2026_PLUGIN_DIR . 'includes/class-matches.php';
require_once WC2026_PLUGIN_DIR . 'includes/class-leaderboard.php';
require_once WC2026_PLUGIN_DIR . 'includes/class-api-sync.php';

// Wallchart data + print action — loaded on every request so the
// [wc_wall_chart_full] shortcode can call wc2026_wallchart_data() on the front end.
require_once WC2026_PLUGIN_DIR . 'admin/wallchart-generator.php';

// Admin-only UI.
if (is_admin()) {
	require_once WC2026_PLUGIN_DIR . 'admin/admin-menu.php';
}

// Public shortcodes.
require_once WC2026_PLUGIN_DIR . 'public/shortcodes.php';

register_activation_hook(__FILE__, array('WC2026_DB', 'install'));
register_deactivation_hook(__FILE__, 'wc2026_on_deactivate');
register_uninstall_hook(__FILE__, array('WC2026_DB', 'uninstall'));

/**
 * On deactivation: unschedule cron.
 */
function wc2026_on_deactivate()
{
	WC2026_API_Sync::unschedule();
}

// Run any pending DB migrations on every page load.
add_action('init', array('WC2026_DB', 'maybe_migrate'));

// Wire cron hook + keep schedule alive on every page load.
add_action('init', array('WC2026_API_Sync', 'maybe_schedule'));
add_action(WC2026_API_Sync::CRON_HOOK, function () {
	if (get_option('wc2026_api_enabled')) {
		WC2026_API_Sync::sync();
	}
});

/**
 * Enqueue front-end assets.
 */
function wc2026_enqueue_public_assets()
{
	wp_enqueue_style(
		'wc2026-sweepstake',
		WC2026_PLUGIN_URL . 'public/assets/css/sweepstake.css',
		array(),
		WC2026_VERSION
	);
	wp_enqueue_script(
		'wc2026-sweepstake',
		WC2026_PLUGIN_URL . 'public/assets/js/sweepstake.js',
		array('jquery'),
		WC2026_VERSION,
		true
	);
	wp_localize_script(
		'wc2026-sweepstake',
		'wc2026',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wc2026_staff_popup' ),
		)
	);
}
add_action('wp_enqueue_scripts', 'wc2026_enqueue_public_assets');
