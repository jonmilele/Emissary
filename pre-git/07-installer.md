# Phase 7: Installer

[← Turn Timer](06-turn-timer.md) | [Back to Index](README.md) | [Next: Image Fixes →](08-image-fixes.md)

## Overview

A standalone web installer at `/install.php` handles first-time game setup. It replaces the manual process of importing SQL, creating users, and configuring cron. The installer streams real-time progress to the browser.

## Auto-Redirect

`index.php` (line 2) redirects to the installer when the game hasn't been set up:

```php
// Redirect to installer if not yet installed
if(!file_exists(__DIR__ . "/.installed")){ header("Location: install.php"); exit; }
```

## Setup Form

The installer presents a form with three sections:

### Database Configuration
- **MySQL Host** — defaults to `db` (Docker service name)
- **MySQL Root Password** — needed to create the database and app user

### Admin Account
- **Username** — becomes PlayerID 1 (admin access)
- **Password** — minimum 4 characters, stored as bcrypt hash
- **Email** — optional

### Turn Processing (Cron)
- **Mini-turn interval** — minutes between construction/movement ticks (default: 1, range: 1–60)
- **Income turn interval** — minutes between resource income awards (default: 30, range: 1–1440)
- **Auto-activate checkbox** — when checked, writes crontab entries directly; when unchecked, shows manual cron instructions

## Installation Steps (Streamed Progress)

When submitted, the installer executes these steps with real-time output:

1. **Connect to MySQL** as root
2. **Import `schema.sql`** — creates `emissary` database with all 17 tables and reference data via `mysqli_multi_query()`
3. **Create database user** — `emissary@%` with full privileges on the `emissary` database
4. **Verify app connection** — connects as the app user to confirm grants work
5. **Write `secrets.inc.php`** — database credentials file
6. **Create admin account** — INSERT into `players` with `password_hash()`, checks for existing user first
7. **Load game engine** — `include_once()` for `userfunctions.inc.php`, `admin/tools.php`, `turnfunctions.inc.php`
8. **Reset names index** — sets line 0 of `userdata/names.txt` back to `1`
9. **Populate galaxy** — calls `PopulateSector()` for all 100 sectors, reporting progress every 10 sectors with system/planet counts
10. **Initialize turn timer** — writes income interval to `turninterval.txt`, calls `ResetTurnTimer()`
11. **Configure cron** — if auto-activate is checked:
    - Reads existing crontab (`crontab -l`)
    - Strips any existing Emissary entries (matching `miniturn.cron.php` or `turn.cron.php`)
    - Appends new entries with configured intervals
    - Installs via `crontab <tmpfile>`
12. **Save config** — writes `config.inc.php` with interval values
13. **Create lock file** — writes `.installed` with timestamp and admin username

## Cron Deduplication

The installer safely handles re-runs by stripping old entries before writing new ones:

```php
$existing = shell_exec("crontab -l 2>/dev/null") ?: "";
$filtered = preg_replace('/# Emissary turn processing\n/', '', $existing);
$filtered = preg_replace('/.*(?:miniturn|turn)\.cron\.php.*\n/', '', $filtered);
$newCrontab = rtrim($filtered) . "\n" . $cronLines;
```

This means re-running the installer (after deleting `.installed`) won't create duplicate cron entries.

## Lock File

After successful installation, `.installed` is created:
```
2026-02-22 10:30:00 - Installed by adminuser
```

When present:
- `install.php` shows "Installation already completed" and refuses to run
- `index.php` skips the redirect and shows the normal login page

To re-run the installer: delete `.installed`, optionally drop the database.

## Error Handling

- Pre-flight validation catches empty username/password before starting
- Each step checks for failure and sets `$failed = true` to skip remaining steps
- Errors display in red, warnings in orange, success in green
- Failed installs show a "Back to Setup" button
- Successful installs show cron instructions (if manual mode) and an "Enter Emissary" link

## Files Created by Installer

| File | Purpose | Gitignored |
|------|---------|------------|
| `secrets.inc.php` | DB credentials | Yes |
| `turntime.txt` | Current turn timestamp | No |
| `turninterval.txt` | Income interval (seconds) | No |
| `config.inc.php` | Cron interval settings | No |
| `.installed` | Installation lock | Yes |
