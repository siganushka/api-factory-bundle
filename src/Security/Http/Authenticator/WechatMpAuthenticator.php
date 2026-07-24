<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\Configuration;
use Siganushka\ApiFactory\Wechat\ConfigurationExtension;
use Siganushka\ApiFactory\Wechat\OAuth\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WechatMpAuthenticator extends ApiFactoryAuthenticator
{
    private readonly Client $client;

    public function __construct(Configuration $configuration, Client $client)
    {
        $this->client = $client->extend(new ConfigurationExtension($configuration));
    }

    protected function createEntryPointResponse(string $redirectUri): RedirectResponse
    {
        $authorizeUrl = $this->client->getRedirectUrl(['redirect_uri' => $redirectUri]);

        return new RedirectResponse($authorizeUrl);
    }

    protected function createUserAttributes(string $code): array
    {
        /** @var array{ unionid: string } */
        $attributes = $this->client->getAccessToken(compact('code'));

        return [$attributes['unionid'], $attributes];
    }
}
