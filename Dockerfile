# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: front-end libraries (vue, jquery). No bundler - npm's postinstall
# copies the prebuilt dist files into public/assets/js/vendor/.
# ---------------------------------------------------------------------------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
COPY bin/copy-assets.js bin/
RUN npm install --no-audit --no-fund --omit=dev

# ---------------------------------------------------------------------------
# Stage 2: PHP dependencies + optimised autoloader.
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-dev \
        --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# ---------------------------------------------------------------------------
# Stage 3: runtime - Apache 2.4 + PHP 8.2 (mod_php).
# ---------------------------------------------------------------------------
FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        libjpeg62-turbo-dev libpng-dev libwebp-dev libfreetype6-dev \
        default-mysql-client \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" gd pdo_mysql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
# fileinfo + mbstring are compiled into the official image already.

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini     /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/assets/js/vendor /var/www/html/public/assets/js/vendor

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint bin/*.php \
 && mkdir -p storage/uploads/products storage/sessions storage/cache storage/logs \
 && chown -R www-data:www-data storage

EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
