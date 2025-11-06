<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Exception\EmptyBillException;
use Tourze\Symfony\BillOrderBundle\Exception\InvalidBillStatusException;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

/**
 * 账单完整生命周期集成测试
 *
 * @internal
 */
#[CoversClass(BillOrderService::class)]
#[RunTestsInSeparateProcesses]
final class BillOrderLifecycleIntegrationTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    private function getBillOrderService(): BillOrderService
    {
        return self::getService(BillOrderService::class);
    }

    /**
     * 测试账单的完整生命周期：创建→添加项目→提交→支付→完成
     */
    public function testCompleteBillLifecycle(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 1. 创建账单
        $bill = $billOrderService->createBill('完整生命周期测试账单', '这是一个完整生命周期的测试账单');
        $this->assertEquals(BillOrderStatus::DRAFT, $bill->getStatus());
        $this->assertNotNull($bill->getBillNumber());
        $this->assertNotNull($bill->getId());
        $this->assertNull($bill->getPayTime());

        // 2. 添加多个账单项目
        $items = [];
        $items[] = $billOrderService->addBillItem($bill, 'PROD001', '产品A', '100.00', 2, '产品A备注');
        $items[] = $billOrderService->addBillItem($bill, 'PROD002', '产品B', '50.50', 3, '产品B备注');
        $items[] = $billOrderService->addBillItem($bill, 'PROD003', '产品C', '25.25', 1, '产品C备注');

        // 验证项目状态
        foreach ($items as $item) {
            $this->assertEquals(BillItemStatus::PENDING, $item->getStatus());
            $this->assertSame($bill, $item->getBill());
        }

        // 验证账单总金额计算
        $expectedTotal = '376.75'; // (100.00 * 2) + (50.50 * 3) + (25.25 * 1)
        $this->assertEquals($expectedTotal, $bill->getTotalAmount());
        $this->assertCount(3, $bill->getItems());

        // 3. 提交账单
        $submittedBill = $billOrderService->submitBill($bill);
        $this->assertSame($bill, $submittedBill);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());

        // 验证日志记录
        $this->assertNotNull($bill->getUpdateTime());

        // 4. 支付账单
        $paidBill = $billOrderService->payBill($bill);
        $this->assertSame($bill, $paidBill);
        $this->assertEquals(BillOrderStatus::PAID, $bill->getStatus());
        $this->assertNotNull($bill->getPayTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $bill->getPayTime());

        // 验证支付时间是否合理（最近一分钟内）
        $now = new \DateTimeImmutable();
        $timeDiff = $now->getTimestamp() - $bill->getPayTime()->getTimestamp();
        $this->assertLessThan(60, $timeDiff, '支付时间应该是当前时间');

        // 5. 完成账单
        $completedBill = $billOrderService->completeBill($bill);
        $this->assertSame($bill, $completedBill);
        $this->assertEquals(BillOrderStatus::COMPLETED, $bill->getStatus());

        // 验证所有项目状态变为已处理
        foreach ($bill->getItems() as $item) {
            $this->assertEquals(BillItemStatus::PROCESSED, $item->getStatus());
        }

        // 验证最终状态
        $this->assertEquals($expectedTotal, $bill->getTotalAmount());
        $this->assertEquals('完整生命周期测试账单', $bill->getTitle());
        $this->assertStringContainsString('这是一个完整生命周期的测试账单', $bill->getRemark());
    }

    /**
     * 测试账单的取消生命周期：创建→添加项目→取消
     */
    public function testCancelledBillLifecycle(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 1. 创建账单并添加项目
        $bill = $billOrderService->createBill('取消测试账单');
        $billOrderService->addBillItem($bill, 'PROD001', '产品A', '100.00', 1);
        $billOrderService->addBillItem($bill, 'PROD002', '产品B', '50.00', 2);

        // 2. 提交账单
        $billOrderService->submitBill($bill);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());

        // 3. 取消账单
        $cancelReason = '用户主动取消';
        $cancelledBill = $billOrderService->cancelBill($bill, $cancelReason);
        $this->assertSame($bill, $cancelledBill);
        $this->assertEquals(BillOrderStatus::CANCELLED, $bill->getStatus());

        // 验证所有项目状态变为已取消
        foreach ($bill->getItems() as $item) {
            $this->assertEquals(BillItemStatus::CANCELLED, $item->getStatus());
        }

        // 验证取消原因被添加到备注中
        $this->assertStringContainsString($cancelReason, $bill->getRemark());
    }

    /**
     * 测试从草稿状态直接取消的账单生命周期
     */
    public function testCancelDraftBillLifecycle(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 1. 创建账单并添加项目
        $bill = $billOrderService->createBill('草稿取消测试账单');
        $billOrderService->addBillItem($bill, 'PROD001', '产品A', '100.00', 1);

        // 2. 直接从草稿状态取消
        $cancelledBill = $billOrderService->cancelBill($bill, '测试取消');
        $this->assertSame($bill, $cancelledBill);
        $this->assertEquals(BillOrderStatus::CANCELLED, $bill->getStatus());

        // 验证项目状态
        foreach ($bill->getItems() as $item) {
            $this->assertEquals(BillItemStatus::CANCELLED, $item->getStatus());
        }
    }

    /**
     * 测试复杂的多产品账单生命周期
     */
    public function testComplexMultiProductBillLifecycle(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建包含多种类型产品的账单
        $bill = $billOrderService->createBill('复杂多产品账单');

        // 添加不同价格范围的产品
        $products = [
            ['PROD001', '低价产品', '0.01', 10],      // 最小价格
            ['PROD002', '中价产品', '99.99', 5],      // 接近100的价格
            ['PROD003', '高价产品', '999999.99', 1],  // 接近最大价格
            ['PROD004', '整数价格', '100.00', 3],     // 标准整数价格
            ['PROD005', '小数价格', '33.33', 7],      // 循环小数价格
        ];

        foreach ($products as [$productId, $productName, $price, $quantity]) {
            $billOrderService->addBillItem($bill, $productId, $productName, $price, $quantity);
        }

        // 测试相同产品的数量累加
        $billOrderService->addBillItem($bill, 'PROD002', '中价产品', '99.99', 2);
        $billOrderService->addBillItem($bill, 'PROD002', '中价产品', '99.99', 3);

        // 验证项目数量（PROD002应该合并为一个项目）
        $this->assertCount(5, $bill->getItems());

        // 找到PROD002项目并验证其数量
        $prod002Item = null;
        foreach ($bill->getItems() as $item) {
            if ($item->getProductId() === 'PROD002') {
                $prod002Item = $item;
                break;
            }
        }
        $this->assertNotNull($prod002Item);
        $this->assertEquals(10, $prod002Item->getQuantity()); // 5 + 2 + 3

        // 计算预期总金额
        $expectedTotal =
            (0.01 * 10) +      // PROD001
            (99.99 * 10) +     // PROD002 (5+2+3)
            (999999.99 * 1) +  // PROD003
            (100.00 * 3) +     // PROD004
            (33.33 * 7);       // PROD005

        $this->assertEquals(number_format($expectedTotal, 2, '.', ''), $bill->getTotalAmount());

        // 执行完整的支付流程
        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);
        $billOrderService->completeBill($bill);

        // 验证最终状态
        $this->assertEquals(BillOrderStatus::COMPLETED, $bill->getStatus());
        foreach ($bill->getItems() as $item) {
            $this->assertEquals(BillItemStatus::PROCESSED, $item->getStatus());
        }
    }

    /**
     * 测试账单项目更新在生命周期中的行为
     */
    public function testBillItemUpdatesInLifecycle(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建账单并添加项目
        $bill = $billOrderService->createBill('项目更新测试账单');
        $item1 = $billOrderService->addBillItem($bill, 'PROD001', '产品A', '100.00', 2);
        $item2 = $billOrderService->addBillItem($bill, 'PROD002', '产品B', '50.00', 3);

        // 初始总金额
        $initialTotal = $bill->getTotalAmount();
        $this->assertEquals('350.00', $initialTotal);

        // 在草稿状态更新项目
        $billOrderService->updateBillItem($item1, '150.00', 3); // 价格从100变为150，数量从2变为3
        $this->assertEquals('600.00', $bill->getTotalAmount()); // 产品A:150×3=450, 产品B:50×3=150, 总计600

        // 添加新项目
        $item3 = $billOrderService->addBillItem($bill, 'PROD003', '产品C', '25.00', 4);
        $this->assertEquals('700.00', $bill->getTotalAmount()); // 600 + 25×4=100, 总计700

        // 提交账单
        $billOrderService->submitBill($bill);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());

        // 在待支付状态更新项目
        $billOrderService->updateBillItem($item2, null, 5); // 只更新数量
        $this->assertEquals('800.00', $bill->getTotalAmount()); // 产品B从50×3=150变为50×5=250，增加100，总计800

        // 支付账单
        $billOrderService->payBill($bill);
        $this->assertEquals(BillOrderStatus::PAID, $bill->getStatus());

        // 在已支付状态更新项目（应该仍然允许）
        $billOrderService->updateBillItem($item3, '30.00', null); // 只更新价格
        $this->assertEquals('820.00', $bill->getTotalAmount()); // 产品C从25×4=100变为30×4=120，增加20，总计820

        // 完成账单
        $billOrderService->completeBill($bill);

        // 验证所有项目状态
        $this->assertEquals(BillItemStatus::PROCESSED, $item1->getStatus());
        $this->assertEquals(BillItemStatus::PROCESSED, $item2->getStatus());
        $this->assertEquals(BillItemStatus::PROCESSED, $item3->getStatus());
    }

    /**
     * 测试账单项目移除在生命周期中的行为
     */
    public function testBillItemRemovalInLifecycle(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建账单并添加多个项目
        $bill = $billOrderService->createBill('项目移除测试账单');
        $item1 = $billOrderService->addBillItem($bill, 'PROD001', '产品A', '100.00', 2);
        $item2 = $billOrderService->addBillItem($bill, 'PROD002', '产品B', '50.00', 3);
        $item3 = $billOrderService->addBillItem($bill, 'PROD003', '产品C', '25.00', 1);

        $initialTotal = $bill->getTotalAmount();
        $this->assertEquals('375.00', $initialTotal);

        // 在草稿状态移除项目
        $removed = $billOrderService->removeBillItem($bill, $item2);
        $this->assertTrue($removed);
        $this->assertCount(2, $bill->getItems());
        $this->assertEquals('225.00', $bill->getTotalAmount()); // 350 - 125

        // 添加新项目
        $item4 = $billOrderService->addBillItem($bill, 'PROD004', '产品D', '75.00', 2);
        $this->assertEquals('375.00', $bill->getTotalAmount()); // 225 + 150

        // 提交并支付账单
        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);

        // 在已支付状态移除项目（应该仍然允许，但需要谨慎的业务逻辑）
        $removedAfterPayment = $billOrderService->removeBillItem($bill, $item3);
        $this->assertTrue($removedAfterPayment);
        $this->assertCount(2, $bill->getItems());
        $this->assertEquals('350.00', $bill->getTotalAmount()); // 375 - 25

        // 完成账单
        $billOrderService->completeBill($bill);

        // 验证剩余项目状态
        $this->assertEquals(BillItemStatus::PROCESSED, $item1->getStatus());
        $this->assertEquals(BillItemStatus::PROCESSED, $item4->getStatus());
    }

    /**
     * 测试异常生命周期流程
     */
    public function testAbnormalLifecycleFlows(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 测试1: 尝试提交空账单
        $emptyBill = $billOrderService->createBill('空账单测试');
        $this->expectException(EmptyBillException::class);
        $this->expectExceptionMessage('账单必须至少包含一个项目才能提交');
        $billOrderService->submitBill($emptyBill);
    }

    /**
     * 测试异常生命周期流程 - 已完成账单的状态转换
     */
    public function testCompletedBillStatusTransitions(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建完整的账单流程
        $bill = $billOrderService->createBill('状态转换测试');
        $billOrderService->addBillItem($bill, 'PROD001', '产品A', '100.00', 1);
        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);
        $billOrderService->completeBill($bill);

        // 测试已完成账单的各种操作
        $this->assertEquals(BillOrderStatus::COMPLETED, $bill->getStatus());

        // 尝试再次支付（应该失败）
        try {
            $billOrderService->payBill($bill);
            $this->fail('支付已完成账单应该抛出异常');
        } catch (InvalidBillStatusException $e) {
            $this->assertStringContainsString('只有待支付状态的账单可以进行支付操作', $e->getMessage());
        }

        // 尝试取消（应该失败）
        try {
            $billOrderService->cancelBill($bill);
            $this->fail('取消已完成账单应该抛出异常');
        } catch (InvalidBillStatusException $e) {
            $this->assertStringContainsString('只有草稿或待支付状态的账单可以取消', $e->getMessage());
        }
    }

    /**
     * 测试边界值账单的生命周期
     */
    public function testBoundaryValueBillLifecycle(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建边界值账单
        $bill = $billOrderService->createBill(str_repeat('A', 255), str_repeat('B', 2000));

        // 添加边界值项目
        $item1 = $billOrderService->addBillItem(
            $bill,
            str_repeat('C', 255),  // 产品ID最大长度
            str_repeat('D', 255),  // 产品名称最大长度
            '99999999.99',         // 最大价格
            999999                 // 最大数量
        );

        $this->assertEquals('99999899990000.02', $bill->getTotalAmount());

        // 测试最小值项目
        $item2 = $billOrderService->addBillItem(
            $bill,
            'MIN',
            '最小产品',
            '0.01',
            1
        );

        $this->assertEquals('99999899990000.03', $bill->getTotalAmount());

        // 执行完整流程
        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);
        $billOrderService->completeBill($bill);

        $this->assertEquals(BillOrderStatus::COMPLETED, $bill->getStatus());
    }
}