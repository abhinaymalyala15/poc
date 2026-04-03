FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    unzip git curl libzip-dev zip libpq-dev \
    && docker-php-ext-install zip pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY . .

# Build-time .env + key (composer scripts need them); Render APP_KEY overrides at runtime
RUN cp .env.example .env \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && php artisan key:generate --force --no-interaction \
    && php artisan package:discover --ansi \
    && npm ci \
    && npm run build \
    && chmod -R 775 storage bootstrap/cache

ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT}"]
