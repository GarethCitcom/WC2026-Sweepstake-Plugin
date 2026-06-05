<?php

/**
 * Wall chart full template — shared by [wc_wall_chart_full] shortcode
 * and the admin print action. Expects $wc_data array in scope (set by caller).
 *
 * @package WC2026_Sweepstake
 */

if (! defined('ABSPATH')) {
	exit;
}

// Unpack data array supplied by the caller.
$standings  = $wc_data['standings'];
$r32_l      = $wc_data['r32_l'];
$r32_r      = $wc_data['r32_r'];
$r16_l      = $wc_data['r16_l'];
$r16_r      = $wc_data['r16_r'];
$qf_l       = $wc_data['qf_l'];
$qf_r       = $wc_data['qf_r'];
$sf_l       = $wc_data['sf_l'];
$sf_r       = $wc_data['sf_r'];
$fin        = $wc_data['fin'];
$trd        = $wc_data['trd'];
$all_staff  = $wc_data['all_staff'];
$site_name  = get_bloginfo('name');

// ── Helpers (all defined before HTML output; guarded against redeclaration) ──

/**
 * Echo two team rows for a match slot.
 *
 * @param object|null $match
 * @param int|null    $match_num  Label shown as Mxx above the slot.
 */
if (! function_exists('wc2026_slot')) :
	function wc2026_slot($match, $match_num = null)
	{
		$is_tbd = ! $match || ! $match->home_team_id;

		$home_name  = $match ? esc_html($match->home_team_name ?? 'TBD') : 'TBD';
		$away_name  = $match ? esc_html($match->away_team_name ?? 'TBD') : 'TBD';
		$home_flag  = ($match && $match->home_flag) ? esc_url($match->home_flag) : '';
		$away_flag  = ($match && $match->away_flag) ? esc_url($match->away_flag) : '';
		$home_score = ($match && null !== $match->home_score) ? esc_html($match->home_score) : '';
		$away_score = ($match && null !== $match->away_score) ? esc_html($match->away_score) : '';

		$home_col = ($match && ! empty($match->home_staff_colour)) ? esc_attr($match->home_staff_colour) : '#ccc';
		$away_col = ($match && ! empty($match->away_staff_colour)) ? esc_attr($match->away_staff_colour) : '#ccc';

		$home_av = '';
		$away_av = '';
		if ($match && $match->home_staff_slug) {
			$hs = (object) array('name' => $match->home_staff_name, 'slug' => $match->home_staff_slug, 'colour' => $match->home_staff_colour, 'photo' => $match->home_staff_photo);
			$home_av = esc_url(WC2026_Staff::get_avatar_url($hs, 'thumbnail'));
		}
		if ($match && $match->away_staff_slug) {
			$as = (object) array('name' => $match->away_staff_name, 'slug' => $match->away_staff_slug, 'colour' => $match->away_staff_colour, 'photo' => $match->away_staff_photo);
			$away_av = esc_url(WC2026_Staff::get_avatar_url($as, 'thumbnail'));
		}

		if (null !== $match_num) {
			echo '<div class="wc-mnum">M' . (int) $match_num . '</div>';
		}
		// Home row.
		echo '<div class="wc-mteam' . ($is_tbd ? ' wc-tbd' : '') . '" style="border-left:3px solid ' . esc_attr($home_col) . '">';
		if ($home_flag) echo '<img class="wc-flag-s" src="' . esc_url($home_flag) . '" alt="" loading="lazy">';
		if ($home_av)  echo '<img class="wc-av-s" src="' . esc_url($home_av) . '" alt="">';
		echo '<span class="wc-tname">' . ($is_tbd ? 'TBD' : esc_html($home_name)) . '</span>';
		if ('' !== $home_score) echo '<span class="wc-tscore">' . esc_html($home_score) . '</span>';
		echo '</div>';
		// Away row.
		echo '<div class="wc-mteam' . ($is_tbd ? ' wc-tbd' : '') . '" style="border-left:3px solid ' . esc_attr($away_col) . '">';
		if ($away_flag) echo '<img class="wc-flag-s" src="' . esc_url($away_flag) . '" alt="" loading="lazy">';
		if ($away_av)  echo '<img class="wc-av-s" src="' . esc_url($away_av) . '" alt="">';
		echo '<span class="wc-tname">' . ($is_tbd ? 'TBD' : esc_html($away_name)) . '</span>';
		if ('' !== $away_score) echo '<span class="wc-tscore">' . esc_html($away_score) . '</span>';
		echo '</div>';
	}
