<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResolverConfiguratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $resolverConfiguratorId = 'siganushka.api_factory.resolver_configurator';
        if (!$container->hasDefinition($resolverConfiguratorId)) {
            return;
        }

        $definition = $container->findDefinition($resolverConfiguratorId);

        $taggedServices = $container->findTaggedServiceIds('siganushka.api_factory.resolver');
        foreach ($taggedServices as $id => $tags) {
            $container->findDefinition($id)->setConfigurator([$definition, 'configure']);
        }
    }
}
