<?php
/**
 * Country CRUD & staff assignments.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC2026_Countries
 */
class WC2026_Countries {

	/**
	 * Return all countries.
	 *
	 * @return array
	 */
	public static function get_all() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}wc_countries ORDER BY name ASC"
		);
	}

	/**
	 * Return countries grouped by group letter (A–L).
	 *
	 * @return array  Keyed by group letter; each value is an array of row objects.
	 */
	public static function get_grouped() {
		global $wpdb;
		$rows   = $wpdb->get_results(
			"SELECT c.*, s.name AS staff_name, s.slug AS staff_slug, s.colour AS staff_colour, s.photo AS staff_photo
			 FROM {$wpdb->prefix}wc_countries c
			 LEFT JOIN {$wpdb->prefix}wc_staff s ON s.id = c.staff_id
			 ORDER BY c.group_letter ASC, c.name ASC"
		);
		$groups = array();
		foreach ( $rows as $row ) {
			$groups[ $row->group_letter ][] = $row;
		}
		return $groups;
	}

	/**
	 * Return a country by ID.
	 *
	 * @param int $id Country ID.
	 * @return object|null
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.*, s.name AS staff_name, s.slug AS staff_slug, s.colour AS staff_colour, s.photo AS staff_photo
				 FROM {$wpdb->prefix}wc_countries c
				 LEFT JOIN {$wpdb->prefix}wc_staff s ON s.id = c.staff_id
				 WHERE c.id = %d",
				(int) $id
			)
		);
	}

	/**
	 * Return a country by FIFA code.
	 *
	 * @param string $code Three-letter FIFA code.
	 * @return object|null
	 */
	public static function get_by_fifa_code( $code ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_countries WHERE fifa_code = %s",
				strtoupper( $code )
			)
		);
	}

	/**
	 * Assign a set of countries to a staff member, replacing their previous assignments.
	 * Any of the given country IDs that were owned by a different staff member will be reassigned.
	 *
	 * @param int   $staff_id    Staff ID.
	 * @param int[] $country_ids Country IDs to assign (empty = unassign all).
	 */
	public static function assign_to_staff( $staff_id, array $country_ids ) {
		global $wpdb;
		$staff_id = (int) $staff_id;

		// Clear all existing assignments for this staff member.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_countries SET staff_id = NULL WHERE staff_id = %d",
				$staff_id
			)
		);

		if ( empty( $country_ids ) ) {
			return;
		}

		$ids      = array_map( 'absint', $country_ids );
		$holders  = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_countries SET staff_id = %d WHERE id IN ($holders)",
				array_merge( array( $staff_id ), $ids )
			)
		);
	}

	/**
	 * Return all countries belonging to a staff member.
	 *
	 * @param int $staff_id Staff ID.
	 * @return array
	 */
	public static function get_by_staff( $staff_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_countries WHERE staff_id = %d ORDER BY name ASC",
				(int) $staff_id
			)
		);
	}
}
