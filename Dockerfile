FROM php:8.2-fpm-alpine

# Install runtime libs and build dependencies, compile PHP extensions, then remove build deps
RUN apk add --no-cache --virtual .runtime-deps \
    nginx \
    nodejs \
    npm \
    git \
    curl \
    libpq \
    libzip \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    supervisor \
    libxml2 \
    zlib \
    openssl

RUN apk add --no-cache --virtual .build-deps \
    build-base \
    autoconf \
    bison \
    re2c \
    linux-headers \
    libxml2-dev \
    zlib-dev \
    libzip-dev \
    postgresql-dev \
    pkgconfig

# Configure and install PHP extensions that require compilation
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    intl \
    opcache \
 && apk del .build-deps

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for dependency caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy package files and install JS deps
COPY package.json package-lock.json vite.config.js ./
RUN npm ci

# Copy full application
COPY . .

# Build frontend assets
RUN npm run build

# Finish Composer install (run scripts, autoload)
RUN composer install --no-dev --optimize-autoloader

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configure supervisord
COPY docker/supervisord.conf /etc/supervisord.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
