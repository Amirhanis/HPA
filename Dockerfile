# Render Docker deploy for Laravel 11 + Vite/Inertia
# Nginx (8080) -> PHP-FPM

FROM php:8.2-fpm-alpine AS base

# System dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    nginx \
    supervisor \
    nodejs \
    npm \
    unzip

# PHP extensions
RUN docker-php-ext-install \
    intl \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source
COPY . .

# Install PHP dependencies (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend assets (Vite -> public/build)
RUN npm ci \
 && npm run build \
 && rm -rf node_modules

# Nginx + Supervisor configs
RUN mkdir -p /run/nginx /var/log/supervisor
COPY ./.render/nginx.conf /etc/nginx/nginx.conf
COPY ./.render/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Laravel permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080
CMD ["/usr/bin/supervisord", "-n"]
