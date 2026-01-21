#!/usr/bin/env sh
set -e

# Ensure PORT is set
export PORT="${PORT:-10000}"

echo "Bootstrapping Laravel..."

# Ensure writable dirs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/app/public/product_images
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Create log file if it doesn't exist
touch /var/www/html/storage/logs/laravel.log

echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Storage link
if [ -e /var/www/html/public/storage ] && [ ! -L /var/www/html/public/storage ]; then
  rm -rf /var/www/html/public/storage
fi

if [ ! -L /var/www/html/public/storage ]; then
  php artisan storage:link || true
fi

# Clear caches
php artisan optimize:clear || true

# Run migrations if enabled
if [ "${RUN_MIGRATIONS:-}" = "1" ] || [ "${RUN_MIGRATIONS:-}" = "true" ]; then
  echo "RUN_MIGRATIONS enabled: running migrations"
  php artisan migrate --force || true
fi

# Cache config
php artisan config:cache || true

# Limit PHP-FPM workers to save memory on Render Free tier (512MB)
if [ -f /usr/local/etc/php-fpm.d/www.conf ]; then
  echo "Optimizing PHP-FPM for low memory..."
  sed -i 's/pm.max_children = 5/pm.max_children = 2/g' /usr/local/etc/php-fpm.d/www.conf
  sed -i 's/pm.start_servers = 2/pm.start_servers = 1/g' /usr/local/etc/php-fpm.d/www.conf
  sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 1/g' /usr/local/etc/php-fpm.d/www.conf
  sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 1/g' /usr/local/etc/php-fpm.d/www.conf
fi

# Ensure AI Service URL is set for Laravel
export AI_SERVICE_URL="http://127.0.0.1:8001"

# Render nginx config
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

exec /usr/bin/supervisord -n -c /etc/supervisord.conf