#!/usr/bin/env bash
#MISE description="Connect to the database"

set -e

if [[ "$APP_ENV" != "" ]]; then
    ENV="$APP_ENV"
else
    ENV="dev"
fi

source .env
[ -f .env.local ] && source .env.local || true
[ -f ".env.${ENV}" ] && source ".env.${ENV}" || true
[ -f ".env.${ENV}.local" ] && source ".env.${ENV}.local" || true

mise run in-app-container mysql \
    --ssl=false \
    -h"${DATABASE_HOST}" \
    -u"${DATABASE_USER}" \
    -p"${DATABASE_PASSWORD}" \
    "${DATABASE_DB}" \
    "$@"
