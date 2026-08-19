<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection;

use Siganushka\ApiFactory\ResolverConfigurator;
use Siganushka\ApiFactory\ResolverConfiguratorInterface;
use Siganushka\ApiFactory\ResolverExtensionInterface;
use Siganushka\ApiFactory\ResolverInterface;
use Siganushka\ApiFactoryBundle\DependencyInjection\Compiler\ResolverConfiguratorPass;
use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\GithubAuthenticator;
use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\WechatMiniappAuthenticator;
use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\WechatMpAuthenticator;
use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\WechatOpenAuthenticator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SiganushkaApiFactoryExtension extends Extension
{
    private array $packages = [];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($this->getAvailablePackages() as $packageAlias => $configurationClass) {
            if (!$this->isConfigEnabled($container, $config[$packageAlias])) {
                continue;
            }

            $defaultConfigurationId = \sprintf('siganushka_api_factory.%s.configuration', $packageAlias);
            foreach ($config[$packageAlias]['configurations'] as $configName => $configValue) {
                $configurationId = \sprintf('%s_%s', $defaultConfigurationId, $configName);

                $container->register($configurationId, $configurationClass)
                    ->setArgument(0, $configValue)
                    ->addTag($defaultConfigurationId)
                ;

                $container->registerAliasForArgument($configurationId, $configurationClass, \sprintf('%sConfiguration', $configName));
                if ($config[$packageAlias]['default_configuration'] === $configName) {
                    $container->setAlias($defaultConfigurationId, $configurationId);
                    $container->setAlias($configurationClass, $configurationId);
                }
            }

            $ref = new \ReflectionClass($configurationClass);
            $fileName = $ref->getFileName();

            if ($fileName && is_file($services = \dirname($fileName).'/../config/services.php')) {
                $loader->load($services);
            }
        }

        $authenticators = [
            GithubAuthenticator::class,
            WechatMpAuthenticator::class,
            WechatOpenAuthenticator::class,
            WechatMiniappAuthenticator::class,
        ];

        array_walk($authenticators, static fn (string $id) => $container->findDefinition($id)->setAbstract(true));

        if (!class_exists(SecurityBundle::class)) {
            array_walk($authenticators, static fn (string $id) => $container->removeDefinition($id));
        }

        $container->registerForAutoconfiguration(ResolverInterface::class)
            ->addTag(ResolverConfiguratorPass::RESOLVER_TAG)
        ;

        $container->registerForAutoconfiguration(ResolverExtensionInterface::class)
            ->addTag(ResolverConfiguratorPass::RESOLVER_EXTENSION_TAG)
        ;

        $container->register(ResolverConfigurator::class)
            ->setArgument(0, new TaggedIteratorArgument(ResolverConfiguratorPass::RESOLVER_EXTENSION_TAG))
        ;

        $container->setAlias(ResolverConfiguratorInterface::class, ResolverConfigurator::class);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration($this->getAvailablePackages());
    }

    public function addPackage(string $packageAlias, string $configurationClass): void
    {
        $this->packages[$packageAlias] = $configurationClass;
    }

    public function getPackages(): array
    {
        return $this->packages;
    }

    public function getAvailablePackages(): array
    {
        return array_filter($this->packages, static fn (string $configurationClass) => class_exists($configurationClass));
    }
}
