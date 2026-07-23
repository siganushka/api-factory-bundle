<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Github\OAuth\Client;

class GithubAuthenticator extends ApiFactoryAuthenticator
{
    public function __construct(private readonly Client $client)
    {
    }

    protected function resolveUserIdentifier(string $code): array
    {
        /** @var array{ access_token: string } */
        $result = $this->client->getAccessToken(compact('code'));
        /** @var array{ login: string } */
        $attributes = $this->client->getUser(['access_token' => $result['access_token']]);

        return [$attributes['login'], $attributes];
    }
}
