<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle;

use Siganushka\ApiFactory\Alipay\Configuration as AlipayConfiguration;
use Siganushka\ApiFactory\Github\Configuration as GithubConfiguration;
use Siganushka\ApiFactory\Wechat\Configuration as WechatConfiguration;
use Siganushka\ApiFactory\Wxpay\Configuration as WxpayConfiguration;
use Siganushka\ApiFactoryBundle\DependencyInjection\Compiler\ResolverConfiguratorPass;
use Siganushka\ApiFactoryBundle\DependencyInjection\SiganushkaApiFactoryExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SiganushkaApiFactoryBundle extends Bundle
{
    /**
     * @psalm-suppress UndefinedClass
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        /** @var SiganushkaApiFactoryExtension */
        $extension = $container->getExtension('siganushka_api_factory');
        $extension->addPackage('siganushka/github-api', GithubConfiguration::class);
        $extension->addPackage('siganushka/wechat-api', WechatConfiguration::class);
        $extension->addPackage('siganushka/wxpay-api', WxpayConfiguration::class);
        $extension->addPackage('siganushka/alipay-api', AlipayConfiguration::class);

        $container->addCompilerPass(new ResolverConfiguratorPass());
    }
}
