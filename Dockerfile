FROM php:8.3-fpm-alpine AS base

LABEL org.opencontainers.image.source=https://github.com/maantje/conductor

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers ca-certificates curl gnupg git unzip \
    oniguruma-dev libzip-dev libpng-dev libjpeg-turbo-dev icu-dev \
    && docker-php-ext-configure gd --enable-gd --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip gd intl opcache sockets pcntl

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

FROM base AS builder_api

COPY composer.* .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --verbose --prefer-dist --no-progress --no-interaction --no-dev --no-scripts

COPY . .
RUN composer dump-autoload --optimize

FROM base AS runner

COPY --from=builder_api /var/www/html /var/www/html
COPY --from=ghcr.io/roadrunner-server/roadrunner:2024.2 /usr/bin/rr /usr/local/bin/rr

COPY ./docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
ENV USER="composer"
ENV GROUP="composer"
WORKDIR /var/www/html

COPY .env.example .env

RUN addgroup -S $GROUP && adduser -S $USER -G $GROUP -H
RUN chown -R $GROUP:$USER /var/www/html

USER $USER

RUN touch database/database.sqlite
RUN php artisan key:generate
RUN php artisan migrate --force
RUN php artisan app:create-root-repository

EXPOSE 80

CMD ["rr", "serve", "-c", ".rr.production.yaml"]
