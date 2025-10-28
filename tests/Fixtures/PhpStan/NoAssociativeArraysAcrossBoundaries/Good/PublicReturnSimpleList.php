<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\NoAssociativeArraysAcrossBoundaries\Good;

final class PublicReturnSimpleList
{
    /** @return list<string> */
    public function values(): array
    {
        return ['a', 'b', 'c'];
    }
}
