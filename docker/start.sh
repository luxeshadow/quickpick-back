#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be configured in the service environment." >&2
    exit 1
fi

case "${PORT:-10000}" in
    ''|*[!0-9]*) echo "PORT must be a number." >&2; exit 1 ;;
esac
export PORT="${PORT:-10000}"

# Cache runtime environment values, never build-time credentials.
php artisan config:cache

# No automatic migrations, seeding or APP_KEY regeneration at startup.
exec "$@"
