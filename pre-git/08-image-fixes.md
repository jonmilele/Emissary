# Phase 8: Image Generation Fixes

[← Installer](07-installer.md) | [Back to Index](README.md)

## Overview

The game dynamically generates images using PHP's GD library for the galaxy map, sector views, planet surfaces, route overlays, and UI elements. Two issues prevented these from rendering in PHP 8.2.

## Issue 1: Relative Image Paths

Image source files were loaded with relative paths, which break when the PHP working directory doesn't match expectations.

### `galaxyimage.img.php` (line 8)

```
# Original
$image = imagecreatefromjpeg('images/galaxy.jpg') or die("booboo");

# Fixed
$image = imagecreatefromjpeg(__DIR__ . '/images/galaxy.jpg') or die("booboo");
```

### `routeimage.img.php` (line 76)

Same change — also loads `images/galaxy.jpg` as the base layer for route visualization.

### Remaining (not yet fixed)

These files still use relative paths but work in the current setup because they're always accessed directly via browser (Apache sets CWD to `html/`):

- `planetimage.img.php:10` — `imagecreatefromjpeg("images/planets/".$Planet->Size.".jpg")`
- `images/shieldcount.img.php:7` — `imagecreatefromjpeg('shield.jpg')`
- `img.php:7` — `imagecreatefromjpeg("images/planets/1.jpg")`

These should be converted to `__DIR__` paths if they cause issues.

## Issue 2: `imagejpeg()` Empty String Parameter

In PHP 8, passing an empty string as the second argument to `imagejpeg()` is deprecated/invalid. The second parameter controls the output destination — `null` means "output to browser", while `''` attempts to write to a file with an empty name.

### `galaxyimage.img.php` (line 43)

```
# Original
imagejpeg($image,'',80);

# Fixed
imagejpeg($image, null, 80);
```

### `routeimage.img.php` (line 115)

Same change.

### Remaining (not yet fixed)

- `images/shieldcount.img.php:21` — `imagejpeg($image,'',80)` — needs same fix

## All Image Generation Files

| File | Generates | Base Image | Status |
|------|-----------|------------|--------|
| `galaxyimage.img.php` | 10×10 galaxy grid with team colors | `images/galaxy.jpg` | Fixed |
| `routeimage.img.php` | Galaxy map with fleet route overlay | `images/galaxy.jpg` | Fixed |
| `sectorimage.img.php` | Star system positions within a sector | Dynamic (no base) | OK |
| `planetimage.img.php` | Planet surface with building grid | `images/planets/*.jpg` | Unfixed path |
| `teamcolour.img.php` | Team color swatch | Dynamic (no base) | OK |
| `images/shieldcount.img.php` | Shield strength indicator | `images/shield.jpg` | Unfixed path + `imagejpeg` |
| `img.php` | Test/debug planet render | `images/planets/1.jpg` | Unfixed path |
