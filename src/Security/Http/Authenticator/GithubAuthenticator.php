<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Http\Authenticator;

use Siganushka\ApiFactory\Github\OAuth\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GithubAuthenticator extends ApiFactoryAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly Client $client)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $redirectUri = $this->httpUtils->generateUri($request, $this->options['check_path']);
        $authorizeUrl = $this->client->getRedirectUrl(['redirect_uri' => $redirectUri]);

        return new RedirectResponse($authorizeUrl);
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
