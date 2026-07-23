<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\OAuth\Qrcode;

class WechatOpenAuthenticator extends ApiFactoryAuthenticator
{
    public function __construct(private readonly Qrcode $client)
    {
    }

    protected function resolveUserIdentifier(string $code): array
    {
        /** @var array{ unionid: string } */
        $attributes = $this->client->getAccessToken(compact('code'));

        return [$attributes['unionid'], $attributes];
    }
}
