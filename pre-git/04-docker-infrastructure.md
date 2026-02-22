# Phase 4: Docker Infrastructure

[← File Path Fixes](03-file-path-fixes.md) | [Back to Index](README.md) | [Next: Admin Panel →](05-admin-panel.md)

## Overview

The game runs in a Docker LAMP stack. The original hosting environment was a shared PHP 4 host circa 2004. The Docker setup provides PHP 8.2, Apache, MySQL 8.1, and phpMyAdmin.

## Files Created

### `Dockerfile`

```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install mysqli
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]
```

Extensions installed:
- **mysqli** — database connectivity (replacing the removed `mysql` extension)
- **gd** (with freetype + jpeg) — dynamic image generation (galaxy map, sector maps, planet views)
- **cron** — in-container turn processing scheduler

### `entrypoint.sh`

```bash
#!/bin/bash
cron            # Start cron daemon in background
exec apache2-foreground  # Start Apache as PID 1
```

This ensures both cron and Apache run inside the same container.

### `docker-compose.yml`

```yaml
services:
  web:
    build: .
    ports: ["80:80"]
    depends_on: [db]
    volumes: [./html:/var/www/html]
  db:
    image: mysql:8.1.0
    env_file: [.env]
    volumes: [./mysql_data:/var/lib/mysql]
  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports: ["8080:80"]
    depends_on: [db]
    environment:
      PMA_HOST: db
```

Key changes from original:
- `build: .` instead of a stock image (needed for extensions)
- `env_file: .env` for secrets management
- MySQL data persisted to `./mysql_data/`

### `.env` (gitignored)

```
MYSQL_ROOT_PASSWORD=<root_password>
```

### `secrets.inc.php` (gitignored, created by installer)

```php
<?php
$hostname_conn = "db";
$database_conn = "emissary";
$username_conn = "emissary";
$password_conn = "bumpy5";
?>
```

### `connect.inc.php` (modified)

```php
<?php
require("secrets.inc.php");
$conn = mysqli_connect($hostname_conn, $username_conn, $password_conn, $database_conn)
    or die(mysqli_connect_error());
?>
```

Originally had hardcoded credentials inline. Now loads from the gitignored secrets file.

## Permissions

The `html/` and `userdata/` directories are set to `777` on the host. This is necessary because the Docker bind-mount (`./html:/var/www/html`) preserves host file ownership, but Apache runs as `www-data` inside the container and needs write access for:
- `secrets.inc.php` (created by installer)
- `turntime.txt` (updated by cron)
- `turninterval.txt` (created by installer)
- `config.inc.php` (created by installer)
- `.installed` (lock file)
- `userdata/names.txt` (updated during galaxy population)

## Access Points

| Service | URL | Purpose |
|---------|-----|---------|
| Game | http://localhost/ | Main game interface |
| Admin | http://localhost/admin/ | Admin panel (PlayerID 1) |
| Installer | http://localhost/install.php | First-time setup |
| phpMyAdmin | http://localhost:8080 | Database management |

## .gitignore

```
secrets.inc.php
.env
.installed
```
