<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;

/**
 * @internal
 */
#[CoversClass(BillItemStatus::class)]
final class BillItemStatusTest extends AbstractEnumTestCase
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
     * 测试 toArray 方法
     */
    public function testToArray(): void
    {
        $instance = BillItemStatus::PENDING;
        $array = $instance->toArray();

        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);

        $this->assertSame('pending', $array['value']);
        $this->assertSame('待处理', $array['label']);

        // 测试其他枚举值
        $processedArray = BillItemStatus::PROCESSED->toArray();
        $this->assertSame('processed', $processedArray['value']);
        $this->assertSame('已处理', $processedArray['label']);
    }

    /**
     * 测试 tryFrom() 方法的无效输入处理
     */
    public function testTryFromInvalidInput(): void
    {
        // PHPStan 可以推断这些调用返回 null，但我们仍需要显式测试这个行为
        $this->assertNull(BillItemStatus::tryFrom('invalid_status')); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertNull(BillItemStatus::tryFrom('')); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertNull(BillItemStatus::tryFrom('PENDING')); // @phpstan-ignore method.alreadyNarrowedType
    }

    /**
     * 测试 tryFrom() 方法的有效输入处理
     */
    public function testTryFromValidInput(): void
    {
        $result = BillItemStatus::tryFrom('pending');
        $this->assertSame(BillItemStatus::PENDING, $result);

        $result = BillItemStatus::tryFrom('processed');
        $this->assertSame(BillItemStatus::PROCESSED, $result);
    }

    /**
     * 测试标签唯一性验证
     */
    public function testLabelUniqueness(): void
    {
        $labels = [];
        foreach (BillItemStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertNotContains($label, $labels, "标签 '{$label}' 重复了");
            $labels[] = $label;
        }
    }

    /**
     * 测试值唯一性验证
     */
    public function testValueUniqueness(): void
    {
        $values = [];
        foreach (BillItemStatus::cases() as $status) {
            $value = $status->value;
            $this->assertNotContains($value, $values, "值 '{$value}' 重复了");
            $values[] = $value;
        }
    }
}
