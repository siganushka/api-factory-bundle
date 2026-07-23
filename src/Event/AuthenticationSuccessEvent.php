<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessEvent extends AuthenticationEvent
{
    public function __construct(
        Request $request,
        Response $response,
        private readonly TokenInterface $token)
    {
        parent::__construct($request, $response);
    }

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public static function getAuthenticator(string $authenticator): string
    {
        return \sprintf('%s.authentication_success', $authenticator);
    }
}
