<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory;

use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\WechatMpAuthenticator;

class WechatMpAuthenticatorFactory extends ApiFactoryAuthenticatorFactory
{
    public function __construct()
    {
        parent::__construct(
            authenticatorClass: WechatMpAuthenticator::class,
            defaultOptions: [
                'check_path' => '/login/wechat/mp',
            ],
        );
    }

    public function getKey(): string
    {
        return 'wechat_mp';
    }
}
