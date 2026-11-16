#!/bin/sh
set -e

# Use PORT from environment or default to 10000 (Render's default)
APP_PORT="${PORT:-10000}"

# Set the port in Apache configuration
sed -i "s/Listen [0-9]*/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${APP_PORT}>/" /etc/apache2/sites-available/000-default.conf

# Execute Apache in foreground
exec apache2-foreground

