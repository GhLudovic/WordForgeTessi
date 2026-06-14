FROM php:8.4-fpm

# Dépendances système requises pour compiler les extensions PHP du projet.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        intl \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Binaire Composer issu de l'image officielle (compatible PHP 8.4, sans bruit de dépréciation).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/zz-wordforge.ini

WORKDIR /var/www
