<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Github\Configuration;
use Siganushka\ApiFactory\Github\ConfigurationExtension;
use Siganushka\ApiFactory\Github\OAuth\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GithubAuthenticator extends ApiFactoryAuthenticator
{
    public const AUTHORIZE_OPTIONS = [
        'login' => null,
        'scope' => null,
        'code_challenge' => null,
        'code_challenge_method' => null,
        'allow_signup' => null,
        'prompt' => null,
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
        /** @var array{ access_token: string } */
        $result = $this->client->getAccessToken(compact('code'));
        /** @var array{ login: string } */
        $attributes = $this->client->getUser(['access_token' => $result['access_token']]);

        return [$attributes['login'], $attributes];
    }
}
