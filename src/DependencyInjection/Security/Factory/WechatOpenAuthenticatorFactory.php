<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory;

use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\WechatOpenAuthenticator;

class WechatOpenAuthenticatorFactory extends ApiFactoryAuthenticatorFactory
{
    public function __construct()
    {
        parent::__construct(
            authenticatorClass: WechatOpenAuthenticator::class,
            defaultOptions: [
                'check_path' => '/wechat/open',
            ],
        );
    }

    public function getKey(): string
    {
        return 'wechat_open';
    }
}
