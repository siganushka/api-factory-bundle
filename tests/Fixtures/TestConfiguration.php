<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Tests\Fixtures;

use Siganushka\ApiFactory\AbstractConfiguration;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractConfiguration<array{ foo: string, bar: int, baz?: bool }>
 */
class TestConfiguration extends AbstractConfiguration
{
    public static function configureOptions(OptionsResolver $resolver): void
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
