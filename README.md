<p align="center">
  <img src="html/images/title.jpg" alt="Emissary" />
</p>

A browser-based space strategy game originally developed between 2003 and 2004, now being restored and modernized to run on a Docker LAMP stack with PHP 8.5 and MySQL 8.

## Table of Contents

- [About the Game](#about-the-game)
  - [Core Gameplay](#core-gameplay)
  - [Technology](#technology)
- [Repository Contents](#repository-contents)
- [⚠️ Pre-Beta Status](#️-pre-beta-status)
  - [What has been done](#what-has-been-done)
  - [What still needs work](#what-still-needs-work)
- [Quick Start](#quick-start)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Access Points](#access-points)
  - [Clean Wipe](#clean-wipe-reset-for-fresh-install)
- [Project Structure](#project-structure)
- [Admin Panel](#admin-panel)
- [License](#license)

## About the Game

Emissary is a multiplayer browser-based space strategy game where players compete for control of a galaxy divided into 100 sectors. Players colonize planets, build structures, construct fleets, form teams, and wage war across star systems.

This should be familiar with anyone (thinking millenials mainly!) who played PC games like Ascendancy or browser-based games like Planetarion back in the day. It was Planetarion that I was originally trying to recreate with this project while at high school.

I fully intend for the UI to remain very 'retro' and simplistic, as well as limit use of 'detailed' graphics wherever possible. I'd hope the UI would closer resemble 1970's 'Alien' than Stellaris type games.

### Core Gameplay

- **Galaxy** — A 10×10 grid of sectors, each containing up to 10 star systems with orbiting planets
- **Resources** — Metal, Mineral, Astrium, and Credits — harvested from planets and used for construction
- **Buildings** — Construct HQs, Mines, Refineries, Shipyards, Turrets, Shields, Hangars, Barracks, and Sensors on planet surfaces
- **Ships** — Build Scouts, Fighters, Bombers, Cruisers, Battleships, Colony Ships, and Transports
- **Fleets** — Organize ships into fleets for movement, combat, and colonization
- **Teams** — Form alliances with other players to control territory
- **Combat** — Automated fleet battles with detailed battle reports
- **Economy** — Cron-driven turn system that processes income, construction, and fleet movement on configurable intervals

### Technology

- **Frontend**: PHP-generated HTML with dynamic GD image rendering (galaxy maps, planet surfaces, sector views)
- **Backend**: PHP 8.5 on Apache
- **Database**: MySQL 8.1
- **Infrastructure**: Docker Compose (web + database + phpMyAdmin)
- **Turn System**: Cron-based with configurable mini-turn and income turn intervals

## Repository Contents

### `archive/` — Original 2004 Source Code

The [`archive/`](archive/) folder contains the original unmodified PHP source code as recovered from an old hard drive. This is the raw 2004-era code — it will not run on any modern PHP version. It is preserved here as a historical reference and is not intended to be executed or modified.

### `html/` — Modernized Game Code

The [`html/`](html/) directory contains the working, modernized version of the game, updated to run on PHP 8.5 and MySQL 8. This is the code that the Docker stack serves.

### `pre-git/` — Pre-Git Change Documentation

The [`pre-git/`](pre-git/) directory contains detailed technical documentation of every code change made to bring the original 2004 source to the point where I started this repo, organized by phase. Simply loading the old PHP files into a new LAMP stack was not enough, as when originally developing this game I was working on a web hosting service platform and did not save the original SQL schema. I stopped development when I went to college in 2004, and the original game database was (foolishly) lost when I cancelled the hosting package. The original game files were saved however and I found them last year when performing a recovery on an old hard drive I found in the attic.

1. [Database Schema Reconstruction](pre-git/01-database-schema.md) — Reverse-engineered 17-table MySQL schema from PHP source
2. [PHP 8.2 Migration](pre-git/02-php82-migration.md) — `mysql_*` → `mysqli_*`, `mcrypt` → `password_hash`, session fixes, 112 warning fixes
3. [File Path & Include Fixes](pre-git/03-file-path-fixes.md) — All relative paths → `__DIR__`, include guards, robust file parsing
4. [Docker Infrastructure](pre-git/04-docker-infrastructure.md) — Dockerfile, docker-compose, entrypoint, secrets management
5. [Admin Panel](pre-git/05-admin-panel.md) — New `/admin/` with stats, world gen, turn processing, The Burn
6. [Turn Timer System](pre-git/06-turn-timer.md) — Rewrote timer functions, fixed cron scripts, configurable intervals
7. [Installer](pre-git/07-installer.md) — Web-based first-time setup with streamed progress and cron config
8. [Image Generation Fixes](pre-git/08-image-fixes.md) — Fixed GD image paths and `imagejpeg()` PHP 8 compatibility

A summarized changelog is also available in [`CHANGELOG.md`](CHANGELOG.md).

## ⚠️ Pre-Beta Status

**This project is in a pre-beta state.** The original 2004 PHP source code has been partially modernized to run on PHP 8.5 / MySQL 8, but not all changes have been fully implemented or tested prior to this first git commit. Some game features may be broken or incomplete.

### What has been done

- Migrated all database calls from `mysql_*` to `mysqli_*`
- Replaced deprecated encryption (`mcrypt`) with modern password hashing (`password_hash`/`password_verify`)
- Fixed session handling, undefined key warnings, and reserved word conflicts
- Converted file paths to absolute (`__DIR__`) for reliable include/cron execution
- Reconstructed the MySQL database schema from source analysis (original DB was lost)
- Created a web-based installer with galaxy population and cron configuration
- Built an admin panel with world generation, turn processing, and galaxy reset ("The Burn")
- Fixed GD image generation for galaxy and route maps
- Dockerized the entire stack
- **POST + CSRF migration** — all state-changing actions converted from GET to POST with CSRF token validation
- **Session security hardening** — IP+UA fingerprinting, secure cookie flags, session timeout, regeneration on login
- **Team management system** — create/join/leave teams, leader elections with 5-turn voting, team editing, join request approval, election history
- **Election motion system** — elections require a motion raised by a member and seconded by 25% of the team before voting begins; automatic elections every 100 turns; leader can resign to trigger an immediate election
- **Team colour system** — 16 preset colours with uniqueness enforcement and visual colour picker
- **Home world system** — starting planet as home world, 2× resource production, 1.5× building HP, forced re-selection on invasion, game-over flow with planet purchase option
- **Configurable game settings** — 14 game balance values (home world multipliers, starting resources, buy planet costs, harvester bonus, election parameters, weapon hit chance, construction slots) stored in the database and editable from the admin panel
- **Galaxy map tooltips** — hover shows sector number, system/planet counts, controlling team, and player breakdown
- **System page improvements** — planet sidebar shows fleet/shield/weapon icons and home world badges
- **Planet list improvements** — home world badge, building/fleet/weapon status icons
- **Player profile** — shows team affiliation with colour swatch, home planet link
- **Fleet ownership checks** — fleet actions verify the logged-in player owns the fleet
- **Various bug fixes** — trade.php `$Mineral`, shieldcount.img.php GD calls, sectorimage.img.php TypeError, undefined array key warnings, include guard for userfunctions.inc.php
- **Alerts system** — full activity log and notification centre replacing the old gamelog system; categorised alerts (system, fleet, construction, combat, team, economy) with links to relevant pages, unread count in header, category filter tabs; action confirmations (fleet moves, building, teams, etc.) now persist as alerts alongside the `?msg=` display; turn events (ship/building completion, fleet arrival, income, battles, invasions, elections) generate alerts automatically; old alerts purged after 30 days
- **Construction queue system** — configurable build queue slots per planet (`base_construction_slots` setting, +1 per factory), queue capacity display on planet page, build forms hidden when queue is full
- **Sector page enhancements** — compass-style navigation buttons for adjacent sectors; planets listed under each stakeholder in the sidebar
- **System & planet renaming** — owners can rename systems (sole controller of all colonised planets) and individual planets; names validated for length, character set, uniqueness (against both custom and default names), and profanity; system renames automatically cascade to planet default names; revert-to-default option for both
- **Profanity filter** — configurable forbidden word list (`data/forbidden_words.txt`) with admin panel editor and `.htaccess` protection; used by system, planet, and sector rename validation
- **Galaxy zoom map** — full-size 5000×5000 stitched view of all 100 sector images with links to each sector, accessible from the galaxy overview
- **Systems list page** — dedicated overview of all owned systems showing income totals, fleet counts, unassigned ships, allied/rival holdings, and majority control status

### What still needs work

#### Security

Some hardening has been done, but significant work remains — this is 2004 code that was never designed for a hostile internet.

**Done:**
- `.htaccess` blocks direct browser access to all `.inc.php` and `.cron.php` files (Apache returns 403; PHP `include()` still works)
- `userdata/` directory blocked from browser access via RewriteRule
- `admin/tools.php` unauthenticated GET endpoints removed; file now requires `ADMIN_TOOLS_ACCESS` constant (only defined by authenticated admin context)
- Admin panel redirects non-admin users to home instead of serving content
- Login flow preserves the originally-requested URL and redirects back after authentication (with open-redirect sanitization)
- Null dereference hardening — ~50 functions across 5 core include files now check `mysqli_fetch_object()` results before property access

**Still needed:**
- **SQL injection everywhere** — every query uses string interpolation instead of prepared statements
- **Unauthenticated dev tools** — `giveplanetships.php` and `simulatebattle.php` have no login check and are publicly accessible
- **Dangerous DELETE queries** — building demolish/cancel queries are missing a planet ID filter and can affect buildings on other planets
- **Weak password handling** — passwords are lowercased before hashing, reducing entropy
- **No HTTPS** configured
- ~~**XSS** — user-supplied values (messages, fleet names, ship names) are echoed without escaping~~ *(fixed — added `h()` helper wrapping `htmlspecialchars()` across all page files, include files, and HTML-building functions)*
- ~~**Missing ownership checks** — fleet actions don't verify the logged-in player owns the fleet; any player can move, rename, or delete any fleet~~ *(fixed — fleet ownership checks added)*
- ~~**No CSRF protection** — no form tokens, no secure cookie flags, no session regeneration on login~~ *(fixed — POST+CSRF migration, session fingerprinting, secure cookie flags, session timeout)*

#### Known Bugs

- Fleet AP display calls the HP function instead — shows HP for both stats
- Building demolish/cancel queries are missing `AND PlanetID` in the WHERE clause
- The cron colonise branch references undefined `$PlanetID` and `$PlayerID` variables
- `FleetBattle()` tries to iterate a `ShipBundle` object as a flat array
- `chooserace.php` calls `StageTwo()` without the required `$username` argument
- ~~`DestroyShip()` references the wrong variable (`$res` instead of `$rescount`) — always fails~~ *(fixed)*
- ~~Building ownership check uses a bare word `(edit)` instead of the variable `($edit)` — always passes~~ *(fixed)*
- ~~`HasShipyard()` uses `$username` without a `global` declaration~~ *(fixed)*
- ~~Trade page has a missing `$` on `Mineral` — evaluates as a string constant instead of a variable~~ *(fixed)*
- ~~`shieldcount.img.php` still uses the deprecated `imagejpeg($image,'',80)` form~~ *(fixed — also fixed float-to-int deprecation)*

#### Incomplete Features

Several features have UI or stubs but were never finished in the original code:

- **Race/species creation** — signup step 2 has a form but the backend is empty
- ~~**Alerts** — the header links to an alerts page that doesn't exist~~ *(fixed — full alerts system with categorised notifications, unread badges, and category filtering; replaces old gamelog/Report() system)*
- **Auctions** — the trade page shows "Create an Auction" as placeholder text with no form behind it
- **Fleet-vs-fleet combat** — the function exists but is commented out; only fleet-vs-planet battles work
- **Exploration and scouting** — described in the game pitch but no mechanics were ever built
- **Tech tree / research** — laboratories can be built on planets but have no effect
- **Orbital structures** — "Orbital Spaces" is hardcoded to 0; the orbital grid is half-implemented
- **Player messaging** — no way for players to communicate in-game
- Several functions are empty stubs: ~~`CanInvade()`~~, `ClearHomePage()`, `GetLowestRankVesselTypeInFleet()`
- ~~**Team management** — you can view your team but can't create, join, leave, or disband one~~ *(fixed — full team system: create, join/leave, leader elections, team editing, colour picker with 16 unique preset colours)*
- **Debug log system** — no centralised game logging; need a structured debug/event log accessible from the admin panel covering turn processing, combat resolution, fleet movement, resource calculations, ownership changes, and error conditions — essential for diagnosing game balance issues and tracing bugs in a live game

#### Code Quality

- Heavy use of global state (`global $username`, `$GLOBALS["conn"]`) instead of passing dependencies
- PHP 4–era class syntax (`var` declarations, named constructors)
- Monolithic files — `fleetfunctions.inc.php` is 1,200+ lines mixing fleet, ship, and battle logic
- No transactions — multi-step operations (battles, trades) can leave the database inconsistent
- Non-atomic resource updates (SELECT then UPDATE) are vulnerable to race conditions
- No type declarations on function parameters or return types
- Error handling is `or die()` everywhere
- ~~N+1 query problem — a single page load can fire 50+ database queries~~ *(improved — galaxy map tooltips use bulk JOINed queries instead of per-sector loops)*

#### UI/UX

- 2004-era HTML 4.01 markup with `<font>` tags, inline styles, and hardcoded pixel widths
- ISO-8859-1 encoding instead of UTF-8
- No responsive design — doesn't work on mobile
- Planet management is a JPEG image map with no hover states or interactivity
- No confirmation dialogs before destructive actions (demolish, delete fleet)
- Error messages passed through URL query strings
- "galazy" typo on the front page

#### Game Balance

All building and ship stat values are estimates — the originals were not recoverable. Beyond that:

- Repair is completely free — restores full HP at zero cost
- No upkeep or maintenance costs for ships or buildings
- Harvester income bonus (5% per harvester) stacks without any cap
- No ship-type advantages or counters — combat is purely stat-based
- ~~Planet weapon hit chance is hardcoded at 1-in-3 with no modifiers~~ *(now configurable via admin panel)*

#### Infrastructure & Testing

- No query caching — every page load hits the database dozens of times
- Zero automated tests — debug scripts (`simulatebattle.php`, `fleettest.php`) used instead
- No CI/CD pipeline
- ~~Cron jobs have no locking mechanism — concurrent runs are possible~~ *(fixed — `flock()` guards added to both cron scripts)*
- ~~Some game data (known systems, battle logs) stored as flat files instead of in the database~~ *(fixed — all migrated to DB: `battles.Log` TEXT column, `known_systems` table, `game_settings` key-value table)*
- ~~Some image generators still use relative paths (`planetimage.img.php`, `shieldcount.img.php`)~~ *(fixed — all six `.img.php` generators now use `__DIR__` for includes and image file paths)*

## Quick Start

### Prerequisites

- Docker and Docker Compose

### Installation

1. Clone this repository:
   ```
   git clone https://github.com/jonmilele/Emissary.git
   cd Emissary
   ```

2. Create a `.env` file with your MySQL root password:
   ```
   echo "MYSQL_ROOT_PASSWORD=your_password_here" > .env
   ```

3. Build and start the stack:
   ```
   docker compose up -d --build
   ```

4. Open http://localhost/ in your browser — you'll be redirected to the installer.

5. Enter your MySQL root password, choose an admin username/password, configure turn intervals, and click **Install Emissary**.

### Access Points

| URL | Purpose |
|-----|---------|
| http://localhost/ | Game |
| http://localhost/admin/ | Admin panel (PlayerID 1 only) |
| http://localhost:8080 | phpMyAdmin |

### Clean Wipe (Reset for Fresh Install)

To reset everything and test the installer from scratch:
```
./clean-wipe.sh
```

## Project Structure

```
Emissary/
├── Dockerfile              # PHP 8.5 + Apache + GD + cron
├── entrypoint.sh           # Starts cron daemon + Apache
├── docker-compose.yml      # Web + MySQL 8.1 + phpMyAdmin
├── clean-wipe.sh           # Reset script for fresh install testing
├── .env                    # MySQL root password (gitignored)
├── CHANGELOG.md            # Full change history
├── pre-git/                # Detailed pre-git change documentation
├── archive/                # Original 2004 source code (unmodified)
└── html/                   # Game files (Apache document root)
    ├── install.php         # Web-based first-time installer
    ├── index.php           # Login page / installer redirect
    ├── schema.sql          # Database schema (18 tables + reference data)
    ├── admin/              # Admin panel
    ├── data/               # Game data files (forbidden words) — blocked from web access
    ├── images/             # Game artwork and static assets
    ├── galaxyzoom.php      # Full-size stitched galaxy map
    └── userdata/           # Static seed data (system name pool)
```

## Admin Panel

The admin panel (accessible to PlayerID 1 at `/admin/`, linked from the header bar) provides:
- Database stats and player management
- **Game Settings** — configure 14 game balance values (home world multipliers, starting resources, planet purchase costs, election timing, weapon hit chance, construction slots, and more)
- **Forbidden Words** — manage the profanity filter word list used by rename validation
- Galaxy population and sector ownership tools
- Manual turn processing (mini-turn and income turn)
- **The Burn** — full galaxy reset that wipes all game data while preserving player accounts

## License

This project is licensed under the [MIT License](LICENSE). This applies to both the original 2004 source code in `archive/` and all modernized code in this repository.
