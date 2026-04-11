# Stage 1: Build assets
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: PHP Application
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

# Install Redis via PECL (since it's not in some standard Alpine repos for 8.4)
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built assets from first stage
COPY --from=assets-builder /app/public/build ./public/build

# Permissions
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache public/build
RUN chmod -R 775 storage bootstrap/cache

# Nginx config
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Entrypoint script
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Env cleanup for production
# Caches will be handled by entrypoint.sh at runtime to ensure Railway vars are used.

# Export port
EXPOSE 80

# Start entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
