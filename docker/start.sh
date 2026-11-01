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

if [ "${1:-}" = /usr/bin/supervisord ]; then
    # Both applications share secrets from Render; never print their values.
    : "${FACE_SERVICE_HMAC_SECRET:?Configure FACE_SERVICE_HMAC_SECRET in Render}"
    if [ "${#FACE_SERVICE_HMAC_SECRET}" -lt 32 ]; then
        echo "FACE_SERVICE_HMAC_SECRET must contain at least 32 characters." >&2
        exit 1
    fi
    export LARAVEL_HMAC_SECRET="$FACE_SERVICE_HMAC_SECRET"
    export SUPABASE_SECRET_KEY="${SUPABASE_SECRET_KEY:-${SUPABASE_SERVICE_ROLE_KEY:-}}"
    : "${SUPABASE_SECRET_KEY:?Configure the Supabase server key in Render}"
    : "${SUPABASE_URL:?Configure SUPABASE_URL in Render}"
    export FACE_SERVICE_URL=http://127.0.0.1:8001
    export LARAVEL_CALLBACK_URL="http://127.0.0.1:${PORT}/api/internal/photo-processing/callback"
    export MODEL_VERSION="${FACE_MODEL_VERSION:-sface-2021dec-v1}"
    export QUEUE_CONNECTION=database
fi

# Cache runtime environment values, never build-time credentials.
php artisan config:cache

# No automatic migrations, seeding or APP_KEY regeneration at startup.
exec "$@"
