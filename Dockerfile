FROM php:8.4-cli AS dev

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

# For composer installs
RUN apt-get update -y
RUN apt-get install -y git zip