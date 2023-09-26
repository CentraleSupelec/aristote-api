#!/bin/sh
set -e

# Install dependencies on dev environments (useful for new dependencies)
if [ "$APP_ENV" != 'prod' ]; then
    composer install --no-interaction --optimize-autoloader
fi

# Run migrations
php bin/console app:sync-migrate

echo "$@"
exec "$@"
