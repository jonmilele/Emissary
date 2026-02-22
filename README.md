# Emissary

A browser-based space strategy game originally developed in 2004, now restored and modernized to run on a Docker LAMP stack with PHP 8.2 and MySQL 8.

## About the Game

Emissary is a multiplayer browser-based space strategy game where players compete for control of a galaxy divided into 100 sectors. Players colonize planets, build structures, construct fleets, form teams, and wage war across star systems.
This should be familiar with anyone (thinking millenials mainly!) who played DOS games like Ascendancy or browser-based games like Planetarion back in the day. It was Planetarion that I was originally trying to recreate with this project.

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
- **Backend**: PHP 8.2 on Apache
- **Database**: MySQL 8.1
- **Infrastructure**: Docker Compose (web + database + phpMyAdmin)
- **Turn System**: Cron-based with configurable mini-turn and income turn intervals

## Repository Contents

### `archive/` — Original 2004 Source Code

The [`archive/`](archive/) folder contains the original unmodified PHP source code as recovered from an old hard drive. This is the raw 2004-era code — it will not run on any modern PHP version. It is preserved here as a historical reference and is not intended to be executed or modified.

### `html/` — Modernized Game Code

The [`html/`](html/) directory contains the working, modernized version of the game, updated to run on PHP 8.2 and MySQL 8. This is the code that the Docker stack serves.

### `pre-git/` — Pre-Git Change Documentation

The [`pre-git/`](pre-git/) directory contains detailed technical documentation of every code change made to bring the original 2004 source to its current state, organized by phase:

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

**This project is in a pre-beta state.** The original 2004 PHP source code has been partially modernized to run on PHP 8.2 / MySQL 8, but not all changes have been fully implemented or tested prior to this first git commit. Some game features may be broken or incomplete.

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

### What still needs work

- Some image generators still use relative paths (`planetimage.img.php`, `shieldcount.img.php`)
- Game balance values (building/ship stats) are estimates — originals were not recoverable
- Battle system and fleet mechanics have not been fully play-tested
- No input sanitization beyond basic escaping (SQL injection surface exists)
- No HTTPS or modern security hardening
- UI is original 2004 HTML — no responsive design or modernization

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
├── Dockerfile              # PHP 8.2 + Apache + GD + cron
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
    ├── schema.sql          # Database schema (17 tables + reference data)
    ├── admin/              # Admin panel
    ├── images/             # Game artwork and static assets
    └── userdata/           # Runtime data (system names, battle logs)
```

## Admin Panel

The admin panel (accessible to PlayerID 1 at `/admin/`) provides:
- Database stats and player management
- Galaxy population and sector ownership tools
- Manual turn processing (mini-turn and income turn)
- **The Burn** — full galaxy reset that wipes all game data while preserving player accounts

## License

This project is licensed under the [MIT License](LICENSE). This applies to both the original 2004 source code in `archive/` and all modernized code in this repository.
