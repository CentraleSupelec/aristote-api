#!/bin/sh
set -e

php bin/console doctrine:migrations:migrate --env=test --no-interaction
