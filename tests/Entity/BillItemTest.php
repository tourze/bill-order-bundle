<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;

/**
 * @internal
 */
#[CoversClass(BillItem::class)]
final class BillItemTest extends AbstractEntityTestCase
{
    protected function createEntity(): BillItem
    {
        return new BillItem();
    }

    /**
     * @return \Generator<string, array{string, mixed}>
     */
    public static function propertiesProvider(): \Generator
    {
        yield 'productId' => ['productId', 'PROD001'];
        yield 'productName' => ['productName', '测试产品'];
        yield 'price' => ['price', '100.50'];
        yield 'quantity' => ['quantity', 5];
        yield 'subtotal' => ['subtotal', '502.50'];
        yield 'remark' => ['remark', '测试备注'];
        yield 'status' => ['status', BillItemStatus::PENDING];
    }

    /**
     * 测试ID的getter和setter
     */
    public function testIdGetterSetter(): void
    {
        $item = $this->createEntity();

        // ID通常由数据库自动生成，因此我们用反射来设置它进行测试
        $reflection = new \ReflectionClass($item);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($item, '123456789');

        $this->assertSame('123456789', $item->getId());
    }

    /**
     * 测试toString方法
     */
    public function testToString(): void
    {
        $item = $this->createEntity();

        // 设置ID
        $reflection = new \ReflectionClass($item);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($item, '123456789');

        $this->assertSame('123456789', (string) $item);

        // 测试ID为null的情况
        $property->setValue($item, null);
        $this->assertSame('', (string) $item);
    }

    /**
     * 测试账单关联的getter和setter
     */
    public function testBillGetterSetter(): void
    {
        $item = $this->createEntity();
        $bill = new BillOrder();
        $item->setBill($bill);
        $this->assertSame($bill, $item->getBill());

        $item->setBill(null);
        $this->assertNull($item->getBill());
    }

    /**
     * 测试产品ID的getter和setter
     */
    public function testProductIdGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setProductId('PROD001');
        $this->assertSame('PROD001', $item->getProductId());
    }

    /**
     * 测试产品名称的getter和setter
     */
    public function testProductNameGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setProductName('测试产品');
        $this->assertSame('测试产品', $item->getProductName());
    }

    /**
     * 测试价格的getter和setter
     */
    public function testPriceGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setPrice('100.50');
        $this->assertSame('100.50', $item->getPrice());
    }

    /**
     * 测试数量的getter和setter
     */
    public function testQuantityGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setQuantity(5);
        $this->assertSame(5, $item->getQuantity());
    }

    /**
     * 测试小计的getter和setter
     */
    public function testSubtotalGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setSubtotal('502.50');
        $this->assertSame('502.50', $item->getSubtotal());
    }

    /**
     * 测试备注的getter和setter
     */
    public function testRemarkGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setRemark('测试备注');
        $this->assertSame('测试备注', $item->getRemark());

        $item->setRemark(null);
        $this->assertNull($item->getRemark());
    }

    /**
     * 测试创建时间的getter和setter
     */
    public function testCreateTimeGetterSetter(): void
    {
        $item = $this->createEntity();
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $item->setCreateTime($date);
        $this->assertSame($date, $item->getCreateTime());

        $item->setCreateTime(null);
        $this->assertNull($item->getCreateTime());
    }

    /**
     * 测试更新时间的getter和setter
     */
    public function testUpdateTimeGetterSetter(): void
    {
        $item = $this->createEntity();
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $item->setUpdateTime($date);
        $this->assertSame($date, $item->getUpdateTime());

        $item->setUpdateTime(null);
        $this->assertNull($item->getUpdateTime());
    }

    /**
     * 测试创建人的getter和setter
     */
    public function testCreatedByGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setCreatedBy('user1');
        $this->assertSame('user1', $item->getCreatedBy());

        $item->setCreatedBy(null);
        $this->assertNull($item->getCreatedBy());
    }

    /**
     * 测试更新人的getter和setter
     */
    public function testUpdatedByGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setUpdatedBy('user2');
        $this->assertSame('user2', $item->getUpdatedBy());

        $item->setUpdatedBy(null);
        $this->assertNull($item->getUpdatedBy());
    }

    /**
     * 测试创建IP的getter和setter
     */
    public function testCreatedFromIpGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setCreatedFromIp('127.0.0.1');
        $this->assertSame('127.0.0.1', $item->getCreatedFromIp());

        $item->setCreatedFromIp(null);
        $this->assertNull($item->getCreatedFromIp());
    }

    /**
     * 测试更新IP的getter和setter
     */
    public function testUpdatedFromIpGetterSetter(): void
    {
        $item = $this->createEntity();
        $item->setUpdatedFromIp('192.168.1.1');
        $this->assertSame('192.168.1.1', $item->getUpdatedFromIp());

        $item->setUpdatedFromIp(null);
        $this->assertNull($item->getUpdatedFromIp());
    }

    /**
     * 测试计算小计金额方法
     */
    public function testCalculateSubtotal(): void
    {
        $item = $this->createEntity();

        // 设置价格和数量
        $item->setPrice('10.50');
        $item->setQuantity(2);

        // 验证小计是否正确计算
        $this->assertSame('21.00', $item->getSubtotal());

        // 修改数量，验证小计是否更新
        $item->setQuantity(3);
        $this->assertSame('31.50', $item->getSubtotal());

        // 修改价格，验证小计是否更新
        $item->setPrice('20.00');
        $this->assertSame('60.00', $item->getSubtotal());
    }
}
