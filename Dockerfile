# Render Docker deploy for Laravel 11 + AI Service (FastAPI)
# Nginx (8080) -> PHP-FPM
# AI Service -> Port 8001

FROM php:8.2-fpm AS base

# System dependencies (Debian based)
RUN apt-get update && apt-get install -y \
    bash \
    curl \
    git \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libpq-dev \
    mariadb-client \
    nginx \
    supervisor \
    nodejs \
    npm \
    unzip \
    gettext-base \
    python3 \
    python3-pip \
    python3-venv \
    build-essential \
    libgomp1 \
 && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install \
    intl \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source
COPY . .

# AI Service Setup (Virtual Environment)
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"
RUN pip install --no-cache-dir --upgrade pip \
 && pip install --no-cache-dir -r ai_service/requirements.txt \
 # Pre-download Semantic Model to ensure local availability and faster startup
 && python3 -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('all-MiniLM-L6-v2')"

# Install PHP dependencies (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend assets (Vite -> public/build)
RUN npm ci \
 && npm run build \
 && rm -rf node_modules

# Nginx + Supervisor configs
# Copy configuration files
COPY ./.render/nginx.conf /etc/nginx/nginx.conf.template
COPY ./.render/supervisord.conf /etc/supervisord.conf
COPY ./.render/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Laravel permissions
RUN mkdir -p /var/www/html/storage/logs && \
    touch /var/www/html/storage/logs/laravel.log && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

ENV PORT 10000
EXPOSE 10000

CMD ["/entrypoint.sh"]
