<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\OAuth\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WechatMpAuthenticator extends ApiFactoryAuthenticator
{
    public function __construct(private readonly Client $client)
    {
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
