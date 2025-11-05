#!/usr/bin/env bash
#MISE description="Run software tests"

set -e

echo
echo "Running unit tests..."
/usr/bin/env php vendor/phpunit/phpunit/phpunit tests/Unit

echo "All tests completed successfully! ✨"
