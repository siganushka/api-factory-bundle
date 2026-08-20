<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory;

use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\GithubAuthenticator;

class GithubAuthenticatorFactory extends ApiFactoryAuthenticatorFactory
{
    public function __construct()
    {
        parent::__construct(
            authenticator: GithubAuthenticator::class,
            options: array_merge([
                'check_path' => '/login/github',
            ], GithubAuthenticator::AUTHORIZE_OPTIONS),
        );
    }

    public function getKey(): string
    {
        return 'github';
    }
}
