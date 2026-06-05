<?php
/**
 * Admin Help page — setup guide, shortcode reference, and usage notes.
 *
 * @package WC2026_Sweepstake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc2026_page_help() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'wc2026-sweepstake' ) );
	}
	$settings_url = admin_url( 'admin.php?page=wc2026-settings' );
	$staff_url    = admin_url( 'admin.php?page=wc2026-sweepstake' );
	?>
	<div class="wrap wc2026-admin-wrap">
		<h1><?php esc_html_e( 'WC2026 Sweepstake — Help &amp; Reference', 'wc2026-sweepstake' ); ?></h1>

		<style>
			.wc2026-help-toc {
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 4px;
				padding: 14px 20px;
				display: inline-block;
				margin-bottom: 28px;
			}
			.wc2026-help-toc ol {
				margin: 0;
				padding-left: 20px;
			}
			.wc2026-help-toc li {
				margin: 4px 0;
			}
			.wc2026-help-section {
				margin-bottom: 36px;
				padding-bottom: 28px;
				border-bottom: 1px solid #eee;
			}
			.wc2026-help-section:last-child {
				border-bottom: none;
			}
			.wc2026-help-section h2 {
				font-size: 1.3rem;
				margin-bottom: 10px;
			}
			.wc2026-help-section h3 {
				font-size: 1rem;
				margin: 18px 0 6px;
				color: #1d2327;
			}
			.wc2026-shortcode-block {
				background: #f0f4f8;
				border: 1px solid #c8d3de;
				border-left: 4px solid #0073aa;
				border-radius: 4px;
				padding: 14px 18px;
				margin-bottom: 18px;
			}
			.wc2026-shortcode-block code.wc2026-sc-tag {
				font-size: 1rem;
				font-weight: 600;
				color: #1d2327;
				background: none;
				padding: 0;
			}
			.wc2026-shortcode-block p {
				margin: 6px 0 0;
				color: #3c434a;
			}
			.wc2026-shortcode-block table {
				margin-top: 10px;
				width: 100%;
				border-collapse: collapse;
				font-size: 13px;
			}
			.wc2026-shortcode-block table th {
				text-align: left;
				background: #e4ecf3;
				padding: 5px 8px;
				font-weight: 600;
			}
			.wc2026-shortcode-block table td {
				padding: 5px 8px;
				border-top: 1px solid #d5dde5;
				vertical-align: top;
			}
			.wc2026-shortcode-block table code {
				font-size: 12px;
			}
			.wc2026-step-list {
				counter-reset: steps;
				list-style: none;
				padding: 0;
				margin: 0;
			}
			.wc2026-step-list li {
				counter-increment: steps;
				display: flex;
				gap: 12px;
				margin-bottom: 12px;
				align-items: flex-start;
			}
			.wc2026-step-list li::before {
				content: counter(steps);
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 26px;
				height: 26px;
				background: #0073aa;
				color: #fff;
				border-radius: 50%;
				font-size: 13px;
				font-weight: 700;
				flex-shrink: 0;
				margin-top: 1px;
			}
			.wc2026-pts-table {
				border-collapse: collapse;
				font-size: 13px;
			}
			.wc2026-pts-table th,
			.wc2026-pts-table td {
				padding: 6px 14px;
				border: 1px solid #ddd;
				text-align: left;
			}
			.wc2026-pts-table thead th {
				background: #f0f0f0;
				font-weight: 600;
			}
			.wc2026-info-box {
				background: #fff8e5;
				border: 1px solid #f0c33c;
				border-left: 4px solid #f0c33c;
				border-radius: 4px;
				padding: 10px 14px;
				margin: 10px 0;
				font-size: 13px;
			}
		</style>

		<div class="wc2026-help-toc">
			<strong><?php esc_html_e( 'Contents', 'wc2026-sweepstake' ); ?></strong>
			<ol>
				<li><a href="#help-setup"><?php esc_html_e( 'Initial Setup', 'wc2026-sweepstake' ); ?></a></li>
				<li><a href="#help-staff"><?php esc_html_e( 'Staff &amp; Country Management', 'wc2026-sweepstake' ); ?></a></li>
				<li><a href="#help-shortcodes"><?php esc_html_e( 'Shortcode Reference', 'wc2026-sweepstake' ); ?></a></li>
				<li><a href="#help-points"><?php esc_html_e( 'Points System', 'wc2026-sweepstake' ); ?></a></li>
				<li><a href="#help-api"><?php esc_html_e( 'API &amp; Score Sync', 'wc2026-sweepstake' ); ?></a></li>
				<li><a href="#help-wallchart"><?php esc_html_e( 'Wall Chart', 'wc2026-sweepstake' ); ?></a></li>
			</ol>
		</div>

		<!-- ── 1. Initial Setup ─────────────────────────────────────── -->
		<div class="wc2026-help-section" id="help-setup">
			<h2><?php esc_html_e( '1. Initial Setup', 'wc2026-sweepstake' ); ?></h2>
			<p><?php esc_html_e( 'Follow these steps after activating the plugin for the first time.', 'wc2026-sweepstake' ); ?></p>
			<ul class="wc2026-step-list">
				<li>
					<div>
						<strong><?php esc_html_e( 'Get a football-data.org API key', 'wc2026-sweepstake' ); ?></strong><br>
						<?php
						printf(
							wp_kses(
								/* translators: %s: football-data.org URL */
								__( 'Register for a free account at <a href="https://www.football-data.org/" target="_blank" rel="noopener">football-data.org</a>. The free tier covers all score and fixture data needed for the sweepstake.', 'wc2026-sweepstake' ),
								array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
							)
						);
						?>
					</div>
				</li>
				<li>
					<div>
						<strong><?php esc_html_e( 'Enter the API key in Settings', 'wc2026-sweepstake' ); ?></strong><br>
						<?php
						printf(
							wp_kses(
								/* translators: %s: settings page URL */
								__( 'Go to <a href="%s">WC2026 → Settings</a>, paste your key into the <strong>API Key</strong> field, and save.', 'wc2026-sweepstake' ),
								array( 'a' => array( 'href' => array() ), 'strong' => array() )
							),
							esc_url( $settings_url )
						);
						?>
					</div>
				</li>
				<li>
					<div>
						<strong><?php esc_html_e( 'Import the fixture schedule', 'wc2026-sweepstake' ); ?></strong><br>
						<?php
						printf(
							wp_kses(
								/* translators: %s: settings page URL */
								__( 'On the <a href="%s">Settings page</a>, click <strong>Import Fixture Schedule</strong>. This loads all group-stage and knockout fixtures from the API into the database.', 'wc2026-sweepstake' ),
								array( 'a' => array( 'href' => array() ), 'strong' => array() )
							),
							esc_url( $settings_url )
						);
						?>
					</div>
				</li>
				<li>
					<div>
						<strong><?php esc_html_e( 'Add staff members', 'wc2026-sweepstake' ); ?></strong><br>
						<?php
						printf(
							wp_kses(
								/* translators: %s: staff page URL */
								__( 'Go to <a href="%s">WC2026 → Staff</a> and add each participant — name, colour, and optional photo.', 'wc2026-sweepstake' ),
								array( 'a' => array( 'href' => array() ) )
							),
							esc_url( $staff_url )
						);
						?>
					</div>
				</li>
				<li>
					<div>
						<strong><?php esc_html_e( 'Assign countries to staff', 'wc2026-sweepstake' ); ?></strong><br>
						<?php esc_html_e( 'Click the flag icon next to any staff member to open the country picker. Assign each of the 48 WC2026 countries to a participant.', 'wc2026-sweepstake' ); ?>
					</div>
				</li>
				<li>
					<div>
						<strong><?php esc_html_e( 'Add shortcodes to your pages', 'wc2026-sweepstake' ); ?></strong><br>
						<?php esc_html_e( 'Use the shortcodes below to embed the sweepstake on any page or post.', 'wc2026-sweepstake' ); ?>
					</div>
				</li>
			</ul>
		</div>

		<!-- ── 2. Staff & Countries ─────────────────────────────────── -->
		<div class="wc2026-help-section" id="help-staff">
			<h2><?php esc_html_e( '2. Staff &amp; Country Management', 'wc2026-sweepstake' ); ?></h2>

			<h3><?php esc_html_e( 'Adding a staff member', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'Fill in the "Add new staff member" form at the bottom of the Staff page. Each person needs a unique name and slug. The slug is used in the [wc_staff_profile] shortcode and URLs — keep it lowercase and hyphenated (e.g. "john-smith").', 'wc2026-sweepstake' ); ?></p>

			<h3><?php esc_html_e( 'Assigning countries', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'Click the flag button in the Countries column to open the country picker modal. Countries already owned by another participant are shown at reduced opacity — you can still reassign them if needed.', 'wc2026-sweepstake' ); ?></p>

			<h3><?php esc_html_e( 'Staff photos', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'Photos are optional. Upload an image through the WordPress Media Library. Square crops work best — the photo is displayed as a circle in the wall chart and leaderboard.', 'wc2026-sweepstake' ); ?></p>

			<h3><?php esc_html_e( 'Colours', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( "Each staff member has a colour used for their profile header and chart accents. Click the colour swatch in the table to change it inline — the change saves immediately.", 'wc2026-sweepstake' ); ?></p>
		</div>

		<!-- ── 3. Shortcodes ────────────────────────────────────────── -->
		<div class="wc2026-help-section" id="help-shortcodes">
			<h2><?php esc_html_e( '3. Shortcode Reference', 'wc2026-sweepstake' ); ?></h2>
			<p><?php esc_html_e( 'Add any of these shortcodes to a page or post using the WordPress block editor (Shortcode block) or the Classic editor.', 'wc2026-sweepstake' ); ?></p>

			<div class="wc2026-shortcode-block">
				<code class="wc2026-sc-tag">[wc_sweepstake]</code>
				<p><?php esc_html_e( 'The all-in-one tabbed hub. Embeds the leaderboard, fixtures, group wall chart, and staff profiles in a single tabbed interface. Recommended for most sites — one shortcode covers everything.', 'wc2026-sweepstake' ); ?></p>
			</div>

			<div class="wc2026-shortcode-block">
				<code class="wc2026-sc-tag">[wc_leaderboard]</code>
				<p><?php esc_html_e( 'Standalone staff leaderboard sorted by total points. Shows each participant\'s name, photo, colour, countries, and current points tally.', 'wc2026-sweepstake' ); ?></p>
			</div>

			<div class="wc2026-shortcode-block">
				<code class="wc2026-sc-tag">[wc_fixtures]</code>
				<p><?php esc_html_e( 'Full fixture list with date, kick-off time, teams, and live/final scores. Includes filter controls for round and group.', 'wc2026-sweepstake' ); ?></p>
			</div>

			<div class="wc2026-shortcode-block">
				<code class="wc2026-sc-tag">[wc_wall_chart]</code>
				<p><?php esc_html_e( 'Group-stage wall chart showing all 12 groups with standings, flags, and the assigned staff member for each country.', 'wc2026-sweepstake' ); ?></p>
			</div>

			<div class="wc2026-shortcode-block">
				<code class="wc2026-sc-tag">[wc_wall_chart_full]</code>
				<p><?php esc_html_e( 'The full tournament wall chart — all 12 groups plus the complete knockout bracket from Round of 32 through to the Final. Best embedded on a wide-layout page or full-width template. Also available as a printable standalone page from the Settings screen.', 'wc2026-sweepstake' ); ?></p>
			</div>

			<div class="wc2026-shortcode-block">
				<code class="wc2026-sc-tag">[wc_staff_profile slug="<em>staff-slug</em>"]</code>
				<p><?php esc_html_e( 'Individual staff profile page showing their assigned countries, points breakdown, and match results. Replace the slug with the staff member\'s unique slug (set on the Staff page).', 'wc2026-sweepstake' ); ?></p>
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Attribute', 'wc2026-sweepstake' ); ?></th>
							<th><?php esc_html_e( 'Required', 'wc2026-sweepstake' ); ?></th>
							<th><?php esc_html_e( 'Description', 'wc2026-sweepstake' ); ?></th>
							<th><?php esc_html_e( 'Example', 'wc2026-sweepstake' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>slug</code></td>
							<td><?php esc_html_e( 'Yes', 'wc2026-sweepstake' ); ?></td>
							<td><?php esc_html_e( 'The staff member\'s slug as set on the Staff page.', 'wc2026-sweepstake' ); ?></td>
							<td><code>[wc_staff_profile slug="jane"]</code></td>
						</tr>
					</tbody>
				</table>
				<div class="wc2026-info-box" style="margin-top:10px;">
					<?php esc_html_e( 'Tip: staff profiles are also accessible via a popup on the wall chart and leaderboard — you only need this shortcode if you want a dedicated profile page.', 'wc2026-sweepstake' ); ?>
				</div>
			</div>
		</div>

		<!-- ── 4. Points System ─────────────────────────────────────── -->
		<div class="wc2026-help-section" id="help-points">
			<h2><?php esc_html_e( '4. Points System', 'wc2026-sweepstake' ); ?></h2>
			<p><?php esc_html_e( 'Points are awarded to the staff member who owns a country when that country achieves one of the following. All values are configurable on the Settings page.', 'wc2026-sweepstake' ); ?></p>

			<?php
			$defaults = array(
				'group_win'   => 3,
				'group_draw'  => 1,
				'reach_r32'   => 2,
				'reach_r16'   => 4,
				'reach_qf'    => 6,
				'reach_sf'    => 10,
				'reach_final' => 15,
				'winner'      => 25,
			);
			$pts = wp_parse_args( get_option( 'wc2026_point_values', array() ), $defaults );
			$rows = array(
				'group_win'   => __( 'Win a group-stage match', 'wc2026-sweepstake' ),
				'group_draw'  => __( 'Draw a group-stage match', 'wc2026-sweepstake' ),
				'reach_r32'   => __( 'Qualify for the Round of 32', 'wc2026-sweepstake' ),
				'reach_r16'   => __( 'Reach the Round of 16', 'wc2026-sweepstake' ),
				'reach_qf'    => __( 'Reach the Quarter Finals', 'wc2026-sweepstake' ),
				'reach_sf'    => __( 'Reach the Semi Finals', 'wc2026-sweepstake' ),
				'reach_final' => __( 'Reach the Final', 'wc2026-sweepstake' ),
				'winner'      => __( 'Win the tournament', 'wc2026-sweepstake' ),
			);
			?>
			<table class="wc2026-pts-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Achievement', 'wc2026-sweepstake' ); ?></th>
						<th><?php esc_html_e( 'Current points value', 'wc2026-sweepstake' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $key => $label ) : ?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td><strong><?php echo esc_html( $pts[ $key ] ); ?></strong></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p style="margin-top:10px;">
				<?php
				printf(
					wp_kses(
						/* translators: %s: settings page URL */
						__( 'Change point values any time on the <a href="%s">Settings page</a>. The leaderboard recalculates automatically when scores are updated.', 'wc2026-sweepstake' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( $settings_url )
				);
				?>
			</p>
		</div>

		<!-- ── 5. API & Score Sync ──────────────────────────────────── -->
		<div class="wc2026-help-section" id="help-api">
			<h2><?php esc_html_e( '5. API &amp; Score Sync', 'wc2026-sweepstake' ); ?></h2>

			<h3><?php esc_html_e( 'How it works', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'Scores are fetched automatically from football-data.org. There is no manual score entry — the plugin keeps scores up to date via two mechanisms:', 'wc2026-sweepstake' ); ?></p>
			<ul>
				<li><strong><?php esc_html_e( 'Hourly cron:', 'wc2026-sweepstake' ); ?></strong> <?php esc_html_e( 'WordPress runs an automatic sync every hour when the API is enabled.', 'wc2026-sweepstake' ); ?></li>
				<li><strong><?php esc_html_e( 'Manual sync:', 'wc2026-sweepstake' ); ?></strong> <?php esc_html_e( 'Use the "Sync Scores Now" button on the Settings page to pull the latest scores immediately.', 'wc2026-sweepstake' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Importing fixtures', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( '"Import Fixture Schedule" loads all fixtures from the API (group stage and knockout rounds). It only needs to be run once. If fixtures change (rescheduled matches, new knockout fixtures as the tournament progresses), click it again — it will insert new fixtures and update existing ones without creating duplicates.', 'wc2026-sweepstake' ); ?></p>

			<h3><?php esc_html_e( 'Force re-sync all scores', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'The "Sync Scores Now" section has a "Re-sync all matches (not just recent)" checkbox. Tick this to recalculate scores for every match in the database — useful if you suspect data is out of sync.', 'wc2026-sweepstake' ); ?></p>

			<h3><?php esc_html_e( 'Test API Key', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'Use the "Test API Key" button on Settings to verify your key is valid before importing. It will show the competition name and remaining rate-limit allowance from the API response headers.', 'wc2026-sweepstake' ); ?></p>

			<div class="wc2026-info-box">
				<?php esc_html_e( 'The free tier of football-data.org allows up to 10 requests per minute. The plugin\'s hourly cron uses a single request per run, well within this limit.', 'wc2026-sweepstake' ); ?>
			</div>
		</div>

		<!-- ── 6. Wall Chart ────────────────────────────────────────── -->
		<div class="wc2026-help-section" id="help-wallchart">
			<h2><?php esc_html_e( '6. Wall Chart', 'wc2026-sweepstake' ); ?></h2>

			<h3><?php esc_html_e( 'Printable standalone version', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'The Settings page has a "Download Wall Chart" button (under the Wall Chart section). This opens a full-screen, print-optimised version of the tournament bracket in a new tab — use your browser\'s print function to print or save as PDF. It includes all groups, flags, staff assignments, and the knockout bracket.', 'wc2026-sweepstake' ); ?></p>

			<h3><?php esc_html_e( 'On-page version', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'The [wc_wall_chart_full] shortcode embeds the same chart directly on a page. For best results use a full-width page template — the chart is wide and will scroll horizontally on narrower viewports.', 'wc2026-sweepstake' ); ?></p>

			<h3><?php esc_html_e( 'Branding', 'wc2026-sweepstake' ); ?></h3>
			<p><?php esc_html_e( 'The wall chart header and footer use your WordPress site name (Settings → General → Site Title) automatically. No configuration needed.', 'wc2026-sweepstake' ); ?></p>
		</div>

	</div>
	<?php
}
