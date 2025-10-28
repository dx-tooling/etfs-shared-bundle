<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Fixtures\PhpStan\DomainServiceInterfaceOnly\Good;

interface DependencyInterface
{
}

final class GoodDomainService
{
    private DependencyInterface $dep;

    public function __construct(DependencyInterface $dep)
    {
        $this->dep = $dep;
    }

    public function getDep(): DependencyInterface
    {
        return $this->dep;
    }
}
