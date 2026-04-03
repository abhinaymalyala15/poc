# Must use PHP 8.3+ (Laravel 13). Bookworm variant is explicit.
FROM php:8.3-cli-bookworm

# Official image puts PHP in /usr/local/bin — keep it first (avoid any distro php in /usr/bin)
ENV PATH="/usr/local/bin:/usr/local/sbin:/usr/bin:/bin"

# OS deps + PHP extensions Laravel needs
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl gnupg git unzip \
    libzip-dev zip libsqlite3-dev libicu-dev \
    && docker-php-ext-install -j2 intl bcmath zip pdo_sqlite \
    && rm -rf /var/lib/apt/lists/* \
    && php -v \
    && php -r "if (version_compare(PHP_VERSION, '8.3.0', '<')) { echo 'Need PHP>=8.3, got '.PHP_VERSION.PHP_EOL; exit(1);}"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    NODE_OPTIONS=--max-old-space-size=4096

# Node 20 (Vite 8)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/* \
    && php -v

WORKDIR /var/www

COPY . .

# Fail fast if lock is missing or out of date for this repo
RUN test -f composer.lock && head -5 composer.lock

RUN git config --global --add safe.directory /var/www

# Build-time .env + key; Render APP_KEY overrides at runtime
RUN php -v \
    && php -r "if (version_compare(PHP_VERSION, '8.3.0', '<')) { exit(1);}" \
    && cp .env.example .env \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && php artisan key:generate --force --no-interaction \
    && php artisan package:discover --ansi \
    && npm ci \
    && npm run build \
    && mkdir -p database \
    && touch database/database.sqlite \
    && chmod 664 database/database.sqlite \
    && chmod -R 775 storage bootstrap/cache

ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT}"]
