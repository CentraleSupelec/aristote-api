FROM php:8.2-fpm-alpine3.17 as php_base

RUN apk update \
 && apk add bash icu-dev icu-data-full postgresql-dev libzip-dev libpng-dev oniguruma-dev autoconf gcc g++ make fcgi optipng jpegoptim libjpeg libpng imagemagick ghostscript git libxml2-dev gnu-libiconv screen \
 && curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash - \
 && apk add symfony-cli=5.4.21

RUN set -xe \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
 && apk add --no-cache bash linux-headers

RUN docker-php-ext-install intl mbstring opcache pdo pdo_pgsql pdo_mysql zip bcmath sockets gd xml pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN echo "date.timezone = Europe/Paris" >> /usr/local/etc/php/conf.d/timezone.ini && \
    echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini && \
    echo 'short_open_tag = off' >> /usr/local/etc/php/conf.d/docker-php-short-open-tag.ini && \
    echo 'expose_php = off' >> /usr/local/etc/php/conf.d/docker-php-expose.ini && \
    echo 'post_max_size = 10M' >> /usr/local/etc/php/conf.d/docker-php-upload.ini && \
    echo 'upload_max_filesize = 10M' >> /usr/local/etc/php/conf.d/docker-php-upload.ini

WORKDIR /app

COPY .env \
    composer.json \
    composer.lock \
    symfony.lock \
    ./

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ADD docker/php/symfony-entrypoint.sh /

CMD ["php-fpm"]

ENTRYPOINT ["/bin/bash", "/symfony-entrypoint.sh"]

FROM php_base as php_installer

RUN APP_ENV=prod composer install --optimize-autoloader --no-progress --no-interaction --no-dev --no-scripts

FROM php_base as php_dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
RUN echo "[xdebug]" >> /usr/local/etc/php/php.ini-development \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/php.ini-development \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/php.ini-development \
    && echo "xdebug.client_port=9000" >> /usr/local/etc/php/php.ini-development \
    && echo "xdebug.discover_client_host=1" >> /usr/local/etc/php/php.ini-development \
    && mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN mkdir -p var /.composer /.symfony && chown -R www-data:www-data var/ /.composer/ /.symfony/
USER www-data

FROM php_base as php_prod

ENV APP_ENV=prod

COPY --from=php_installer /app/vendor /app/vendor

## Copy project files to workdir
COPY bin bin
COPY config config
COPY public public
COPY src src
COPY templates templates
COPY tests tests
COPY migrations migrations

RUN chmod +x bin/console

# php-fpm is run with www-data
RUN mkdir -p var && chown -R www-data:www-data var/
USER www-data
