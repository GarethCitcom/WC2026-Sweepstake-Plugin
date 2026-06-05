<?php
/**
 * Staff CRUD operations.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC2026_Staff
 */
class WC2026_Staff {

	/**
	 * Return all staff rows ordered by name.
	 *
	 * @return array
	 */
	public static function get_all() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}wc_staff ORDER BY name ASC"
		);
	}

	/**
	 * Return a single staff member by ID.
	 *
	 * @param int $id Staff ID.
	 * @return object|null
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_staff WHERE id = %d",
				(int) $id
			)
		);
	}

	/**
	 * Return a single staff member by slug.
	 *
	 * @param string $slug Staff slug.
	 * @return object|null
	 */
	public static function get_by_slug( $slug ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_staff WHERE slug = %s",
				$slug
			)
		);
	}

	/**
	 * Insert a new staff member. Returns the new ID or false on failure.
	 *
	 * @param array $data  Keys: name (required), colour (optional).
	 * @return int|false
	 */
	public static function insert( array $data ) {
		global $wpdb;
		$name   = trim( $data['name'] ?? '' );
		$colour = $data['colour'] ?? '#cccccc';
		if ( empty( $name ) ) {
			return false;
		}
		$slug = self::unique_slug( sanitize_title( $name ) );
		$wpdb->insert(
			"{$wpdb->prefix}wc_staff",
			array(
				'name'       => $name,
				'slug'       => $slug,
				'colour'     => $colour,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
		return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Generate a slug that is unique in the staff table.
	 *
	 * @param string $base  Sanitized base slug.
	 * @return string
	 */
	private static function unique_slug( $base ) {
		global $wpdb;
		$slug = $base;
		$n    = 1;
		while ( (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_staff WHERE slug = %s", $slug ) ) > 0 ) {
			$slug = $base . '-' . $n++;
		}
		return $slug;
	}

	/**
	 * Delete a staff member: unassigns their countries, removes their points log, then deletes the row.
	 *
	 * @param int $id Staff ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wc_countries SET staff_id = NULL WHERE staff_id = %d", $id ) );
		$wpdb->delete( "{$wpdb->prefix}wc_points_log", array( 'staff_id' => $id ), array( '%d' ) );
		return (bool) $wpdb->delete( "{$wpdb->prefix}wc_staff", array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Update a staff record.
	 *
	 * @param int   $id   Staff ID.
	 * @param array $data Associative array of columns to update.
	 * @return bool
	 */
	public static function update( $id, array $data ) {
		global $wpdb;

		$allowed = array( 'name', 'slug', 'colour', 'photo' );
		$clean   = array();
		$formats = array();

		foreach ( $allowed as $col ) {
			if ( array_key_exists( $col, $data ) ) {
				$clean[ $col ] = $data[ $col ];
				$formats[]     = ( 'photo' === $col ) ? '%d' : '%s';
			}
		}

		if ( empty( $clean ) ) {
			return false;
		}

		return (bool) $wpdb->update(
			"{$wpdb->prefix}wc_staff",
			$clean,
			array( 'id' => (int) $id ),
			$formats,
			array( '%d' )
		);
	}

	/**
	 * Get the avatar URL for a staff member. Falls back to a DiceBear avatar seeded
	 * from the staff name so each person always gets the same generated avatar.
	 *
	 * @param object $staff Staff row object.
	 * @param string $size  WP image size. Default 'thumbnail'.
	 * @return string
	 */
	public static function get_avatar_url( $staff, $size = 'thumbnail' ) {
		if ( ! empty( $staff->photo ) ) {
			$url = wp_get_attachment_image_url( (int) $staff->photo, $size );
			if ( $url ) {
				return $url;
			}
		}
		return self::dicebear_avatar_url( $staff->name );
	}

	/**
	 * Return a DiceBear avatar URL for the given name. The seed is derived from
	 * the name so the same person always gets the same avatar.
	 *
	 * @param string $name Staff member's display name.
	 * @return string
	 */
	public static function dicebear_avatar_url( $name ) {
		return 'https://api.dicebear.com/9.x/thumbs/svg?seed=' . rawurlencode( $name );
	}
}
