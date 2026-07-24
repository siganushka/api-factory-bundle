<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Github\Configuration;
use Siganushka\ApiFactory\Github\ConfigurationExtension;
use Siganushka\ApiFactory\Github\OAuth\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GithubAuthenticator extends ApiFactoryAuthenticator
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
        /** @var array{ access_token: string } */
        $result = $this->client->getAccessToken(compact('code'));
        /** @var array{ login: string } */
        $attributes = $this->client->getUser(['access_token' => $result['access_token']]);

        return [$attributes['login'], $attributes];
    }
}
