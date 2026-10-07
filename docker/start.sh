#!/bin/sh

set -e

cd /var/www

attempt=1
max_attempts=10

until php artisan migrate --force --no-interaction; do
    if [ "$attempt" -ge "$max_attempts" ]; then
        echo "Database migrations failed after ${max_attempts} attempts."
        exit 1
    fi

    echo "Database is not ready or migrations failed; retrying in 5 seconds..."
    attempt=$((attempt + 1))
    sleep 5
done

php artisan storage:link --force --no-interaction

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan event:cache --no-interaction

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
