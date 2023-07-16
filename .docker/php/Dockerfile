ARG PHP_TYPE
FROM php:8.2-${PHP_TYPE}-alpine

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions zip redis amqp xsl gd intl pdo_pgsql xdebug opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache bash nodejs npm \
    && curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash \
    && apk add --no-cache symfony-cli git

WORKDIR /var/www/html
