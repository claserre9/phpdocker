#!/bin/sh
set -x # print all executed commands to the terminal
composer install --optimize-autoloader --quiet
exec php-fpm -F