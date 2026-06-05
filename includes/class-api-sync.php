<?php
/**
 * football-data.org score sync via wp_cron.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC2026_API_Sync
 */
class WC2026_API_Sync {

	const API_BASE  = 'https://api.football-data.org/v4';
	const COMP_CODE = 'WC';
	const CRON_HOOK = 'wc2026_cron_sync';

	/**
	 * Maps football-data.org TLA codes to our FIFA codes where they differ.
	 */
	private static $tla_map = array(
		'GER' => 'DEU',  // Germany
		'CRO' => 'HRV',  // Croatia
		'PAR' => 'PRY',  // Paraguay
		'SUI' => 'CHE',  // Switzerland
		'RSA' => 'ZAF',  // South Africa
		'SAF' => 'ZAF',
		'SAU' => 'KSA',  // Saudi Arabia
		'IRI' => 'IRN',  // Iran
		'ALG' => 'ALG',
	);

	/**
	 * Fallback name-to-FIFA-code map for teams whose TLA doesn't resolve directly.
	 */
	private static $name_map = array(
		'korea republic'               => 'KOR',
		'south korea'                  => 'KOR',
		'republic of korea'            => 'KOR',
		'ir iran'                      => 'IRN',
		'iran'                         => 'IRN',
		'islamic republic of iran'     => 'IRN',
		'united states'                => 'USA',
		'usa'                          => 'USA',
		'cape verde'                   => 'CPV',
		'cabo verde'                   => 'CPV',
		'dr congo'                     => 'COD',
		'congo dr'                     => 'COD',
		'democratic republic of congo' => 'COD',
		'dem. rep. congo'              => 'COD',
		'ivory coast'                  => 'CIV',
		"cote d'ivoire"                => 'CIV',
		'côte d\'ivoire'               => 'CIV',
		'turkey'                       => 'TUR',
		'türkiye'                      => 'TUR',
		'netherlands'                  => 'NED',
		'czech republic'               => 'CZE',
		'czechia'                      => 'CZE',
		'new zealand'                  => 'NZL',
		'south africa'                 => 'ZAF',
		'saudi arabia'                 => 'KSA',
		'bosnia'                       => 'BIH',
		'bosnia and herzegovina'       => 'BIH',
		'bosnia-herzegovina'           => 'BIH',
		'uzbekistan'                   => 'UZB',
		'curacao'                      => 'CUW',
		'curaçao'                      => 'CUW',
		'germany'                      => 'DEU',
		'croatia'                      => 'HRV',
		'paraguay'                     => 'PRY',
		'switzerland'                  => 'CHE',
		'haiti'                        => 'HTI',
		'algeria'                      => 'ALG',
	);

	// ----------------------------------------------------------------
	// Cron registration
	// ----------------------------------------------------------------

	/**
	 * Schedule the hourly cron if not already scheduled.
	 */
	public static function maybe_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the cron job — called on plugin deactivation.
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	// ----------------------------------------------------------------
	// Main sync entry point
	// ----------------------------------------------------------------

