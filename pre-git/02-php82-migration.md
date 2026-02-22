# Phase 2: PHP 8.2 Migration

[← Database Schema](01-database-schema.md) | [Back to Index](README.md) | [Next: File Path Fixes →](03-file-path-fixes.md)

## Overview

Every PHP source file (~55 files) was updated from PHP 4/5 era APIs to PHP 8.2 compatibility. The original code was written circa 2004 and used APIs that have been removed from modern PHP.

## MySQL API Migration

**All files containing database calls** — replaced `mysql_*` functions with `mysqli_*` equivalents.

### Connection Pattern

The original code used a global `$conn` variable. The migration preserves this via `$GLOBALS["conn"]`.

```
# Original (PHP 4)
$conn = mysql_connect($host, $user, $pass);
mysql_select_db($database);
$result = mysql_query($query);
$row = mysql_fetch_object($result);
$num = mysql_num_rows($result);
$id = mysql_insert_id();
mysql_error()

# Migrated (PHP 8.2)
$conn = mysqli_connect($host, $user, $pass, $database);
$result = mysqli_query($GLOBALS["conn"], $query);
$row = mysqli_fetch_object($result);
$num = mysqli_num_rows($result);
$id = mysqli_insert_id($GLOBALS["conn"]);
mysqli_error($GLOBALS["conn"])
```

**451 total `mysqli_query`/`$GLOBALS["conn"]` references** across the codebase after migration.

### Files with database calls (non-exhaustive)

`userfunctions.inc.php`, `planetfunctions.inc.php`, `fleetfunctions.inc.php`, `buildingfunctions.inc.php`, `resourcefunctions.inc.php`, `setupfunctions.inc.php`, `logfunctions.inc.php`, `turn.cron.php`, `miniturn.cron.php`, `login.back.php`, `signup.back.php`, `home.php`, `planet.php`, `sector.php`, `system.php`, `fleet.php`, `building.php`, `battle.php`, `auction.php`, `trade.php`, `team.php`, `teams.php`, `player.php`, `galaxyimage.img.php`, `sectorimage.img.php`, `planetimage.img.php`, `routeimage.img.php`, and others.

## String Function Migration

### `split()` → `explode()`

`split()` was removed in PHP 7.0. All 21 occurrences replaced:

```
# Original
$coords = split("/", $System->Coords);

# Migrated
$coords = explode("/", $System->Coords);
```

**Files affected:** `userfunctions.inc.php`, `fleetfunctions.inc.php`, `planetfunctions.inc.php`, `admin/tools.php`, `routeimage.img.php`, `galaxyimage.img.php`, and others.

## Encryption → Hashing Migration

### `mcrypt_encrypt` → `password_hash()`/`password_verify()`

The original game used `mcrypt_encrypt()` for password storage (symmetric encryption, insecure). Replaced with modern bcrypt hashing.

```
# Original (signup.back.php)
$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $pass1, MCRYPT_MODE_ECB);

# Migrated (signup.back.php, line 22)
$crypttext = password_hash($pass1, PASSWORD_DEFAULT);
```

```
# Original (login.back.php)
$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $password, MCRYPT_MODE_ECB);
if($crypttext == $result->Password)

# Migrated (login.back.php, line 14)
if($result && password_verify($password, $result->Password))
```

**Files changed:**
- `signup.back.php` — password hashing on registration
- `login.back.php` — password verification on login
- `admin/tools.php` (`ResetPassword()`) — admin password reset

> **Note:** Existing passwords from the original database are incompatible. All users need password resets after migration.

## Session Handling

### `session_register()` removal

`session_register()` was removed in PHP 5.4. Replaced with direct `$_SESSION` assignments.

```
# Original (login.back.php)
session_register('username');
$username = $user_name;

# Migrated (login.back.php)
$_SESSION['username'] = $user_name;
```

### Authentication fix (`authenticate.inc.php`)

Added explicit session variable extraction since `session_register()` no longer auto-globalizes variables:

```php
<?php
session_start();
if(empty($_SESSION['username'])) {
    header("Location: index.php?msg=Not+Logged+In");
    exit;
}
$username = $_SESSION['username'];
?>
```

The `$username = $_SESSION['username']` line is critical — nearly every game file uses `$username` as a global after including `authenticate.inc.php`.

## Undefined Array Key Warnings

PHP 8.2 emits warnings for accessing undefined array keys. Applied `?? ""` null coalescing fallbacks to **112 occurrences** across all files accessing `$_GET` or `$_POST`.

```
# Original
$action = $_GET['action'];
$id = $_POST['sector_id'];

# Migrated
$action = ($_GET['action'] ?? "");
$id = ($_POST['sector_id'] ?? "");
```

Applied via bulk Perl one-liner:
```
perl -pi -e "s/\\\$_GET\['([^']+)'\]/(\\\$_GET['\1'] ?? \"\")/g; s/\\\$_POST\['([^']+)'\]/(\\\$_POST['\1'] ?? \"\")/g;" *.php
```

## Reserved Word Quoting

`System` is a reserved word in MySQL 8. The `planets` table has a column named `System` that required backtick-quoting in INSERT statements.

```
# Original (admin/tools.php)
INSERT INTO planets(Name,Orbit,System,Size) VALUES(...)

# Migrated (admin/tools.php, line 38)
INSERT INTO planets(Name,Orbit,`System`,Size) VALUES(...)
```

## Line Ending Fixes

Two files had Mac Classic line endings (`\r` only) that caused PHP parsing issues:
- `planetfunctions.inc.php`
- `turn.cron.php`

Fixed with: `sed -i 's/\r$//' <file>` and `tr '\r' '\n'`
