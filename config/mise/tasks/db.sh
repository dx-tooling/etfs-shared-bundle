#!/usr/bin/env bash
#MISE description="lalala"

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

/usr/bin/env docker \
    run \
    -it \
    --network etfs-app-starter-kit_etfs_app_starter_kit \
    --rm \
    mysql \
    mysql \
    -h"${DATABASE_HOST}" \
    -u"${DATABASE_USER}" \
    -p"${DATABASE_PASSWORD}" \
    "${DATABASE_DB}" \
    "$@"
