<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WechatJscodeAuthenticationSuccessEvent extends WechatJscodeAuthenticationEvent
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
}
