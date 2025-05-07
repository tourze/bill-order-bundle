<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;

class BillItemTest extends TestCase
{
    private BillItem $item;
    
    protected function setUp(): void
    {
        $this->item = new BillItem();
    }
    
    /**
     * 测试ID的getter和setter
     */
    public function testIdGetterSetter(): void
    {
        // ID通常由数据库自动生成，因此我们用反射来设置它进行测试
        $reflection = new \ReflectionClass($this->item);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($this->item, '123456789');
        
        $this->assertSame('123456789', $this->item->getId());
    }
    
    /**
     * 测试toString方法
     */
    public function testToString(): void
    {
        // 设置ID
        $reflection = new \ReflectionClass($this->item);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($this->item, '123456789');
        
        $this->assertSame('123456789', (string)$this->item);
        
        // 测试ID为null的情况
        $property->setValue($this->item, null);
        $this->assertSame('', (string)$this->item);
    }
    
    /**
     * 测试账单关联的getter和setter
     */
    public function testBillGetterSetter(): void
    {
        $bill = new BillOrder();
        $this->item->setBill($bill);
        $this->assertSame($bill, $this->item->getBill());
        
        $this->item->setBill(null);
        $this->assertNull($this->item->getBill());
    }
    
    /**
     * 测试状态的getter和setter
     */
    public function testStatusGetterSetter(): void
    {
        $this->item->setStatus(BillItemStatus::PROCESSED);
        $this->assertSame('processed', $this->item->getStatus());
        $this->assertSame(BillItemStatus::PROCESSED, $this->item->getStatusEnum());
        
        $this->item->setStatus(BillItemStatus::REFUNDED);
        $this->assertSame('refunded', $this->item->getStatus());
        $this->assertSame(BillItemStatus::REFUNDED, $this->item->getStatusEnum());
        
        // 测试使用字符串设置状态
        $this->item->setStatus('cancelled');
        $this->assertSame('cancelled', $this->item->getStatus());
        $this->assertSame(BillItemStatus::CANCELLED, $this->item->getStatusEnum());
    }
    
    /**
     * 测试产品ID的getter和setter
     */
    public function testProductIdGetterSetter(): void
    {
        $this->item->setProductId('PROD001');
        $this->assertSame('PROD001', $this->item->getProductId());
    }
    
    /**
     * 测试产品名称的getter和setter
     */
    public function testProductNameGetterSetter(): void
    {
        $this->item->setProductName('测试产品');
        $this->assertSame('测试产品', $this->item->getProductName());
    }
    
    /**
     * 测试价格的getter和setter
     */
    public function testPriceGetterSetter(): void
    {
        $this->item->setPrice('100.50');
        $this->assertSame('100.50', $this->item->getPrice());
    }
    
    /**
     * 测试数量的getter和setter
     */
    public function testQuantityGetterSetter(): void
    {
        $this->item->setQuantity(5);
        $this->assertSame(5, $this->item->getQuantity());
    }
    
    /**
     * 测试小计的getter和setter
     */
    public function testSubtotalGetterSetter(): void
    {
        $this->item->setSubtotal('502.50');
        $this->assertSame('502.50', $this->item->getSubtotal());
    }
    
    /**
     * 测试备注的getter和setter
     */
    public function testRemarkGetterSetter(): void
    {
        $this->item->setRemark('测试备注');
        $this->assertSame('测试备注', $this->item->getRemark());
        
        $this->item->setRemark(null);
        $this->assertNull($this->item->getRemark());
    }
    
    /**
     * 测试创建时间的getter和setter
     */
    public function testCreateTimeGetterSetter(): void
    {
        $date = new \DateTime('2023-01-01 12:00:00');
        $this->item->setCreateTime($date);
        $this->assertSame($date, $this->item->getCreateTime());
        
        $this->item->setCreateTime(null);
        $this->assertNull($this->item->getCreateTime());
    }
    
    /**
     * 测试更新时间的getter和setter
     */
    public function testUpdateTimeGetterSetter(): void
    {
        $date = new \DateTime('2023-01-01 12:00:00');
        $this->item->setUpdateTime($date);
        $this->assertSame($date, $this->item->getUpdateTime());
        
        $this->item->setUpdateTime(null);
        $this->assertNull($this->item->getUpdateTime());
    }
    
    /**
     * 测试创建人的getter和setter
     */
    public function testCreatedByGetterSetter(): void
    {
        $this->item->setCreatedBy('user1');
        $this->assertSame('user1', $this->item->getCreatedBy());
        
        $this->item->setCreatedBy(null);
        $this->assertNull($this->item->getCreatedBy());
    }
    
    /**
     * 测试更新人的getter和setter
     */
    public function testUpdatedByGetterSetter(): void
    {
        $this->item->setUpdatedBy('user2');
        $this->assertSame('user2', $this->item->getUpdatedBy());
        
        $this->item->setUpdatedBy(null);
        $this->assertNull($this->item->getUpdatedBy());
    }
    
    /**
     * 测试创建IP的getter和setter
     */
    public function testCreatedFromIpGetterSetter(): void
    {
        $this->item->setCreatedFromIp('127.0.0.1');
        $this->assertSame('127.0.0.1', $this->item->getCreatedFromIp());
        
        $this->item->setCreatedFromIp(null);
        $this->assertNull($this->item->getCreatedFromIp());
    }
    
    /**
     * 测试更新IP的getter和setter
     */
    public function testUpdatedFromIpGetterSetter(): void
    {
        $this->item->setUpdatedFromIp('192.168.1.1');
        $this->assertSame('192.168.1.1', $this->item->getUpdatedFromIp());
        
        $this->item->setUpdatedFromIp(null);
        $this->assertNull($this->item->getUpdatedFromIp());
    }
    
    /**
     * 测试计算小计金额方法
     */
    public function testCalculateSubtotal(): void
    {
        // 设置价格和数量
        $this->item->setPrice('10.50');
        $this->item->setQuantity(2);
        
        // 验证小计是否正确计算
        $this->assertSame('21.00', $this->item->getSubtotal());
        
        // 修改数量，验证小计是否更新
        $this->item->setQuantity(3);
        $this->assertSame('31.50', $this->item->getSubtotal());
        
        // 修改价格，验证小计是否更新
        $this->item->setPrice('20.00');
        $this->assertSame('60.00', $this->item->getSubtotal());
    }
} 