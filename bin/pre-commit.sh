#!/usr/bin/env bash

set -e

echo "php-cs-fixer start"

PHP_CS_FIXER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )/../../vendor/bin/php-cs-fixer"

if [ -x "$PHP_CS_FIXER" ]; then
    if git diff --cached --name-only --diff-filter=ACMRTUXB | grep -q '\.php$'; then
        STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACMRTUXB | grep '\.php$')
        docker-compose run --no-TTY --entrypoint "" --rm php php vendor/bin/php-cs-fixer fix --verbose --dry-run --config=.php-cs-fixer.dist.php -- ${STAGED_FILES[@]};
        git add ${STAGED_FILES[@]}
    fi
else
    echo ""
    echo "Please install php-cs-fixer, e.g.:"
    echo ""
    echo "  composer require --dev friendsofphp/php-cs-fixer"
    echo ""
fi
