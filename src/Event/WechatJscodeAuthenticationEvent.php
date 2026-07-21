<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class WechatJscodeAuthenticationEvent extends Event
{
    public function __construct(
        private readonly Request $request,
        private Response $response)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): static
    {
        $this->response = $response;

        return $this;
    }
}
