ARG BASE_IMAGE_TAG
FROM php:${BASE_IMAGE_TAG}
ARG DRUSH

# Install PHP and composer dependencies
RUN apt-get update -yqq && apt-get install git libzip-dev zip libpng-dev mariadb-client chromium-driver -yqq

# Install needed extensions
RUN docker-php-ext-install gd pdo_mysql zip bcmath

# Install Composer
RUN curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install drush
RUN composer global require "drush/drush:${DRUSH}.*"

RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

ENV PATH "$PATH:/root/.composer/vendor/bin"
