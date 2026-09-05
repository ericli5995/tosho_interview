# Stage 1: front-end libraries (vue, jquery). No bundler - npm's postinstall
# (see package.json) copies the prebuilt dist files into public/assets/js/vendor/.
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install --no-audit --no-fund --omit=dev

# Stage 2: Composer autoloader (no third-party PHP packages).
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
COPY src/ src/
RUN composer install --no-dev --no-interaction --no-progress --ignore-platform-reqs \
 && composer dump-autoload --optimize --classmap-authoritative

# Stage 3: runtime - Apache 2.4 + PHP 8.2.
FROM php:8.2-apache

# gd (jpeg/png/webp) for image resizing, pdo_mysql for the database.
# fileinfo and mbstring are already compiled into the official image.
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd pdo_mysql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini     /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor vendor
COPY --from=assets /app/public/assets/js/vendor public/assets/js/vendor

# Writable storage (seeds the named volumes on first mount) + /media symlink.
RUN mkdir -p storage/uploads/products storage/sessions \
 && chown -R www-data:www-data storage \
 && ln -s ../storage/uploads public/media

EXPOSE 80
