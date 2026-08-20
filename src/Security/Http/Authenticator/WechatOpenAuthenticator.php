<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\Configuration;
use Siganushka\ApiFactory\Wechat\ConfigurationExtension;
use Siganushka\ApiFactory\Wechat\OAuth\Qrcode;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WechatOpenAuthenticator extends ApiFactoryAuthenticator
{
    public const AUTHORIZE_OPTIONS = [
        'scope' => null,
    ];

    private readonly Qrcode $client;

    public function __construct(Configuration $configuration, Qrcode $client)
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
