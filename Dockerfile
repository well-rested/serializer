ARG IMG_BASE_VERSION="8.4"
FROM php:${IMG_BASE_VERSION}-cli

RUN apt-get update \
    && apt-get install -y libzip-dev zip git \
    && docker-php-ext-install zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov

COPY ./scripts/install_composer.sh /install_composer.sh
RUN chmod +x /install_composer.sh

RUN /install_composer.sh