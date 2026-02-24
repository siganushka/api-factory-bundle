<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Siganushka\ApiFactory\ResolverConfigurator;
use Siganushka\ApiFactory\ResolverConfiguratorInterface;
use Siganushka\ApiFactoryBundle\DependencyInjection\Compiler\ResolverConfiguratorPass;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ResolverConfigurator::class)
            ->arg(0, tagged_iterator(ResolverConfiguratorPass::RESOLVER_EXTENSION_TAG))
            ->alias(ResolverConfiguratorInterface::class, ResolverConfigurator::class)
    ;
};
