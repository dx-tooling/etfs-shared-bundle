#!/usr/bin/env bash
#@metadata-start
#@name test
#@description Run the test suite
#@source-repo git@github.com:Enterprise-Tooling-for-Symfony/dxcli-commands-bundle.git
#@source-commit-id 8c27e98361300a9794838dbc40f3a93e818e87b9
#@metadata-end

# >>> BEGIN SETUP - always keep this section in your scripts!
set -e
set -u
set -o pipefail

SOURCE=${BASH_SOURCE[0]}
while [ -L "$SOURCE" ]; do
    DIR=$( cd -P "$( dirname "$SOURCE" )" >/dev/null 2>&1 && pwd )
    SOURCE=$(readlink "$SOURCE")
    [[ $SOURCE != /* ]] && SOURCE=$DIR/$SOURCE
done

SCRIPT_FOLDER=$( cd -P "$( dirname "$SOURCE" )" >/dev/null 2>&1 && pwd )
if [ -z "$SCRIPT_FOLDER" ]; then
    echo "Failed to determine script location" >&2
    exit 1
fi

PROJECT_ROOT=$( cd "$SCRIPT_FOLDER/../.." >/dev/null 2>&1 && pwd )
if [ -z "$PROJECT_ROOT" ]; then
    echo "Failed to determine dxcli root" >&2
    exit 1
fi

source "$PROJECT_ROOT/.dxcli/shared.sh"
# <<< END SETUP - from now on, use $PROJECT_ROOT to get the full path to your project's root folder.


# Validate environment
require_command php

log_info "Running test suite..."
/usr/bin/env php "$PROJECT_ROOT/bin/phpunit.php" "$PROJECT_ROOT/tests/"

log_info "All tests completed successfully! ✨"
