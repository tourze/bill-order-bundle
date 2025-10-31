<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\Symfony\BillOrderBundle\DependencyInjection\BillOrderExtension;

/**
 * @internal
 */
#[CoversClass(BillOrderExtension::class)]
final class BillOrderExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private BillOrderExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new BillOrderExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoadSetsAutoconfigureAndAutowire(): void
    {
        $this->extension->load([], $this->container);

        $definition = $this->container->getDefinition('Tourze\Symfony\BillOrderBundle\Service\BillOrderService');
        $this->assertTrue($definition->isAutoconfigured());
        $this->assertTrue($definition->isAutowired());
    }

    public function testLoadTagsCommands(): void
    {
        $this->extension->load([], $this->container);

        $cleanupCommand = $this->container->getDefinition('Tourze\Symfony\BillOrderBundle\Command\BillCleanupCommand');
        $this->assertTrue($cleanupCommand->hasTag('console.command'));

        $statisticsCommand = $this->container->getDefinition('Tourze\Symfony\BillOrderBundle\Command\BillStatisticsCommand');
        $this->assertTrue($statisticsCommand->hasTag('console.command'));
    }

    public function testLoadRegistersRepositoriesAsServices(): void
    {
        $this->extension->load([], $this->container);

        $billOrderRepo = $this->container->getDefinition('Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository');
        $this->assertTrue($billOrderRepo->hasTag('doctrine.repository_service'));

        $billItemRepo = $this->container->getDefinition('Tourze\Symfony\BillOrderBundle\Repository\BillItemRepository');
        $this->assertTrue($billItemRepo->hasTag('doctrine.repository_service'));
    }

    public function testLoadWithEmptyConfig(): void
    {
        $this->extension->load([], $this->container);

        // 应该正常加载，没有异常
        $this->assertTrue($this->container->hasDefinition('Tourze\Symfony\BillOrderBundle\Service\BillOrderService'));
    }

    public function testLoadMultipleTimes(): void
    {
        // 多次加载不应该产生问题
        $this->extension->load([], $this->container);
        $this->extension->load([], $this->container);

        // 服务应该仍然存在
        $this->assertTrue($this->container->hasDefinition('Tourze\Symfony\BillOrderBundle\Service\BillOrderService'));
    }
}
