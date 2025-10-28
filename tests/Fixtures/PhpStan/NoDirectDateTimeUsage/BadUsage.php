<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\NoDirectDateTimeUsage;

use DateTimeImmutable;

final class BadUsage
{
    public function run(): void
    {
        // should be flagged by NoDirectDateTimeUsageRule
        $now    = new DateTimeImmutable('now');
        $unused = $now;
    }
}
