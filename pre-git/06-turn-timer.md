# Phase 6: Turn Timer System

[← Admin Panel](05-admin-panel.md) | [Back to Index](README.md) | [Next: Installer →](07-installer.md)

## Overview

The game uses a cron-based turn system with two cycles:
- **Mini-turn** (default: every 1 minute) — processes construction queues, fleet movement, and combat
- **Income turn** (default: every 30 minutes) — awards resource income from planets to all players

The original turn timer was broken because `turntime.txt` contained a timestamp from August 2004, causing the "Next turn in: -11308808 minutes" display on every page.

## `turnfunctions.inc.php` (Complete Rewrite)

### Original Code (broken)

```php
function MinutesToNextTurn(){
    $turn = 1800;   // hardcoded 30 min
    $fp = fopen("turntime.txt","r");
    $ts = fread($fp, filesize("turntime.txt"));
    fclose($fp);
    $diff = ($ts + $turn) - time();
    return ceil($diff / 60);
    // Returns -11308808 with a 2004 timestamp
}
```

### New Code

```php
function GetTurnInterval(){
    $file = __DIR__ . "/turninterval.txt";
    if(file_exists($file)){
        $val = (int)trim(file_get_contents($file));
        if($val > 0) return $val;
    }
    return 1800; // default 30 min
}

function MinutesToNextTurn(){
    $turn = GetTurnInterval();
    $file = __DIR__ . "/turntime.txt";
    if(!file_exists($file)){
        ResetTurnTimer();
    }
    $ts = (int)file_get_contents($file);
    if($ts == 0){
        ResetTurnTimer();
        $ts = time();
    }
    $next = $ts + $turn;
    $diff = $next - time();
    if($diff < 0) return 0;    // Never return negative
    return ceil($diff / 60);
}

function ResetTurnTimer(){
    $file = __DIR__ . "/turntime.txt";
    file_put_contents($file, time());
}
```

### Key Improvements

- **Configurable interval** — reads from `turninterval.txt` instead of hardcoded 1800s
- **Graceful fallback** — returns 0 (not negative) if timer is stale or missing
- **Auto-recovery** — creates `turntime.txt` if missing, resets if corrupt
- **Absolute paths** — `__DIR__` instead of relative paths

## Cron Script Fixes

### `turn.cron.php` (Income Turn)

```
# Original shebang + includes
#!/usr/local/bin/php -q
include("connect.inc.php");
include("userfunctions.inc.php");
$fp = fopen("turntime.txt","w");

# Fixed
#!/usr/bin/env php
include(__DIR__ . "/connect.inc.php");
include(__DIR__ . "/userfunctions.inc.php");
$fp = fopen(__DIR__ . "/turntime.txt","w");
```

Processes income by iterating all players and calling `AddResources()` for Metal, Mineral, and Astrium based on `GetUserIncome()`.

### `miniturn.cron.php` (Construction/Movement Turn)

Same include path fixes. Contains functions:
- `DropFleetTTF($FleetID)` — decrements fleet travel time, triggers attack/colonize on arrival
- `DropShipTTF($ShipID)` — decrements ship construction time, creates ship on completion
- `DropBuildingTTF($BuildingID)` — decrements building construction time, creates building on completion
- `ResumePausedQueues()` — restarts queued ship production when shipyard becomes available
- `ProcessShipConstruction()`, `ProcessBuildingConstruction()`, `ProcessFleetMovements()` — iterate active queues

## Supporting Files

| File | Purpose | Created By |
|------|---------|------------|
| `turntime.txt` | Unix timestamp of last income turn | Cron / installer / admin |
| `turninterval.txt` | Income turn interval in seconds | Installer |
| `config.inc.php` | Cron interval settings for admin reference | Installer |

## System Names (`userdata/names.txt`)

Used by `PopulateSector()` to name star systems. Format:
```
1          ← current index (line 0)
Falax      ← name at index 1
Smorgasbord
Zenith
...        ← 1200 names total
```

The counter on line 0 advances by ~10 per sector populated. Reset to 1 by the installer and The Burn.
