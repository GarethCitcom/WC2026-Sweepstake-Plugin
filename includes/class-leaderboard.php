<?php
/**
 * Points calculation and leaderboard.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC2026_Leaderboard
 */
class WC2026_Leaderboard {

	/**
	 * Hard-coded defaults — used when no option is saved yet.
	 *
	 * @return array
	 */
	public static function get_default_point_values() {
		return array(
			'group_win'   => 2,
			'group_draw'  => 1,
			'reach_r32'   => 3,
			'reach_r16'   => 5,
			'reach_qf'    => 8,
			'reach_sf'    => 12,
			'reach_final' => 18,
			'winner'      => 25,
		);
	}

	/**
	 * Return active point values: saved option merged over defaults, then filtered.
	 *
	 * @return array
	 */
	public static function get_point_values() {
		$saved = get_option( 'wc2026_point_values', array() );
		$pts   = wp_parse_args( $saved, self::get_default_point_values() );
		return apply_filters( 'wc2026_point_values', $pts );
	}

	/**
	 * Recalculate and log points for a single finished match.
	 * Deletes existing log rows for this match before re-inserting.
	 *
	 * @param int $match_id Match ID.
	 */
	public static function recalculate_match( $match_id ) {
		global $wpdb;

		$match = WC2026_Matches::get_by_id( $match_id );
		if ( ! $match || null === $match->home_score ) {
			return;
		}

		// Clear old log rows for this match.
		$wpdb->delete(
			"{$wpdb->prefix}wc_points_log",
			array( 'match_id' => $match_id ),
			array( '%d' )
		);

		if ( 'finished' !== $match->status ) {
			return;
		}

		$pts = self::get_point_values();
		$now = current_time( 'mysql' );

		if ( 'Group' === $match->round ) {
			self::award_group_points( $match, $pts, $now );
		} else {
			self::award_knockout_points( $match, $pts, $now );
		}
	}

	/**
	 * Award points for a finished group-stage match.
	 *
	 * @param object $match Match row.
	 * @param array  $pts   Point values.
	 * @param string $now   MySQL datetime.
	 */
	private static function award_group_points( $match, $pts, $now ) {
		$home = WC2026_Countries::get_by_id( (int) $match->home_team_id );
		$away = WC2026_Countries::get_by_id( (int) $match->away_team_id );

		if ( ! $home || ! $away ) {
			return;
		}

		$hs = (int) $match->home_score;
		$as = (int) $match->away_score;

		if ( $hs > $as ) {
			self::log_points( $home->staff_id, $home->id, $match->id, $pts['group_win'], 'Group stage win', $now );
		} elseif ( $hs < $as ) {
			self::log_points( $away->staff_id, $away->id, $match->id, $pts['group_win'], 'Group stage win', $now );
		} else {
			self::log_points( $home->staff_id, $home->id, $match->id, $pts['group_draw'], 'Group stage draw', $now );
			self::log_points( $away->staff_id, $away->id, $match->id, $pts['group_draw'], 'Group stage draw', $now );
		}
	}

	/**
	 * Award "reach round" points for a knockout match.
	 * Both teams in the match get "reach [round]" points (they already qualified to play it).
	 * The winner additionally gets "reach next round" points if applicable.
	 *
	 * @param object $match Match row.
	 * @param array  $pts   Point values.
	 * @param string $now   MySQL datetime.
	 */
	private static function award_knockout_points( $match, $pts, $now ) {
		// Map our round values to the "reach this round" point key.
		$round_pts_map = array(
			'R32'   => 'reach_r32',
			'R16'   => 'reach_r16',
			'QF'    => 'reach_qf',
			'SF'    => 'reach_sf',
			'3rd'   => 'reach_sf',    // 3rd place = reached SF level.
			'Final' => 'reach_final',
		);

		$round_labels = array(
			'R32'   => 'Reached Round of 32',
			'R16'   => 'Reached Round of 16',
			'QF'    => 'Reached Quarter-final',
			'SF'    => 'Reached Semi-final',
			'3rd'   => 'Reached Semi-final',
			'Final' => 'Reached Final',
		);

		if ( ! isset( $round_pts_map[ $match->round ] ) ) {
			return;
		}

		$pt_key = $round_pts_map[ $match->round ];
		$label  = $round_labels[ $match->round ];
		$points = $pts[ $pt_key ] ?? 0;

		$home = $match->home_team_id ? WC2026_Countries::get_by_id( (int) $match->home_team_id ) : null;
		$away = $match->away_team_id ? WC2026_Countries::get_by_id( (int) $match->away_team_id ) : null;

		if ( $home ) {
			self::log_points( $home->staff_id, $home->id, $match->id, $points, $label, $now );
		}
		if ( $away ) {
			self::log_points( $away->staff_id, $away->id, $match->id, $points, $label, $now );
		}

		// Winner bonus for the Final.
		if ( 'Final' === $match->round ) {
			$hs = (int) $match->home_score;
			$as = (int) $match->away_score;
			$winner = ( $hs >= $as ) ? $home : $away; // >= covers AET/pens (same score, home wins on pens treated as home win by API).
			if ( $winner ) {
				self::log_points( $winner->staff_id, $winner->id, $match->id, $pts['winner'], 'Won the tournament', $now );
			}
		}
	}

	/**
	 * Wipe and fully recalculate all points from all finished matches.
	 * Useful after changing point values in settings.
	 */
	public static function recalculate_all() {
		global $wpdb;

		// Wipe entire log.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_points_log" ); // phpcs:ignore WordPress.DB.PreparedSQL

		$matches = $wpdb->get_results(
			"SELECT id FROM {$wpdb->prefix}wc_matches WHERE status = 'finished' ORDER BY match_date ASC, id ASC"
		);

		foreach ( $matches as $row ) {
			self::recalculate_match( (int) $row->id );
		}
	}

	/**
	 * Insert a points log entry.
	 *
	 * @param int|null $staff_id   Staff ID (skipped if null/0).
	 * @param int      $country_id Country ID.
	 * @param int      $match_id   Match ID.
	 * @param int      $points     Points to award.
	 * @param string   $reason     Human-readable reason.
	 * @param string   $now        MySQL datetime string.
	 */
	private static function log_points( $staff_id, $country_id, $match_id, $points, $reason, $now ) {
		if ( ! $staff_id || ! $points ) {
			return;
		}
		global $wpdb;
		$wpdb->insert(
			"{$wpdb->prefix}wc_points_log",
			array(
				'staff_id'   => (int) $staff_id,
				'country_id' => (int) $country_id,
				'match_id'   => (int) $match_id,
				'points'     => (int) $points,
				'reason'     => $reason,
				'created_at' => $now,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Return leaderboard data: all staff with total points, sorted descending.
	 *
	 * @return array
	 */
	public static function get_leaderboard() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT s.id, s.name, s.slug, s.colour, s.photo,
					COALESCE(SUM(pl.points), 0) AS total_points
			 FROM {$wpdb->prefix}wc_staff s
			 LEFT JOIN {$wpdb->prefix}wc_points_log pl ON pl.staff_id = s.id
			 GROUP BY s.id
			 ORDER BY total_points DESC, s.name ASC"
		);
	}
}
