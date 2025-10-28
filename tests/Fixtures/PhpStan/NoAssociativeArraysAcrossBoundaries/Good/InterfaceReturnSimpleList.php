<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\NoAssociativeArraysAcrossBoundaries\Good;

interface InterfaceReturnSimpleList
{
    /** @return list<int> */
    public function ids(): array;
}
