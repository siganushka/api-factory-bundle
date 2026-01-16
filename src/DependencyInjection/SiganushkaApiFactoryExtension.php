<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection;

use Composer\InstalledVersions;
use Siganushka\ApiFactory\ResolverExtensionInterface;
use Siganushka\ApiFactory\ResolverInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
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

        foreach ($this->getAvailablePackages() as $packageName => $configurationClass) {
            $packageAlias = Configuration::normalizePackageAlias($packageName);
            if ($this->isConfigEnabled($container, $config[$packageAlias])) {
                $defaultConfigurationId = \sprintf('siganushka_api_factory.%s.configuration', $packageAlias);
                foreach ($config[$packageAlias]['configurations'] as $configName => $configValue) {
                    $configurationId = \sprintf('%s_%s', $defaultConfigurationId, $configName);

                    $container->register($configurationId, $configurationClass)->setArgument(0, $configValue);
                    $container->registerAliasForArgument($configurationId, $configurationClass, \sprintf('%sConfiguration', $configName));

                    if ($config[$packageAlias]['default_configuration'] === $configName) {
                        $container->setAlias($defaultConfigurationId, $configurationId);
                        $container->setAlias($configurationClass, $configurationId);
                    }
                }

                try {
                    $installPath = InstalledVersions::getInstallPath($packageName);
                } catch (\Throwable) {
                    continue;
                }

                if ($installPath && is_file($services = $installPath.'/config/services.php')) {
                    $loader->load($services);
                }
            }
        }

        $container->registerForAutoconfiguration(ResolverInterface::class)
            ->addTag('siganushka_api_factory.resolver')
        ;

        $container->registerForAutoconfiguration(ResolverExtensionInterface::class)
            ->addTag('siganushka_api_factory.resolver_extension')
        ;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration($this->getAvailablePackages());
    }

    public function addPackage(string $packageName, string $configurationClass): void
    {
        $this->packages[$packageName] = $configurationClass;
    }

    public function getPackages(): array
    {
        return $this->packages;
    }

    public function getAvailablePackages(): array
    {
        return array_filter($this->packages, fn (string $packageName) => ContainerBuilder::willBeAvailable($packageName, $this->packages[$packageName], ['siganushka/api-factory-bundle']), \ARRAY_FILTER_USE_KEY);
    }
}
