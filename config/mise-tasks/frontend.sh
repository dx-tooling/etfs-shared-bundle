#!/usr/bin/env bash
#MISE description="Build all frontend assets"

set -e

rm -rf public/assets
/usr/bin/env php bin/console tailwind:build
/usr/bin/env php bin/console asset-map:compile
