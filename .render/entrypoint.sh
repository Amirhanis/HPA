#!/usr/bin/env sh
set -e

echo "Bootstrapping Laravel..."

# Ensure writable dirs (avoid bash-only brace expansion)
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

chmod -R ug+rwx storage bootstrap/cache || true

# Clear stale caches (safe on every boot)
php artisan optimize:clear || true

# Run migrations only if enabled
if [ "${RUN_MIGRATIONS:-}" = "1" ] || [ "${RUN_MIGRATIONS:-}" = "true" ]; then
  echo "RUN_MIGRATIONS enabled: running migrations"
  php artisan migrate --force || true
fi

# Rebuild config cache from Render env vars
php artisan config:cache || true

# Render nginx config
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

exec /usr/bin/supervisord -n -c /etc/supervisord.conf