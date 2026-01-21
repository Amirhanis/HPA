#!/usr/bin/env sh
set -e

echo "Building frontend assets..."
cd /var/www/html

# Try to build, but don't fail if it errors (use pre-built assets)
npm ci --legacy-peer-deps 2>/dev/null || true
NODE_OPTIONS="--max-old-space-size=2048" npm run build 2>/dev/null || echo "Using pre-built assets"
rm -rf node_modules

echo "Bootstrapping Laravel..."

# Ensure writable dirs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/app/public/product_images
mkdir -p /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

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

# Generate AI service .env from Laravel environment
echo "Creating AI service configuration..."
cat > /var/www/html/ai_service/.env <<EOF
DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_USER=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}
DB_NAME=${DB_DATABASE:-hpa}
OPENROUTER_API_KEY=${OPENROUTER_API_KEY:-}
EOF

# Render nginx config
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

exec /usr/bin/supervisord -n -c /etc/supervisord.conf