# Stage 1: Build PHP dependencies
FROM composer:2.7 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
# Instalamos con --no-dev y optimizamos el autoloader para producción
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

# Stage 2: Build assets
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
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
    netcat-openbsd

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
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache public/build
RUN chmod -R 777 storage bootstrap/cache

# Nginx config
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Entrypoint script
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Export port
EXPOSE 80

# Start entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
