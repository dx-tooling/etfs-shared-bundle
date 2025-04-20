<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('etfs_shared');

        return $treeBuilder;
    }
}
