<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\NoDirectDateTimeUsage;

// any lib class to ensure autoloading

use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

final class GoodUsage
{
    public function run(): void
    {
        $now = DateAndTimeService::getDateTimeImmutable();
    }
}
