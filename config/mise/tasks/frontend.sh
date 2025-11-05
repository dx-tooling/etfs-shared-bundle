#!/usr/bin/env bash
#MISE description="Build all frontend assets"

set -e

rm -rf public/assets
mise run in-app-container mise exec node -- php bin/console tailwind:build
mise run in-app-container php bin/console asset-map:compile
