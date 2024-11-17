FROM php:8.2-apache AS app_dev

RUN apt-get update && \
    apt-get install -y git zip curl

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN install-php-extensions pdo_sqlite intl
