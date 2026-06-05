<?php

/**
 * Database installation, seeding, and uninstall.
 *
 * @package WC2026_Sweepstake
 */

if (! defined('ABSPATH')) {
	exit;
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

/**
 * Class WC2026_DB
 */
class WC2026_DB
{

	/**
	 * Current DB schema version stored in options.
	 */
	const SCHEMA_VERSION = 2;

	/**
	 * Run on plugin activation: create tables then seed data.
	 */
	public static function install()
	{
		self::create_tables();
		self::seed_data();
		update_option('wc2026_db_version', self::SCHEMA_VERSION);
	}

	/**
	 * Run any pending DB migrations. Called on every page load via init hook.
	 */
	public static function maybe_migrate()
	{
		$installed = (int) get_option('wc2026_db_version', 0);
		if ($installed >= self::SCHEMA_VERSION) {
			return;
		}

		// v2 — update flag CDN path from /w40/ to /28x21/.
		if ($installed < 2) {
			global $wpdb;
			$wpdb->query(
				"UPDATE {$wpdb->prefix}wc_countries
				 SET flag_url = REPLACE(flag_url, '/w40/', '/28x21/')
				 WHERE flag_url LIKE '%/w40/%'"
			); // phpcs:ignore WordPress.DB.PreparedSQL
		}

		update_option('wc2026_db_version', self::SCHEMA_VERSION);
	}

	/**
	 * Create all plugin tables using dbDelta.
	 */
	private static function create_tables()
	{
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}wc_staff (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			colour varchar(7) NOT NULL DEFAULT '#cccccc',
			photo int(11) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) $charset;

		CREATE TABLE {$wpdb->prefix}wc_countries (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			flag_url varchar(500) DEFAULT NULL,
			staff_id int(11) DEFAULT NULL,
			group_letter varchar(1) DEFAULT NULL,
			fifa_code varchar(3) DEFAULT NULL,
			PRIMARY KEY (id),
			KEY staff_id (staff_id),
			KEY group_letter (group_letter)
		) $charset;

		CREATE TABLE {$wpdb->prefix}wc_matches (
			id int(11) NOT NULL AUTO_INCREMENT,
			match_date date DEFAULT NULL,
			kick_off_time time DEFAULT NULL,
			home_team_id int(11) DEFAULT NULL,
			away_team_id int(11) DEFAULT NULL,
			home_score int(11) DEFAULT NULL,
			away_score int(11) DEFAULT NULL,
			round varchar(50) NOT NULL DEFAULT 'Group' COMMENT 'Group|R32|R16|QF|SF|3rd|Final',
			group_letter varchar(1) DEFAULT NULL,
			api_match_id int(11) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'scheduled',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY home_team_id (home_team_id),
			KEY away_team_id (away_team_id),
			KEY match_date (match_date),
			KEY round (round)
		) $charset;

		CREATE TABLE {$wpdb->prefix}wc_points_log (
			id int(11) NOT NULL AUTO_INCREMENT,
			staff_id int(11) NOT NULL,
			country_id int(11) NOT NULL,
			match_id int(11) DEFAULT NULL,
			points int(11) NOT NULL DEFAULT 0,
			reason varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY staff_id (staff_id),
			KEY country_id (country_id),
			KEY match_id (match_id)
		) $charset;";

		dbDelta($sql);
	}

	/**
	 * Seed the 48 WC2026 countries — only runs once on activation.
	 * No staff or fixtures are seeded; clients configure those via admin.
	 */
	private static function seed_data()
	{
		global $wpdb;

		// Guard: skip if countries already seeded.
		$count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wc_countries");
		if ($count > 0) {
			return;
		}

		// Countries (name, fifa_code, iso2 for flag, group)
		$countries_data = array(
			// Group A
			array('Mexico',              'MEX', 'mx',     'A'),
			array('South Africa',        'ZAF', 'za',     'A'),
			array('South Korea',         'KOR', 'kr',     'A'),
			array('Czechia',             'CZE', 'cz',     'A'),
			// Group B
			array('Canada',              'CAN', 'ca',     'B'),
			array('Bosnia-Herzegovina',  'BIH', 'ba',     'B'),
			array('Qatar',               'QAT', 'qa',     'B'),
			array('Switzerland',         'CHE', 'ch',     'B'),
			// Group C
			array('Brazil',              'BRA', 'br',     'C'),
			array('Morocco',             'MAR', 'ma',     'C'),
			array('Haiti',               'HTI', 'ht',     'C'),
			array('Scotland',            'SCO', 'gb-sct', 'C'),
			// Group D
			array('United States',       'USA', 'us',     'D'),
			array('Paraguay',            'PRY', 'py',     'D'),
			array('Australia',           'AUS', 'au',     'D'),
			array('Türkiye',             'TUR', 'tr',     'D'),
			// Group E
			array('Germany',             'DEU', 'de',     'E'),
			array('Curaçao',             'CUW', 'cw',     'E'),
			array('Côte d\'Ivoire',      'CIV', 'ci',     'E'),
			array('Ecuador',             'ECU', 'ec',     'E'),
			// Group F
			array('Netherlands',         'NED', 'nl',     'F'),
			array('Japan',               'JPN', 'jp',     'F'),
			array('Sweden',              'SWE', 'se',     'F'),
			array('Tunisia',             'TUN', 'tn',     'F'),
			// Group G
			array('Belgium',             'BEL', 'be',     'G'),
			array('Egypt',               'EGY', 'eg',     'G'),
			array('Iran',                'IRN', 'ir',     'G'),
			array('New Zealand',         'NZL', 'nz',     'G'),
			// Group H
			array('Spain',               'ESP', 'es',     'H'),
			array('Cabo Verde',          'CPV', 'cv',     'H'),
			array('Saudi Arabia',        'KSA', 'sa',     'H'),
			array('Uruguay',             'URU', 'uy',     'H'),
			// Group I
			array('France',              'FRA', 'fr',     'I'),
			array('Senegal',             'SEN', 'sn',     'I'),
			array('Iraq',                'IRQ', 'iq',     'I'),
			array('Norway',              'NOR', 'no',     'I'),
			// Group J
			array('Argentina',           'ARG', 'ar',     'J'),
			array('Algeria',             'ALG', 'dz',     'J'),
			array('Austria',             'AUT', 'at',     'J'),
			array('Jordan',              'JOR', 'jo',     'J'),
			// Group K
			array('Portugal',            'POR', 'pt',     'K'),
			array('DR Congo',            'COD', 'cd',     'K'),
			array('Uzbekistan',          'UZB', 'uz',     'K'),
			array('Colombia',            'COL', 'co',     'K'),
			// Group L
			array('England',             'ENG', 'gb-eng', 'L'),
			array('Croatia',             'HRV', 'hr',     'L'),
			array('Ghana',               'GHA', 'gh',     'L'),
			array('Panama',              'PAN', 'pa',     'L'),
		);

		foreach ($countries_data as $c) {
			list($name, $fifa, $iso2, $group) = $c;
			$wpdb->insert(
				"{$wpdb->prefix}wc_countries",
				array(
					'name'         => $name,
					'flag_url'     => 'https://flagcdn.com/40x30/' . $iso2 . '.png',
					'staff_id'     => null,
					'group_letter' => $group,
					'fifa_code'    => $fifa,
				),
				array('%s', '%s', '%d', '%s', '%s')
			);
		}
	}

	/**
	 * Reset all user data back to the post-activation blank state.
	 * Clears staff, matches, and points log; resets country staff assignments.
	 */
	public static function reset_data()
	{
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_points_log" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_matches" );    // phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_staff" );      // phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( "UPDATE {$wpdb->prefix}wc_countries SET staff_id = NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Drop all plugin tables on uninstall.
	 */
	public static function uninstall()
	{
		global $wpdb;
		$tables = array('wc_points_log', 'wc_matches', 'wc_countries', 'wc_staff');
		foreach ($tables as $table) {
			$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}"); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		delete_option('wc2026_db_version');
	}
}
