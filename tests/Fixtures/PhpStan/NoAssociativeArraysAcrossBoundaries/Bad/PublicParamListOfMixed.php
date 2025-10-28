<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\NoAssociativeArraysAcrossBoundaries\Bad;

final class PublicParamListOfMixed
{
    /** @param list<mixed> $values */
    public function setMixedValues(array $values): void
    {
        // noop
    }
}
