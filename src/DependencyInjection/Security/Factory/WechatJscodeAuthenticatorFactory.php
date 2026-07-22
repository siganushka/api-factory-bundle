<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory;

use Siganushka\ApiFactoryBundle\Security\Core\User\UserPersisterInterface;
use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\WechatJscodeAuthenticator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

class WechatJscodeAuthenticatorFactory implements AuthenticatorFactoryInterface
{
    public function getPriority(): int
    {
        return -50;
    }

    public function getKey(): string
    {
        return 'wechat_jscode';
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('user_persister')
                    ->defaultNull()
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_subclass_of($v, UserPersisterInterface::class, true))
                        ->thenInvalid('The value must be instanceof '.UserPersisterInterface::class.', %s given.')
                    ->end()
                ->end()
                ->scalarNode('check_path')
                    ->defaultValue('/wechat/jscode')
                ->end()
                ->scalarNode('jscode_parameter')
                    ->defaultValue('jscode')
                ->end()
                ->booleanNode('interactive')
                    ->defaultTrue()
                ->end()
            ->end();
    }

    /**
     * @param array{
     *  user_persister: string|null,
     *  check_path: string,
     *  jscode_parameter: string,
     *  interactive: bool,
     * } $config
     */
    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string|array
    {
        $authenticatorId = \sprintf('security.authenticator.wechat_jscode.%s', $firewallName);

        $container
            ->setDefinition($authenticatorId, new ChildDefinition(WechatJscodeAuthenticator::class))
            ->replaceArgument('$userProvider', new Reference($userProviderId))
            ->replaceArgument('$userPersister', $config['user_persister'] ? new Reference($config['user_persister'], ContainerInterface::NULL_ON_INVALID_REFERENCE) : null)
            ->replaceArgument('$options', [
                'check_path' => $config['check_path'],
                'jscode_parameter' => $config['jscode_parameter'],
                'interactive' => $config['interactive'],
            ])
        ;

        return $authenticatorId;
    }
}
