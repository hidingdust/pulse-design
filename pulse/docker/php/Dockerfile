FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        procps \
        curl \
    && docker-php-ext-install pdo_mysql opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /srv

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-pulse.ini
COPY docker/php/zz-pulse.conf /usr/local/etc/php-fpm.d/zz-pulse.conf
COPY . /srv

