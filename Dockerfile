ARG PHP_VERSION=7.3

FROM php:${PHP_VERSION}-alpine

ENV IMGIX_SOURCE_ROOT=http://localhost:8080

RUN apk add --no-cache ca-certificates && \
    update-ca-certificates

RUN mkdir -p /srv/www

RUN apk add --no-cache bash curl libmcrypt-dev libpng-dev libxml2-dev libzip-dev openssl openssl-dev icu-dev tidyhtml-dev oniguruma-dev curl-dev libxslt-dev libgcrypt-dev freetype-dev jpeg-dev libjpeg-turbo libjpeg-turbo-dev

RUN docker-php-ext-configure gd \
        --with-freetype-dir=/usr/lib/ \
        --with-png-dir=/usr/lib/ \
        --with-jpeg-dir=/usr/lib/ \
        --with-gd

RUN php -m
RUN docker-php-ext-install -j$(nproc) bcmath opcache intl zip tidy mbstring dom intl curl xsl sockets gd

RUN apk add --no-cache autoconf gcc g++ make pcre-dev \
    && pecl install psr \
    && docker-php-ext-enable psr \
    && pecl install mcrypt \
    && docker-php-ext-enable mcrypt \
    && apk del --purge autoconf gcc g++ make \
    && pecl clear-cache

RUN apk add --no-cache ghostscript npm \
    && npm install -g heic-cli

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /srv/www/public
COPY public/index.php /srv/www/public/index.php
COPY public/server.php /srv/www/public/server.php
COPY composer.json /srv/www/

WORKDIR /srv/www
RUN composer install --prefer-dist --no-progress --no-interaction --ignore-platform-reqs

EXPOSE 8082

COPY Docker/php-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR "/srv/www/public"
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8082", "server.php"]
