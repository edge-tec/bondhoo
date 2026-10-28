# =====================================================================
# Jugajug Social Network — Enterprise Production Dockerfile
# =====================================================================
FROM php:8.4-fpm-alpine AS base

# Install system dependencies, supervisor & ffmpeg for media processing
RUN apk add --no-cache \
    curl \
    git \
    bash \
    zip \
    unzip \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    supervisor \
    fcgi \
    ffmpeg

# Install PHP extension installer script
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install high-performance Laravel extensions
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
    pdo_mysql \
    redis \
    opcache \
    pcntl \
    bcmath \
    gd \
    zip \
    exif \
    intl

# Install Composer 2
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy OPcache configuration
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application files
COPY . /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Production optimization stage
FROM base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
