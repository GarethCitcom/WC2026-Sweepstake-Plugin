<?php
/**
 * Register WP admin menus and enqueue admin assets.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register top-level menu and sub-pages.
 */
function wc2026_register_admin_menu() {
	add_menu_page(
		__( 'WC2026 Sweepstake', 'wc2026-sweepstake' ),
		__( 'WC2026', 'wc2026-sweepstake' ),
		'manage_options',
		'wc2026-sweepstake',
		'wc2026_page_staff',
		'dashicons-flag',
		30
	);

	add_submenu_page(
		'wc2026-sweepstake',
		__( 'Staff Manager', 'wc2026-sweepstake' ),
		__( 'Staff', 'wc2026-sweepstake' ),
		'manage_options',
		'wc2026-sweepstake',
		'wc2026_page_staff'
	);

	add_submenu_page(
		'wc2026-sweepstake',
		__( 'Settings', 'wc2026-sweepstake' ),
		__( 'Settings', 'wc2026-sweepstake' ),
		'manage_options',
		'wc2026-settings',
		'wc2026_page_settings'
	);

	add_submenu_page(
		'wc2026-sweepstake',
		__( 'Help &amp; Reference', 'wc2026-sweepstake' ),
		__( 'Help', 'wc2026-sweepstake' ),
		'manage_options',
		'wc2026-help',
		'wc2026_page_help'
	);
}
add_action( 'admin_menu', 'wc2026_register_admin_menu' );

// Always load page files when in admin so the callback functions exist.
require_once WC2026_PLUGIN_DIR . 'admin/page-staff.php';
require_once WC2026_PLUGIN_DIR . 'admin/page-settings.php';
require_once WC2026_PLUGIN_DIR . 'admin/page-help.php';

/**
 * Enqueue admin CSS and the WP media library.
 *
 * @param string $hook Current admin page hook.
 */
function wc2026_enqueue_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'wc2026' ) ) {
		return;
	}
	wp_enqueue_style(
		'wc2026-admin',
		WC2026_PLUGIN_URL . 'admin/css/admin.css',
		array(),
		WC2026_VERSION
	);
	// WP Media Library.
	wp_enqueue_media();
	wp_enqueue_script(
		'wc2026-admin',
		WC2026_PLUGIN_URL . 'admin/js/admin.js',
		array( 'jquery' ),
		WC2026_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'wc2026_enqueue_admin_assets' );

/**
 * Show a setup notice until both an API key is saved and fixtures are imported.
 */
function wc2026_setup_notice() {
	$api_key       = get_option( 'wc2026_api_key', '' );
	$has_fixtures  = (bool) WC2026_Matches::count();

	if ( $api_key && $has_fixtures ) {
		return;
	}

	$settings_url = admin_url( 'admin.php?page=wc2026-settings' );
	echo '<div class="notice notice-warning"><p>';
	echo '<strong>' . esc_html__( 'WC2026 Sweepstake — setup required.', 'wc2026-sweepstake' ) . '</strong> ';
	if ( ! $api_key ) {
		printf(
			wp_kses(
				/* translators: %s: settings page URL */
				__( 'Get a free API key at <a href="https://www.football-data.org/" target="_blank" rel="noopener">football-data.org</a>, enter it on the <a href="%s">Settings page</a>, then click <strong>Import Fixture Schedule</strong> to load all matches.', 'wc2026-sweepstake' ),
				array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ), 'strong' => array() )
			),
			esc_url( $settings_url )
		);
	} else {
		printf(
			wp_kses(
				/* translators: %s: settings page URL */
				__( 'API key saved — go to the <a href="%s">Settings page</a> and click <strong>Import Fixture Schedule</strong> to load all matches.', 'wc2026-sweepstake' ),
				array( 'a' => array( 'href' => array() ), 'strong' => array() )
			),
			esc_url( $settings_url )
		);
	}
	echo '</p></div>';
}
add_action( 'admin_notices', 'wc2026_setup_notice' );
