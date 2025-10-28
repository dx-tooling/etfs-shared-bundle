<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\NoAssociativeArraysAcrossBoundaries\Bad;

final class PublicParamAssociative
{
    /** @param array<string, int> $map */
    public function setMap(array $map): void
    {
        // noop
    }
}
