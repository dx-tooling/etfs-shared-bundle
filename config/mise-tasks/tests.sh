#!/usr/bin/env bash
#MISE description="Run all quality tools"
#USAGE flag "--quick" help="Only run quick-running tests (architecture, bats, unit)"

set -e

QUICK="${usage_quick:-false}"

echo $QUICK

echo
echo "Running architecture tests..."
/usr/bin/env php vendor/bin/pest --group=architecture

echo
echo "Running shell-scripts tests..."
/usr/bin/env bats tests/ShellScripts/test-docker-cli-wrapper.bats

echo
echo "Running unit tests..."
/usr/bin/env php bin/phpunit tests/Unit

if [ "${QUICK}" == "true" ]
then
    echo "All quick tests completed successfully! ✨"
    exit 0
fi

echo
echo "Running integration tests..."
/usr/bin/env php bin/console doctrine:database:drop --if-exists --force --env=test
/usr/bin/env php bin/console doctrine:database:create --env=test
/usr/bin/env php bin/console doctrine:migrations:migrate --no-interaction --env=test
/usr/bin/env php bin/phpunit tests/Integration

echo
echo "Running application tests..."
/usr/bin/env php bin/console doctrine:database:drop --if-exists --force --env=test
/usr/bin/env php bin/console doctrine:database:create --env=test
/usr/bin/env php bin/console doctrine:migrations:migrate --no-interaction --env=test
/usr/bin/env php bin/phpunit tests/Application

echo "All tests completed successfully! ✨"

