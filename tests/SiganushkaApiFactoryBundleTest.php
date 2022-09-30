<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactoryBundle\DependencyInjection\Compiler\ResolverConfiguratorPass;
use Siganushka\ApiFactoryBundle\DependencyInjection\SiganushkaApiFactoryExtension;
use Siganushka\ApiFactoryBundle\SiganushkaApiFactoryBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SiganushkaApiFactoryBundleTest extends TestCase
{
    public function testAll(): void
    {
        $extension = new SiganushkaApiFactoryExtension();

        static::assertCount(0, $extension->getPackages());

        $container = new ContainerBuilder();
        $container->registerExtension($extension);

        $bundle = new SiganushkaApiFactoryBundle();
        $bundle->build($container);

        static::assertCount(4, $extension->getPackages());

        $passConfig = $container->getCompilerPassConfig();
        $filteredPasses = array_filter($passConfig->getBeforeOptimizationPasses(), fn (CompilerPassInterface $compiler) => ResolverConfiguratorPass::class === \get_class($compiler));

        static::assertCount(1, $filteredPasses);
    }
}
