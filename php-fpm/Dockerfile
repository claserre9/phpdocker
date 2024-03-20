FROM php:8.2-fpm
WORKDIR "/app"

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        libzip-dev \
    && docker-php-ext-install mysqli \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
