# syntax=docker/dockerfile:1
# composer.lock pulls Symfony 8.1 packages that require PHP >= 8.4.1
FROM php:8.4-fpm

COPY ./docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY ./docker/php-fpm-www.conf /usr/local/etc/php-fpm.d/zz-maison.conf

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

# System packages, PHP extensions, and Node.js (rarely changes — keep early for cache)
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    git \
    unzip \
    curl \
    ca-certificates \
    fontconfig \
    fonts-dejavu-core \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip gd bcmath intl pcntl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && mkdir -p /var/log/supervisor /run/nginx \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Composer deps — cached until composer.lock / composer.json change
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer/cache,sharing=locked \
    composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --prefer-dist

# NPM deps — cached until package-lock.json / package.json change
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm,sharing=locked \
    npm ci --no-audit --no-fund

# App source (.dockerignore excludes vendor/, node_modules/, .env, public/hot, etc.)
COPY . .

# Echo / Reverb Vite env — declared after dependency layers so Coolify build-arg
# changes do not bust composer/npm cache.
ARG VITE_REVERB_APP_KEY=
ARG VITE_REVERB_HOST=ws.maisonanversa.com
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

# Wayfinder TS outputs are gitignored, so generate them before Vite build.
RUN cp .env.example .env \
    && mkdir -p \
    storage/framework/views \
    storage/framework/sessions \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    && php artisan package:discover --ansi \
    && php artisan key:generate --force --ansi \
    && php artisan wayfinder:generate --with-form --ansi \
    && npm run build \
    && node -e "import('@resvg/resvg-js').then(() => console.log('resvg-ok'))" \
    && mkdir -p storage/app/private storage/app/public \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/start.sh /usr/local/bin/start-container

RUN chmod +x /usr/local/bin/start-container

EXPOSE 80

# Laravel /up via nginx → php-fpm. start-period covers migrate + cache warm-up in start.sh.
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up >/dev/null || exit 1

CMD ["/usr/local/bin/start-container"]
