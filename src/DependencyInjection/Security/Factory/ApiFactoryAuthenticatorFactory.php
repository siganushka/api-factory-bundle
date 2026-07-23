<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory;

use Siganushka\ApiFactoryBundle\Security\Core\User\NullUserPersister;
use Siganushka\ApiFactoryBundle\Security\Core\User\UserPersisterInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

abstract class ApiFactoryAuthenticatorFactory implements AuthenticatorFactoryInterface
{
    public const DEFAULT_OPTIONS = [
        'check_path' => '/login',
        'success_path' => '/',
        'failure_path' => '/',
        'code_parameter' => 'code',
        'interactive' => true,
    ];

    /**
     * @param array{
     *  check_path?: string,
     *  success_path?: string,
     *  failure_path?: string,
     *  code_parameter?: string,
     *  interactive?: bool
     * } $defaultOptions
     */
    public function __construct(
        private readonly string $authenticatorClass,
        private readonly array $defaultOptions = [])
    {
    }

    public function getPriority(): int
    {
        return -50;
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('user_persister')
                    ->defaultValue(NullUserPersister::class)
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_subclass_of($v, UserPersisterInterface::class, true))
                        ->thenInvalid('The value must be instanceof '.UserPersisterInterface::class.', %s given.')
                    ->end()
                ->end()
                ->scalarNode('check_path')
                    ->defaultValue($this->defaultOptions['check_path'] ?? self::DEFAULT_OPTIONS['check_path'])
                ->end()
                ->scalarNode('success_path')
                    ->defaultValue($this->defaultOptions['success_path'] ?? self::DEFAULT_OPTIONS['success_path'])
                ->end()
                ->scalarNode('failure_path')
                    ->defaultValue($this->defaultOptions['failure_path'] ?? self::DEFAULT_OPTIONS['failure_path'])
                ->end()
                ->scalarNode('code_parameter')
                    ->defaultValue($this->defaultOptions['code_parameter'] ?? self::DEFAULT_OPTIONS['code_parameter'])
                ->end()
                ->booleanNode('interactive')
                    ->defaultValue($this->defaultOptions['interactive'] ?? self::DEFAULT_OPTIONS['interactive'])
                ->end()
            ->end();
    }

    /**
     * @param array{
     *  user_persister: string,
     *  check_path: string,
     *  success_path: string,
     *  failure_path: string,
     *  code_parameter: string,
     *  interactive: bool
     * } $config
     */
    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string|array
    {
        $authenticatorId = \sprintf('security.authenticator.%s.%s', $this->getKey(), $firewallName);
        $options = array_intersect_key($config, self::DEFAULT_OPTIONS);

        $container
            ->setDefinition($authenticatorId, new ChildDefinition($this->authenticatorClass))
            ->addMethodCall('setUserProvider', [new Reference($userProviderId)])
            ->addMethodCall('setUserPersister', [new Reference($config['user_persister'])])
            ->addMethodCall('setOptions', [$options])
        ;

        return $authenticatorId;
    }
}
