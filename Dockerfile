# Stage 1: Build PHP dependencies
FROM composer:2.7 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
# Instalamos con --ignore-platform-reqs para que no bloquee PHP 8.4
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

# Stage 2: Build assets
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# IMPORTANTE: Copiamos vendor aquí para que Vite (Ziggy) pueda encontrar sus rutas
COPY --from=composer-builder /app/vendor ./vendor
RUN npm run build

# Stage 3: PHP Application
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    icu-dev \
    linux-headers \
    git \
    unzip \
    nginx \
    supervisor \
    netcat-openbsd \
    mysql-client

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql gd zip intl opcache bcmath pcntl posix

# Install Redis via PECL
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy vendor from composer-builder
COPY --from=composer-builder /app/vendor ./vendor

# Copy built assets from assets-builder
COPY --from=assets-builder /app/public/build ./public/build

# Permissions
RUN mkdir -p storage/logs && \
    mkdir -p storage/framework/cache && \
    mkdir -p storage/framework/sessions && \
    mkdir -p storage/framework/views && \
    mkdir -p bootstrap/cache && \
    mkdir -p /etc/supervisor/conf.d

RUN chown -R www-data:www-data storage bootstrap/cache public/build
RUN find storage bootstrap/cache -type d -exec chmod 775 {} \; \
    && find storage bootstrap/cache -type f -exec chmod 664 {} \;

# Nginx config
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# PHP-FPM: pass environment variables to worker processes (needed for Railway runtime env)
RUN echo '[www]' > /usr/local/etc/php-fpm.d/zz-docker-env.conf && \
    echo 'clear_env = no' >> /usr/local/etc/php-fpm.d/zz-docker-env.conf

# Supervisor config
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Entrypoint script
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Export port
EXPOSE 80

# Start entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
