<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\Symfony\BillOrderBundle\Controller\Admin\BillOrderCrudController;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;

/**
 * 账单CRUD控制器单元测试
 *
 * @internal
 */
#[CoversClass(BillOrderCrudController::class)]
#[RunTestsInSeparateProcesses]
final class BillOrderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /** @return AbstractCrudController<BillOrder> */
    protected function getControllerService(): AbstractCrudController
    {
        return new BillOrderCrudController();
    }

    protected function getControllerFqcn(): string
    {
        return BillOrderCrudController::class;
    }

    protected function getEntityFqcn(): string
    {
        return BillOrder::class;
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '账单标题' => ['账单标题'];
        yield '状态' => ['状态'];
        yield '总金额' => ['总金额'];
        yield '创建时间' => ['创建时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'title' => ['title'];
        yield 'billNumber' => ['billNumber'];
        yield 'status' => ['status'];
        yield 'totalAmount' => ['totalAmount'];
        yield 'remark' => ['remark'];
        yield 'payTime' => ['payTime'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        yield 'title' => ['title'];
        yield 'billNumber' => ['billNumber'];
        yield 'status' => ['status'];
        yield 'totalAmount' => ['totalAmount'];
        yield 'remark' => ['remark'];
        yield 'payTime' => ['payTime'];
    }

    public function testEntityFqcn(): void
    {
        $this->assertSame(BillOrder::class, BillOrderCrudController::getEntityFqcn());
    }

    public function testControllerIsInstantiable(): void
    {
        $controller = new BillOrderCrudController();
        $this->assertInstanceOf(BillOrderCrudController::class, $controller);
    }

    public function testBasicCrudFunctionality(): void
    {
        $controller = new BillOrderCrudController();

        // 测试基本的CRUD配置方法存在且不抛出异常
        // 如果方法正常执行而不抛出异常，则测试通过
        $controller->configureFields('index');

        // 这个测试验证方法能够正常调用，不返回异常即为成功
        $this->assertTrue(true);
    }
}
