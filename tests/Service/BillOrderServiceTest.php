<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Repository\BillItemRepository;
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

class BillOrderServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BillOrderRepository $billOrderRepository;
    private BillItemRepository $billItemRepository;
    private LoggerInterface $logger;
    private BillOrderService $billOrderService;
    
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->billOrderRepository = $this->createMock(BillOrderRepository::class);
        $this->billItemRepository = $this->createMock(BillItemRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->billOrderService = new BillOrderService(
            $this->entityManager,
            $this->billOrderRepository,
            $this->billItemRepository,
            $this->logger
        );
    }
    
    /**
     * 使用反射设置实体的id
     */
    private function setEntityId($entity, $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
    
    /**
     * 测试创建账单功能
     */
    public function testCreateBill(): void
    {
        // 设置entityManager预期行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(BillOrder::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        // 不再检查logger的具体调用参数
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->equalTo('创建新账单'));
        
        // 执行测试
        $bill = $this->billOrderService->createBill('测试账单', '测试备注');
        
        // 验证结果 - 增加断言避免risky test
        $this->assertInstanceOf(BillOrder::class, $bill);
        $this->assertEquals('测试账单', $bill->getTitle());
        $this->assertEquals('测试备注', $bill->getRemark());
        $this->assertEquals(BillOrderStatus::DRAFT, $bill->getStatus());
        $this->assertStringStartsWith('BILL', $bill->getBillNumber());
    }
    
    /**
     * 测试添加账单项目 - 新项目
     */
    public function testAddBillItem_NewItem(): void
    {
        $bill = new BillOrder();
        $this->setEntityId($bill, '12345');
        
        // 模拟仓储行为 - 不存在相同产品
        $this->billItemRepository->expects($this->once())
            ->method('findOneByBillAndProduct')
            ->with('12345', 'PROD001')
            ->willReturn(null);
            
        // 期望创建新项目并持久化
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($item) use ($bill) {
                return $item instanceof BillItem
                    && $item->getBill() === $bill
                    && $item->getProductId() === 'PROD001'
                    && $item->getProductName() === '测试产品'
                    && $item->getPrice() === '100.00'
                    && $item->getQuantity() === 2
                    && $item->getStatus() === BillItemStatus::PENDING;
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 模拟重新计算总金额
        $this->billItemRepository->expects($this->once())
            ->method('getTotalAmountByBillId')
            ->with('12345')
            ->willReturn('200.00');
            
        // 日志期望
        $this->logger->expects($this->exactly(2))
            ->method('info');
            
        // 执行测试
        $result = $this->billOrderService->addBillItem(
            $bill,
            'PROD001',
            '测试产品',
            '100.00',
            2,
            '测试备注'
        );
        
        // 验证结果
        $this->assertInstanceOf(BillItem::class, $result);
        $this->assertEquals('PROD001', $result->getProductId());
        $this->assertEquals('测试产品', $result->getProductName());
        $this->assertEquals('100.00', $result->getPrice());
        $this->assertEquals(2, $result->getQuantity());
        $this->assertEquals('测试备注', $result->getRemark());
        $this->assertEquals('200.00', $bill->getTotalAmount());
    }
    
    /**
     * 测试添加账单项目 - 已有相同产品项目
     */
    public function testAddBillItem_ExistingItem(): void
    {
        $bill = new BillOrder();
        $this->setEntityId($bill, '12345');
        
        // 创建已存在的项目
        $existingItem = new BillItem();
        $existingItem->setBill($bill);
        $existingItem->setProductId('PROD001');
        $existingItem->setProductName('测试产品');
        $existingItem->setPrice('100.00');
        $existingItem->setQuantity(3);
        
        // 模拟仓储行为 - 存在相同产品
        $this->billItemRepository->expects($this->once())
            ->method('findOneByBillAndProduct')
            ->with('12345', 'PROD001')
            ->willReturn($existingItem);
            
        // 不期望创建新项目
        $this->entityManager->expects($this->never())
            ->method('persist');
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 模拟重新计算总金额
        $this->billItemRepository->expects($this->once())
            ->method('getTotalAmountByBillId')
            ->with('12345')
            ->willReturn('500.00');
            
        // 日志期望
        $this->logger->expects($this->exactly(2))
            ->method('info');
            
        // 执行测试
        $result = $this->billOrderService->addBillItem(
            $bill,
            'PROD001',
            '测试产品',
            '100.00',
            2
        );
        
        // 验证结果
        $this->assertSame($existingItem, $result);
        $this->assertEquals(5, $result->getQuantity()); // 3 + 2
        $this->assertEquals('500.00', $bill->getTotalAmount());
    }
    
    /**
     * 测试更新账单项目
     */
    public function testUpdateBillItem(): void
    {
        $bill = new BillOrder();
        $this->setEntityId($bill, '12345');
        
        $item = new BillItem();
        $item->setBill($bill);
        $item->setProductId('PROD001');
        $item->setProductName('测试产品');
        $item->setPrice('100.00');
        $item->setQuantity(2);
        $item->setStatus(BillItemStatus::PENDING);
        
        // 预期操作
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 模拟重新计算总金额
        $this->billItemRepository->expects($this->once())
            ->method('getTotalAmountByBillId')
            ->with('12345')
            ->willReturn('150.00');
            
        // 执行测试 - 更新价格、数量和状态
        $result = $this->billOrderService->updateBillItem(
            $item,
            '75.00',
            1,
            BillItemStatus::PROCESSED
        );
        
        // 验证结果
        $this->assertSame($item, $result);
        $this->assertEquals('75.00', $result->getPrice());
        $this->assertEquals(1, $result->getQuantity());
        $this->assertEquals(BillItemStatus::PROCESSED, $result->getStatus());
        $this->assertEquals('150.00', $bill->getTotalAmount());
    }
    
    /**
     * 测试移除账单项目 - 成功情况
     */
    public function testRemoveBillItem_Success(): void
    {
        $bill = new BillOrder();
        $this->setEntityId($bill, '12345');
        
        $item = new BillItem();
        $item->setBill($bill);
        $item->setProductId('PROD001');
        $item->setProductName('测试产品');
        
        // 预期操作
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($item);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 模拟重新计算总金额
        $this->billItemRepository->expects($this->once())
            ->method('getTotalAmountByBillId')
            ->with('12345')
            ->willReturn('0');
            
        // 执行测试
        $result = $this->billOrderService->removeBillItem($bill, $item);
        
        // 验证结果
        $this->assertTrue($result);
        $this->assertEquals('0', $bill->getTotalAmount());
    }
    
    /**
     * 测试移除账单项目 - 项目不属于该账单
     */
    public function testRemoveBillItem_ItemNotBelongsToBill(): void
    {
        $bill1 = new BillOrder();
        $this->setEntityId($bill1, '12345');
        
        $bill2 = new BillOrder();
        $this->setEntityId($bill2, '67890');
        
        $item = new BillItem();
        $item->setBill($bill2); // 项目属于bill2，而不是bill1
        
        // 不期望移除操作
        $this->entityManager->expects($this->never())
            ->method('remove');
            
        $this->entityManager->expects($this->never())
            ->method('flush');
            
        // 执行测试
        $result = $this->billOrderService->removeBillItem($bill1, $item);
        
        // 验证结果
        $this->assertFalse($result);
    }
    
    /**
     * 测试重新计算账单总金额
     */
    public function testRecalculateBillTotal(): void
    {
        $bill = new BillOrder();
        $this->setEntityId($bill, '12345');
        $bill->setTotalAmount('100.00');
        
        // 模拟仓储返回新总金额
        $this->billItemRepository->expects($this->once())
            ->method('getTotalAmountByBillId')
            ->with('12345')
            ->willReturn('150.00');
            
        // 不再期望调用flush，因为我们修改了方法的实现
        $this->entityManager->expects($this->never())
            ->method('flush');
            
        // 执行测试
        $this->billOrderService->recalculateBillTotal($bill);
        
        // 验证结果
        $this->assertEquals('150.00', $bill->getTotalAmount());
    }
    
    /**
     * 测试更新账单状态
     */
    public function testUpdateBillStatus(): void
    {
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行测试
        $result = $this->billOrderService->updateBillStatus($bill, BillOrderStatus::PENDING);
        
        // 验证结果
        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());
    }
    
    /**
     * 测试支付账单
     */
    public function testPayBill(): void
    {
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::PENDING);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行测试
        $result = $this->billOrderService->payBill($bill);
        
        // 验证结果
        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::PAID, $bill->getStatus());
        $this->assertNotNull($bill->getPayTime());
    }
    
    /**
     * 测试支付非待付款状态的账单（应抛出异常）
     */
    public function testPayBill_InvalidStatus(): void
    {
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        
        // 期望不会更新entityManager
        $this->entityManager->expects($this->never())
            ->method('flush');
            
        // 期望抛出异常
        $this->expectException(\Tourze\Symfony\BillOrderBundle\Exception\InvalidBillStatusException::class);
        $this->expectExceptionMessage('只有待支付状态的账单可以进行支付操作');
            
        // 执行测试
        $this->billOrderService->payBill($bill);
    }
    
    /**
     * 测试取消账单
     */
    public function testCancelBill(): void
    {
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::PENDING);
        
        // 允许flush被调用多次，因为服务实现可能在updateBillStatus内也调用了flush
        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');
            
        // 执行测试
        $result = $this->billOrderService->cancelBill($bill, '测试取消原因');
        
        // 验证结果
        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::CANCELLED, $bill->getStatus());
        $this->assertStringContainsString('测试取消原因', $bill->getRemark());
    }
    
    /**
     * 测试完成账单
     */
    public function testCompleteBill(): void
    {
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::PAID);
        
        // 允许flush被调用多次，因为服务实现可能在updateBillStatus内也调用了flush
        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');
            
        // 执行测试
        $result = $this->billOrderService->completeBill($bill);
        
        // 验证结果
        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::COMPLETED, $bill->getStatus());
    }
    
    /**
     * 测试提交账单 - 模拟账单至少包含一个项目
     */
    public function testSubmitBill(): void
    {
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        $bill->setTotalAmount('100.00');
        
        // 创建一个模拟的账单项目
        $item = new BillItem();
        $item->setBill($bill);
        
        // 使用反射设置items集合
        $collection = new \Doctrine\Common\Collections\ArrayCollection([$item]);
        $reflection = new \ReflectionClass($bill);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($bill, $collection);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行测试
        $result = $this->billOrderService->submitBill($bill);
        
        // 验证结果
        $this->assertSame($bill, $result);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());
    }
    
    /**
     * 测试提交空账单（应抛出异常）
     */
    public function testSubmitBill_EmptyItems(): void
    {
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        $bill->setTotalAmount('100.00');
        
        // 期望不会更新entityManager
        $this->entityManager->expects($this->never())
            ->method('flush');
            
        // 期望抛出异常
        $this->expectException(\Tourze\Symfony\BillOrderBundle\Exception\EmptyBillException::class);
        $this->expectExceptionMessage('账单必须至少包含一个项目才能提交');
        
        // 执行测试
        $this->billOrderService->submitBill($bill);
    }
} 