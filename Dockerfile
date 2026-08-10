# composer.lock pulls Symfony 8.1 packages that require PHP >= 8.4.1
FROM php:8.4-fpm

COPY ./docker/php.ini /usr/local/etc/php/conf.d/custom.ini

ENV COMPOSER_ALLOW_SUPERUSER=1

# System packages, PHP extensions, and Node.js
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip gd bcmath intl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# .dockerignore excludes vendor/, node_modules/, .env, public/hot, etc.
COPY . .

# Wayfinder TS outputs are gitignored, so generate them before Vite build.
RUN cp .env.example .env \
    && mkdir -p \
    storage/framework/views \
    storage/framework/sessions \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    && composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    && php artisan package:discover --ansi \
    && php artisan key:generate --force --ansi \
    && php artisan wayfinder:generate --with-form --ansi \
    && npm ci \
    && npm run build \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/start.sh /usr/local/bin/start-container

RUN chmod +x /usr/local/bin/start-container

EXPOSE 80
CMD ["/usr/local/bin/start-container"]
