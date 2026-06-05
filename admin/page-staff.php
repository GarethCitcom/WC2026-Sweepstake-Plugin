<?php
/**
 * Admin Staff Manager page — list, add, edit, and delete staff.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle form saves and render the staff admin page.
 */
function wc2026_page_staff() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'wc2026-sweepstake' ) );
	}

	$notice      = '';
	$notice_type = 'success';

	// ── Photo / colour save (existing mechanism) ───────────────────
	if (
		isset( $_POST['wc2026_staff_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc2026_staff_nonce'] ) ), 'wc2026_save_staff' )
	) {
		$staff_id = (int) ( $_POST['staff_id'] ?? 0 );
		$photo_id = isset( $_POST['photo_id'] ) ? (int) $_POST['photo_id'] : null;
		$colour   = isset( $_POST['colour'] ) ? sanitize_hex_color( wp_unslash( $_POST['colour'] ) ) : null;

		if ( $staff_id ) {
			$update = array();
			if ( null !== $photo_id ) {
				$update['photo'] = $photo_id > 0 ? $photo_id : null;
			}
			if ( $colour ) {
				$update['colour'] = $colour;
			}
			if ( ! empty( $update ) ) {
				WC2026_Staff::update( $staff_id, $update );
			}
		}
		$notice = 'Staff updated.';
	}

	// ── Add / Edit staff (modal form) ──────────────────────────────
	if (
		isset( $_POST['wc2026_upsert_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc2026_upsert_nonce'] ) ), 'wc2026_upsert_staff' )
	) {
		$staff_id    = (int) ( $_POST['staff_id'] ?? 0 );
		$name        = sanitize_text_field( wp_unslash( $_POST['staff_name'] ?? '' ) );
		$colour      = sanitize_hex_color( wp_unslash( $_POST['colour'] ?? '#cccccc' ) ) ?: '#cccccc';
		$country_ids = isset( $_POST['country_ids'] ) ? array_map( 'absint', (array) $_POST['country_ids'] ) : array();
		$is_edit     = $staff_id > 0;

		if ( ! empty( $name ) ) {
			if ( $is_edit ) {
				WC2026_Staff::update( $staff_id, array( 'name' => $name, 'colour' => $colour ) );
			} else {
				$staff_id = WC2026_Staff::insert( array( 'name' => $name, 'colour' => $colour ) );
			}
			if ( $staff_id ) {
				WC2026_Countries::assign_to_staff( $staff_id, $country_ids );
				$notice = $is_edit ? 'Staff member updated.' : 'Staff member added.';
			} else {
				$notice      = 'Error saving staff member.';
				$notice_type = 'error';
			}
		}
	}

	// ── Delete staff ───────────────────────────────────────────────
	if (
		isset( $_POST['wc2026_delete_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc2026_delete_nonce'] ) ), 'wc2026_delete_staff' )
	) {
		$staff_id = (int) ( $_POST['staff_id'] ?? 0 );
		if ( $staff_id > 0 && WC2026_Staff::delete( $staff_id ) ) {
			$notice = 'Staff member deleted.';
		}
	}

	// ── Fetch data ─────────────────────────────────────────────────
	$all_staff         = WC2026_Staff::get_all();
	$grouped_countries = WC2026_Countries::get_grouped(); // keyed by group letter, includes staff info via JOIN

	// Build staff_id → countries[] map for the table column.
	$staff_countries = array();
	foreach ( $grouped_countries as $group_countries ) {
		foreach ( $group_countries as $c ) {
			if ( $c->staff_id ) {
				$staff_countries[ (int) $c->staff_id ][] = $c;
			}
		}
	}
	?>
	<div class="wrap wc2026-admin-wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'WC2026 Staff Manager', 'wc2026-sweepstake' ); ?></h1>
		<button type="button" class="page-title-action" id="wc2026-add-staff">
			<?php esc_html_e( '+ Add Staff Member', 'wc2026-sweepstake' ); ?>
		</button>
		<hr class="wp-header-end">

		<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
		<?php endif; ?>

		<table class="widefat wc2026-staff-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Photo', 'wc2026-sweepstake' ); ?></th>
					<th><?php esc_html_e( 'Name', 'wc2026-sweepstake' ); ?></th>
					<th><?php esc_html_e( 'Colour', 'wc2026-sweepstake' ); ?></th>
					<th><?php esc_html_e( 'Countries', 'wc2026-sweepstake' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wc2026-sweepstake' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $all_staff as $staff ) :
				$avatar_url       = WC2026_Staff::get_avatar_url( $staff );
				$my_countries     = $staff_countries[ (int) $staff->id ] ?? array();
				$my_country_ids   = array_map( function ( $c ) { return (int) $c->id; }, $my_countries );
				$my_country_names = array_map( function ( $c ) { return $c->name; }, $my_countries );
				?>
				<tr id="staff-row-<?php echo esc_attr( $staff->id ); ?>">
					<td>
						<img
							src="<?php echo esc_url( $avatar_url ); ?>"
							alt="<?php echo esc_attr( $staff->name ); ?>"
							class="wc2026-staff-avatar"
							id="staff-preview-<?php echo esc_attr( $staff->id ); ?>"
						>
						<div style="margin-top:5px;display:flex;gap:4px;flex-wrap:wrap;">
							<button
								type="button"
								class="button button-small wc2026-open-media"
								data-staff-id="<?php echo esc_attr( $staff->id ); ?>"
							>
								<?php esc_html_e( 'Photo', 'wc2026-sweepstake' ); ?>
							</button>
							<?php if ( $staff->photo ) : ?>
							<form method="post" style="display:inline;">
								<?php wp_nonce_field( 'wc2026_save_staff', 'wc2026_staff_nonce' ); ?>
								<input type="hidden" name="staff_id" value="<?php echo esc_attr( $staff->id ); ?>">
								<input type="hidden" name="photo_id" value="0">
								<button type="submit" class="button button-small">
									<?php esc_html_e( 'Remove', 'wc2026-sweepstake' ); ?>
								</button>
							</form>
							<?php endif; ?>
						</div>
					</td>
					<td><strong><?php echo esc_html( $staff->name ); ?></strong></td>
					<td>
						<span class="wc2026-colour-swatch" style="background:<?php echo esc_attr( $staff->colour ); ?>"></span>
						<?php echo esc_html( $staff->colour ); ?>
					</td>
					<td class="wc2026-countries-cell">
						<?php echo esc_html( implode( ', ', $my_country_names ) ); ?>
					</td>
					<td class="wc2026-actions-cell">
						<button
							type="button"
							class="button wc2026-edit-staff"
							data-staff-id="<?php echo esc_attr( $staff->id ); ?>"
							data-staff-name="<?php echo esc_attr( $staff->name ); ?>"
							data-staff-colour="<?php echo esc_attr( $staff->colour ); ?>"
							data-country-ids="<?php echo esc_attr( wp_json_encode( $my_country_ids ) ); ?>"
						>
							<?php esc_html_e( 'Edit', 'wc2026-sweepstake' ); ?>
						</button>

						<form
							method="post"
							class="wc2026-delete-form"
							id="wc2026-delete-form-<?php echo esc_attr( $staff->id ); ?>"
							style="display:inline;"
						>
							<?php wp_nonce_field( 'wc2026_delete_staff', 'wc2026_delete_nonce' ); ?>
							<input type="hidden" name="staff_id" value="<?php echo esc_attr( $staff->id ); ?>">
							<button
								type="button"
								class="button wc2026-confirm-delete"
								data-staff-name="<?php echo esc_attr( $staff->name ); ?>"
								data-form-id="wc2026-delete-form-<?php echo esc_attr( $staff->id ); ?>"
							>
								<?php esc_html_e( 'Delete', 'wc2026-sweepstake' ); ?>
							</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<!-- ── Edit / Add modal ─────────────────────────────────────── -->
		<div id="wc2026-staff-modal" class="wc2026-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="wc2026-modal-title">
			<div class="wc2026-modal-inner">
				<button type="button" class="wc2026-modal-close" aria-label="<?php esc_attr_e( 'Close', 'wc2026-sweepstake' ); ?>">&times;</button>
				<h2 id="wc2026-modal-title"><?php esc_html_e( 'Edit Staff Member', 'wc2026-sweepstake' ); ?></h2>

				<form method="post" id="wc2026-staff-form">
					<?php wp_nonce_field( 'wc2026_upsert_staff', 'wc2026_upsert_nonce' ); ?>
					<input type="hidden" name="staff_id" id="modal-staff-id" value="">

					<table class="form-table" role="presentation">
						<tr>
							<th><label for="modal-staff-name"><?php esc_html_e( 'Name', 'wc2026-sweepstake' ); ?></label></th>
							<td>
								<input
									type="text"
									id="modal-staff-name"
									name="staff_name"
									class="regular-text"
									required
									autocomplete="off"
								>
							</td>
						</tr>
						<tr>
							<th><label for="modal-staff-colour"><?php esc_html_e( 'Colour', 'wc2026-sweepstake' ); ?></label></th>
							<td>
								<input
									type="color"
									id="modal-staff-colour"
									name="colour"
									value="#3498db"
									class="wc2026-colour-picker"
								>
								<span id="modal-colour-preview" class="wc2026-colour-swatch" style="background:#3498db;margin-left:6px;"></span>
							</td>
						</tr>
						<tr>
							<th style="padding-top:14px;vertical-align:top;">
								<?php esc_html_e( 'Countries', 'wc2026-sweepstake' ); ?>
							</th>
							<td>
								<div class="wc2026-countries-grid">
								<?php foreach ( $grouped_countries as $letter => $group_countries ) : ?>
									<div class="wc2026-group-col">
										<div class="wc2026-group-header">
											<?php
											/* translators: %s: group letter A–L */
											printf( esc_html__( 'Group %s', 'wc2026-sweepstake' ), esc_html( $letter ) );
											?>
										</div>
										<?php foreach ( $group_countries as $c ) : ?>
										<label
											class="wc2026-country-label"
											id="country-label-<?php echo esc_attr( $c->id ); ?>"
										>
											<input
												type="checkbox"
												class="wc2026-country-check"
												name="country_ids[]"
												value="<?php echo esc_attr( $c->id ); ?>"
												id="country-<?php echo esc_attr( $c->id ); ?>"
											>
											<img
												src="<?php echo esc_url( $c->flag_url ); ?>"
												alt=""
												class="wc2026-flag-xs"
											>
											<span class="wc2026-country-name"><?php echo esc_html( $c->name ); ?></span>
											<?php if ( ! empty( $c->staff_name ) ) : ?>
											<span
												class="wc2026-country-owner"
												data-staff-id="<?php echo esc_attr( $c->staff_id ); ?>"
											>(<?php echo esc_html( $c->staff_name ); ?>)</span>
											<?php endif; ?>
										</label>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
								</div>
							</td>
						</tr>
					</table>

					<div class="wc2026-modal-footer">
						<?php submit_button( __( 'Save', 'wc2026-sweepstake' ), 'primary', 'submit', false ); ?>
						<button type="button" class="button wc2026-modal-close">
							<?php esc_html_e( 'Cancel', 'wc2026-sweepstake' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Hidden form for media library photo saves -->
		<form id="wc2026-photo-form" method="post">
			<?php wp_nonce_field( 'wc2026_save_staff', 'wc2026_staff_nonce' ); ?>
			<input type="hidden" id="wc2026-photo-staff-id" name="staff_id" value="">
			<input type="hidden" id="wc2026-photo-attachment-id" name="photo_id" value="">
		</form>
	</div>
	<?php
}
