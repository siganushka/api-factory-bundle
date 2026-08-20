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
    /**
     * @var array{
     *  check_path: string,
     *  success_path: string,
     *  failure_path: string,
     *  code_parameter: string,
     *  state_parameter: string,
     *  state_enabled: bool,
     *  ...
     * }
     */
    protected array $options = [
        'check_path' => '/login',
        'success_path' => '/',
        'failure_path' => '/',
        'code_parameter' => 'code',
        'state_parameter' => 'state',
        'state_enabled' => true,
    ];

    /**
     * @param array{
     *  check_path?: string,
     *  success_path?: string,
     *  failure_path?: string,
     *  code_parameter?: string,
     *  state_parameter?: string,
     *  state_enabled?: bool,
     *  ...
     * } $options
     */
    public function __construct(private readonly string $authenticator, array $options = [])
    {
        $this->options = array_merge($this->options, $options);
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
        $builder = $node->children();

        $builder
            ->scalarNode('user_persister')
                ->defaultValue(NullUserPersister::class)
                ->validate()
                    ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_subclass_of($v, UserPersisterInterface::class, true))
                    ->thenInvalid('The value must be instanceof '.UserPersisterInterface::class.', %s given.')
                ->end()
            ->end()
            ->scalarNode('configuration')
                ->defaultNull()
            ->end()
        ;

        foreach ($this->options as $name => $default) {
            if (\is_bool($default)) {
                $builder->booleanNode($name)->defaultValue($default);
            } else {
                $builder->scalarNode($name)->defaultValue($default);
            }
        }
    }

    /**
     * @param array{ user_persister: string, configuration: string|null, ... } $config
     */
    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string|array
    {
        $authenticatorId = \sprintf('security.authenticator.%s.%s', $this->getKey(), $firewallName);

        $authenticatorDef = $container->setDefinition($authenticatorId, new ChildDefinition($this->authenticator));
        $authenticatorDef
            ->addMethodCall('setUserProvider', [new Reference($userProviderId)])
            ->addMethodCall('setUserPersister', [new Reference($config['user_persister'])])
            ->addMethodCall('setOptions', [array_intersect_key(array_filter($config, static fn ($v) => null !== $v), $this->options)])
        ;

        if ($config['configuration']) {
            $authenticatorDef->replaceArgument('$configuration', new Reference($config['configuration']));
        }

        return $authenticatorId;
    }
}
