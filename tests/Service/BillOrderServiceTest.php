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
}
