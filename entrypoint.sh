#!/bin/bash
# Start cron daemon in background
cron
# Start Apache in foreground (default CMD from php:apache image)
exec apache2-foreground
