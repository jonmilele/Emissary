# Phase 3: File Path & Include Fixes

[← PHP 8.2 Migration](02-php82-migration.md) | [Back to Index](README.md) | [Next: Docker Infrastructure →](04-docker-infrastructure.md)

## Overview

The original code used relative file paths for `include()` statements and `fopen()`/`file()` calls. These work when PHP is invoked from a specific working directory, but break when files are included from different locations (e.g., the installer including `admin/tools.php`, or cron scripts run by the system cron daemon). All paths were converted to `__DIR__`-based absolute paths.

## Include Path Fixes

### Cron Scripts

Both cron scripts included files with bare relative paths that only worked if executed from the `html/` directory.

**`turn.cron.php` (lines 3–4):**
```
# Original
include("connect.inc.php");
include("userfunctions.inc.php");

# Fixed
include(__DIR__ . "/connect.inc.php");
include(__DIR__ . "/userfunctions.inc.php");
```

**`miniturn.cron.php` (lines 3–4):** Same change.

**Shebang also updated:**
```
# Original
#!/usr/local/bin/php -q

# Fixed
#!/usr/bin/env php
```

### Admin Tools (`admin/tools.php`, lines 2–5)

When included from `install.php` (one directory up), the `../` relative paths resolved incorrectly.

```
# Original
include("../connect.inc.php");
include("../userfunctions.inc.php");

# Fixed
include_once(__DIR__ . "/../connect.inc.php");
include_once(__DIR__ . "/../userfunctions.inc.php");

define('GAME_ROOT', realpath(__DIR__ . '/..'));
```

The `GAME_ROOT` constant is used by `PopulateSector()` and other functions to locate `userdata/names.txt` reliably.

### Shared Include Guard (`include_once`)

Several files were included multiple times through different paths, causing "Cannot redeclare" fatal errors. Key change:

**`userfunctions.inc.php` (line 6):**
```
# Original
include("turnfunctions.inc.php");

# Fixed
include_once("turnfunctions.inc.php");
```

**`admin/index.php` (lines 78, 84):**
```
# Original
include("../turnfunctions.inc.php");

# Fixed
include_once("../turnfunctions.inc.php");
```

**Total `include_once` usage:** 8 instances across the codebase.

## File Operation Path Fixes

### Turn Timer (`turnfunctions.inc.php`)

Completely rewritten. All file operations use `__DIR__`:

```php
function GetTurnInterval(){
    $file = __DIR__ . "/turninterval.txt";    // was: no such function existed
    ...
}
function MinutesToNextTurn(){
    $file = __DIR__ . "/turntime.txt";        // was: "turntime.txt" (relative)
    ...
}
function ResetTurnTimer(){
    $file = __DIR__ . "/turntime.txt";        // was: "turntime.txt" (relative)
    ...
}
```

### Turn Cron (`turn.cron.php`, line 6)

```
# Original
$fp = fopen("turntime.txt","w");

# Fixed
$fp = fopen(__DIR__ . "/turntime.txt","w");
```

### Battle File I/O

**`battle.php` (lines 32–33):**
```
# Original
$fp = fopen("userdata/battles/".$BattleID.".txt","r");
echo fread($fp,filesize("userdata/battles/".$BattleID.".txt"));

# Fixed
$fp = fopen(__DIR__ . "/userdata/battles/".$BattleID.".txt","r");
echo fread($fp,filesize(__DIR__ . "/userdata/battles/".$BattleID.".txt"));
```

**`fleetfunctions.inc.php` (line 986):**
```
# Original
$fp = fopen("userdata/battles/".$id.".txt","w");

# Fixed
$fp = fopen(__DIR__ . "/userdata/battles/".$id.".txt","w");
```

**`write.php` (line 3):**
```
# Original
$fp = fopen("userdata/battles/4.txt","w");

# Fixed
$fp = fopen(__DIR__ . "/userdata/battles/4.txt","w");
```

### Known Systems (`userfunctions.inc.php`, line 318)

```
# Original
$sys = @file("userdata/knownsystems/".GetPlayerIDFromName($username).".txt");

# Fixed
$sys = @file(__DIR__ . "/userdata/knownsystems/".GetPlayerIDFromName($username).".txt");
```

### Galaxy Population (`admin/tools.php`)

Names file access changed from relative `../userdata/names.txt` to `GAME_ROOT` constant:

```
# Original
$sys = @file("../userdata/names.txt");
...
$fp = fopen("../userdata/names.txt","w");

# Fixed
$namesFile = GAME_ROOT . "/userdata/names.txt";
$sys = @file($namesFile);
...
file_put_contents($namesFile, implode('', $sys));
```

Also hardened the names.txt parser:
```
# Original — fragile, crashes on empty/missing file
$start = $sys[0];
$start = substr($start,0,strlen($start)-1);

# Fixed — robust with trim() and bounds checking
$start = (int)trim($sys[0]);
...
if(!isset($sys[$i]) || $sys[$i] == ""){ break; }
...
$syst = trim($sys[$i]);
```

## Summary of `__DIR__` additions

**32 total `__DIR__` references** added across: `turnfunctions.inc.php`, `turn.cron.php`, `miniturn.cron.php`, `battle.php`, `write.php`, `fleetfunctions.inc.php`, `userfunctions.inc.php`, `admin/tools.php`, `galaxyimage.img.php`, `routeimage.img.php`, `install.php`, `admin/index.php`.
