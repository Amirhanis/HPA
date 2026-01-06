#!/usr/bin/env sh
set -e

: "${PORT:=8080}"

mkdir -p /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Optional: run DB migrations at startup (use with care; best for one-time deploys)
# Enable by setting RUN_MIGRATIONS=true (or 1) in Render env vars.
if [ "${RUN_MIGRATIONS:-}" = "1" ] || [ "${RUN_MIGRATIONS:-}" = "true" ] || [ "${RUN_MIGRATIONS:-}" = "TRUE" ]; then
    echo "RUN_MIGRATIONS enabled: running php artisan migrate --force"
    php /var/www/html/artisan migrate --force
fi

envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

exec /usr/bin/supervisord -n -c /etc/supervisord.conf
