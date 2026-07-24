<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle;

use Siganushka\ApiFactory\Alipay\Configuration as AlipayConfiguration;
use Siganushka\ApiFactory\Github\Configuration as GithubConfiguration;
use Siganushka\ApiFactory\Wechat\Configuration as WechatConfiguration;
use Siganushka\ApiFactory\Wxpay\Configuration as WxpayConfiguration;
use Siganushka\ApiFactoryBundle\DependencyInjection\Compiler\ResolverConfiguratorPass;
use Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory\GithubAuthenticatorFactory;
use Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory\WechatMiniappAuthenticatorFactory;
use Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory\WechatMpAuthenticatorFactory;
use Siganushka\ApiFactoryBundle\DependencyInjection\Security\Factory\WechatOpenAuthenticatorFactory;
use Siganushka\ApiFactoryBundle\DependencyInjection\SiganushkaApiFactoryExtension;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SiganushkaApiFactoryBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        /** @var SiganushkaApiFactoryExtension */
        $extension = $container->getExtension('siganushka_api_factory');
        $extension->addPackage('siganushka/github-api', GithubConfiguration::class);
        $extension->addPackage('siganushka/wechat-api', WechatConfiguration::class);
        $extension->addPackage('siganushka/wxpay-api', WxpayConfiguration::class);
        $extension->addPackage('siganushka/alipay-api', AlipayConfiguration::class);

        if ($container->hasExtension('security')) {
            /** @var SecurityExtension */
            $security = $container->getExtension('security');
            $security->addAuthenticatorFactory(new GithubAuthenticatorFactory());
            $security->addAuthenticatorFactory(new WechatMpAuthenticatorFactory());
            $security->addAuthenticatorFactory(new WechatOpenAuthenticatorFactory());
            $security->addAuthenticatorFactory(new WechatMiniappAuthenticatorFactory());
        }

        $container->addCompilerPass(new ResolverConfiguratorPass());
    }
}