	/**
	 * Fetch latest scores from football-data.org and update our matches.
	 *
	 * @param bool $force_all  When true, fetches all tournament fixtures.
	 * @return array { updated: int, skipped: int, errors: string[] }
	 */
	public static function sync( $force_all = false ) {
		$api_key = get_option( 'wc2026_api_key', '' );
		if ( empty( $api_key ) ) {
			return array( 'updated' => 0, 'skipped' => 0, 'errors' => array( 'No API key configured.' ) );
		}

		$result = array( 'updated' => 0, 'skipped' => 0, 'errors' => array() );

		$params = array();
		if ( ! $force_all ) {
			$params['dateFrom'] = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
			$params['dateTo']   = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		}

		$data = self::api_request( 'competitions/' . self::COMP_CODE . '/matches', $params, $api_key );
		if ( is_wp_error( $data ) ) {
			$result['errors'][] = $data->get_error_message();
			update_option( 'wc2026_last_sync_error', $data->get_error_message() );
			return $result;
		}

		if ( empty( $data['matches'] ) ) {
			update_option( 'wc2026_last_sync', current_time( 'mysql' ) );
			return $result;
		}

		// Build country lookup keyed by FIFA code.
		$countries  = WC2026_Countries::get_all();
		$code_to_id = array();
		foreach ( $countries as $c ) {
			$code_to_id[ $c->fifa_code ] = (int) $c->id;
		}

		foreach ( $data['matches'] as $item ) {
			$api_id     = (int) $item['id'];
			$api_date   = substr( $item['utcDate'], 0, 10 );   // "2026-06-11T…" → "2026-06-11"
			$api_status = $item['status'];
			$home_name  = $item['homeTeam']['name'] ?? '';
			$home_tla   = $item['homeTeam']['tla']  ?? '';
			$away_name  = $item['awayTeam']['name'] ?? '';
			$away_tla   = $item['awayTeam']['tla']  ?? '';
			$home_goals = $item['score']['fullTime']['home'] ?? null;
			$away_goals = $item['score']['fullTime']['away'] ?? null;

			$our_status = self::map_status( $api_status );

			$match = self::find_match( $api_id, $api_date, $home_name, $home_tla, $away_name, $away_tla, $code_to_id );
			if ( ! $match ) {
				$result['skipped']++;
				continue;
			}

			// Store the api_match_id if not yet set.
			if ( ! $match->api_match_id ) {
				self::set_api_match_id( $match->id, $api_id );
			}

			// Update team IDs when the API fills in a TBD knockout slot.
			if ( ! $match->home_team_id && $home_id ) {
				global $wpdb;
				$wpdb->update( "{$wpdb->prefix}wc_matches", array( 'home_team_id' => $home_id ), array( 'id' => $match->id ), array( '%d' ), array( '%d' ) );
			}
			if ( ! $match->away_team_id && $away_id ) {
				global $wpdb;
				$wpdb->update( "{$wpdb->prefix}wc_matches", array( 'away_team_id' => $away_id ), array( 'id' => $match->id ), array( '%d' ), array( '%d' ) );
			}

			$score_changed  = ( (string) $match->home_score !== (string) $home_goals )
							|| ( (string) $match->away_score !== (string) $away_goals );
			$status_changed = $match->status !== $our_status;
			$teams_changed  = ( ! $match->home_team_id && $home_id ) || ( ! $match->away_team_id && $away_id );

			if ( ! $score_changed && ! $status_changed && ! $teams_changed ) {
				$result['skipped']++;
				continue;
			}

			if ( null !== $home_goals && null !== $away_goals ) {
				WC2026_Matches::update_score( $match->id, (int) $home_goals, (int) $away_goals, $our_status );
				$result['updated']++;
			} elseif ( $status_changed ) {
				self::update_status_only( $match->id, $our_status );
				$result['skipped']++;
			} else {
				$result['skipped']++;
			}
		}

		update_option( 'wc2026_last_sync', current_time( 'mysql' ) );
		update_option( 'wc2026_last_sync_error', '' );
		return $result;
	}

	// ----------------------------------------------------------------
	// Helpers
	// ----------------------------------------------------------------

	/**
	 * Make an authenticated GET request to football-data.org.
	 *
	 * @param string $endpoint  e.g. 'competitions/WC/matches'
	 * @param array  $params    Query params.
	 * @param string $api_key   API token.
	 * @return array|WP_Error   Decoded JSON body or WP_Error.
	 */
	private static function api_request( $endpoint, array $params, $api_key ) {
		$url = self::API_BASE . '/' . $endpoint;
		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'X-Auth-Token' => $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== (int) $code ) {
			$msg = isset( $body['message'] ) ? $body['message'] : 'API returned HTTP ' . $code;
			return new WP_Error( 'api_error', $msg );
		}

		if ( null === $body ) {
			return new WP_Error( 'parse_error', 'Could not parse API response.' );
		}

