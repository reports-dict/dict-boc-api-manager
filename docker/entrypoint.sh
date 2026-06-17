#!/bin/sh
set -e

LOCKFILE="/var/www/package-lock.json"
HASH_FILE="/var/www/node_modules/.install_hash"

# Only run npm ci if node_modules is missing or package-lock.json changed
CURRENT_HASH=$(md5sum "$LOCKFILE" | awk '{print $1}')

if [ ! -d "/var/www/node_modules" ] || [ ! -f "$HASH_FILE" ] || [ "$(cat "$HASH_FILE")" != "$CURRENT_HASH" ]; then
    echo "[startup] Installing npm dependencies..."
    npm ci --prefix /var/www
    echo "$CURRENT_HASH" > "$HASH_FILE"
fi

echo "[startup] Building frontend assets..."
npm run build --prefix /var/www

echo "[startup] Starting PHP-FPM..."
exec php-fpm
