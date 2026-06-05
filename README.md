# WC2026 Sweepstake

A WordPress plugin for running an internal World Cup 2026 sweepstake. Staff members are assigned countries, and the plugin tracks group-stage standings, match scores, and a live leaderboard.

## Features

- **Wall Chart** — full tournament bracket (Groups A–L through to the Final), rendered via shortcode
- **Fixtures** — all 104 matches (72 group stage + 32 knockouts), with scores and status
- **Leaderboard** — points-based rankings for each staff member based on their assigned countries
- **Staff Profiles** — per-person pages showing assigned countries and points
- **Admin Panel** — manage staff assignments, enter scores, configure settings
- **API-Football Sync** — optional automated score sync via WP-Cron

## Tournament Details

- **Teams:** 48 countries across groups A–L
- **Staff:** 8 participants, each assigned a set of countries
- **Fixtures:** 104 total (group + R32 + R16 + QF + SF + 3rd place + Final)
- **Points:** Group stage wins = 2pts, draws = 1pt; knockout milestones configurable

## Requirements

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

## Installation

1. Upload the `wc2026-sweepstake` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins → Installed Plugins** in WordPress admin
3. The database tables are created automatically on activation
4. Navigate to **WC2026 Sweepstake** in the admin menu to set up staff and configure settings

## Shortcodes

| Shortcode | Description |
|---|---|
| `[wc_wall_chart]` | Compact wall chart widget |
| `[wc_wall_chart_full]` | Full tournament bracket |
| `[wc_fixtures]` | Fixtures list with scores |
| `[wc_leaderboard]` | Staff points leaderboard |
| `[wc_staff_profile id="X"]` | Individual staff profile page |
| `[wc_sweepstake_hub]` | Main hub page |

## File Structure

```
wc2026-sweepstake/
├── wc2026-sweepstake.php       # Plugin entry point
├── includes/
│   ├── class-db.php            # Database schema, install, migrations
│   ├── class-staff.php         # Staff management
│   ├── class-countries.php     # Country & group data
│   ├── class-matches.php       # Fixture & score logic
│   ├── class-leaderboard.php   # Points calculation
│   └── class-api-sync.php      # API-Football cron sync
├── admin/
│   ├── admin-menu.php          # Admin menu registration
│   ├── page-staff.php          # Staff admin page
│   ├── page-settings.php       # Settings page
│   ├── page-help.php           # Help page
│   ├── wallchart-generator.php # Wall chart data builder
│   ├── css/admin.css
│   └── js/admin.js
├── public/
│   ├── shortcodes.php          # Shortcode registration
│   ├── templates/
│   │   ├── sweepstake-hub.php
│   │   ├── wall-chart.php
│   │   ├── wall-chart-full.php
│   │   ├── fixtures.php
│   │   ├── leaderboard.php
│   │   └── staff-profile.php
│   └── assets/
│       ├── css/sweepstake.css
│       └── js/sweepstake.js
└── data/                       # Seed data (fixtures, countries)
```

## License

GPL-2.0-or-later
