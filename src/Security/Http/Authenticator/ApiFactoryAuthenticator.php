<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactoryBundle\Event\AuthenticationFailureEvent;
use Siganushka\ApiFactoryBundle\Event\AuthenticationSuccessEvent;
use Siganushka\ApiFactoryBundle\Security\Core\User\UserPersisterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\AttributesBasedUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class ApiFactoryAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    use TargetPathTrait;

    #[Required]
    public EventDispatcherInterface $eventDispatcher;

    #[Required]
    public CsrfTokenManagerInterface $csrfTokenManager;

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
        if ($this->options['state_enabled']) {
            $state = $this->getRequestParameter($request, $this->options['state_parameter']);
            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(static::class, $state))) {
                throw new BadCredentialsException(\sprintf('The %s is invalid.', $this->options['state_parameter']));
            }
        }

        $code = $this->getRequestParameter($request, $this->options['code_parameter']);

        try {
            [$userIdentifier, $attributes] = $this->createUserAttributes($code);
        } catch (\Throwable $th) {
            throw new BadCredentialsException(\sprintf('The %s is invalid.', $this->options['code_parameter']), 0, $th);
        }

        return new SelfValidatingPassport(new UserBadge($userIdentifier, $this->createUserLoader(...), $attributes));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $response = $this->createAuthenticationSuccessResponse($request, $token, $firewallName);

        $event = new AuthenticationSuccessEvent($request, $response, $token);
        $this->eventDispatcher->dispatch($event, AuthenticationSuccessEvent::getName(static::class));

        return $event->getResponse();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $response = $this->createAuthenticationFailureResponse($request, $exception);

        $event = new AuthenticationFailureEvent($request, $response, $exception);
        $this->eventDispatcher->dispatch($event, AuthenticationFailureEvent::getName(static::class));

        return $event->getResponse();
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $redirect = $this->httpUtils->generateUri($request, $this->options['check_path']);
        $response = $this->createEntryPointResponse($redirect);

        if ($response instanceof RedirectResponse && $this->options['state_enabled']) {
            $qs = \sprintf('%s=', $this->options['state_parameter']);
            if (!preg_match('/[?&]'.preg_quote($qs, '/').'/', $targetUrl = $response->getTargetUrl())) {
                $response->setTargetUrl($targetUrl .= (str_contains($targetUrl, '?') ? '&' : '?').$qs.$this->csrfTokenManager->getToken(static::class)->getValue());
            }
        }

        return $response;
    }

    public function isInteractive(): bool
    {
        return true;
    }

    protected function createUserLoader(string $userIdentifier, array $attributes): ?UserInterface
    {
        try {
            return $this->userProvider instanceof AttributesBasedUserProviderInterface
                ? $this->userProvider->loadUserByIdentifier($userIdentifier, $attributes)
                : $this->userProvider->loadUserByIdentifier($userIdentifier);
        } catch (UserNotFoundException) {
            return $this->userPersister->persist($userIdentifier, $attributes);
        }
    }

    protected function createAuthenticationSuccessResponse(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        return $this->httpUtils->createRedirectResponse($request, $targetPath ?? $this->options['success_path']);
    }

    protected function createAuthenticationFailureResponse(Request $request, AuthenticationException $exception): Response
    {
        $session = $request->getSession();
        $session->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return $this->httpUtils->createRedirectResponse($request, $this->options['failure_path']);
    }

    protected function getRequestParameter(Request $request, string $key): string
    {
        if ($request->query->has($key)) {
            return $request->query->getString($key);
        }

        if ($request->request->has($key)) {
            return $request->request->getString($key);
        }

        if ($request->getPayload()->has($key)) {
            return $request->getPayload()->getString($key);
        }

        throw new BadCredentialsException(\sprintf('The %s not found.', $key));
    }

    /**
     * Creates entry point response.
     *
     * @param string $redirectUri The redirect uri generated by "check_path"
     *
     * @return Response The entry point response
     */
    abstract protected function createEntryPointResponse(string $redirectUri): Response;

    /**
     * Creates user identifier and attributes by authentication code.
     *
     * @param string $code The authentication code
     *
     * @return array{ 0: string, 1: array } The user identifier and attributes
     */
    abstract protected function createUserAttributes(string $code): array;
}
