<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;

class BillOrderStatusTest extends TestCase
{
    /**
     * 测试所有枚举值是否正确
     */
    public function testEnumValues(): void
    {
        $this->assertSame('draft', BillOrderStatus::DRAFT->value);
        $this->assertSame('pending', BillOrderStatus::PENDING->value);
        $this->assertSame('paid', BillOrderStatus::PAID->value);
        $this->assertSame('completed', BillOrderStatus::COMPLETED->value);
        $this->assertSame('cancelled', BillOrderStatus::CANCELLED->value);
    }
    
    /**
     * 测试获取枚举标签
     */
    public function testGetLabel(): void
    {
        $this->assertSame('草稿', BillOrderStatus::DRAFT->getLabel());
        $this->assertSame('待付款', BillOrderStatus::PENDING->getLabel());
        $this->assertSame('已付款', BillOrderStatus::PAID->getLabel());
        $this->assertSame('已完成', BillOrderStatus::COMPLETED->getLabel());
        $this->assertSame('已取消', BillOrderStatus::CANCELLED->getLabel());
    }
    
    /**
     * 测试从字符串值创建枚举
     */
    public function testFromString(): void
    {
        $this->assertSame(BillOrderStatus::DRAFT, BillOrderStatus::from('draft'));
        $this->assertSame(BillOrderStatus::PENDING, BillOrderStatus::from('pending'));
        $this->assertSame(BillOrderStatus::PAID, BillOrderStatus::from('paid'));
        $this->assertSame(BillOrderStatus::COMPLETED, BillOrderStatus::from('completed'));
        $this->assertSame(BillOrderStatus::CANCELLED, BillOrderStatus::from('cancelled'));
    }
    
    /**
     * 测试传入无效值时抛出异常
     */
    public function testInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        BillOrderStatus::from('invalid_status');
    }
} 