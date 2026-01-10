FROM php:7.4-fpm-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    icu-dev \
    mysql-client

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN addgroup -g ${GID} laravel \
    && adduser -u ${UID} -G laravel -s /bin/sh -D laravel

RUN mkdir -p /var/www/html \
    && chown -R laravel:laravel /var/www/html

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

USER laravel

EXPOSE 9000

CMD ["php-fpm"]
