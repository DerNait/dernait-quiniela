# PHP-FPM image for the Laravel API. Kept slim on purpose: this runs on a
# limited server for <= 50 users, so no Xdebug / dev extras in the runtime.
FROM php:8.3-fpm-alpine

# Reliable extension installer (handles deps + configure flags).
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/bin/

RUN install-php-extensions \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        bcmath \
        opcache \
        pcntl \
        intl \
        zip

# Composer from the official image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Sensible opcache settings for a small production box.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=64'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Match the nginx/www-data uid in most setups.
RUN addgroup -g 1000 app && adduser -G app -u 1000 -D app
USER app
