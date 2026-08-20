<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\Configuration;
use Siganushka\ApiFactory\Wechat\ConfigurationExtension;
use Siganushka\ApiFactory\Wechat\OAuth\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WechatMpAuthenticator extends ApiFactoryAuthenticator
{
    public const AUTHORIZE_OPTIONS = [
        'scope' => null,
    ];

    private readonly Client $client;

    public function __construct(Configuration $configuration, Client $client)
    {
        $this->client = $client->extend(new ConfigurationExtension($configuration));
    }

    protected function createEntryPointResponse(string $redirectUri): RedirectResponse
    {
        $options = [
            'redirect_uri' => $redirectUri,
            ...array_intersect_key($this->options, self::AUTHORIZE_OPTIONS),
        ];

        $authorizeUrl = $this->client->getRedirectUrl($options);

        return new RedirectResponse($authorizeUrl);
    }

    protected function createUserAttributes(string $code): array
    {
        /** @var array{ openid: string } */
        $attributes = $this->client->getAccessToken(compact('code'));

        return [$attributes['openid'], $attributes];
    }
}
