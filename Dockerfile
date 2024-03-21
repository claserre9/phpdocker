FROM php:8.2-fpm
WORKDIR /app

# Copy composer.lock and composer.json
COPY composer.* /app/

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    git \
    libzip-dev \
    unzip && \
    docker-php-ext-install mysqli && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Grant write permissions for composer
RUN chown -R www-data:www-data /app

# Switch to www-data user and install dependencies with Composer
USER www-data
RUN composer install --no-interaction

# Switch back to root user for further setup (optional)
USER root