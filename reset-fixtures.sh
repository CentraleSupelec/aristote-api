#!/bin/bash

set -e

docker-compose exec php bin/console doctrine:database:drop --force
docker-compose exec php bin/console doctrine:database:create
docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec php bin/console doctrine:fixtures:load
