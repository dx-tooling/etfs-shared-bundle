<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle;

use EnterpriseToolingForSymfony\SharedBundle\DependencyInjection\EtfsSharedExtension;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function dirname;

class SharedBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if (!class_exists('Symfony\Bundle\FrameworkBundle\FrameworkBundle')) {
            throw new LogicException('The Symfony FrameworkBundle is required to use the enterprise-tooling-for-symfony/shared-bundle.');
        }
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new EtfsSharedExtension($this);
        }

        if ($this->extension === false) {
            return null;
        }

        return $this->extension;
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
