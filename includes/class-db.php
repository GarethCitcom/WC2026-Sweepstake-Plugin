<?php

/**
 * Database installation, seeding, and uninstall.
 *
 * @package WC2026_Sweepstake
 */

if (! defined('ABSPATH')) {
	exit;
}

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
			array('United States',      'USA', 'us',     'A'),
			array('Uruguay',             'URU', 'uy',     'A'),
			array('Algeria',             'ALG', 'dz',     'A'),
			array('Uzbekistan',          'UZB', 'uz',     'A'),
			// Group B
			array('Germany',             'DEU', 'de',     'B'),
			array('Netherlands',         'NED', 'nl',     'B'),
			array('Czechia',             'CZE', 'cz',     'B'),
			array('Curaçao',             'CUW', 'cw',     'B'),
			// Group C
			array('Brazil',              'BRA', 'br',     'C'),
			array('Paraguay',            'PRY', 'py',     'C'),
			array('Switzerland',         'CHE', 'ch',     'C'),
			array('Ghana',               'GHA', 'gh',     'C'),
			// Group D
			array('Spain',               'ESP', 'es',     'D'),
			array('Croatia',             'HRV', 'hr',     'D'),
			array('Morocco',             'MAR', 'ma',     'D'),
			array('Japan',               'JPN', 'jp',     'D'),
			// Group E
			array('France',              'FRA', 'fr',     'E'),
			array('Belgium',             'BEL', 'be',     'E'),
			array('Canada',              'CAN', 'ca',     'E'),
			array('Cabo Verde',          'CPV', 'cv',     'E'),
			// Group F
			array('Portugal',            'POR', 'pt',     'F'),
			array('Australia',           'AUS', 'au',     'F'),
			array('DR Congo',            'COD', 'cd',     'F'),
			array('Qatar',               'QAT', 'qa',     'F'),
			// Group G
			array('Argentina',           'ARG', 'ar',     'G'),
			array('Colombia',            'COL', 'co',     'G'),
			array('South Korea',         'KOR', 'kr',     'G'),
			array('Ecuador',             'ECU', 'ec',     'G'),
			// Group H
			array('England',             'ENG', 'gb-eng', 'H'),
			array('Iran',                'IRN', 'ir',     'H'),
			array('Senegal',             'SEN', 'sn',     'H'),
			array('Tunisia',             'TUN', 'tn',     'H'),
			// Group I
			array('Mexico',              'MEX', 'mx',     'I'),
			array('South Africa',        'ZAF', 'za',     'I'),
			array('Norway',              'NOR', 'no',     'I'),
			array('Iraq',                'IRQ', 'iq',     'I'),
			// Group J
			array('Sweden',              'SWE', 'se',     'J'),
			array('Austria',             'AUT', 'at',     'J'),
			array('Bosnia-Herzegovina',  'BIH', 'ba',     'J'),
			array('New Zealand',         'NZL', 'nz',     'J'),
			// Group K
			array('Scotland',            'SCO', 'gb-sct', 'K'),
			array('Côte d\'Ivoire',      'CIV', 'ci',     'K'),
			array('Jordan',              'JOR', 'jo',     'K'),
			array('Saudi Arabia',        'KSA', 'sa',     'K'),
			// Group L
			array('Panama',              'PAN', 'pa',     'L'),
			array('Haiti',               'HTI', 'ht',     'L'),
			array('Türkiye',             'TUR', 'tr',     'L'),
			array('Egypt',               'EGY', 'eg',     'L'),
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
