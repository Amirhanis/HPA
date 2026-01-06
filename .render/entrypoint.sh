#!/usr/bin/env sh
set -e

echo "Booting Laravel..."

# Create ALL required runtime directories
mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Fix permissions
chmod -R ug+rwx storage bootstrap/cache || true

# Clear stale caches (safe every boot)
php artisan optimize:clear || true

# Run migrations if enabled
if [ "${RUN_MIGRATIONS:-}" = "1" ] || [ "${RUN_MIGRATIONS:-}" = "true" ]; then
  php artisan migrate --force || true
fi

# Rebuild config cache from Render env
php artisan config:cache || true

# Render nginx config
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

exec /usr/bin/supervisord -n -c /etc/supervisord.conf
