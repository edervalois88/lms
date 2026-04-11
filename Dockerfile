# Stage 1: Build Assets
FROM node:20-alpine as assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: PHP Application
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    git \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql gd zip intl opcache bcmath mbstring pcntl posix

# Working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .
COPY --from=assets-builder /app/public/build ./public/build

# Install PHP dependencies with platform ignorance for minor version mismatches
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Permissions
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache public/build

# Nginx config
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Entrypoint script
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Env cleanup for production
RUN php artisan optimize

# Export port
EXPOSE 80

# Start entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
