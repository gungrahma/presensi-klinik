FROM php:8.3-cli

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    NODE_OPTIONS=--max-old-space-size=4096

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl zip gd bcmath opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress \
    && npm ci --no-audit --no-fund --legacy-peer-deps \
    && npm run build \
    && php artisan vendor:publish --all --force --no-interaction \
    && php artisan storage:link || true \
    && for pkg in $(ls vendor/filament/ 2>/dev/null); do \
        if [ -d "vendor/filament/$pkg/dist" ]; then \
            mkdir -p public/css/filament/$pkg public/js/filament/$pkg public/fonts/filament/$pkg; \
            if [ -f "vendor/filament/$pkg/dist/theme.css" ]; then \
                cp vendor/filament/$pkg/dist/theme.css public/css/filament/$pkg/app.css; \
            elif [ -f "vendor/filament/$pkg/dist/index.css" ]; then \
                cp vendor/filament/$pkg/dist/index.css public/css/filament/$pkg/app.css; \
            fi; \
            [ -f "vendor/filament/$pkg/dist/index.js" ] && cp vendor/filament/$pkg/dist/index.js public/js/filament/$pkg/app.js; \
            [ -f "vendor/filament/$pkg/dist/echo.js" ] && cp vendor/filament/$pkg/dist/echo.js public/js/filament/$pkg/echo.js; \
            [ -d "vendor/filament/$pkg/dist/components" ] && cp -r vendor/filament/$pkg/dist/components public/js/filament/$pkg/; \
            [ -d "vendor/filament/$pkg/dist/fonts" ] && cp -r vendor/filament/$pkg/dist/fonts/. public/fonts/filament/$pkg/; \
        fi; \
    done \
    && composer dump-autoload --optimize --no-scripts

EXPOSE $PORT

CMD ["sh", "-c", "php artisan storage:link 2>/dev/null; php artisan migrate --force 2>/dev/null; php -S 0.0.0.0:$PORT -t public"]
