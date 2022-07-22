#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

# if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
#     mkdir -p /var/www/logs

#     if [ "$APP_ENV" != 'prod' ]; then
#         composer install --prefer-dist --no-progress --no-interaction --ignore-platform-reqs
#     fi
# fi

exec docker-php-entrypoint "$@"
