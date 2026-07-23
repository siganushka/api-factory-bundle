<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory\ApiFactoryAuthenticatorFactory;
use Siganushka\ApiFactoryBundle\Event\AuthenticationFailureEvent;
use Siganushka\ApiFactoryBundle\Event\AuthenticationSuccessEvent;
use Siganushka\ApiFactoryBundle\Security\Core\User\UserPersisterInterface;
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
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class ApiFactoryAuthenticator extends AbstractAuthenticator implements InteractiveAuthenticatorInterface
{
    use TargetPathTrait;

    #[Required]
    public EventDispatcherInterface $eventDispatcher;

    #[Required]
    public HttpUtils $httpUtils;

    /**
     * @var UserProviderInterface<UserInterface>
     */
    protected UserProviderInterface $userProvider;

    /**
     * @var UserPersisterInterface<UserInterface>
     */
    protected UserPersisterInterface $userPersister;

    /**
     * @var array{
     *  check_path: string,
     *  success_path: string,
     *  failure_path: string,
     *  code_parameter: string,
     *  interactive: bool
     * }
     */
    protected array $options = ApiFactoryAuthenticatorFactory::DEFAULT_OPTIONS;

    /**
     * @param UserProviderInterface<UserInterface> $userProvider
     */
    public function setUserProvider(UserProviderInterface $userProvider): void
    {
        $this->userProvider = $userProvider;
    }

    /**
     * @param UserPersisterInterface<UserInterface> $userPersister
     */
    public function setUserPersister(UserPersisterInterface $userPersister): void
    {
        $this->userPersister = $userPersister;
    }

    public function setOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }

    public function supports(Request $request): ?bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }

    public function authenticate(Request $request): Passport
    {
        $code = $request->query->get($this->options['code_parameter'])
            ?? $request->getPayload()->getString($this->options['code_parameter']);

        if (!$code) {
            throw new BadCredentialsException(\sprintf('The %s not found.', $this->options['code_parameter']));
        }

        try {
            [$userIdentifier, $attributes] = $this->resolveUserIdentifier($code);
        } catch (\Throwable $th) {
            throw new BadCredentialsException(\sprintf('The %s is invalid.', $this->options['code_parameter']), 0, $th);
        }

        return new SelfValidatingPassport(new UserBadge($userIdentifier, $this->createUserLoader(...), $attributes));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $response = $this->createAuthenticationSuccessResponse($request, $token, $firewallName);

        $event = new AuthenticationSuccessEvent($request, $response, $token);
        $this->eventDispatcher->dispatch($event, AuthenticationSuccessEvent::getAuthenticator(static::class));

        return $event->getResponse();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $response = $this->createAuthenticationFailureResponse($request, $exception);

        $event = new AuthenticationFailureEvent($request, $response, $exception);
        $this->eventDispatcher->dispatch($event, AuthenticationFailureEvent::getAuthenticator(static::class));

        return $event->getResponse();
    }

    public function isInteractive(): bool
    {
        return $this->options['interactive'];
    }

    protected function createUserLoader(string $userIdentifier, array $attributes): ?UserInterface
    {
        $arguments = $this->userProvider instanceof AttributesBasedUserProviderInterface
            ? [$userIdentifier, $attributes]
            : [$userIdentifier];

        try {
            return $this->userProvider->loadUserByIdentifier(...$arguments);
        } catch (UserNotFoundException) {
            return $this->userPersister->persist($userIdentifier, $attributes);
        }
    }

    protected function createAuthenticationSuccessResponse(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName)
            ?? $this->httpUtils->generateUri($request, $this->options['success_path']);

        return $this->httpUtils->createRedirectResponse($request, $targetPath);
    }

    protected function createAuthenticationFailureResponse(Request $request, AuthenticationException $exception): Response
    {
        $session = $request->getSession();
        $session->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return $this->httpUtils->createRedirectResponse($request, $this->options['failure_path']);
    }

    /**
     * @return array{ 0: string, 1: array }
     */
    abstract protected function resolveUserIdentifier(string $code): array;
}
