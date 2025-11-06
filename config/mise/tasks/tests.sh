#!/usr/bin/env bash
#MISE description="Run tests"
#USAGE flag "--quick" help="Only run quick-running tests (architecture, bats, unit)"

set -e

QUICK="${usage_quick:-false}"

echo
echo "Running architecture tests..."
mise run in-app-container php vendor/bin/pest -v --group=architecture

echo
echo "Running shell-scripts tests..."
mise run in-app-container mise exec bats -- bats tests/ShellScripts

echo
echo "Running unit tests..."
mise run in-app-container php bin/phpunit tests/Unit

if [ "${QUICK}" == "true" ]
then
    echo "All quick tests completed successfully! ✨"
    exit 0
fi

echo
echo "Running integration tests..."
mise run in-app-container php bin/console doctrine:database:drop --if-exists --force --env=test
mise run in-app-container php bin/console doctrine:database:create --env=test
mise run in-app-container php bin/console doctrine:migrations:migrate --no-interaction --env=test
mise run in-app-container php bin/phpunit tests/Integration

echo
echo "Running application tests..."
mise run in-app-container php bin/console doctrine:database:drop --if-exists --force --env=test
mise run in-app-container php bin/console doctrine:database:create --env=test
mise run in-app-container php bin/console doctrine:migrations:migrate --no-interaction --env=test
mise run in-app-container php bin/phpunit tests/Application

echo "All tests completed successfully! ✨"
