#!/usr/bin/env bash
#MISE description="Build all frontend assets"

set -e

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

rm -rf "$SCRIPT_FOLDER/../public/assets"
/usr/bin/env php "$SCRIPT_FOLDER/../bin/console" tailwind:build
/usr/bin/env php "$SCRIPT_FOLDER/../bin/console" asset-map:compile
