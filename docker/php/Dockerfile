FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip opcache \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite deflate expires headers

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-decor.ini
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 80