endif; // wc2026_slot

/**
 * Echo a round column containing match groups with bracket arm connectors.
 *
 * @param array  $matches        All match objects for this round (half-side).
 * @param int    $group_size     How many match slots form one bracket group (always 2).
 * @param string $arm_class      'wc-arm-r' or 'wc-arm-l'.
 * @param int    $start_num      Starting match number label.
 */
if (! function_exists('wc2026_round_col')) :
	function wc2026_round_col($matches, $group_size, $arm_class, $start_num = 1)
	{
		$count  = count($matches);
		$groups = $count / $group_size;
		$n      = $start_num;
		echo '<div class="wc-rcol">';
		for ($g = 0; $g < $groups; $g++) {
			echo '<div class="wc-mgroup ' . esc_attr($arm_class) . '">';
			for ($p = 0; $p < $group_size; $p++) {
				$m = $matches[$g * $group_size + $p] ?? null;
				echo '<div class="wc-mbox">';
				wc2026_slot($m, $n++);
				echo '</div>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
endif; // wc2026_round_col

/**
 * Render a single group card (header + standings table).
 *
 * @param string $letter Group letter A–L.
 * @param array  $rows   Standings rows from WC2026_Matches::get_group_standings().
 */
if (! function_exists('wc2026_render_group_card')) :
	function wc2026_render_group_card($letter, $rows)
	{
		echo '<div class="wc-gcard">';
		echo '<div class="wc-gcard-hdr">GROUP ' . esc_html($letter) . '</div>';
		echo '<table class="wc-gtbl">';
		echo '<thead class="th-wc-legend"><tr>';
		echo '<th class="wc-th-pos wc-legend-note">#</th>';
		echo '<th class="wc-th-staff-color wc-legend-note"></th>';
		echo '<th class="wc-th-av"></th>';
		echo '<th class="wc-th-flag"></th>';

		echo '<th class="wc-th-name wc-legend-note" style="text-align:left;">Team</th>';
		echo '<th class="wc-th-stat wc-legend-note">P</th>';
		echo '<th class="wc-th-stat wc-legend-note">W</th>';
		echo '<th class="wc-th-stat wc-legend-note">D</th>';
		echo '<th class="wc-th-stat wc-legend-note">L</th>';
		echo '<th class="wc-th-pts wc-legend-note">Pts</th>';
		echo '</tr></thead>';
		echo '<tbody>';
		foreach ($rows as $pos => $row) {
			$c   = $row['country'];
			$cls = 0 === $pos ? 'wc-q1' : (1 === $pos ? 'wc-q2' : (2 === $pos ? 'wc-q3' : ''));
			$sv  = (object) array('name' => $c->staff_name, 'slug' => $c->staff_slug, 'colour' => $c->staff_colour, 'photo' => $c->staff_photo);
			$av  = $c->staff_name ? WC2026_Staff::get_avatar_url($sv, 'thumbnail') : '';
			echo '<tr class="' . esc_attr($cls) . '">';
			echo '<td class="wc-td-pos">' . (int) ($pos + 1) . '</td>';
			echo '<td class="wc-td-staff-color">';
			if ($c->staff_colour) {
				echo '<span class="wc-bar" style="background:' . esc_attr($c->staff_colour) . '"></span>';
			}
			echo '</td>';
			echo '<td class="wc-td-av">';
			if ($av) {
				echo '<img src="' . esc_url($av) . '" alt="' . esc_attr($c->staff_name) . '" title="' . esc_attr($c->staff_name) . '">';
			} else {
				echo '<span class="wc-bar" style="background:' . esc_attr($c->staff_colour ?? '#ccc') . '"></span>';
			}
			echo '</td>';
			echo '<td class="wc-td-flag">';
			if ($c->flag_url) echo '<img src="' . esc_url($c->flag_url) . '" alt="" loading="lazy">';
			echo '</td>';

			echo '<td class="wc-td-name">' . esc_html($c->name) . '</td>';
			echo '<td class="wc-td-stat">' . esc_html($row['played']) . '</td>';
			echo '<td class="wc-td-stat">' . esc_html($row['wins']) . '</td>';
			echo '<td class="wc-td-stat">' . esc_html($row['draws']) . '</td>';
			echo '<td class="wc-td-stat">' . esc_html($row['losses']) . '</td>';
			echo '<td class="wc-td-pts">' . esc_html($row['points']) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}
endif; // wc2026_render_group_card

// ── CSS (only emitted once per page request) ─────────────────────────────
static $css_done = false;
if (! $css_done) :
	$css_done = true;
?>
	<style>
		/* ================================================================
   CitCom WC2026 Wall Chart — scoped to .wc2026-chart
   ================================================================ */
		.wc2026-chart {
			--wc-purple: #2d1b52;
			--wc-teal: #009fa0;
			--wc-green: #7eb831;
			--wc-grad: linear-gradient(135deg, #2d1b52 0%, #009fa0 55%, #7eb831 100%);
			--wc-border: #d4d4d4;
			--wc-mh: 17px;
			font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
			font-size: 8px;
			color: #1a1a2e;
			background: #fff;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
			line-height: 1.2;
			min-width: 1350px;
			/* width: 1350px;
			margin: 0 auto;
			border: 1px solid var(--wc-border);
			border-radius: 4px;
			box-shadow: 0 4px 14px rgba(0, 0, 0, .15); */
		}

		.wc2026-chart * {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}

		/* Header */
		.wc-header {
			background: var(--wc-grad);
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 7px 14px;
			gap: 8px;
			color: #fff;
		}

		.wc-header-logo,
		.wc-header-trophy {
			width: 200px;
		}

		.wc-header-logo img {
			height: 25px;
			display: block;
		}

		.wc-header-title {
			flex: 1;
			text-align: center;
		}

		.wc-header-title .wc-t1 {
			font-size: 9px;
			font-weight: 700;
			letter-spacing: .12em;
			text-transform: uppercase;
			opacity: .85;
		}

		.wc-header-title .wc-t2 {
			font-size: 24px;
			font-weight: 900;
			letter-spacing: .04em;
			text-transform: uppercase;
			line-height: 1;
		}

		.wc-header-title .wc-t3 {
			font-size: 12px;
			font-weight: 600;
			opacity: .75;
			margin-top: 1px;
		}

		.wc-header-trophy {
			font-size: 40px;
			line-height: 1;
			text-align: right;
		}

		.wc-header-trophy.left {
			text-align: left;
		}

		/* Staff key */
		.wc-staff-key {
			display: flex;
			gap: 6px;
			align-items: center;
			padding: 3px 8px;
			background: #f0f2f5;
			border-bottom: 1px solid var(--wc-border);
			flex-wrap: wrap;
		}

		.wc-staff-key-lbl {
			font-size: 7px;
			font-weight: 700;
			color: #555;
			letter-spacing: .05em;
			text-transform: uppercase;
			margin-right: 4px;
		}

		.wc-staff-key-item {
			display: flex;
			align-items: center;
			gap: 4px;
			font-size: 7px;
			font-weight: 600;
			border-left: 3px solid #ccc;
			padding-left: 4px;
		}

		.wc-staff-key-item img {
			width: 18px;
			height: 18px;
			border-radius: 50%;
			object-fit: cover;
			border: 1px solid var(--wc-border);
			display: block;
		}

		/* Legend */
		.wc-legend {
			display: flex;
			gap: 10px;
			align-items: center;
			padding: 3px 8px;
			background: #f5f7fa;
			border-bottom: 1px solid var(--wc-border);
			font-size: 7px;
			flex-wrap: wrap;
		}

		.wc-legend-dot {
			display: inline-block;
			width: 8px;
			height: 8px;
			border-radius: 50%;
			margin-right: 3px;
			vertical-align: middle;
		}

		.wc-ldq {
			background: var(--wc-green);
		}

		.wc-ldt {
			background: var(--wc-teal);
		}

		.wc-legend-item {
			display: flex;
			align-items: center;
			color: #555;
		}

		.wc-legend-note {
			margin-left: auto;
			color: #aaa;
		}

		.wc-td-staff-color {
			width: 6px;
			text-align: center;
		}

		.wc-td-staff-color .wc-bar {
			display: inline-block;
			width: 3px;
			height: 22px;
			border-radius: 1px;
			vertical-align: middle;
		}

		/* Round labels */
		.wc-rlabels {
			display: grid;
			grid-template-columns: 220px 1fr 220px;
			gap: 4px;
			padding: 2px 4px;
		}

		.wc-rl-inner {
			display: flex;
			flex-direction: row;
		}

		.wc-rl-half {
			display: flex;
			flex-direction: row;
			flex: 1;
		}

		.wc-rl-col {
			flex: 1;
			text-align: center;
			font-size: 6.5px;
			font-weight: 700;
			color: var(--wc-teal);
			letter-spacing: .06em;
			text-transform: uppercase;
			padding: 0 3px;
		}

		.wc-rl-fin {
			width: 126px;
			flex-shrink: 0;
			text-align: center;
			font-size: 6.5px;
			font-weight: 700;
			color: var(--wc-purple);
			letter-spacing: .06em;
			text-transform: uppercase;
		}

		/* Main body */
		.wc-body {
			display: grid;
			grid-template-columns: 220px 1fr 220px;
			gap: 4px;
			padding: 4px;
			align-items: stretch;
		}

		/* Group columns */
		.wc-gcol {
			display: flex;
			flex-direction: column;
			gap: 3px;
		}

		/* Group card */
		.wc-gcard {
			border: 1px solid var(--wc-border);
			border-radius: 3px;
			overflow: hidden;
			background: #fff;
		}

		.wc-gcard-hdr {
			background: var(--wc-grad);
			color: #fff;
			font-size: 8px;
			font-weight: 800;
			padding: 2px 6px;
			letter-spacing: .1em;
			text-transform: uppercase;
		}

		.wc-gtbl {
			width: 100%;
			border-collapse: collapse;
		}

		.wc-gtbl td {
			padding: 1.5px 2px;
			vertical-align: middle;
			border-bottom: 1px solid #f3f3f3;
			white-space: nowrap;
		}

		.wc-gtbl tr:last-child td {
			border-bottom: none;
		}

		.wc-gtbl tr.wc-q1,
		.wc-gtbl tr.wc-q2 {
			background: #edfae6;
		}

		.wc-gtbl tr.wc-q3 {
			background: #e6f6f6;
		}

		.wc-gtbl .wc-td-pos {
			width: 10px;
			text-align: center;
			font-size: 7px;
			color: #aaa;
		}

		.wc-gtbl .wc-td-flag {
			width: 26px;
			text-align: center;
		}

		.wc-gtbl .wc-td-flag img {
			width: 22px;
			height: 18px;
			object-fit: cover;
			border-radius: 4px;
			display: block;
		}

		.wc-td-av {
			width: 22px;
			text-align: center;
		}

		.wc-gtbl .wc-td-av img {
			width: 15px;
			height: 15px;
			border-radius: 50%;
			object-fit: cover;
			display: block;
		}

		.wc-gtbl .wc-td-av .wc-bar {
			display: inline-block;
			width: 4px;
			height: 22px;
			border-radius: 1px;
			vertical-align: middle;
		}

		.wc-gtbl .wc-td-name {
			font-size: 7px;
			font-weight: 600;
			max-width: 58px;
			overflow: hidden;
			padding-left: 3px;
		}

		.wc-gtbl .wc-td-stat {
			text-align: center;
			font-size: 7px;
			color: #888;
			width: 13px;
		}

		.wc-gtbl .wc-td-pts {
			text-align: center;
			font-size: 8px;
			font-weight: 800;
			color: var(--wc-purple);
			width: 14px;
			padding-right: 3px;
		}

		/* Bracket centre */
		.wc-bracket-centre {
			display: flex;
			flex-direction: column;
		}

		.wc-bracket-rounds {
			display: flex;
			flex-direction: row;
			align-items: stretch;
			flex: 1;
		}

		.wc-bhalf {
			display: flex;
			flex-direction: row;
			align-items: stretch;
			justify-content: space-evenly;
			flex: 1;
		}

		.th-wc-legend {
			background: #f5f7fa;
			font-size: 7px;
			color: #555;
			height: 14px;
		}



		/* Final column */
		.wc-fin-col {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 6px;
			padding: 0 6px;
			flex-shrink: 0;
			width: 126px;
		}

		.wc-fin-box {
			background: var(--wc-grad);
			border-radius: 5px;
			padding: 6px 8px;
			text-align: center;
			color: #fff;
			width: 100%;
			box-shadow: 0 2px 8px rgba(45, 27, 82, .2);
		}

		.wc-fin-box .wc-fl {
			font-size: 10px;
			font-weight: 900;
			letter-spacing: .06em;
			text-transform: uppercase;
		}

		.wc-fin-box .wc-fd {
			font-size: 7px;
			opacity: .75;
			margin: 2px 0;
		}

		.wc-fin-box .wc-fv {
			font-size: 6.5px;
			opacity: .6;
		}

		.wc-fin-slot {
			margin-top: 4px;
		}

		.wc-fin-slot .wc-mteam {
			border-color: rgba(255, 255, 255, .35);
			background: rgba(255, 255, 255, .12);
			color: #fff;
		}

		.wc-fin-slot .wc-mteam .wc-tname {
			color: #fff;
		}

		.wc-3rd-box {
			border: 2px solid var(--wc-teal);
			border-radius: 4px;
			padding: 4px 6px;
			text-align: center;
			background: #fff;
			width: 100%;
		}

		.wc-3rd-box .wc-3l {
			font-size: 7.5px;
			font-weight: 700;
			color: var(--wc-teal);
			text-transform: uppercase;
			letter-spacing: .06em;
		}

		.wc-3rd-box .wc-3d {
			font-size: 6.5px;
			color: #666;
			margin: 2px 0;
		}

		/* Round columns */
		.wc-rcol {
			display: flex;
			flex-direction: column;
			justify-content: space-around;
			padding: 0 3px;
		}

		/* Match group (2 slots + bracket arm) */
		.wc-mgroup {
			position: relative;
			display: flex;
			flex-direction: column;
			flex: 1;
			justify-content: space-around;
			gap: 1px;
			padding: 2px 0;
		}

		.wc-arm-r::after {
			content: '';
			position: absolute;
			right: -4px;
			top: calc(25% + 4px);
			height: calc(50% - 8px);
			width: 4px;
			border-top: 1px solid #bbb;
			border-right: 1px solid #bbb;
			border-bottom: 1px solid #bbb;
		}

		.wc-arm-l::before {
			content: '';
			position: absolute;
			left: -4px;
			top: calc(25% + 4px);
			height: calc(50% - 8px);
			width: 4px;
			border-top: 1px solid #bbb;
			border-left: 1px solid #bbb;
			border-bottom: 1px solid #bbb;
		}

		/* Match slot */
		.wc-mbox {
			position: relative;
		}

		.wc-mnum {
			font-size: 6px;
			color: #ccc;
			text-align: right;
			padding-right: 2px;
			line-height: 1.3;
		}

		.wc-mteam {
			display: flex;
			align-items: center;
			gap: 2px;
			height: var(--wc-mh);
			padding: 0 3px 0 2px;
			background: #fff;
			border: 1px solid #ccc;
			border-left-width: 3px;
			font-size: 7px;
			white-space: nowrap;
			overflow: hidden;
			min-width: 88px;
			max-width: 106px;
		}

		.wc-mteam:first-of-type {
			border-bottom: none;
		}

		.wc-mteam.wc-tbd .wc-tname {
			color: #bbb;
			font-style: italic;
			font-weight: 400;
		}

		.wc-flag-s {
			width: 18px;
			height: auto;
			flex-shrink: 0;
			display: block;
			border: 1px solid #eee;
		}

		.wc-av-s {
			width: 13px;
			height: 13px;
			border-radius: 50%;
			object-fit: cover;
			flex-shrink: 0;
			display: block;
		}

		.wc-tname {
			flex: 1;
			overflow: hidden;
			text-overflow: ellipsis;
			font-weight: 600;
			font-size: 7px;
		}

		.wc-tscore {
			margin-left: auto;
			font-weight: 800;
			font-size: 8px;
			color: var(--wc-purple);
			padding-left: 3px;
			flex-shrink: 0;
		}

		/* SF single-match */
		.wc-sf-wrap {
			flex: 1;
			display: flex;
			flex-direction: column;
			justify-content: center;
			padding: 2px;
		}

		/* Footer */
		.wc-footer {
			background: var(--wc-purple);
			color: rgba(255, 255, 255, .5);
			text-align: center;
			padding: 4px;
			font-size: 7px;
			letter-spacing: .04em;
		}

		.wc-footer strong {
			color: rgba(255, 255, 255, .8);
		}

		/* Print */
		@media print {
			.wc2026-chart {
				font-size: 8px;
			}

			.wc2026-zoom-bar {
				display: none !important;
			}
		}

		@page {
			size: A2 landscape;
			margin: 7mm;
		}
	</style>
<?php endif; ?>

<div class="wc2026-chart-zoom-wrap">
	<div class="wc2026-zoom-bar">
		<button type="button" class="wc2026-zoom-btn" data-zoom="fit"><?php esc_html_e('Fit', 'wc2026-sweepstake'); ?></button>
		<div class="wc2026-zoom-stepper">
			<button type="button" class="wc2026-zoom-btn" data-zoom="out" aria-label="<?php esc_attr_e('Zoom out', 'wc2026-sweepstake'); ?>">&#8722;</button>
			<span class="wc2026-zoom-level">100%</span>
			<button type="button" class="wc2026-zoom-btn" data-zoom="in" aria-label="<?php esc_attr_e('Zoom in', 'wc2026-sweepstake'); ?>">+</button>
		</div>
		<button type="button" class="wc2026-zoom-btn" data-zoom="reset"><?php esc_html_e('Reset', 'wc2026-sweepstake'); ?></button>
	</div>
	<div class="wc2026-chart-viewport">

		<div class="wc2026-chart">

			<!-- HEADER -->
			<div class="wc-header">
				<div class="wc-header-trophy left">🏆</div>
				<div class="wc-header-title">
					<div class="wc-t1"><?php echo esc_html($site_name); ?> Sweepstake</div>
					<div class="wc-t2">World Cup 2026</div>
					<div class="wc-t3">Wall Chart &amp; Bracket</div>
				</div>
				<div class="wc-header-trophy">🏆</div>
			</div>

			<!-- STAFF KEY -->
			<div class="wc-staff-key">
				<span class="wc-staff-key-lbl">Staff:</span>
				<?php foreach ($all_staff as $s) :
					$av = WC2026_Staff::get_avatar_url($s, 'thumbnail');
				?>
					<div class="wc-staff-key-item" style="border-left-color:<?php echo esc_attr($s->colour); ?>">
						<img src="<?php echo esc_url($av); ?>" alt="<?php echo esc_attr($s->name); ?>">
						<span><?php echo esc_html($s->name); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- LEGEND -->
			<div class="wc-legend">
				<span class="wc-legend-item"><span class="wc-legend-dot wc-ldq"></span>Qualifies to R32</span>
				<span class="wc-legend-item"><span class="wc-legend-dot wc-ldt"></span>Best third-place contender</span>
				<span class="wc-legend-note">P=Played &nbsp; W=Wins &nbsp; D=Draws &nbsp; L=Losses &nbsp; Pts=Points</span>
			</div>

			<!-- ROUND LABELS -->
			<div class="wc-rlabels">
				<div></div>
				<div class="wc-rl-inner">
					<div class="wc-rl-half">
						<div class="wc-rl-col">R32</div>
						<div class="wc-rl-col">R16</div>
						<div class="wc-rl-col">QF</div>
						<div class="wc-rl-col">SF</div>
					</div>
					<div class="wc-rl-fin">Final</div>
					<div class="wc-rl-half wc-rl-r">
						<div class="wc-rl-col">SF</div>
						<div class="wc-rl-col">QF</div>
						<div class="wc-rl-col">R16</div>
						<div class="wc-rl-col">R32</div>
					</div>
				</div>
				<div></div>
			</div>

			<!-- MAIN BODY -->
			<div class="wc-body">

				<!-- LEFT GROUPS A–F -->
				<div class="wc-gcol">
					<?php foreach (array('A', 'B', 'C', 'D', 'E', 'F') as $g) :
						wc2026_render_group_card($g, $standings[$g] ?? array());
					endforeach; ?>
				</div>

				<!-- BRACKET -->
				<div class="wc-bracket-centre">
					<div class="wc-bracket-rounds">

						<!-- LEFT HALF: R32→R16→QF→SF -->
						<div class="wc-bhalf">
							<?php wc2026_round_col($r32_l, 2, 'wc-arm-r', 1); ?>
							<?php wc2026_round_col($r16_l, 2, 'wc-arm-r', 1); ?>
							<?php wc2026_round_col($qf_l,  2, 'wc-arm-r', 1); ?>
							<div class="wc-rcol">
								<div class="wc-sf-wrap"><?php wc2026_slot($sf_l, 1); ?></div>
							</div>
						</div>

						<!-- FINAL + 3RD -->
						<div class="wc-fin-col">
							<div class="wc-fin-box">
								<div class="wc-fl">⚽ Final</div>
								<div class="wc-fd">26 July 2026</div>
								<div class="wc-fv">MetLife Stadium, NY/NJ</div>
								<div class="wc-fin-slot"><?php wc2026_slot($fin); ?></div>
							</div>
							<div class="wc-3rd-box">
								<div class="wc-3l">3rd Place Play-off</div>
								<div class="wc-3d">25 July 2026</div>
								<?php wc2026_slot($trd); ?>
							</div>
						</div>

						<!-- RIGHT HALF (mirrored): SF→QF→R16→R32 -->
						<div class="wc-bhalf wc-br">
							<div class="wc-rcol">
								<div class="wc-sf-wrap"><?php wc2026_slot($sf_r, 2); ?></div>
							</div>
							<?php wc2026_round_col($qf_r,  2, 'wc-arm-l', 3); ?>
							<?php wc2026_round_col($r16_r, 2, 'wc-arm-l', 5); ?>
							<?php wc2026_round_col($r32_r, 2, 'wc-arm-l', 9); ?>
						</div>

					</div>
				</div>

				<!-- RIGHT GROUPS G–L -->
				<div class="wc-gcol">
					<?php foreach (array('G', 'H', 'I', 'J', 'K', 'L') as $g) :
						wc2026_render_group_card($g, $standings[$g] ?? array());
					endforeach; ?>
				</div>

			</div><!-- .wc-body -->

			<!-- FOOTER -->
			<div class="wc-footer">
				<?php echo esc_html($site_name); ?> &mdash; World Cup 2026 Sweepstake &nbsp;|&nbsp;
				Generated <?php echo esc_html(current_time('j F Y, H:i')); ?>
			</div>

		</div><!-- .wc2026-chart -->

	</div><!-- .wc2026-chart-viewport -->
</div><!-- .wc2026-chart-zoom-wrap -->