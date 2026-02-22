#!/bin/bash
# Install Emissary cron jobs (these don't persist across container restarts)
echo '* * * * * /usr/local/bin/php /var/www/html/miniturn.cron.php > /dev/null 2>&1
*/30 * * * * /usr/local/bin/php /var/www/html/turn.cron.php > /dev/null 2>&1' | crontab -

# Start cron daemon in background
cron
# Start Apache in foreground (default CMD from php:apache image)
exec apache2-foreground
