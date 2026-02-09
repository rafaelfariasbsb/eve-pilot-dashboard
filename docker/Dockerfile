FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    gmp-dev \
    git \
    unzip \
    curl \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip \
    intl \
    mbstring \
    bcmath \
    pcntl \
    gmp \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

USER www

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
