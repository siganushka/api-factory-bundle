<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\Miniapp\SessionKey;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class WechatMiniappAuthenticator extends ApiFactoryAuthenticator
{
    public function __construct(private readonly SessionKey $sessionKey)
    {
    }

    protected function createEntryPointResponse(string $redirectUri): Response
    {
        return $this->createHttpUnauthorizedResponse(\sprintf('The %s not found.', $this->options['code_parameter']));
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
        return $this->createHttpUnauthorizedResponse(strtr($exception->getMessageKey(), $exception->getMessageData()));
    }

    protected function createHttpUnauthorizedResponse(string $message, int $code = JsonResponse::HTTP_UNAUTHORIZED): Response
    {
        return new JsonResponse(compact('code', 'message'), $code, ['WWW-Authenticate' => 'Bearer']);
    }
}
