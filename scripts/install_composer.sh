#!/bin/sh

# See https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md#how-do-i-install-composer-programmatically-
# Slightly customised at the end to install it globally, rather than just the phar in current directory

EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php

if [ "$RESULT" != "0" ]; then
    exit $RESULT;
fi

mv composer.phar /usr/local/bin/composer