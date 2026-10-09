FROM php:8.4-fpm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        default-mysql-client \
        git \
        unzip \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install -j"$(nproc)" opcache pdo_mysql \
    && docker-php-source delete \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS dev

ENV APP_ENV=dev \
    APP_DEBUG=1

CMD ["php-fpm"]

FROM base AS vendor

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

COPY symfony/composer.json symfony/composer.lock symfony/symfony.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-progress \
    --no-interaction \
    --no-scripts \
    --no-autoloader

COPY symfony/ ./
RUN composer dump-autoload --classmap-authoritative --no-dev

FROM base AS production

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY --from=vendor /var/www/html /var/www/html

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

CMD ["php-fpm"]

FROM nginx:1.27.4-alpine AS nginx-production

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=vendor /var/www/html/public /var/www/html/public
