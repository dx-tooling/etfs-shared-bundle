#!/usr/bin/env bash
#MISE description="Run all quality tools"
#USAGE flag "--check-violations" help="Only check for violations, do not fix them"

set -e

CHECK_VIOLATIONS="${usage_check_violations:-false}"

echo
echo "Running PHP CS Fixer..."
if [ "${CHECK_VIOLATIONS}" == "true" ]
then
    /usr/bin/env php bin/php-cs-fixer.php check
else
    /usr/bin/env php bin/php-cs-fixer.php fix
fi

echo
echo "Running Prettier..."

if [ "${CHECK_VIOLATIONS}" == "true" ]
then
    /usr/bin/env npm run prettier
else
    /usr/bin/env npm run prettier:fix
fi

echo
echo "Running ESLint..."
/usr/bin/env npm run lint

echo
echo "Running PHPStan..."
/usr/bin/env php vendor/bin/phpstan --memory-limit=1024M

echo
echo "All checks and cleanups completed successfully! ✨"
