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
 * @internal
 */
#[CoversClass(BillOrderService::class)]
#[RunTestsInSeparateProcesses]
final class BillOrderServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    private function getBillOrderService(): BillOrderService
    {
        return self::getService(BillOrderService::class);
    }

    /**
     * 测试创建账单功能
     */
    public function testCreateBill(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单', '测试备注');

        $this->assertInstanceOf(BillOrder::class, $bill);
        $this->assertEquals('测试账单', $bill->getTitle());
        $this->assertEquals('测试备注', $bill->getRemark());
        $this->assertEquals(BillOrderStatus::DRAFT, $bill->getStatus());
        $billNumber = $bill->getBillNumber();
        $this->assertNotNull($billNumber);
        $this->assertStringStartsWith('BILL', $billNumber);
        $this->assertNotNull($bill->getId());
    }

    /**
     * 测试添加账单项目功能
     */
    public function testAddBillItem(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单', '测试备注');

        $result = $billOrderService->addBillItem(
            $bill,
            'PROD001',
            '测试产品',
            '100.00',
            2,
            '测试备注'
        );

        $this->assertInstanceOf(BillItem::class, $result);
        $this->assertEquals('PROD001', $result->getProductId());
        $this->assertEquals('测试产品', $result->getProductName());
        $this->assertEquals('100.00', $result->getPrice());
        $this->assertEquals(2, $result->getQuantity());
        $this->assertEquals('测试备注', $result->getRemark());
        $this->assertEquals(BillItemStatus::PENDING, $result->getStatus());
        $this->assertSame($bill, $result->getBill());
    }

    /**
     * 测试添加相同产品时数量累加
     */
    public function testAddBillItemExistingProduct(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');

        $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 2);
        $result = $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 3);

        $this->assertEquals(5, $result->getQuantity());
    }

    /**
     * 测试更新账单项目
     */
    public function testUpdateBillItem(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');
        $item = $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 2);

        $result = $billOrderService->updateBillItem(
            $item,
            '150.00',
            3,
            BillItemStatus::PROCESSED
        );

        $this->assertSame($item, $result);
        $this->assertEquals('150.00', $result->getPrice());
        $this->assertEquals(3, $result->getQuantity());
        $this->assertEquals(BillItemStatus::PROCESSED, $result->getStatus());
    }

    /**
     * 测试移除账单项目
     */
    public function testRemoveBillItem(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');
        $item = $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 2);

        $result = $billOrderService->removeBillItem($bill, $item);

        $this->assertTrue($result);
    }

    /**
     * 测试移除不属于账单的项目
     */
    public function testRemoveBillItemNotBelongsToBill(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill1 = $billOrderService->createBill('测试账单1');
        $bill2 = $billOrderService->createBill('测试账单2');
        $item = $billOrderService->addBillItem($bill2, 'PROD001', '测试产品', '100.00', 2);

        $result = $billOrderService->removeBillItem($bill1, $item);

        $this->assertFalse($result);
    }

    /**
     * 测试提交账单
     */
    public function testSubmitBill(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');
        $item = $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 2);

        $this->assertFalse($bill->getItems()->isEmpty(), '账单应该包含项目');

        $result = $billOrderService->submitBill($bill);

        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());
    }

    /**
     * 测试提交空账单抛出异常
     */
    public function testSubmitEmptyBillThrowsException(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');

        $this->expectException(EmptyBillException::class);
        $this->expectExceptionMessage('账单必须至少包含一个项目才能提交');

        $billOrderService->submitBill($bill);
    }

    /**
     * 测试支付账单
     */
    public function testPayBill(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');
        $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 2);
        $billOrderService->submitBill($bill);

        $result = $billOrderService->payBill($bill);

        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::PAID, $bill->getStatus());
        $this->assertNotNull($bill->getPayTime());
    }

    /**
     * 测试支付非待支付状态账单抛出异常
     */
    public function testPayBillInvalidStatusThrowsException(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');

        $this->expectException(InvalidBillStatusException::class);
        $this->expectExceptionMessage('只有待支付状态的账单可以进行支付操作');

        $billOrderService->payBill($bill);
    }

    /**
     * 测试完成账单
     */
    public function testCompleteBill(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');
        $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 2);
        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);

        $result = $billOrderService->completeBill($bill);

        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::COMPLETED, $bill->getStatus());
    }

    /**
     * 测试取消账单
     */
    public function testCancelBill(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('测试账单');

        $result = $billOrderService->cancelBill($bill, '测试取消原因');

        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::CANCELLED, $bill->getStatus());
        $remark = $bill->getRemark();
        $this->assertNotNull($remark);
        $this->assertStringContainsString('测试取消原因', $remark);
    }

    /**
     * 测试获取账单统计
     */
    public function testGetBillStatistics(): void
    {
        $billOrderService = $this->getBillOrderService();

        $statistics = $billOrderService->getBillStatistics();

        // 验证统计数据结构
        $this->assertArrayHasKey('draft', $statistics);
        $this->assertArrayHasKey('pending', $statistics);
        $this->assertArrayHasKey('paid', $statistics);
        $this->assertArrayHasKey('completed', $statistics);
        $this->assertArrayHasKey('cancelled', $statistics);
    }

    /**
     * 测试重新计算账单总金额
     */
    public function testRecalculateBillTotal(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('Test Bill', 'Test Remark');
        $billOrderService->addBillItem($bill, 'PROD001', 'Product 1', '50.00', 2);
        $billOrderService->addBillItem($bill, 'PROD002', 'Product 2', '30.00', 1);

        // 测试重新计算
        $billOrderService->recalculateBillTotal($bill);

        // 验证总金额被正确计算
        $this->assertEquals('130.00', $bill->getTotalAmount());
    }

    /**
     * 测试更新账单状态
     */
    public function testUpdateBillStatus(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill = $billOrderService->createBill('Test Bill', 'Test Remark');

        // 测试更新状态
        $result = $billOrderService->updateBillStatus($bill, BillOrderStatus::PENDING);

        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());
    }

    // ===================== 边界条件和异常测试 =====================

    /**
     * 测试创建账单时的边界条件
     */
    public function testCreateBillBoundaryConditions(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 测试空标题和备注
        $bill1 = $billOrderService->createBill(null, null);
        $this->assertNull($bill1->getTitle());
        $this->assertNull($bill1->getRemark());

        // 测试空字符串
        $bill2 = $billOrderService->createBill('', '');
        $this->assertEquals('', $bill2->getTitle());
        $this->assertEquals('', $bill2->getRemark());

        // 测试长字符串
        $longTitle = str_repeat('A', 255);
        $longRemark = str_repeat('B', 2000);
        $bill3 = $billOrderService->createBill($longTitle, $longRemark);
        $this->assertEquals($longTitle, $bill3->getTitle());
        $this->assertEquals($longRemark, $bill3->getRemark());
    }

    /**
     * 测试账单编号生成的唯一性
     */
    public function testBillNumberUniqueness(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bills = [];
        for ($i = 0; $i < 10; $i++) {
            $bill = $billOrderService->createBill("测试账单 {$i}");
            $bills[] = $bill;
        }

        $billNumbers = array_map(fn($bill) => $bill->getBillNumber(), $bills);
        $uniqueBillNumbers = array_unique($billNumbers);

        $this->assertCount(10, $uniqueBillNumbers, '所有账单编号应该唯一');

        foreach ($billNumbers as $billNumber) {
            $this->assertStringStartsWith('BILL', $billNumber);
            $this->assertMatchesRegularExpression('/^BILL\d{8}[a-f0-9]{8}$/', $billNumber);
        }
    }

    /**
     * 测试添加账单项目的数据验证 - 边界条件
     */
    public function testAddBillItemDataValidationBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('测试账单');

        // 测试各种无效的产品ID
        $invalidProductIds = ['', '   ', "\t", "\n"];
        foreach ($invalidProductIds as $productId) {
            $this->expectException(\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillDataException::class);
            $this->expectExceptionMessage('产品ID不能为空');
            $billOrderService->addBillItem($bill, $productId, '测试产品', '100.00', 1);
        }

        // 测试各种无效的产品名称
        $invalidProductNames = ['', '   ', "\t", "\n"];
        foreach ($invalidProductNames as $productName) {
            $this->expectException(\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillDataException::class);
            $this->expectExceptionMessage('产品名称不能为空');
            $billOrderService->addBillItem($bill, 'PROD001', $productName, '100.00', 1);
        }
    }

    /**
     * 测试价格验证的边界条件
     */
    public function testPriceValidationBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('测试账单');

        // 测试无效的价格格式
        $invalidPrices = [
            '-100.00',    // 负数
            'abc',        // 非数字
            '100.123',    // 超过两位小数
            '100.',       // 小数点后无数字
            '.50',        // 小数点前无数字
            '100.0.0',    // 多个小数点
            '',           // 空字符串
            '0',          // 零价格（虽然非负，但可能不符合业务逻辑）
        ];

        foreach ($invalidPrices as $price) {
            try {
                $billOrderService->addBillItem($bill, 'PROD001', '测试产品', $price, 1);
                // 如果没有抛出异常，检查是否价格是有效的（如0可能是有效的）
                $this->assertTrue(in_array($price, ['0']), "价格 '{$price}' 应该被接受或抛出异常");
            } catch (\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillDataException $e) {
                // 预期的异常情况
                $this->assertStringContainsString('价格', $e->getMessage());
            }
        }

        // 测试有效的边界价格
        $validBoundaryPrices = [
            '0.01',        // 最小正数
            '0.99',        // 接近1的小数
            '99999999.99', // 最大允许金额
            '100.00',      // 标准价格
            '100.5',       // 一位小数
            '100.55',      // 两位小数
        ];

        foreach ($validBoundaryPrices as $price) {
            $item = $billOrderService->addBillItem($bill, 'PROD' . uniqid(), '测试产品', $price, 1);
            $this->assertEquals($price, $item->getPrice());
        }
    }

    /**
     * 测试数量验证的边界条件
     */
    public function testQuantityValidationBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('测试账单');

        // 测试无效数量
        $invalidQuantities = [0, -1, -100, 1000000, 999999999];
        foreach ($invalidQuantities as $quantity) {
            $this->expectException(\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillDataException::class);
            $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', $quantity);
        }

        // 测试有效边界数量
        $validBoundaryQuantities = [1, 999999];
        foreach ($validBoundaryQuantities as $quantity) {
            $item = $billOrderService->addBillItem($bill, 'PROD' . uniqid(), '测试产品', '100.00', $quantity);
            $this->assertEquals($quantity, $item->getQuantity());
        }
    }

    /**
     * 测试更新账单项目的边界条件
     */
    public function testUpdateBillItemBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('测试账单');
        $item = $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 2);

        // 测试部分更新 - 只更新价格
        $updated1 = $billOrderService->updateBillItem($item, '150.00');
        $this->assertEquals('150.00', $updated1->getPrice());
        $this->assertEquals(2, $updated1->getQuantity()); // 数量应该保持不变
        $this->assertEquals(BillItemStatus::PENDING, $updated1->getStatus()); // 状态应该保持不变

        // 测试部分更新 - 只更新数量
        $updated2 = $billOrderService->updateBillItem($item, null, 5);
        $this->assertEquals('150.00', $updated2->getPrice()); // 价格应该保持不变
        $this->assertEquals(5, $updated2->getQuantity());
        $this->assertEquals(BillItemStatus::PENDING, $updated2->getStatus()); // 状态应该保持不变

        // 测试无更新 - 所有参数为null
        $updated3 = $billOrderService->updateBillItem($item);
        $this->assertEquals($item, $updated3); // 应该返回同一个对象
    }

    /**
     * 测试账单状态转换的异常情况
     */
    public function testBillStatusTransitionExceptions(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('测试账单');
        $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 1);

        // 测试从草稿状态直接完成（应该失败）
        $this->expectException(\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillStatusException::class);
        $this->expectExceptionMessage('只有已支付状态的账单可以标记为完成');
        $billOrderService->completeBill($bill);
    }

    /**
     * 测试取消账单的边界条件
     */
    public function testCancelBillBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建完整流程的账单
        $bill = $billOrderService->createBill('测试账单');
        $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 1);
        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);

        // 测试取消已支付的账单（应该失败）
        $this->expectException(\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillStatusException::class);
        $this->expectExceptionMessage('只有草稿或待支付状态的账单可以取消');
        $billOrderService->cancelBill($bill);
    }

    /**
     * 测试重新计算总金额的边界条件
     */
    public function testRecalculateBillTotalBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 测试重新计算未持久化账单的总金额（应该失败）
        $bill = new \Tourze\Symfony\BillOrderBundle\Entity\BillOrder();
        $this->expectException(\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillDataException::class);
        $this->expectExceptionMessage('账单ID不能为空');
        $billOrderService->recalculateBillTotal($bill);

        // 测试空账单的总金额计算
        $validBill = $billOrderService->createBill('空账单');
        $billOrderService->recalculateBillTotal($validBill);
        $this->assertEquals('0', $validBill->getTotalAmount());
    }

    /**
     * 测试账单统计功能的边界条件
     */
    public function testGetBillStatisticsBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 空数据库的统计
        $statistics = $billOrderService->getBillStatistics();
        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('draft', $statistics);
        $this->assertArrayHasKey('pending', $statistics);
        $this->assertArrayHasKey('paid', $statistics);
        $this->assertArrayHasKey('completed', $statistics);
        $this->assertArrayHasKey('cancelled', $statistics);

        // 验证初始统计都是0
        foreach ($statistics as $status => $data) {
            $this->assertArrayHasKey('count', $data);
            $this->assertArrayHasKey('totalAmount', $data);
            $this->assertEquals(0, $data['count']);
            $this->assertEquals('0', $data['totalAmount']);
        }
    }

    /**
     * 测试移除账单项目的边界条件
     */
    public function testRemoveBillItemBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();

        $bill1 = $billOrderService->createBill('账单1');
        $bill2 = $billOrderService->createBill('账单2');

        $item1 = $billOrderService->addBillItem($bill1, 'PROD001', '产品1', '100.00', 1);
        $item2 = $billOrderService->addBillItem($bill2, 'PROD002', '产品2', '200.00', 1);

        // 测试移除不属于该账单的项目
        $result = $billOrderService->removeBillItem($bill1, $item2);
        $this->assertFalse($result);

        // 测试重复移除同一个项目
        $result1 = $billOrderService->removeBillItem($bill1, $item1);
        $this->assertTrue($result1);

        // 第二次移除应该失败（项目已经不存在）
        $result2 = $billOrderService->removeBillItem($bill1, $item1);
        $this->assertFalse($result2);
    }

    /**
     * 测试相同产品数量累加的边界条件
     */
    public function testAddBillItemExistingProductBoundary(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('测试账单');

        // 添加多次相同产品，测试数量累加
        $quantities = [1, 2, 3, 4];
        $expectedTotal = 0;

        foreach ($quantities as $i => $quantity) {
            $item = $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', $quantity);
            $expectedTotal += $quantity;
            $this->assertEquals($expectedTotal, $item->getQuantity());

            // 验证只有一个项目存在
            $this->assertCount(1, $bill->getItems());
        }

        // 测试数量边界 - 添加大数量可能导致溢出
        try {
            $largeQuantity = 999999;
            $billOrderService->addBillItem($bill, 'PROD002', '大数量产品', '1.00', $largeQuantity);
            $this->assertEquals($largeQuantity, $bill->getItems()->last()->getQuantity());
        } catch (\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillDataException $e) {
            // 如果抛出异常，验证是数量限制导致的
            $this->assertStringContainsString('数量', $e->getMessage());
        }
    }
}
