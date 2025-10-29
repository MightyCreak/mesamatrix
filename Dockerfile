#################
## Build image
##

FROM alpine:3.22 AS build

RUN apk add --no-cache composer

WORKDIR /app

RUN mkdir config public src vendor
COPY config ./config/
COPY public ./public/
COPY scripts ./scripts/
COPY src ./src/
COPY \
    composer.json \
    composer.lock \
    mesamatrixctl \
    ./
RUN rm -f ./public/features.xml

RUN composer install --no-dev

#################
## Final image
##

FROM php:8.2-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends git && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Uncomment if in development environment:
# RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
# RUN rm /var/log/apache2/*

# Point Apache to the public/ directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy Mesamatrix into /var/www/html/
COPY --from=build /app/ /var/www/html/
