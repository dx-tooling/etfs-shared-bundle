<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\NoAssociativeArraysAcrossBoundaries\Bad;

final class PublicReturnListOfMixed
{
    /** @return list<mixed> */
    public function getMixedValues(): array
    {
        return ['string', 123, true, null];
    }
}
