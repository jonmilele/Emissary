# Phase 5: Admin Panel

[← Docker Infrastructure](04-docker-infrastructure.md) | [Back to Index](README.md) | [Next: Turn Timer System →](06-turn-timer.md)

## Overview

A new admin panel was created at `admin/index.php`, accessible at `/admin/`. Access is restricted to PlayerID 1 (the first account created during installation). The admin panel provides game management tools that did not exist in the original game.

## Files

- **`admin/index.php`** — Admin panel UI and action handlers (new file)
- **`admin/tools.php`** — Helper functions for world generation and management (moved from root, heavily modified)

## Access Control

```php
$adminID = GetPlayerIDFromName($username);
if($adminID != 1){
    echo "Access denied.";
    exit;
}
```

Only the player with `PlayerID = 1` can access the panel. All other users see "Access denied."

## Features

### Database Stats Dashboard

Displays live counts for all game entities: players, teams, planets, systems, sectors, ships, fleets, buildings, ships/buildings under construction, battles, auctions.

### Player List

Full table showing all registered players with: PlayerID, Username, Email, Team, Metal, Mineral, Astrium, Credits.

### World Generation

- **Populate Sector** — Generate star systems and planets for a specific sector (1–100)
- **Populate All Empty Sectors** — Fill all sectors that have no systems yet
- **Recalculate Sector Owners** — Recompute the `MajOwner` field for all sectors based on planet ownership

Each sector gets ~10 star systems with 1–2 planets each. System names are drawn sequentially from `userdata/names.txt` (1200 names available).

### Turn Processing

- **Run Mini-Turn** — Executes `miniturn.cron.php` inline (processes construction queues and fleet movement)
- **Run Income Turn** — Executes `turn.cron.php` inline (awards resource income to all players)
- **Reset Turn Timer** — Writes current `time()` to `turntime.txt`, resetting the countdown

### Player Management

- **Reset Password** — Set a new password for any player by PlayerID (uses `password_hash()`)

### The Burn (Galaxy Reset)

Full galaxy wipe and regeneration. Requires double confirmation (confirm dialog + type "BURN").

**What it does (in order):**
1. Deletes all rows from: `planets`, `Systems`, `ships`, `fleets`, `buildings`, `cbuildings`, `cships`, `qships`, `battles`, `auctions`, `gamelog`
2. Deletes all `teams`
3. Resets all players: `Metal=0, Mineral=0, Astrium=0, Credits=0, TeamID=0, SetupStage=0`
4. Resets `userdata/names.txt` counter to 1
5. Repopulates all 100 sectors with fresh star systems
6. Recalculates sector ownership
7. Resets turn timer
8. Clears all Emissary cron entries from the system crontab

**What it preserves:** Player accounts (username, password, email).

### Destructive Tools

- **Clear Sector** — Remove all systems from a specific sector
- **Clear All Planets** — Delete all planets with PlanetID > 6

## `admin/tools.php` Functions

| Function | Purpose |
|----------|---------|
| `PopulateSector($id)` | Generate ~10 systems + planets for a sector |
| `CheckClashes($sector, $x, $y)` | Prevent overlapping system coordinates |
| `CreateCoords($sector)` | Generate non-clashing random coordinates |
| `AddPlanet($system, $name, $orbit)` | Insert a planet into a system |
| `ClearSector($id)` | Delete all systems in a sector |
| `ClearPlanets()` | Delete all planets with ID > 6 |
| `ResetPassword($id, $pass)` | Hash and update a player's password |
| `CalcOwners()` | Recalculate `MajOwner` for all sectors |

## Styling

The admin panel uses the game's existing `style.css` plus inline styles for admin-specific elements: dark theme (#1a1a2e backgrounds), orange (#ff9900) headings, green result messages, red danger sections.
