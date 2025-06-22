<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;

class BillOrderTest extends TestCase
{
    private BillOrder $order;
    
    protected function setUp(): void
    {
        $this->order = new BillOrder();
    }
    
    /**
     * 测试账单ID的getter和setter
     */
    public function testIdGetterSetter(): void
    {
        // ID通常由数据库自动生成，因此我们用反射来设置它进行测试
        $reflection = new \ReflectionClass($this->order);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($this->order, '123456789');
        
        $this->assertSame('123456789', $this->order->getId());
    }
    
    /**
     * 测试账单toString方法
     */
    public function testToString(): void
    {
        // 设置ID
        $reflection = new \ReflectionClass($this->order);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($this->order, '123456789');
        
        $this->assertSame('123456789', (string)$this->order);
        
        // 测试ID为null的情况
        $property->setValue($this->order, null);
        $this->assertSame('', (string)$this->order);
    }
    
    /**
     * 测试账单状态的getter和setter
     */
    public function testStatusGetterSetter(): void
    {
        $this->order->setStatus(BillOrderStatus::PENDING);
        $this->assertSame(BillOrderStatus::PENDING, $this->order->getStatus());
        
        $this->order->setStatus(BillOrderStatus::PAID);
        $this->assertSame(BillOrderStatus::PAID, $this->order->getStatus());
    }
    
    /**
     * 测试总金额的getter和setter
     */
    public function testTotalAmountGetterSetter(): void
    {
        $this->order->setTotalAmount('100.50');
        $this->assertSame('100.50', $this->order->getTotalAmount());
        
        $this->order->setTotalAmount('0');
        $this->assertSame('0', $this->order->getTotalAmount());
    }
    
    /**
     * 测试标题的getter和setter
     */
    public function testTitleGetterSetter(): void
    {
        $this->order->setTitle('测试账单');
        $this->assertSame('测试账单', $this->order->getTitle());
        
        $this->order->setTitle(null);
        $this->assertNull($this->order->getTitle());
    }
    
    /**
     * 测试账单编号的getter和setter
     */
    public function testBillNumberGetterSetter(): void
    {
        $this->order->setBillNumber('BILL20230101ABCD');
        $this->assertSame('BILL20230101ABCD', $this->order->getBillNumber());
        
        $this->order->setBillNumber(null);
        $this->assertNull($this->order->getBillNumber());
    }
    
    /**
     * 测试备注的getter和setter
     */
    public function testRemarkGetterSetter(): void
    {
        $this->order->setRemark('测试备注');
        $this->assertSame('测试备注', $this->order->getRemark());
        
        $this->order->setRemark(null);
        $this->assertNull($this->order->getRemark());
    }
    
    /**
     * 测试付款时间的getter和setter
     */
    public function testPayTimeGetterSetter(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $this->order->setPayTime($date);
        $this->assertSame($date, $this->order->getPayTime());
        
        $this->order->setPayTime(null);
        $this->assertNull($this->order->getPayTime());
    }
    
    /**
     * 测试创建时间的getter和setter
     */
    public function testCreateTimeGetterSetter(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $this->order->setCreateTime($date);
        $this->assertSame($date, $this->order->getCreateTime());
        
        $this->order->setCreateTime(null);
        $this->assertNull($this->order->getCreateTime());
    }
    
    /**
     * 测试更新时间的getter和setter
     */
    public function testUpdateTimeGetterSetter(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $this->order->setUpdateTime($date);
        $this->assertSame($date, $this->order->getUpdateTime());
        
        $this->order->setUpdateTime(null);
        $this->assertNull($this->order->getUpdateTime());
    }
    
    /**
     * 测试创建人的getter和setter
     */
    public function testCreatedByGetterSetter(): void
    {
        $this->order->setCreatedBy('user1');
        $this->assertSame('user1', $this->order->getCreatedBy());
        
        $this->order->setCreatedBy(null);
        $this->assertNull($this->order->getCreatedBy());
    }
    
    /**
     * 测试更新人的getter和setter
     */
    public function testUpdatedByGetterSetter(): void
    {
        $this->order->setUpdatedBy('user2');
        $this->assertSame('user2', $this->order->getUpdatedBy());
        
        $this->order->setUpdatedBy(null);
        $this->assertNull($this->order->getUpdatedBy());
    }
    
    /**
     * 测试创建IP的getter和setter
     */
    public function testCreatedFromIpGetterSetter(): void
    {
        $this->order->setCreatedFromIp('127.0.0.1');
        $this->assertSame('127.0.0.1', $this->order->getCreatedFromIp());
        
        $this->order->setCreatedFromIp(null);
        $this->assertNull($this->order->getCreatedFromIp());
    }
    
    /**
     * 测试更新IP的getter和setter
     */
    public function testUpdatedFromIpGetterSetter(): void
    {
        $this->order->setUpdatedFromIp('192.168.1.1');
        $this->assertSame('192.168.1.1', $this->order->getUpdatedFromIp());
        
        $this->order->setUpdatedFromIp(null);
        $this->assertNull($this->order->getUpdatedFromIp());
    }
    
    /**
     * 测试账单项目集合
     */
    public function testItemsCollection(): void
    {
        // 初始应为空集合
        $this->assertCount(0, $this->order->getItems());
        
        // 添加项目
        $item1 = new BillItem();
        $this->order->addItem($item1);
        $this->assertCount(1, $this->order->getItems());
        $this->assertSame($this->order, $item1->getBill());
        
        // 添加相同项目不会重复添加
        $this->order->addItem($item1);
        $this->assertCount(1, $this->order->getItems());
        
        // 添加第二个项目
        $item2 = new BillItem();
        $this->order->addItem($item2);
        $this->assertCount(2, $this->order->getItems());
        
        // 移除项目
        $this->order->removeItem($item1);
        $this->assertCount(1, $this->order->getItems());
        $this->assertNull($item1->getBill());
        
        // 移除已移除的项目不会报错
        $this->order->removeItem($item1);
        $this->assertCount(1, $this->order->getItems());
    }
    
    /**
     * 测试计算总金额方法
     */
    public function testCalculateTotalAmount(): void
    {
        // 创建两个模拟的账单项目
        $item1 = $this->createMock(BillItem::class);
        $item1->method('getSubtotal')->willReturn('100.50');
        
        $item2 = $this->createMock(BillItem::class);
        $item2->method('getSubtotal')->willReturn('200.25');
        
        // 设置items属性
        $items = new \Doctrine\Common\Collections\ArrayCollection([$item1, $item2]);
        $reflection = new \ReflectionClass($this->order);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($this->order, $items);
        
        // 直接设置总金额为0
        $this->order->setTotalAmount('0.00');
        
        // 调用calculateTotalAmount方法
        $this->order->calculateTotalAmount();
        
        // 验证总金额
        $this->assertEquals('300.75', $this->order->getTotalAmount());
    }
} 