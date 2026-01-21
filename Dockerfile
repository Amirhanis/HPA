# Multi-stage build for Laravel 11 + AI Service
# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend-builder

WORKDIR /app
COPY package*.json ./
RUN npm ci --legacy-peer-deps
COPY . .
RUN npm run build

# Stage 2: Main application with PHP + Python AI Service
FROM php:8.2-fpm

# Install system dependencies
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
    unzip \
    gettext-base \
    python3 \
    python3-pip \
    python3-venv \
    build-essential \
 && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built frontend assets from builder stage
COPY --from=frontend-builder /app/public/build ./public/build

# Setup Python virtual environment for AI service
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Install Python dependencies
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r ai_service/requirements.txt

# Pre-download the semantic model to avoid runtime delays
RUN python3 -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('all-MiniLM-L6-v2')" || true

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy configuration files
COPY ./.render/nginx.conf /etc/nginx/nginx.conf.template
COPY ./.render/supervisord.conf /etc/supervisord.conf
COPY ./.render/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000

CMD ["/entrypoint.sh"]
