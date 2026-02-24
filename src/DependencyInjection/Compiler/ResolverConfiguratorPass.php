<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Compiler;

use Siganushka\ApiFactory\ResolverConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResolverConfiguratorPass implements CompilerPassInterface
{
    public const RESOLVER_TAG = 'siganushka_api_factory.resolver';
    public const RESOLVER_EXTENSION_TAG = 'siganushka_api_factory.resolver_extension';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ResolverConfigurator::class)) {
            return;
        }

        $definition = $container->findDefinition(ResolverConfigurator::class);
        foreach ($container->findTaggedServiceIds(self::RESOLVER_TAG) as $id => $tags) {
            $container->findDefinition($id)->setConfigurator([$definition, 'configure']);
        }
    }
}
