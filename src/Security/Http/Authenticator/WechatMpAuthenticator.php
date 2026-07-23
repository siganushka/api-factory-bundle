<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Wechat\OAuth\Client;

class WechatMpAuthenticator extends ApiFactoryAuthenticator
{
    public function __construct(private readonly Client $client)
    {
    }

    protected function resolveUserIdentifier(string $code): array
    {
        /** @var array{ unionid: string } */
        $attributes = $this->client->getAccessToken(compact('code'));

        return [$attributes['unionid'], $attributes];
    }
}
