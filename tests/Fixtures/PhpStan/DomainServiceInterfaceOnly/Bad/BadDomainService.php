<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\DomainServiceInterfaceOnly\Bad;

final class ConcreteDependency
{
}

final class BadDomainService
{
    private ConcreteDependency $dep;

    public function __construct(ConcreteDependency $dep)
    {
        $this->dep = $dep;
    }

    public function getDep(): ConcreteDependency
    {
        return $this->dep;
    }
}
