# Mirrors the php-base stage of docker/production/Dockerfile, plus dev tooling
FROM php:8.4.27-fpm-alpine3.23

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache curl git unzip mariadb-client libpng libjpeg-turbo freetype libzip oniguruma icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev oniguruma-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && apk del .build-deps

COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

RUN addgroup -g ${GID} laravel \
    && adduser -u ${UID} -G laravel -s /bin/sh -D laravel

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

USER laravel

EXPOSE 9000

CMD ["php-fpm"]
