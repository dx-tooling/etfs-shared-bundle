#!/usr/bin/env bash
#MISE description="Run all quality tools"
#USAGE flag "--check-violations" help="Only check for violations, do not fix them"

set -e

CHECK_VIOLATIONS="${usage_check_violations:-false}"

echo
echo "Running Doctrine Schema Validation..."
mise run in-app-container php bin/console doctrine:schema:validate

echo
echo "Running PHP CS Fixer..."
if [ "${CHECK_VIOLATIONS}" == "true" ]
then
    mise run in-app-container php bin/php-cs-fixer.php check
else
    mise run in-app-container php bin/php-cs-fixer.php fix
fi

echo
echo "Running frontend checks..."
echo
echo "Running Prettier..."

if [ "${CHECK_VIOLATIONS}" == "true" ]
then
    mise run in-app-container mise exec node -- npm run prettier
else
    mise run in-app-container mise exec node -- npm run prettier:fix
fi

echo
echo "Running ESLint..."
mise run in-app-container mise exec node -- npm run lint

echo
echo "Running tsc to check for TypeScript errors..."
mise run in-app-container mise exec node -- npm exec tsc

echo
echo "Running PHPStan..."
mise run in-app-container mise exec node -- php vendor/bin/phpstan --memory-limit=1024M

echo
echo "All checks and cleanups completed successfully! ✨"
