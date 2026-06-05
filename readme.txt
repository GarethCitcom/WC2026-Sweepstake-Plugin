=== WC2026 Sweepstake ===
Contributors: citcom
Tags: world cup, sweepstake, fixtures, leaderboard
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.2.1
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Internal World Cup 2026 sweepstake — wall chart, fixtures, scores, and leaderboard for staff.

== Description ==

An internal sweepstake plugin for the FIFA World Cup 2026. Features include:

* Full wall chart with group standings and bracket
* Live fixture list with scores synced from football-data.org
* Staff leaderboard sorted by points
* Individual staff profile pages
* Admin tools: staff manager, country assignment, score entry, and printable wall chart

Designed for private/internal use with a small group of staff members.

== Installation ==

1. Upload the `wc2026-sweepstake` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WC2026** in the admin menu to configure staff and settings.
4. Use the shortcodes below to display content on any page.

== Shortcodes ==

* `[wc_sweepstake]` — Tabbed hub wrapping all sections.
* `[wc_wall_chart]` — Group stage wall chart.
* `[wc_wall_chart_full]` — Full wall chart with bracket.
* `[wc_fixtures]` — Full fixture list with filters.
* `[wc_leaderboard]` — Staff leaderboard.
* `[wc_staff_profile slug="name"]` — Individual staff profile.

== Changelog ==

= 1.2.1 =
* Security and coding standards improvements.

= 1.0.0 =
* Initial release.
