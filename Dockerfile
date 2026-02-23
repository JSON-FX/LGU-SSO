# Stage 1: Install PHP dependencies
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
# Copy full source so we can generate an optimized autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev

# Stage 2: Build frontend assets
FROM node:20-alpine AS node
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# Stage 3: Production image
FROM php:8.4-cli-alpine AS runner
WORKDIR /app

# Install PHP extensions
RUN apk add --no-cache \
        libpng-dev \
        oniguruma-dev \
        libzip-dev \
        icu-dev \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        mbstring \
        zip \
        intl \
        pcntl

# Create non-root user
RUN addgroup -g 1001 -S laravel && \
    adduser -u 1001 -S laravel -G laravel

# Copy application code
COPY . .

# Copy composer dependencies (with optimized autoloader)
COPY --from=composer /app/vendor ./vendor

# Copy built frontend assets
COPY --from=node /app/public/build ./public/build

# Create .env placeholder (real env comes from Docker runtime)
RUN php -r "file_exists('.env') || copy('.env.example', '.env');"

# Set permissions
RUN chown -R laravel:laravel /app && \
    chmod -R 775 storage bootstrap/cache

# Copy entrypoint
COPY docker-entrypoint.sh /app/docker-entrypoint.sh
RUN chmod +x /app/docker-entrypoint.sh

USER laravel

EXPOSE 8000

ENTRYPOINT ["/app/docker-entrypoint.sh"]
