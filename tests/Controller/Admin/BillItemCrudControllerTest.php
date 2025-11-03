<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\Symfony\BillOrderBundle\Controller\Admin\BillItemCrudController;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;

/**
 * 账单明细CRUD控制器单元测试
 *
 * @internal
 */
#[CoversClass(BillItemCrudController::class)]
#[RunTestsInSeparateProcesses]
final class BillItemCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /** @return AbstractCrudController<BillItem> */
    protected function getControllerService(): AbstractCrudController
    {
        return new BillItemCrudController();
    }

    protected function getControllerFqcn(): string
    {
        return BillItemCrudController::class;
    }

    protected function getEntityFqcn(): string
    {
        return BillItem::class;
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '所属账单' => ['所属账单'];
        yield '产品ID' => ['产品ID'];
        yield '产品名称' => ['产品名称'];
        yield '状态' => ['状态'];
        yield '单价' => ['单价'];
        yield '数量' => ['数量'];
        yield '小计' => ['小计'];
        yield '创建时间' => ['创建时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'bill' => ['bill'];
        yield 'productId' => ['productId'];
        yield 'productName' => ['productName'];
        yield 'status' => ['status'];
        yield 'price' => ['price'];
        yield 'quantity' => ['quantity'];
        yield 'remark' => ['remark'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        // 'bill' 字段在编辑时隐藏，因为不应该修改账单明细的所属账单
        yield 'productId' => ['productId'];
        yield 'productName' => ['productName'];
        yield 'status' => ['status'];
        yield 'price' => ['price'];
        yield 'quantity' => ['quantity'];
        yield 'remark' => ['remark'];
    }

    public function testEntityFqcn(): void
    {
        $this->assertSame(BillItem::class, BillItemCrudController::getEntityFqcn());
    }

    public function testControllerIsInstantiable(): void
    {
        $controller = new BillItemCrudController();
        $this->assertInstanceOf(BillItemCrudController::class, $controller);
    }

    public function testBasicCrudFunctionality(): void
    {
        $controller = new BillItemCrudController();

        // 测试基本的CRUD配置方法存在且不抛出异常
        // 如果方法正常执行而不抛出异常，则测试通过
        $controller->configureFields('index');

        // 这个测试验证方法能够正常调用，不返回异常即为成功
        $this->assertTrue(true);
    }
}
