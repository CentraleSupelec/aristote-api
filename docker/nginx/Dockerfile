ARG PHP_DESTINATION=php
FROM $PHP_DESTINATION as php

FROM nginx:1.19-alpine as nginx_dev

COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template

FROM nginx:1.19-alpine as nginx_prod

COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template

COPY --from=php /app/public /app/public

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["curl", "localhost"]
