FROM php:8.3-fpm-alpine AS base

LABEL org.opencontainers.image.source="https://github.com/packistry/packistry"
LABEL org.opencontainers.image.description="Packistry is a Composer repository for PHP packages Packistry is a Composer repository for PHP packages"
LABEL org.opencontainers.image.licenses="GPL-3.0"

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers ca-certificates curl gnupg git unzip supervisor \
    oniguruma-dev libzip-dev libpng-dev libjpeg-turbo-dev icu-dev \
    && docker-php-ext-configure gd --enable-gd --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip gd intl opcache sockets pcntl

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

FROM node:22-slim AS builder_frontend

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable

WORKDIR /frontend

COPY frontend/pnpm-lock.yaml frontend/package.json ./

RUN pnpm i --frozen-lockfile

COPY frontend/ .

RUN pnpm build

FROM base AS builder_api

COPY composer.* .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --verbose --prefer-dist --no-progress --no-interaction --no-dev --no-scripts

COPY . .
RUN composer dump-autoload --optimize
RUN rm -rf frontend

FROM base AS runner

COPY --from=builder_api /var/www/html /var/www/html
COPY --from=builder_frontend /frontend/dist /var/www/html/dist
COPY --from=ghcr.io/roadrunner-server/roadrunner:2024.2 /usr/bin/rr /usr/local/bin/rr

COPY ./docker/supervisord.conf /etc/supervisord.conf
COPY ./docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY ./docker/packistry /usr/bin

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN mv dist/* public/

ENV USER="packistry"
ENV GROUP="packistry"

RUN addgroup -S $GROUP && adduser -S $USER -G $GROUP -H
RUN chown -R $GROUP:$USER /var/www/html

USER $USER

EXPOSE 80

CMD ["sh", "-c", "./setup.sh"]
