FROM php:8.4.27-fpm-alpine3.23 AS php-base

RUN apk add --no-cache curl libpng libjpeg-turbo freetype libzip oniguruma icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev oniguruma-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && apk del .build-deps \
    && addgroup -g 1003 app \
    && adduser -D -u 10000 -G app app

WORKDIR /var/www/html
COPY docker/production/php.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf

FROM php-base AS build
COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/media-library/temp bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage \
    && composer dump-autoload --no-dev --optimize --no-interaction --no-scripts \
    && php artisan package:discover --ansi

FROM php-base AS app
COPY --from=build --chown=app:app /var/www/html /var/www/html
USER app
EXPOSE 9000
CMD ["php-fpm"]

FROM nginx:1.24.0-alpine AS web
COPY docker/production/nginx.conf /etc/nginx/nginx.conf
COPY --from=build /var/www/html/public /var/www/html/public
USER nginx
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=5s --start-period=15s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8080/api/mobile/v1/docs/openapi.yaml || exit 1

FROM build AS test
RUN composer install --prefer-dist --no-interaction --no-progress --no-scripts \
    && composer dump-autoload --optimize --no-interaction --no-scripts \
    && chown -R app:app storage bootstrap/cache
USER app
CMD ["php", "vendor/bin/phpunit", "--do-not-cache-result", "--filter", "StaffAuth|Mobile|Trainer|Timezone"]
