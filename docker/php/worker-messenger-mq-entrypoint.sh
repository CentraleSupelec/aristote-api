#!/bin/bash
set -e

# The application requires integers to be encoded on 64-bits
INT_ENCODING_BYTES="$(php -r 'echo PHP_INT_SIZE;')"
if [ "$INT_ENCODING_BYTES" != "8" ]; then
    echo "Error: the application requires a 64 bits CPU architecture to encode integers on 8 bytes."
    exit 1
fi

until timeout 1 bash -c "cat < /dev/null > /dev/tcp/${PHP_HOST:-php}/9000" > /dev/null 2>&1; do
  >&2 echo "Wait for it - php"
  sleep 8
done

cd /app

RANDOMIZED_TTL=$(( ( RANDOM % 30 ) + 3600 ))

php bin/console messenger:setup-transports
php bin/console messenger:consume async -vv --time-limit=$RANDOMIZED_TTL
