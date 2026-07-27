<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\Configuration;
use Siganushka\ApiFactory\Wechat\ConfigurationExtension;
use Siganushka\ApiFactory\Wechat\Miniapp\SessionKey;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class WechatMiniappAuthenticator extends ApiFactoryAuthenticator
{
    private readonly SessionKey $sessionKey;

    public function __construct(Configuration $configuration, SessionKey $sessionKey)
    {
        $this->sessionKey = $sessionKey->extend(new ConfigurationExtension($configuration));
    }

    protected function createUserAttributes(string $code): array
    {
        /** @var array{ unionid: string } */
        $attributes = $this->sessionKey->send(compact('code'));

        return [$attributes['unionid'], $attributes];
    }

    protected function createAuthenticationSuccessResponse(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $identifier = $token->getUser()?->getUserIdentifier();

        return new JsonResponse(compact('identifier'));
    }

    protected function createAuthenticationFailureResponse(Request $request, AuthenticationException $exception): Response
    {
        $error = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new JsonResponse(compact('error'), JsonResponse::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => 'Bearer']);
    }

    protected function createEntryPointResponse(string $redirectUri): Response
    {
        $error = \sprintf('The %s not found.', $this->options['code_parameter']);

        return new JsonResponse(compact('error'), JsonResponse::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => 'Bearer']);
    }
}
