# Emissary - Change History

Record of all modifications made to restore and modernize the original ~2004 PHP space strategy game, prior to uploading to GitHub repository.

> **Full documentation with code-level detail:** See [`docs/`](pre-git/README.md)

## Phase 1: Database Schema Reconstruction
> [Detailed documentation →](pre-git/01-database-schema.md)

- Reconstructed full MySQL schema from ~55 PHP source files into `schema.sql` (17 tables)
- Seeded reference data: 100 sectors, 9 building types, 7 ship types, 4 planet types
- Renamed database and user from `nonny51_game` to `emissary`
- Connection credentials managed via `secrets.inc.php` (gitignored)

## Phase 2: PHP 8.2 Migration
> [Detailed documentation →](pre-git/02-php82-migration.md)

- Replaced all `mysql_*` calls with `mysqli_*` using `$GLOBALS["conn"]` (451 references across all source files)
- Replaced all 21 `split()` calls with `explode()`
- Replaced `mcrypt_encrypt`/`mcrypt_decrypt` with `password_hash()`/`password_verify()` in `signup.back.php`, `login.back.php`, and `admin/tools.php`
- Removed deprecated `session_register()` calls; replaced with `$_SESSION` assignments
- Added `$username = $_SESSION['username']` to `authenticate.inc.php`
- Fixed 112 undefined array key warnings by adding `?? ""` fallbacks to all `$_GET`/`$_POST` accesses
- Fixed Mac-style `\r` line endings in `planetfunctions.inc.php` and `turn.cron.php`
- Changed shared includes to `include_once()` where needed to prevent redeclaration errors
- Backtick-quoted `System` column name in SQL queries (reserved word in MySQL 8)

## Phase 3: File Path & Include Fixes
> [Detailed documentation →](pre-git/03-file-path-fixes.md)

- Converted relative file paths to `__DIR__`-based absolute paths (32 references) in:
  - `turnfunctions.inc.php`, `turn.cron.php`, `miniturn.cron.php`
  - `fleetfunctions.inc.php`, `userfunctions.inc.php`
  - `battle.php`, `write.php`
  - `galaxyimage.img.php`, `routeimage.img.php`
- `admin/tools.php`: all includes use `__DIR__`, file operations use `GAME_ROOT` constant
- `admin/tools.php`: replaced fragile `substr($start, 0, strlen-1)` with `trim()` for names.txt parsing; added bounds checking with `isset()`
- Cron shebangs updated from `#!/usr/local/bin/php -q` to `#!/usr/bin/env php`

## Phase 4: Docker Infrastructure
> [Detailed documentation →](pre-git/04-docker-infrastructure.md)

- Created `Dockerfile` based on `php:8.2-apache` with `mysqli`, `gd`, and `cron`
- Created `entrypoint.sh` to start cron daemon alongside Apache
- `docker-compose.yml` updated to use `build: .` and `env_file: .env`
- Docker secrets (`MYSQL_ROOT_PASSWORD`) moved to `.env` (gitignored)
- File permissions: `html/` and `userdata/` set to 777 for Apache write access

## Phase 5: Admin Panel
> [Detailed documentation →](pre-git/05-admin-panel.md)

- New admin panel at `/admin/` restricted to PlayerID 1
- Dashboard: database stats, full player list with resources
- World generation: populate individual or all empty sectors, recalculate sector ownership
- Turn processing: run mini-turn, run income turn, reset turn timer
- Player management: password reset
- "The Burn" galaxy reset: wipes all game data (planets, systems, ships, fleets, buildings, battles, auctions, teams, cron jobs), resets player resources to zero while preserving accounts, repopulates galaxy, resets turn timer; double-confirmation required
- Destructive tools: clear individual sector, clear all planets

## Phase 6: Turn Timer System
> [Detailed documentation →](pre-git/06-turn-timer.md)

- `turnfunctions.inc.php` completely rewritten: handles missing/stale `turntime.txt` gracefully (returns 0 instead of negative values like -11308808)
- `ResetTurnTimer()` function writes current `time()` to `turntime.txt`
- `GetTurnInterval()` reads configurable interval from `turninterval.txt` (default 1800s)
- Expanded `userdata/names.txt` from original to 1200 star system names

## Phase 7: Installer
> [Detailed documentation →](pre-git/07-installer.md)

- Standalone first-time setup page at `/install.php`
- Creates database from `schema.sql`, creates DB user, writes `secrets.inc.php`
- Creates admin account (PlayerID 1 = admin access)
- Resets system names index and populates all 100 sectors
- Configurable cron intervals for mini-turn and income turn
- Option to auto-activate cron or show manual setup instructions
- Strips existing Emissary cron entries before writing new ones (safe for re-install)
- Streams progress output in real time during installation
- Self-disables via `.installed` lock file after completion
- `index.php` redirects to installer when `.installed` is absent

## Phase 8: Image Generation Fixes
> [Detailed documentation →](pre-git/08-image-fixes.md)

- Fixed `galaxyimage.img.php` and `routeimage.img.php`: relative image paths → `__DIR__`-based absolute paths
- Fixed `imagejpeg($image,'',80)` → `imagejpeg($image, null, 80)` (empty string invalid in PHP 8)
- Remaining image files (`planetimage.img.php`, `images/shieldcount.img.php`, `img.php`) identified for future fixes

## Files Added
- `schema.sql` — full database schema with reference data
- `install.php` — first-time installer with streamed progress
- `admin/index.php` — admin panel
- `admin/tools.php` — admin helper functions (moved from root)
- `Dockerfile` — PHP 8.2 + Apache + mysqli + gd + cron
- `entrypoint.sh` — container startup script
- `.env` — Docker environment secrets (gitignored)
- `secrets.inc.php` — DB credentials (gitignored, created by installer)
- `config.inc.php` — game configuration (created by installer)
- `turninterval.txt` — income turn interval in seconds (created by installer)
- `CHANGELOG.md` — this file
- `docs/` — full development documentation (8 files + index)

## Files Gitignored
- `secrets.inc.php`
- `.env`
- `.installed`
