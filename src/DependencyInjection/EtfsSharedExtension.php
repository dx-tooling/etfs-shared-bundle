<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use ValueError;

class EtfsSharedExtension extends Extension implements PrependExtensionInterface
{
    private BundleInterface $bundle;

    public function __construct(BundleInterface $bundle)
    {
        $this->bundle = $bundle;
    }

    /**
     * @throws Exception
     */
    public function load(
        array            $configs,
        ContainerBuilder $container
    ): void {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        if (!is_string($projectDir)) {
            throw new ValueError('Parameter "kernel.project_dir" must be a string, ' . gettype($projectDir) . ' given. ');
        }

        // Get absolute bundle root path
        $bundlePath = Path::makeAbsolute(
            $this->bundle->getPath(),
            $projectDir
        );

        // Add doctrine mapping configuration automatically
        $container->prependExtensionConfig(
            'doctrine', [
                'orm' => [
                    'mappings' => [
                        'SharedBundle' => [
                            'type'      => 'attribute',
                            'is_bundle' => false,
                            'prefix'    => 'EnterpriseToolingForSymfony\SharedBundle',
                            'dir'       => $bundlePath . '/src',
                        ],
                    ],
                ],
            ]
        );
    }
}
