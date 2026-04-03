FROM php:8.3-cli

# OS deps + PHP extensions Laravel often needs on minimal images
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl gnupg git unzip \
    libzip-dev zip libsqlite3-dev libicu-dev \
    && docker-php-ext-install -j2 intl bcmath zip pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    NODE_OPTIONS=--max-old-space-size=4096

# Node 20 (Vite 8)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY . .

RUN git config --global --add safe.directory /var/www

# Build-time .env + key; Render APP_KEY overrides at runtime
RUN cp .env.example .env \
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
