<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Compiler;

use Siganushka\ApiFactory\ResolverConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResolverConfiguratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ResolverConfigurator::class)) {
            return;
        }

        $definition = $container->findDefinition(ResolverConfigurator::class);

        $taggedServices = $container->findTaggedServiceIds('siganushka_api_factory.resolver');
        foreach ($taggedServices as $id => $tags) {
            $container->findDefinition($id)->setConfigurator([$definition, 'configure']);
        }
    }
}
