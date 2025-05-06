<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Siganushka\ApiFactory\ResolverConfigurator;
use Siganushka\ApiFactory\ResolverConfiguratorInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ResolverConfigurator::class)
            ->arg(0, tagged_iterator('siganushka.api_factory.resolver_extension'))
            ->alias(ResolverConfiguratorInterface::class, ResolverConfigurator::class)
    ;
};
