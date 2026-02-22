#!/bin/bash
# Emissary - Clean Wipe
# Resets everything to a fresh state for testing the installer.
# Run from the host (not inside the container).

set -o pipefail

read -sp "MySQL root password: " MYSQL_ROOT_PASSWORD
echo ""

COMPOSE_FILE="/root/lamp-docker/docker-compose.yml"
HTML_DIR="/root/lamp-docker/html"

echo "=== Emissary Clean Wipe ==="
echo ""

# 1. Drop database and user
echo "[1/5] Dropping database and user..."
docker exec lamp-docker-db-1 mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
  DROP DATABASE IF EXISTS emissary;
  DROP USER IF EXISTS 'emissary'@'%';
  FLUSH PRIVILEGES;
" 2>/dev/null
if [ $? -eq 0 ]; then
  echo "  ✓ Database 'emissary' dropped"
  echo "  ✓ User 'emissary' dropped"
else
  echo "  ✗ Failed (is the db container running?)"
  exit 1
fi

# 2. Remove installer-generated files
echo "[2/5] Removing generated files..."
rm -f "$HTML_DIR/.installed"
rm -f "$HTML_DIR/secrets.inc.php"
rm -f "$HTML_DIR/turntime.txt"
rm -f "$HTML_DIR/turninterval.txt"
rm -f "$HTML_DIR/config.inc.php"
echo "  ✓ .installed, secrets.inc.php, turntime.txt, turninterval.txt, config.inc.php removed"

# 3. Reset system names counter
echo "[3/5] Resetting system names index..."
NAMES_FILE="$HTML_DIR/userdata/names.txt"
if [ -f "$NAMES_FILE" ]; then
  sed -i '1s/.*$/1/' "$NAMES_FILE"
  echo "  ✓ names.txt counter reset to 1"
else
  echo "  ✗ names.txt not found"
fi

# 4. Clear crontab inside container
echo "[4/5] Clearing cron jobs..."
docker exec lamp-docker-web-1 crontab -r 2>/dev/null
echo "  ✓ Crontab cleared"

# 5. Verify
echo "[5/5] Verifying..."
ERRORS=0
[ -f "$HTML_DIR/.installed" ] && echo "  ✗ .installed still exists" && ERRORS=$((ERRORS+1))
[ -f "$HTML_DIR/secrets.inc.php" ] && echo "  ✗ secrets.inc.php still exists" && ERRORS=$((ERRORS+1))
docker exec lamp-docker-db-1 mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "USE emissary" 2>/dev/null && echo "  ✗ Database still exists" && ERRORS=$((ERRORS+1))

if [ $ERRORS -eq 0 ]; then
  echo "  ✓ All clean"
  echo ""
  echo "=== Wipe complete. Visit http://localhost/ to run the installer. ==="
else
  echo ""
  echo "=== Wipe completed with $ERRORS warning(s) ==="
fi
