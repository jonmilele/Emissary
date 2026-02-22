# Emissary — Development Documentation

Technical documentation for the restoration and modernization of Emissary, a PHP space strategy game originally built circa 2004.

## Change Documentation

| Phase | Document | Summary |
|-------|----------|---------|
| 1 | [Database Schema Reconstruction](01-database-schema.md) | Reverse-engineered 17-table MySQL schema from PHP source |
| 2 | [PHP 8.2 Migration](02-php82-migration.md) | `mysql_*` → `mysqli_*`, `mcrypt` → `password_hash`, session fixes, 112 warning fixes |
| 3 | [File Path & Include Fixes](03-file-path-fixes.md) | All relative paths → `__DIR__`, include guards, robust file parsing |
| 4 | [Docker Infrastructure](04-docker-infrastructure.md) | Dockerfile, docker-compose, entrypoint, secrets management |
| 5 | [Admin Panel](05-admin-panel.md) | New `/admin/` with stats, world gen, turn processing, The Burn |
| 6 | [Turn Timer System](06-turn-timer.md) | Rewrote timer functions, fixed cron scripts, configurable intervals |
| 7 | [Installer](07-installer.md) | Web-based first-time setup with streamed progress and cron config |
| 8 | [Image Generation Fixes](08-image-fixes.md) | Fixed GD image paths and `imagejpeg()` PHP 8 compatibility |

## Quick Reference

- **Changelog**: [`CHANGELOG.md`](../CHANGELOG.md)
- **Database schema**: [`html/schema.sql`](../html/schema.sql)
- **Game URL**: http://localhost/
- **Admin panel**: http://localhost/admin/
- **Installer**: http://localhost/install.php
- **phpMyAdmin**: http://localhost:8080

## Project Structure

```
lamp-docker/
├── Dockerfile              # PHP 8.2 + Apache + GD + cron
├── entrypoint.sh           # Starts cron + Apache
├── docker-compose.yml      # Web + MySQL + phpMyAdmin
├── .env                    # MySQL root password (gitignored)
├── CHANGELOG.md            # Change history
├── pre-git/                # Pre-git documentation
│   ├── README.md
│   ├── 01-database-schema.md
│   ├── 02-php82-migration.md
│   ├── 03-file-path-fixes.md
│   ├── 04-docker-infrastructure.md
│   ├── 05-admin-panel.md
│   ├── 06-turn-timer.md
│   ├── 07-installer.md
│   └── 08-image-fixes.md
└── html/                   # Game files (Apache document root)
    ├── install.php         # First-time installer
    ├── index.php           # Login / installer redirect
    ├── schema.sql          # Database schema
    ├── secrets.inc.php     # DB credentials (gitignored)
    ├── config.inc.php      # Game config (created by installer)
    ├── connect.inc.php     # Database connection
    ├── authenticate.inc.php # Session auth
    ├── turnfunctions.inc.php # Turn timer logic
    ├── userfunctions.inc.php # Core game functions
    ├── turn.cron.php       # Income turn cron
    ├── miniturn.cron.php   # Mini-turn cron
    ├── admin/
    │   ├── index.php       # Admin panel
    │   └── tools.php       # Admin helper functions
    ├── images/             # Static assets
    └── userdata/           # Runtime data (names, battles)
```
