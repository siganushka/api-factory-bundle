<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory;

use Siganushka\ApiFactoryBundle\Security\Http\Authenticator\WechatMiniappAuthenticator;

class WechatMiniappAuthenticatorFactory extends ApiFactoryAuthenticatorFactory
{
    public function __construct()
    {
        parent::__construct(
            authenticatorClass: WechatMiniappAuthenticator::class,
            defaultOptions: [
                'check_path' => '/login/wechat/miniapp',
                'code_parameter' => 'jscode',
            ],
        );
    }

    public function getKey(): string
    {
        return 'wechat_miniapp';
    }
}
