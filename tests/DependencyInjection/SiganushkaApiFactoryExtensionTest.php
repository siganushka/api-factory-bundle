<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\ResolverConfigurator;
use Siganushka\ApiFactory\ResolverConfiguratorInterface;
use Siganushka\ApiFactory\ResolverExtensionInterface;
use Siganushka\ApiFactory\ResolverInterface;
use Siganushka\ApiFactoryBundle\DependencyInjection\SiganushkaApiFactoryExtension;
use Siganushka\ApiFactoryBundle\Tests\Fixtures\TestConfiguration;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SiganushkaApiFactoryExtensionTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $container = $this->createContainerWithConfig();

        static::assertTrue($container->hasDefinition(ResolverConfigurator::class));
        static::assertTrue($container->hasAlias(ResolverConfiguratorInterface::class));

        $instanceof = $container->getAutoconfiguredInstanceof();

        static::assertArrayHasKey(ResolverInterface::class, $instanceof);
        static::assertArrayHasKey(ResolverExtensionInterface::class, $instanceof);
        static::assertTrue($instanceof[ResolverInterface::class]->hasTag('siganushka_api_factory.resolver'));
        static::assertTrue($instanceof[ResolverExtensionInterface::class]->hasTag('siganushka_api_factory.resolver_extension'));
    }

    public function testCustomConfig(): void
    {
        $container = $this->createContainerWithConfig([
            'test' => [
                'foo' => 'test1_foo',
            ],
        ]);

        static::assertTrue($container->hasAlias('siganushka_api_factory.test.configuration'));
        static::assertTrue($container->hasAlias(TestConfiguration::class));

        static::assertSame('siganushka_api_factory.test.configuration_default', (string) $container->getAlias('siganushka_api_factory.test.configuration'));
        static::assertSame('siganushka_api_factory.test.configuration_default', (string) $container->getAlias(TestConfiguration::class));

        static::assertTrue($container->hasDefinition('siganushka_api_factory.test.configuration_default'));
        static::assertTrue($container->hasAlias(TestConfiguration::class.' $defaultConfiguration'));

        $container = $this->createContainerWithConfig([
            'test' => [
                'default_configuration' => 'test2',
                'configurations' => [
                    'test1' => [
                        'foo' => 'test1_foo',
                        'bar' => 1024,
                        'baz' => true,
                    ],
                    'test2' => [
                        'foo' => 'test2_foo',
                        'bar' => 2048,
                        'baz' => false,
                    ],
                ],
            ],
        ]);

        static::assertTrue($container->hasAlias('siganushka_api_factory.test.configuration'));
        static::assertTrue($container->hasAlias(TestConfiguration::class));

        static::assertSame('siganushka_api_factory.test.configuration_test2', (string) $container->getAlias('siganushka_api_factory.test.configuration'));
        static::assertSame('siganushka_api_factory.test.configuration_test2', (string) $container->getAlias(TestConfiguration::class));

        static::assertTrue($container->hasDefinition('siganushka_api_factory.test.configuration_test1'));
        static::assertTrue($container->hasDefinition('siganushka_api_factory.test.configuration_test2'));

        static::assertTrue($container->hasAlias(TestConfiguration::class.' $test1Configuration'));
        static::assertTrue($container->hasAlias(TestConfiguration::class.' $test2Configuration'));
    }

    private function createContainerWithConfig(array $config = []): ContainerBuilder
    {
        $extension = new SiganushkaApiFactoryExtension();
        $extension->addPackage('vendor/test', TestConfiguration::class);

        $container = new ContainerBuilder();
        $container->registerExtension($extension);
        $container->loadFromExtension('siganushka_api_factory', $config);

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
