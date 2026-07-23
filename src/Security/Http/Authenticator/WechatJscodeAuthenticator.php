<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\Miniapp\SessionKey;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class WechatJscodeAuthenticator extends ApiFactoryAuthenticator
{
    public function __construct(private readonly SessionKey $sessionKey)
    {
    }

    protected function resolveUserIdentifier(string $code): array
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
        $code = JsonResponse::HTTP_UNAUTHORIZED;
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new JsonResponse(compact('code', 'message'), $code, ['WWW-Authenticate' => 'Bearer']);
    }
}
