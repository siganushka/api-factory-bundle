<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class WechatJscodeAuthenticationFailureEvent extends WechatJscodeAuthenticationEvent
{
    public function __construct(
        Request $request,
        Response $response,
        private readonly AuthenticationException $exception)
    {
        parent::__construct($request, $response);
    }

    public function getException(): AuthenticationException
    {
        return $this->exception;
    }
}
