<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Psr\EventDispatcher\EventDispatcherInterface;
use Siganushka\ApiFactory\Wechat\Miniapp\SessionKey;
use Siganushka\ApiFactoryBundle\Event\WechatJscodeAuthenticationFailureEvent;
use Siganushka\ApiFactoryBundle\Event\WechatJscodeAuthenticationSuccessEvent;
use Siganushka\ApiFactoryBundle\Security\Core\User\UserPersisterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\AttributesBasedUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

class WechatJscodeAuthenticator extends AbstractAuthenticator implements InteractiveAuthenticatorInterface
{
    private readonly array $options;

    /**
     * @param UserProviderInterface<UserInterface>       $userProvider
     * @param UserPersisterInterface<UserInterface>|null $userPersister
     */
    public function __construct(
        private readonly HttpUtils $httpUtils,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserProviderInterface $userProvider,
        private readonly ?UserPersisterInterface $userPersister,
        private readonly SessionKey $sessionKey,
        array $options = [])
    {
        $this->options = array_merge([
            'check_path' => '/wechat/jscode',
            'jscode_parameter' => 'jscode',
        ], $options);
    }

    public function supports(Request $request): ?bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }

    public function authenticate(Request $request): Passport
    {
        $code = $request->query->get($this->options['jscode_parameter'])
            ?? $request->getPayload()->getString($this->options['jscode_parameter']);

        if (empty($code)) {
            throw new BadCredentialsException('The jscode not found.');
        }

        try {
            /** @var array{ session_key: string, openid: string, unionid: string } */
            $result = $this->sessionKey->send(compact('code'));
        } catch (\Throwable $th) {
            throw new BadCredentialsException('The jscode is invalid.', 0, $th);
        }

        $arguments = $this->userProvider instanceof AttributesBasedUserProviderInterface
            ? [$result['unionid'], $result]
            : [$result['unionid']];

        try {
            $authorizedUser = $this->userProvider->loadUserByIdentifier(...$arguments);
        } catch (UserNotFoundException $th) {
            if (!$authorizedUser = $this->userPersister?->persist($result)) {
                throw $th;
            }
        }

        return new SelfValidatingPassport(new UserBadge($authorizedUser->getUserIdentifier()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $identifier = $token->getUser()?->getUserIdentifier();

        $response = new JsonResponse(compact('identifier'));

        $event = new WechatJscodeAuthenticationSuccessEvent($request, $response, $token);
        $this->eventDispatcher->dispatch($event);

        return $event->getResponse();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $code = JsonResponse::HTTP_UNAUTHORIZED;
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        $response = new JsonResponse(compact('code', 'message'), $code);
        $response->headers->set('WWW-Authenticate', 'Bearer');

        $event = new WechatJscodeAuthenticationFailureEvent($request, $response, $exception);
        $this->eventDispatcher->dispatch($event);

        return $event->getResponse();
    }

    public function isInteractive(): bool
    {
        return true;
    }
}
