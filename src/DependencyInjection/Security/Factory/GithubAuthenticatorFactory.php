<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory;

use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\GithubAuthenticator;

class GithubAuthenticatorFactory extends ApiFactoryAuthenticatorFactory
{
    public function __construct()
    {
        parent::__construct(
            authenticatorClass: GithubAuthenticator::class,
            defaultOptions: [
                'check_path' => '/github/oauth',
            ],
        );
    }

    public function getKey(): string
    {
        return 'github';
    }
}
