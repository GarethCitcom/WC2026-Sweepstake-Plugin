<?php
/**
 * Admin Settings page — API key, sync toggle, points config, manual sync.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle form saves and manual sync, then render the settings page.
 */
function wc2026_page_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'wc2026-sweepstake' ) );
	}

	$notice = '';
	$notice_type = 'success';

	// ── Save settings ──────────────────────────────────────────────
	if (
		isset( $_POST['wc2026_settings_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc2026_settings_nonce'] ) ), 'wc2026_save_settings' )
	) {
		// API settings.
		$api_key     = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );
		$api_enabled = isset( $_POST['api_enabled'] ) ? 1 : 0;

		update_option( 'wc2026_api_key',     $api_key );
		update_option( 'wc2026_api_enabled', $api_enabled );

		// Points values.
		$pts_fields = array( 'group_win', 'group_draw', 'reach_r32', 'reach_r16', 'reach_qf', 'reach_sf', 'reach_final', 'winner' );
		$pts = array();
		foreach ( $pts_fields as $f ) {
			$pts[ $f ] = isset( $_POST['pts'][ $f ] ) ? absint( $_POST['pts'][ $f ] ) : 0;
		}
		update_option( 'wc2026_point_values', $pts );

		// Re-schedule or unschedule cron based on toggle.
		if ( $api_enabled ) {
			WC2026_API_Sync::maybe_schedule();
		} else {
			WC2026_API_Sync::unschedule();
		}

		$notice = 'Settings saved.';
	}

	// ── Manual sync ────────────────────────────────────────────────
	if (
		isset( $_POST['wc2026_sync_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc2026_sync_nonce'] ) ), 'wc2026_manual_sync' )
	) {
		$force_all = isset( $_POST['sync_all'] );
		$result    = WC2026_API_Sync::sync( $force_all );

		if ( ! empty( $result['errors'] ) ) {
			$notice      = 'Sync error: ' . implode( ' ', $result['errors'] );
			$notice_type = 'error';
		} else {
			$notice = sprintf(
				'Sync complete — %d match(es) updated, %d skipped.',
				$result['updated'],
				$result['skipped']
			);
		}
	}

	// ── Import fixtures ───────────────────────────────────────────
	if (
		isset( $_POST['wc2026_import_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc2026_import_nonce'] ) ), 'wc2026_import_fixtures' ) &&
		! empty( $_POST['import_confirmed'] )
	) {
		$result = WC2026_API_Sync::import_fixtures();
		if ( ! empty( $result['errors'] ) ) {
			$notice      = 'Import error: ' . implode( ' ', $result['errors'] );
			$notice_type = 'error';
		} else {
			$notice = sprintf(
				'Import complete — %d fixture(s) imported from football-data.org.',
				$result['imported']
			);
		}
	}

	// ── Clear all data ────────────────────────────────────────────
	if (
		isset( $_POST['wc2026_reset_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc2026_reset_nonce'] ) ), 'wc2026_reset_data' ) &&
		! empty( $_POST['reset_confirmed'] )
	) {
		WC2026_DB::reset_data();
		$notice = 'All data cleared — staff, fixtures, and points log have been reset.';
	}

	// ── Test API key ───────────────────────────────────────────────
	$api_status = null;
	$saved_key  = get_option( 'wc2026_api_key', '' );
	if (
		isset( $_POST['wc2026_test_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc2026_test_nonce'] ) ), 'wc2026_test_api' )
	) {
		$test_key   = sanitize_text_field( wp_unslash( $_POST['test_key'] ?? $saved_key ) );
		$api_status = WC2026_API_Sync::get_status( $test_key );
		if ( is_wp_error( $api_status ) ) {
			$notice      = 'API test failed: ' . $api_status->get_error_message();
			$notice_type = 'error';
			$api_status  = null;
		}
	}

	// ── Current option values ──────────────────────────────────────
	$api_enabled   = (bool) get_option( 'wc2026_api_enabled', 0 );
	$last_sync     = get_option( 'wc2026_last_sync', '' );
	$last_error    = get_option( 'wc2026_last_sync_error', '' );
	$next_cron     = wp_next_scheduled( WC2026_API_Sync::CRON_HOOK );
	$saved_pts     = get_option( 'wc2026_point_values', array() );
	$default_pts   = WC2026_Leaderboard::get_default_point_values();
	$pts           = wp_parse_args( $saved_pts, $default_pts );
	?>
	<div class="wrap wc2026-admin-wrap">
		<h1><?php esc_html_e( 'WC2026 Settings', 'wc2026-sweepstake' ); ?></h1>

		<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
		<?php endif; ?>

		<?php if ( $last_error ) : ?>
		<div class="notice notice-warning"><p>
			<?php esc_html_e( 'Last sync error:', 'wc2026-sweepstake' ); ?>
			<strong><?php echo esc_html( $last_error ); ?></strong>
		</p></div>
		<?php endif; ?>

		<!-- ── API CONFIGURATION ─────────────────────────────────── -->
		<form method="post">
			<?php wp_nonce_field( 'wc2026_save_settings', 'wc2026_settings_nonce' ); ?>

			<h2 class="title"><?php esc_html_e( 'football-data.org', 'wc2026-sweepstake' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="api_key"><?php esc_html_e( 'API Key', 'wc2026-sweepstake' ); ?></label></th>
					<td>
						<input
							type="password"
							id="api_key"
							name="api_key"
							value="<?php echo esc_attr( $saved_key ); ?>"
							class="regular-text"
							autocomplete="off"
						>
						<p class="description">
							<?php esc_html_e( 'Get a free token at football-data.org (10 req/min on free tier, WC 2026 included).', 'wc2026-sweepstake' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Auto-sync', 'wc2026-sweepstake' ); ?></th>
					<td>
						<label>
							<input
								type="checkbox"
								name="api_enabled"
								value="1"
								<?php checked( $api_enabled ); ?>
							>
							<?php esc_html_e( 'Enable hourly score sync via wp_cron', 'wc2026-sweepstake' ); ?>
						</label>
						<p class="description">
							<?php if ( $next_cron ) : ?>
								<?php
								printf(
									/* translators: %s: human time diff */
									esc_html__( 'Next scheduled run in %s.', 'wc2026-sweepstake' ),
									esc_html( human_time_diff( $next_cron ) )
								);
								?>
							<?php else : ?>
								<?php esc_html_e( 'Cron not currently scheduled.', 'wc2026-sweepstake' ); ?>
							<?php endif; ?>
						</p>
					</td>
				</tr>
				<?php if ( $last_sync ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last sync', 'wc2026-sweepstake' ); ?></th>
					<td>
						<span><?php echo esc_html( $last_sync ); ?></span>
					</td>
				</tr>
				<?php endif; ?>
			</table>

			<!-- ── POINTS CONFIGURATION ──────────────────────────── -->
			<h2 class="title"><?php esc_html_e( 'Points Configuration', 'wc2026-sweepstake' ); ?></h2>
			<table class="form-table wc2026-pts-table" role="presentation">
				<?php
				$pt_labels = array(
					'group_win'   => __( 'Group stage win', 'wc2026-sweepstake' ),
					'group_draw'  => __( 'Group stage draw', 'wc2026-sweepstake' ),
					'reach_r32'   => __( 'Reach Round of 32', 'wc2026-sweepstake' ),
					'reach_r16'   => __( 'Reach Round of 16', 'wc2026-sweepstake' ),
					'reach_qf'    => __( 'Reach Quarter-final', 'wc2026-sweepstake' ),
					'reach_sf'    => __( 'Reach Semi-final', 'wc2026-sweepstake' ),
					'reach_final' => __( 'Reach Final', 'wc2026-sweepstake' ),
					'winner'      => __( 'Win the tournament', 'wc2026-sweepstake' ),
				);
				foreach ( $pt_labels as $key => $label ) : ?>
				<tr>
					<th scope="row"><label for="pts_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
					<td>
						<input
							type="number"
							id="pts_<?php echo esc_attr( $key ); ?>"
							name="pts[<?php echo esc_attr( $key ); ?>]"
							value="<?php echo esc_attr( $pts[ $key ] ?? 0 ); ?>"
							min="0"
							max="100"
							style="width:70px"
						>
						<?php esc_html_e( 'pts', 'wc2026-sweepstake' ); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button( __( 'Save Settings', 'wc2026-sweepstake' ) ); ?>
		</form>

		<hr>

		<!-- ── TEST API KEY ──────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Test API Connection', 'wc2026-sweepstake' ); ?></h2>
		<form method="post" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
			<?php wp_nonce_field( 'wc2026_test_api', 'wc2026_test_nonce' ); ?>
			<input
				type="password"
				name="test_key"
				value="<?php echo esc_attr( $saved_key ); ?>"
				class="regular-text"
				placeholder="<?php esc_attr_e( 'Enter key to test…', 'wc2026-sweepstake' ); ?>"
				autocomplete="off"
			>
			<?php submit_button( __( 'Test Key', 'wc2026-sweepstake' ), 'secondary', 'submit', false ); ?>
		</form>

		<?php if ( $api_status ) : ?>
		<div class="wc2026-api-status-box">
			<strong><?php esc_html_e( 'Connection OK', 'wc2026-sweepstake' ); ?> ✓</strong>
			<ul>
				<li><?php esc_html_e( 'Competition:', 'wc2026-sweepstake' ); ?> <strong><?php echo esc_html( $api_status['requests_limit'] ); ?></strong></li>
			</ul>
		</div>
		<?php endif; ?>

		<hr>

		<!-- ── WALL CHART GENERATOR ─────────────────────────────── -->
		<h2><?php esc_html_e( 'Wall Chart', 'wc2026-sweepstake' ); ?></h2>
		<p><?php esc_html_e( 'Generate a printable A2 landscape wall chart with all 12 groups, staff photos, country flags, and the full knockout bracket.', 'wc2026-sweepstake' ); ?></p>
		<p style="margin-top:6px;">
			<a
				href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wc2026_wallchart' ), 'wc2026_wallchart' ) ); ?>"
				target="_blank"
				class="button button-primary"
				style="font-size:14px;padding:6px 20px;height:auto;display:inline-flex;align-items:center;gap:8px;background:#2d1b52;border-color:#2d1b52;"
			>
				🏆 <?php esc_html_e( 'Generate World Cup Wall Chart', 'wc2026-sweepstake' ); ?>
			</a>
			<span style="margin-left:12px;color:#666;font-size:12px;">
				<?php esc_html_e( 'Opens in a new tab — use browser Print → Save as PDF for A2 landscape.', 'wc2026-sweepstake' ); ?>
			</span>
		</p>

		<hr>

		<!-- ── MANUAL SYNC ───────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Manual Sync', 'wc2026-sweepstake' ); ?></h2>
		<p><?php esc_html_e( 'Fetch latest scores immediately without waiting for the cron job.', 'wc2026-sweepstake' ); ?></p>
		<form method="post" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
			<?php wp_nonce_field( 'wc2026_manual_sync', 'wc2026_sync_nonce' ); ?>
			<?php submit_button( __( 'Sync Today ± 1 Day', 'wc2026-sweepstake' ), 'primary', 'submit', false ); ?>
			<label style="display:flex;align-items:center;gap:6px;">
				<input type="checkbox" name="sync_all" value="1">
				<?php esc_html_e( 'Fetch all fixtures (uses more API credits)', 'wc2026-sweepstake' ); ?>
			</label>
		</form>

		<hr>

		<!-- ── IMPORT FIXTURES ──────────────────────────────────── -->
		<h2><?php esc_html_e( 'Import Fixture Schedule', 'wc2026-sweepstake' ); ?></h2>
		<p><?php esc_html_e( 'Fetch the official fixture list from football-data.org and replace all matches in the database. Use this once to load the correct schedule.', 'wc2026-sweepstake' ); ?></p>
		<div class="wc2026-import-warning">
			<strong><?php esc_html_e( 'Warning:', 'wc2026-sweepstake' ); ?></strong>
			<?php esc_html_e( 'All existing fixtures and points log entries will be deleted and replaced with data from the API. This cannot be undone.', 'wc2026-sweepstake' ); ?>
		</div>
		<form method="post" style="margin-top:12px;">
			<?php wp_nonce_field( 'wc2026_import_fixtures', 'wc2026_import_nonce' ); ?>
			<label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
				<input type="checkbox" id="wc2026-import-confirm" name="import_confirmed" value="1">
				<?php esc_html_e( 'I understand — delete existing data and import from football-data.org', 'wc2026-sweepstake' ); ?>
			</label>
			<button
				type="submit"
				id="wc2026-import-btn"
				class="button button-secondary"
				disabled
			>
				<?php esc_html_e( 'Import All Fixtures', 'wc2026-sweepstake' ); ?>
			</button>
		</form>
		<script>
		document.getElementById('wc2026-import-confirm').addEventListener('change', function () {
			document.getElementById('wc2026-import-btn').disabled = ! this.checked;
		});
		</script>

		<hr>

		<!-- ── CLEAR ALL DATA ───────────────────────────────────── -->
		<h2 style="color:#b32d2e;"><?php esc_html_e( 'Clear All Data', 'wc2026-sweepstake' ); ?></h2>
		<p><?php esc_html_e( 'Resets the plugin to its post-activation state: removes all staff, fixtures, and points. The 48 countries remain. Use this to test a clean setup.', 'wc2026-sweepstake' ); ?></p>
		<div class="wc2026-danger-warning">
			<strong><?php esc_html_e( 'Danger:', 'wc2026-sweepstake' ); ?></strong>
			<?php esc_html_e( 'This permanently deletes all staff, match fixtures, and points log entries. Country assignments will be cleared. This cannot be undone.', 'wc2026-sweepstake' ); ?>
		</div>
		<form method="post" style="margin-top:12px;">
			<?php wp_nonce_field( 'wc2026_reset_data', 'wc2026_reset_nonce' ); ?>
			<label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
				<input type="checkbox" id="wc2026-reset-confirm" name="reset_confirmed" value="1">
				<?php esc_html_e( 'I understand — permanently delete all staff, fixtures, and points', 'wc2026-sweepstake' ); ?>
			</label>
			<button
				type="submit"
				id="wc2026-reset-btn"
				class="button wc2026-btn-danger"
				disabled
			>
				<?php esc_html_e( 'Clear All Data', 'wc2026-sweepstake' ); ?>
			</button>
		</form>
		<script>
		document.getElementById('wc2026-reset-confirm').addEventListener('change', function () {
			document.getElementById('wc2026-reset-btn').disabled = ! this.checked;
		});
		</script>

	</div><!-- .wc2026-admin-wrap -->

	<style>
	.wc2026-api-status-box {
		margin: 12px 0;
		padding: 12px 18px;
		background: #f0fdf4;
		border: 1px solid #86efac;
		border-radius: 6px;
		max-width: 420px;
	}
	.wc2026-api-status-box ul { margin: 8px 0 0 1em; }
	.wc2026-import-warning {
		margin: 10px 0;
		padding: 10px 16px;
		background: #fff8e5;
		border-left: 4px solid #f0a500;
		border-radius: 3px;
		max-width: 640px;
	}
	.wc2026-danger-warning {
		margin: 10px 0;
		padding: 10px 16px;
		background: #fef2f2;
		border-left: 4px solid #b32d2e;
		border-radius: 3px;
		max-width: 640px;
	}
	.wc2026-btn-danger {
		color: #fff !important;
		background: #b32d2e !important;
		border-color: #8a1f1f !important;
	}
	.wc2026-btn-danger:hover:not(:disabled) {
		background: #8a1f1f !important;
		border-color: #6b1717 !important;
	}
	.wc2026-btn-danger:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}
	</style>
	<?php
}
