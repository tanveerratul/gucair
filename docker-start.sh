#!/bin/sh
set -e

# Render supplies PORT (for example 10000). Local Docker uses 80.
PORT="${PORT:-80}"

# The PHP Apache image listens on 80 by default. Reconfigure it at startup.
sed -i "s/^Listen [0-9]*$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s|<VirtualHost \*:80>|<VirtualHost \*:${PORT}>|" /etc/apache2/sites-available/000-default.conf

echo "GUCAIR Apache starting on port ${PORT}"
exec apache2-foreground
