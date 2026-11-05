#!/bin/sh
set -eu

export APP_ENV=e2e APP_URL=http://127.0.0.1:9003 APP_LOCALE=es
export APP_CONFIG_CACHE=bootstrap/cache/config-e2e.php
export DB_CONNECTION=pgsql DB_HOST=db-e2e DB_PORT="${E2E_DB_PORT:-5432}"
export DB_DATABASE=rutelydte_e2e DB_USERNAME=rutely_e2e DB_PASSWORD=rutely_e2e_local DB_URL=''
export CACHE_STORE=database QUEUE_CONNECTION=database SESSION_DRIVER=database MAIL_MAILER=array
export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"

php artisan config:clear --no-interaction
php artisan migrate:fresh --seed --no-interaction
exec php artisan serve --host="${E2E_SERVER_HOST:-127.0.0.1}" --port=9003 --tries=1 --no-reload --no-interaction
