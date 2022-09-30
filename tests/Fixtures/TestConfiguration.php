<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Tests\Fixtures;

use Siganushka\ApiFactory\AbstractConfiguration;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestConfiguration extends AbstractConfiguration
{
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('foo')
            ->required()
            ->allowedTypes('string')
        ;

        $resolver
            ->define('bar')
            ->default(123)
            ->allowedTypes('int')
        ;

        $resolver
            ->define('baz')
            ->allowedTypes('bool')
        ;
    }
}
