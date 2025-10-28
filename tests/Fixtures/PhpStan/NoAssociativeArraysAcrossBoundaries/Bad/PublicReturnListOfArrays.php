<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\NoAssociativeArraysAcrossBoundaries\Bad;

final class PublicReturnListOfArrays
{
    /** @return list<array{0: string, 1: string}> */
    public function windows(): array
    {
        return [
            ['2024-01-01 10:00:00', '2024-01-01 12:00:00'],
        ];
    }
}