		return $body;
	}

	/**
	 * Map football-data.org status strings to our status values.
	 *
	 * @param string $api_status  e.g. 'FINISHED', 'IN_PLAY', 'SCHEDULED'.
	 * @return string  'scheduled'|'live'|'finished'
	 */
	private static function map_status( $api_status ) {
		$finished = array( 'FINISHED', 'AWARDED' );
		$live     = array( 'IN_PLAY', 'PAUSED', 'LIVE' );
		if ( in_array( $api_status, $finished, true ) ) {
			return 'finished';
		}
		if ( in_array( $api_status, $live, true ) ) {
			return 'live';
		}
		return 'scheduled';
	}

	/**
	 * Try to locate our match row for an API fixture.
	 *
	 * Priority: (1) api_match_id exact match, (2) date + resolved team IDs.
	 *
	 * @param int    $api_id    API match ID.
	 * @param string $api_date  Match date Y-m-d.
	 * @param string $home_name API home team name.
	 * @param string $home_tla  API home team TLA code.
	 * @param string $away_name API away team name.
	 * @param string $away_tla  API away team TLA code.
	 * @param array  $code_to_id Map of fifa_code → country id.
	 * @return object|null  Match row or null.
	 */
	private static function find_match( $api_id, $api_date, $home_name, $home_tla, $away_name, $away_tla, $code_to_id ) {
		global $wpdb;

		// 1. By stored api_match_id.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_matches WHERE api_match_id = %d",
				$api_id
			)
		);
		if ( $row ) {
			return $row;
		}

		// 2. Resolve team names/TLAs to our country IDs.
		$home_id = self::resolve_country_id( $home_name, $home_tla, $code_to_id );
		$away_id = self::resolve_country_id( $away_name, $away_tla, $code_to_id );

		if ( ! $home_id || ! $away_id ) {
			return null;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_matches
				 WHERE match_date = %s AND home_team_id = %d AND away_team_id = %d",
				$api_date,
				$home_id,
				$away_id
			)
		);
	}

	/**
	 * Resolve an API team (by TLA then name) to one of our country IDs.
	 *
	 * @param string $api_name  Team name from API response.
	 * @param string $api_tla   Three-letter code from API response.
	 * @param array  $code_to_id Map of fifa_code → country id.
	 * @return int|null
	 */
	private static function resolve_country_id( $api_name, $api_tla, $code_to_id ) {
		$upper = strtoupper( trim( $api_tla ) );

		// 1. TLA matches our FIFA code directly (covers most teams).
		if ( $upper && isset( $code_to_id[ $upper ] ) ) {
			return $code_to_id[ $upper ];
		}

		// 2. TLA remapped through our translation table.
		if ( $upper && isset( self::$tla_map[ $upper ] ) ) {
			$code = self::$tla_map[ $upper ];
			if ( isset( $code_to_id[ $code ] ) ) {
				return $code_to_id[ $code ];
			}
		}

		// 3. Name map lookup.
		$lower = strtolower( trim( $api_name ) );
		if ( isset( self::$name_map[ $lower ] ) ) {
			$code = self::$name_map[ $lower ];
			return isset( $code_to_id[ $code ] ) ? $code_to_id[ $code ] : null;
		}

		// 4. Direct name match in DB.
		global $wpdb;
		$id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}wc_countries WHERE LOWER(name) = %s",
				$lower
			)
		);

		return $id ?: null;
	}

	/**
	 * Persist the API match ID against our match row.
	 *
	 * @param int $match_id Our match ID.
	 * @param int $api_id   API match ID.
	 */
	private static function set_api_match_id( $match_id, $api_id ) {
		global $wpdb;
		$wpdb->update(
			"{$wpdb->prefix}wc_matches",
			array( 'api_match_id' => $api_id ),
			array( 'id' => $match_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Update match status without touching the score.
	 *
	 * @param int    $match_id Our match ID.
	 * @param string $status   New status.
	 */
	private static function update_status_only( $match_id, $status ) {
		global $wpdb;
		$wpdb->update(
			"{$wpdb->prefix}wc_matches",
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $match_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	// ----------------------------------------------------------------
	// Full fixture import (one-time setup / re-seed from API)
	// ----------------------------------------------------------------

	/**
	 * Fetch ALL fixtures from football-data.org and replace the matches table.
	 * Also clears the points log since match IDs change.
	 *
	 * @return array { imported: int, skipped: int, errors: string[] }
	 */
	public static function import_fixtures() {
		$api_key = get_option( 'wc2026_api_key', '' );
		if ( empty( $api_key ) ) {
			return array( 'imported' => 0, 'skipped' => 0, 'errors' => array( 'No API key configured.' ) );
		}

		$data = self::api_request( 'competitions/' . self::COMP_CODE . '/matches', array(), $api_key );
		if ( is_wp_error( $data ) ) {
			return array( 'imported' => 0, 'skipped' => 0, 'errors' => array( $data->get_error_message() ) );
		}

		if ( empty( $data['matches'] ) ) {
			return array( 'imported' => 0, 'skipped' => 0, 'errors' => array( 'No matches returned from API.' ) );
		}

		// Build country lookup keyed by FIFA code.
		$countries  = WC2026_Countries::get_all();
		$code_to_id = array();
		foreach ( $countries as $c ) {
			$code_to_id[ $c->fifa_code ] = (int) $c->id;
		}

		global $wpdb;

		// Wipe existing data — match IDs are about to change so points log is invalid anyway.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_points_log" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_matches" );    // phpcs:ignore WordPress.DB.PreparedSQL

		$imported       = 0;
		$skipped        = 0;
		$errors         = array();
		$country_groups = array(); // country_id → group_letter, built from group-stage matches

		foreach ( $data['matches'] as $item ) {
			$api_id     = (int) ( $item['id'] ?? 0 );
			$utc_date   = $item['utcDate'] ?? '';
			$api_date   = substr( $utc_date, 0, 10 );                       // "2026-06-11"
			$kick_off   = strlen( $utc_date ) >= 19 ? substr( $utc_date, 11, 8 ) : null; // "19:00:00"
			$stage      = $item['stage']  ?? 'GROUP_STAGE';
			$group      = $item['group']  ?? null;
			$home_name  = $item['homeTeam']['name'] ?? '';
			$home_tla   = $item['homeTeam']['tla']  ?? '';
			$away_name  = $item['awayTeam']['name'] ?? '';
			$away_tla   = $item['awayTeam']['tla']  ?? '';
			$api_status = $item['status'] ?? 'SCHEDULED';
			$home_goals = $item['score']['fullTime']['home'] ?? null;
			$away_goals = $item['score']['fullTime']['away'] ?? null;

			$home_id    = self::resolve_country_id( $home_name, $home_tla, $code_to_id );
			$away_id    = self::resolve_country_id( $away_name, $away_tla, $code_to_id );
			$round      = self::map_stage( $stage );
			$letter     = self::extract_group_letter( $group );
			$our_status = self::map_status( $api_status );

			// Collect group assignments from group-stage matches to fix wc_countries later.
			if ( 'Group' === $round && $letter ) {
				if ( $home_id ) { $country_groups[ $home_id ] = $letter; }
				if ( $away_id ) { $country_groups[ $away_id ] = $letter; }
			}

			// Build insert array — omit nullable columns when null so DB DEFAULT (NULL) applies.
			$insert  = array(
				'match_date'    => $api_date,
				'round'         => $round,
				'api_match_id'  => $api_id,
				'status'        => $our_status,
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			);
			$formats = array( '%s', '%s', '%d', '%s', '%s', '%s' );

			if ( $kick_off ) {
				$insert['kick_off_time'] = $kick_off;
				$formats[]               = '%s';
			}
			if ( $letter ) {
				$insert['group_letter'] = $letter;
				$formats[]              = '%s';
			}
			if ( $home_id ) {
				$insert['home_team_id'] = $home_id;
				$formats[]              = '%d';
			}
			if ( $away_id ) {
				$insert['away_team_id'] = $away_id;
				$formats[]              = '%d';
			}
			if ( null !== $home_goals && null !== $away_goals ) {
				$insert['home_score'] = (int) $home_goals;
				$insert['away_score'] = (int) $away_goals;
				$formats[]            = '%d';
				$formats[]            = '%d';
			}

			$wpdb->insert( "{$wpdb->prefix}wc_matches", $insert, $formats );

			if ( $wpdb->last_error ) {
				$errors[] = $wpdb->last_error;
			} elseif ( ! $home_id || ! $away_id ) {
				$skipped++;  // Knockout TBD — inserted with null team IDs, counts as partial.
				$imported++;
			} else {
				$imported++;
			}
		}

		// Update each country's group_letter to match the real fixture data.
		foreach ( $country_groups as $country_id => $group_letter ) {
			$wpdb->update(
				"{$wpdb->prefix}wc_countries",
				array( 'group_letter' => $group_letter ),
				array( 'id'           => $country_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		update_option( 'wc2026_last_sync', current_time( 'mysql' ) );
		update_option( 'wc2026_last_sync_error', '' );

		return array( 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors );
	}

	/**
	 * Map football-data.org stage strings to our round values.
	 *
	 * @param string $stage e.g. 'GROUP_STAGE', 'LAST_16', 'FINAL'.
	 * @return string
	 */
	private static function map_stage( $stage ) {
		$map = array(
			'GROUP_STAGE'    => 'Group',
			'LAST_32'        => 'R32',
			'ROUND_OF_32'    => 'R32',
			'LAST_16'        => 'R16',
			'ROUND_OF_16'    => 'R16',
			'QUARTER_FINALS' => 'QF',
			'SEMI_FINALS'    => 'SF',
			'THIRD_PLACE'    => '3rd',
			'PLAY_OFF'       => 'Final',
			'FINAL'          => 'Final',
		);
		return $map[ strtoupper( $stage ) ] ?? 'Group';
	}

	/**
	 * Extract a group letter (A–L) from the API group field.
	 *
	 * Handles formats: "GROUP_A", "Group A", "A", null, etc.
	 *
	 * @param string|null $group API group string.
	 * @return string|null
	 */
	private static function extract_group_letter( $group ) {
		if ( empty( $group ) ) {
			return null;
		}
		// Match the last A–L character in the string (handles "GROUP_A", "Group B", etc.)
		if ( preg_match( '/([A-L])$/i', trim( $group ), $m ) ) {
			return strtoupper( $m[1] );
		}
		return null;
	}

	// ----------------------------------------------------------------
	// Connection test
	// ----------------------------------------------------------------

	/**
	 * Test the API key by fetching the WC competition info.
	 * Returns a status array or WP_Error.
	 *
	 * @param string $api_key API token.
	 * @return array|WP_Error
	 */
	public static function get_status( $api_key ) {
		$response = self::api_request( 'competitions/' . self::COMP_CODE, array(), $api_key );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$comp_name = $response['name'] ?? 'FIFA World Cup';
		return array(
			'plan'           => 'Connected',
			'requests_today' => '—',
			'requests_limit' => $comp_name,
		);
	}
}
