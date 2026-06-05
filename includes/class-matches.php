<?php
/**
 * Match CRUD and standings calculation.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

/**
 * Class WC2026_Matches
 */
class WC2026_Matches {

	/**
	 * Return total number of matches in the database.
	 *
	 * @return int
	 */
	public static function count() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_matches" ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Return all matches, optionally filtered.
	 *
	 * @param array $args {
	 *   @type string $round        Filter by round (Group, R32, QF, SF, Final).
	 *   @type string $group_letter Filter by group letter.
	 *   @type string $date         Filter by exact date (Y-m-d).
	 *   @type int    $staff_id     Filter to matches where a staff's country plays.
	 * }
	 * @return array
	 */
	public static function get_all( array $args = array() ) {
		global $wpdb;

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['round'] ) ) {
			$where[]  = 'm.round = %s';
			$params[] = $args['round'];
		}
		if ( ! empty( $args['group_letter'] ) ) {
			$where[]  = 'm.group_letter = %s';
			$params[] = $args['group_letter'];
		}
		if ( ! empty( $args['date'] ) ) {
			$where[]  = 'm.match_date = %s';
			$params[] = $args['date'];
		}

		$where_sql = implode( ' AND ', $where );

		$sql = "SELECT m.*,
				hc.name AS home_team_name, hc.flag_url AS home_flag, hc.fifa_code AS home_code,
				hs.name AS home_staff_name, hs.slug AS home_staff_slug, hs.colour AS home_staff_colour, hs.photo AS home_staff_photo,
				ac.name AS away_team_name, ac.flag_url AS away_flag, ac.fifa_code AS away_code,
				as2.name AS away_staff_name, as2.slug AS away_staff_slug, as2.colour AS away_staff_colour, as2.photo AS away_staff_photo
			FROM {$wpdb->prefix}wc_matches m
			LEFT JOIN {$wpdb->prefix}wc_countries hc ON hc.id = m.home_team_id
			LEFT JOIN {$wpdb->prefix}wc_staff hs ON hs.id = hc.staff_id
			LEFT JOIN {$wpdb->prefix}wc_countries ac ON ac.id = m.away_team_id
			LEFT JOIN {$wpdb->prefix}wc_staff as2 ON as2.id = ac.staff_id
			WHERE $where_sql
			ORDER BY m.match_date ASC, m.kick_off_time ASC, m.id ASC";

		if ( ! empty( $params ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Return a single match by ID.
	 *
	 * @param int $id Match ID.
	 * @return object|null
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT m.*,
					hc.name AS home_team_name, hc.flag_url AS home_flag,
					ac.name AS away_team_name, ac.flag_url AS away_flag
				 FROM {$wpdb->prefix}wc_matches m
				 LEFT JOIN {$wpdb->prefix}wc_countries hc ON hc.id = m.home_team_id
				 LEFT JOIN {$wpdb->prefix}wc_countries ac ON ac.id = m.away_team_id
				 WHERE m.id = %d",
				(int) $id
			)
		);
	}

	/**
	 * Update a match score and status.
	 *
	 * @param int      $id         Match ID.
	 * @param int      $home_score Home score.
	 * @param int      $away_score Away score.
	 * @param string   $status     Match status (scheduled|live|finished).
	 * @return bool
	 */
	public static function update_score( $id, $home_score, $away_score, $status = 'finished' ) {
		global $wpdb;
		$result = $wpdb->update(
			"{$wpdb->prefix}wc_matches",
			array(
				'home_score'  => (int) $home_score,
				'away_score'  => (int) $away_score,
				'status'      => $status,
				'updated_at'  => current_time( 'mysql' ),
			),
			array( 'id' => (int) $id ),
			array( '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);
		if ( $result ) {
			WC2026_Leaderboard::recalculate_match( (int) $id );
		}
		return (bool) $result;
	}

	/**
	 * Return today's group-stage matches.
	 *
	 * @return array
	 */
	public static function get_todays_matches() {
		return self::get_all( array( 'date' => current_time( 'Y-m-d' ) ) );
	}

	/**
	 * Return group-stage standings for a given group.
	 *
	 * @param string $group_letter Group letter A–L.
	 * @return array  Country rows with added keys: played, wins, draws, losses, gf, ga, gd, points.
	 */
	public static function get_group_standings( $group_letter ) {
		global $wpdb;

		$countries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*, s.name AS staff_name, s.slug AS staff_slug, s.colour AS staff_colour, s.photo AS staff_photo
				 FROM {$wpdb->prefix}wc_countries c
				 LEFT JOIN {$wpdb->prefix}wc_staff s ON s.id = c.staff_id
				 WHERE c.group_letter = %s",
				$group_letter
			)
		);

		$matches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_matches
				 WHERE group_letter = %s AND round = 'Group' AND status = 'finished'",
				$group_letter
			)
		);

		// Build standings array keyed by country ID.
		$standings = array();
		foreach ( $countries as $country ) {
			$standings[ $country->id ] = array(
				'country' => $country,
				'played'  => 0,
				'wins'    => 0,
				'draws'   => 0,
				'losses'  => 0,
				'gf'      => 0,
				'ga'      => 0,
				'gd'      => 0,
				'points'  => 0,
			);
		}

		foreach ( $matches as $match ) {
			if ( null === $match->home_score || null === $match->away_score ) {
				continue;
			}
			$h = (int) $match->home_team_id;
			$a = (int) $match->away_team_id;
			$hs = (int) $match->home_score;
			$as = (int) $match->away_score;

			if ( isset( $standings[ $h ] ) ) {
				$standings[ $h ]['played']++;
				$standings[ $h ]['gf'] += $hs;
				$standings[ $h ]['ga'] += $as;
				if ( $hs > $as ) {
					$standings[ $h ]['wins']++;
					$standings[ $h ]['points'] += 3;
				} elseif ( $hs === $as ) {
					$standings[ $h ]['draws']++;
					$standings[ $h ]['points'] += 1;
				} else {
					$standings[ $h ]['losses']++;
				}
			}
			if ( isset( $standings[ $a ] ) ) {
				$standings[ $a ]['played']++;
				$standings[ $a ]['gf'] += $as;
				$standings[ $a ]['ga'] += $hs;
				if ( $as > $hs ) {
					$standings[ $a ]['wins']++;
					$standings[ $a ]['points'] += 3;
				} elseif ( $hs === $as ) {
					$standings[ $a ]['draws']++;
					$standings[ $a ]['points'] += 1;
				} else {
					$standings[ $a ]['losses']++;
				}
			}
		}

		foreach ( $standings as &$row ) {
			$row['gd'] = $row['gf'] - $row['ga'];
		}
		unset( $row );

		// Sort: points desc, then GD desc, then GF desc.
		usort(
			$standings,
			function ( $a, $b ) {
				if ( $a['points'] !== $b['points'] ) {
					return $b['points'] - $a['points'];
				}
				if ( $a['gd'] !== $b['gd'] ) {
					return $b['gd'] - $a['gd'];
				}
				return $b['gf'] - $a['gf'];
			}
		);

		return $standings;
	}
}
