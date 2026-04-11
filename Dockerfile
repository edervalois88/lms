# Stage 1: Build Assets
FROM node:20-alpine as assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: PHP Application
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    git \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql gd zip intl opcache bcmath mbstring

# Working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .
COPY --from=assets-builder /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache public/build

# Nginx config
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Env cleanup for production
RUN php artisan optimize

# Export port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
