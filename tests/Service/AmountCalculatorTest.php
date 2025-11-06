<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Service\AmountCalculator;

/**
 * 金额计算工具类测试
 */
#[CoversClass(AmountCalculator::class)]
class AmountCalculatorTest extends TestCase
{
    /**
     * 测试计算总金额
     */
    public function testCalculateTotalAmount(): void
    {
        $item1 = $this->createMockBillItem('10.50');
        $item2 = $this->createMockBillItem('20.30');
        $item3 = $this->createMockBillItem('5.20');

        $total = AmountCalculator::calculateTotalAmount([$item1, $item2, $item3]);

        $this->assertSame('36.00', $total);
    }

    /**
     * 测试计算空数组的总金额
     */
    public function testCalculateTotalAmountWithEmptyArray(): void
    {
        $total = AmountCalculator::calculateTotalAmount([]);

        $this->assertSame('0.00', $total);
    }

    /**
     * 测试计算包含无效金额的总金额
     */
    public function testCalculateTotalAmountWithInvalidAmount(): void
    {
        $item1 = $this->createMockBillItem('10.50');
        $item2 = $this->createMockBillItem(''); // 空字符串应该被跳过
        $item3 = $this->createMockBillItem('invalid'); // 无效金额应该被跳过

        $total = AmountCalculator::calculateTotalAmount([$item1, $item2, $item3]);

        $this->assertSame('10.50', $total);
    }

    /**
     * 测试计算小计金额
     */
    public function testCalculateSubtotal(): void
    {
        $subtotal = AmountCalculator::calculateSubtotal('10.50', 2);

        $this->assertSame('21.00', $subtotal);
    }

    /**
     * 测试计算小计金额 - 带小数
     */
    public function testCalculateSubtotalWithDecimal(): void
    {
        $subtotal = AmountCalculator::calculateSubtotal('15.99', 3);

        $this->assertSame('47.97', $subtotal);
    }

    /**
     * 测试验证金额格式
     */
    public function testIsValidAmount(): void
    {
        $this->assertTrue(AmountCalculator::isValidAmount('10'));
        $this->assertTrue(AmountCalculator::isValidAmount('10.50'));
        $this->assertTrue(AmountCalculator::isValidAmount('0'));
        $this->assertTrue(AmountCalculator::isValidAmount('0.01'));

        $this->assertFalse(AmountCalculator::isValidAmount(''));
        $this->assertFalse(AmountCalculator::isValidAmount('10.123')); // 超过两位小数
        $this->assertFalse(AmountCalculator::isValidAmount('abc'));
        $this->assertFalse(AmountCalculator::isValidAmount('-10'));
        $this->assertFalse(AmountCalculator::isValidAmount('10.50.50'));
    }

    /**
     * 测试验证非负金额
     */
    public function testIsNonNegativeAmount(): void
    {
        $this->assertTrue(AmountCalculator::isNonNegativeAmount('0'));
        $this->assertTrue(AmountCalculator::isNonNegativeAmount('0.01'));
        $this->assertTrue(AmountCalculator::isNonNegativeAmount('10.50'));

        $this->assertFalse(AmountCalculator::isNonNegativeAmount('-1'));
        $this->assertFalse(AmountCalculator::isNonNegativeAmount('-0.01'));
        $this->assertFalse(AmountCalculator::isNonNegativeAmount('invalid'));
    }

    /**
     * 测试验证正数金额
     */
    public function testIsPositiveAmount(): void
    {
        $this->assertTrue(AmountCalculator::isPositiveAmount('0.01'));
        $this->assertTrue(AmountCalculator::isPositiveAmount('10.50'));

        $this->assertFalse(AmountCalculator::isPositiveAmount('0'));
        $this->assertFalse(AmountCalculator::isPositiveAmount('-1'));
        $this->assertFalse(AmountCalculator::isPositiveAmount('invalid'));
    }

    /**
     * 测试格式化金额
     */
    public function testFormatAmount(): void
    {
        $this->assertSame('10.00', AmountCalculator::formatAmount('10'));
        $this->assertSame('10.50', AmountCalculator::formatAmount('10.5'));
        $this->assertSame('10.50', AmountCalculator::formatAmount('10.50'));
        $this->assertSame('10.50', AmountCalculator::formatAmount('10.500'));
        $this->assertSame('0.01', AmountCalculator::formatAmount('0.01'));
        $this->assertSame('10.00', AmountCalculator::formatAmount(10));
        $this->assertSame('10.50', AmountCalculator::formatAmount(10.5));
    }

    /**
     * 创建模拟的 BillItem 对象
     */
    private function createMockBillItem(string $subtotal): BillItem
    {
        $item = $this->createMock(BillItem::class);
        $item->method('getSubtotal')->willReturn($subtotal);

        return $item;
    }
}