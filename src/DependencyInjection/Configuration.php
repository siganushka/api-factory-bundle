<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection;

use Siganushka\ApiFactory\AbstractConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Configuration implements ConfigurationInterface
{
    public function __construct(private readonly array $packages = [])
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('siganushka_api_factory');
        /** @var ArrayNodeDefinition */
        $rootNode = $treeBuilder->getRootNode();

        foreach ($this->packages as $packageName => $configurationClass) {
            if (!class_exists($configurationClass)) {
                throw new \InvalidArgumentException(\sprintf('The configuration class "%s" does not exists.', $configurationClass));
            }

            if (!is_subclass_of($configurationClass, AbstractConfiguration::class)) {
                throw new \UnexpectedValueException(\sprintf('The configuration class expected of type "%s", "%s" given.', AbstractConfiguration::class, $configurationClass));
            }

            $packageAlias = static::normalizePackageAlias($packageName);
            $packageNode = $this->createMultipleConfigsNode($rootNode, $packageAlias, \sprintf('%s configuration', ucfirst($packageAlias)))
                ->children()
                    ->arrayNode('configurations')
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                        ->children()
            ;

            $resolver = new OptionsResolver();
            $configurationClass::configureOptions($resolver);

            foreach ($resolver->getDefinedOptions() as $option) {
                $optionNode = $packageNode->variableNode($option);
                if ($resolver->isRequired($option) && !$resolver->hasDefault($option)) {
                    $optionNode->isRequired();
                }
            }

            // Unset after options defined
            unset($resolver, $configurationClass);
        }

        return $treeBuilder;
    }

    private function createMultipleConfigsNode(ArrayNodeDefinition $rootNode, string $nodeName, string $nodeInfo): ArrayNodeDefinition
    {
        /* @phpstan-ignore method.notFound */
        return $rootNode
            ->children()
                ->arrayNode($nodeName)
                ->info($nodeInfo)
                ->canBeEnabled()
                ->beforeNormalization()
                    ->ifTrue(static fn ($v): bool => \is_array($v) && !isset($v['configurations']))
                    ->then(static function (array $v): array {
                        $excludedKeys = ['default_configuration', 'enabled'];
                        $config = [];

                        foreach ($v as $key => $value) {
                            if (\in_array($key, $excludedKeys)) {
                                continue;
                            }

                            $config[$key] = $value;
                            unset($v[$key]);
                        }

                        $v['default_configuration'] ??= 'default';
                        $v['configurations'] = [$v['default_configuration'] => $config];

                        return $v;
                    })
                ->end()
                ->beforeNormalization()
                    ->ifTrue(static fn ($v): bool => \is_array($v) && isset($v['configurations']) && 1 === \count($v['configurations']) && !isset($v['default_configuration']))
                    ->then(static function (array $v): array {
                        $v['default_configuration'] = key($v['configurations']);

                        return $v;
                    })
                ->end()
                ->beforeNormalization()
                    ->ifTrue(static fn ($v): bool => null === $v)
                    ->then(static fn ($v): array => ['enabled' => false])
                ->end()
                ->validate()
                    ->ifTrue(static fn ($v): bool => isset($v['configurations']) && \count($v['configurations']) > 1 && !isset($v['default_configuration']))
                    ->thenInvalid(\sprintf('You must specify the "%s.default_configuration" if you define more than one configs.', $nodeName))
                ->end()
                ->validate()
                    ->ifTrue(static fn ($v): bool => isset($v['configurations']) && isset($v['default_configuration']) && !isset($v['configurations'][$v['default_configuration']]))
                    ->then(static function (array $v) use ($nodeName): void {
                        throw new InvalidConfigurationException(\sprintf('The specified "siganushka_api_factory.%s.default_configuration" with value "%s" is invalid. Available config are "%s".', $nodeName, $v['default_configuration'], implode('", "', array_keys($v['configurations']))));
                    })
                ->end()
                ->children()
                    ->scalarNode('default_configuration')->end()
                ->end()
        ;
    }

    public static function normalizePackageAlias(string $packageName): string
    {
        $packageName = Container::underscore($packageName);
        $packageName = rtrim($packageName, '-api');

        $paths = explode('/', $packageName);
        $alias = array_pop($paths);

        return str_replace(['-', '.'], '_', $alias);
    }
}
