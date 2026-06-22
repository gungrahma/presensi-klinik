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
    && composer dump-autoload --optimize --no-scripts

RUN php artisan storage:link 2>&1 || echo "storage:link skipped (likely already exists)"

RUN set -e; \
    for pkg in $(ls vendor/filament/ 2>/dev/null); do \
        if [ -d "vendor/filament/$pkg/dist" ]; then \
            echo "Processing filament/$pkg..."; \
            mkdir -p public/css/filament/$pkg public/js/filament/$pkg public/fonts/filament/$pkg; \
            if [ -f "vendor/filament/$pkg/dist/theme.css" ]; then \
                cp vendor/filament/$pkg/dist/theme.css public/css/filament/$pkg/app.css; \
                echo "  + theme.css -> app.css"; \
            elif [ -f "vendor/filament/$pkg/dist/index.css" ]; then \
                cp vendor/filament/$pkg/dist/index.css public/css/filament/$pkg/app.css; \
                echo "  + index.css -> app.css"; \
            fi; \
            if [ -f "vendor/filament/$pkg/dist/index.js" ]; then \
                cp vendor/filament/$pkg/dist/index.js public/js/filament/$pkg/$pkg.js; \
                cp vendor/filament/$pkg/dist/index.js public/js/filament/$pkg/app.js; \
                echo "  + index.js -> $pkg.js + app.js"; \
            fi; \
            if [ -f "vendor/filament/$pkg/dist/echo.js" ]; then \
                cp vendor/filament/$pkg/dist/echo.js public/js/filament/$pkg/echo.js; \
                echo "  + echo.js -> echo.js"; \
            fi; \
            if [ -d "vendor/filament/$pkg/dist/components" ]; then \
                cp -r vendor/filament/$pkg/dist/components public/js/filament/$pkg/; \
                echo "  + components/"; \
            fi; \
            if [ -d "vendor/filament/$pkg/dist/fonts" ]; then \
                cp -r vendor/filament/$pkg/dist/fonts/. public/fonts/filament/$pkg/; \
                echo "  + fonts/"; \
            fi; \
        fi; \
    done; \
    echo "Filament assets done."

EXPOSE $PORT

CMD ["sh", "-c", "php artisan storage:link 2>/dev/null; php artisan migrate --force --seed 2>/dev/null; php -S 0.0.0.0:$PORT -t public"]
