<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;

class BillItemStatusTest extends TestCase
{
    /**
     * 测试所有枚举值是否正确
     */
    public function testEnumValues(): void
    {
        $this->assertSame('pending', BillItemStatus::PENDING->value);
        $this->assertSame('processed', BillItemStatus::PROCESSED->value);
        $this->assertSame('refunded', BillItemStatus::REFUNDED->value);
        $this->assertSame('cancelled', BillItemStatus::CANCELLED->value);
    }
    
    /**
     * 测试获取枚举标签
     */
    public function testGetLabel(): void
    {
        $this->assertSame('待处理', BillItemStatus::PENDING->getLabel());
        $this->assertSame('已处理', BillItemStatus::PROCESSED->getLabel());
        $this->assertSame('已退款', BillItemStatus::REFUNDED->getLabel());
        $this->assertSame('已取消', BillItemStatus::CANCELLED->getLabel());
    }
    
    /**
     * 测试从字符串值创建枚举
     */
    public function testFromString(): void
    {
        $this->assertSame(BillItemStatus::PENDING, BillItemStatus::from('pending'));
        $this->assertSame(BillItemStatus::PROCESSED, BillItemStatus::from('processed'));
        $this->assertSame(BillItemStatus::REFUNDED, BillItemStatus::from('refunded'));
        $this->assertSame(BillItemStatus::CANCELLED, BillItemStatus::from('cancelled'));
    }
    
    /**
     * 测试传入无效值时抛出异常
     */
    public function testInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        BillItemStatus::from('invalid_status');
    }
} 