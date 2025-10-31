<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;

/**
 * @internal
 */
#[CoversClass(BillOrderStatus::class)]
final class BillOrderStatusTest extends AbstractEnumTestCase
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

    /**
     * 测试 toArray 方法
     */
    public function testToArray(): void
    {
        $instance = BillOrderStatus::DRAFT;
        $array = $instance->toArray();

        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);

        $this->assertSame('draft', $array['value']);
        $this->assertSame('草稿', $array['label']);

        // 测试其他枚举值
        $pendingArray = BillOrderStatus::PENDING->toArray();
        $this->assertSame('pending', $pendingArray['value']);
        $this->assertSame('待付款', $pendingArray['label']);
    }

    /**
     * 测试 from() 方法的异常处理
     */
    public function testFromExceptionHandling(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"invalid_status" is not a valid backing value for enum');
        BillOrderStatus::from('invalid_status');
    }

    /**
     * 测试 tryFrom() 方法的无效输入处理
     */
    public function testTryFromInvalidInput(): void
    {
        // PHPStan 可以推断这些调用返回 null，但我们仍需要显式测试这个行为
        $this->assertNull(BillOrderStatus::tryFrom('invalid_status')); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertNull(BillOrderStatus::tryFrom('')); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertNull(BillOrderStatus::tryFrom('DRAFT')); // @phpstan-ignore method.alreadyNarrowedType
    }

    /**
     * 测试 tryFrom() 方法的有效输入处理
     */
    public function testTryFromValidInput(): void
    {
        $result = BillOrderStatus::tryFrom('draft');
        $this->assertSame(BillOrderStatus::DRAFT, $result);

        $result = BillOrderStatus::tryFrom('pending');
        $this->assertSame(BillOrderStatus::PENDING, $result);
    }

    /**
     * 测试标签唯一性验证
     */
    public function testLabelUniqueness(): void
    {
        $labels = [];
        foreach (BillOrderStatus::cases() as $status) {
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
        foreach (BillOrderStatus::cases() as $status) {
            $value = $status->value;
            $this->assertNotContains($value, $values, "值 '{$value}' 重复了");
            $values[] = $value;
        }
    }
}
