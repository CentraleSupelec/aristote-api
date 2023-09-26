#!/bin/sh
set -e

php bin/console --env=test doctrine:schema:update --force --complete
