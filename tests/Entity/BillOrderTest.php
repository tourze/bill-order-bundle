<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;

/**
 * @internal
 */
#[CoversClass(BillOrder::class)]
final class BillOrderTest extends AbstractEntityTestCase
{
    protected function createEntity(): BillOrder
    {
        return new BillOrder();
    }

    /**
     * @return \Generator<string, array{string, mixed}>
     */
    public static function propertiesProvider(): \Generator
    {
        yield 'totalAmount' => ['totalAmount', '100.50'];
        yield 'title' => ['title', '测试账单'];
        yield 'billNumber' => ['billNumber', 'BILL20230101ABCD'];
        yield 'remark' => ['remark', '测试备注'];
        yield 'status' => ['status', BillOrderStatus::PENDING];
        yield 'payTime' => ['payTime', new \DateTimeImmutable('2023-01-01 12:00:00')];
    }

    /**
     * 测试账单ID的getter和setter
     */
    public function testIdGetterSetter(): void
    {
        $order = $this->createEntity();

        // ID通常由数据库自动生成，因此我们用反射来设置它进行测试
        $reflection = new \ReflectionClass($order);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($order, '123456789');

        $this->assertSame('123456789', $order->getId());
    }

    /**
     * 测试账单toString方法
     */
    public function testToString(): void
    {
        $order = $this->createEntity();

        // 设置ID
        $reflection = new \ReflectionClass($order);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($order, '123456789');

        $this->assertSame('123456789', (string) $order);

        // 测试ID为null的情况
        $property->setValue($order, null);
        $this->assertSame('', (string) $order);
    }

    /**
     * 测试账单状态的getter和setter
     */
    public function testStatusGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setStatus(BillOrderStatus::PENDING);
        $this->assertSame(BillOrderStatus::PENDING, $order->getStatus());

        $order->setStatus(BillOrderStatus::PAID);
        $this->assertSame(BillOrderStatus::PAID, $order->getStatus());
    }

    /**
     * 测试总金额的getter和setter
     */
    public function testTotalAmountGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setTotalAmount('100.50');
        $this->assertSame('100.50', $order->getTotalAmount());

        $order->setTotalAmount('0');
        $this->assertSame('0', $order->getTotalAmount());
    }

    /**
     * 测试标题的getter和setter
     */
    public function testTitleGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setTitle('测试账单');
        $this->assertSame('测试账单', $order->getTitle());

        $order->setTitle(null);
        $this->assertNull($order->getTitle());
    }

    /**
     * 测试账单编号的getter和setter
     */
    public function testBillNumberGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setBillNumber('BILL20230101ABCD');
        $this->assertSame('BILL20230101ABCD', $order->getBillNumber());

        $order->setBillNumber(null);
        $this->assertNull($order->getBillNumber());
    }

    /**
     * 测试备注的getter和setter
     */
    public function testRemarkGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setRemark('测试备注');
        $this->assertSame('测试备注', $order->getRemark());

        $order->setRemark(null);
        $this->assertNull($order->getRemark());
    }

    /**
     * 测试付款时间的getter和setter
     */
    public function testPayTimeGetterSetter(): void
    {
        $order = $this->createEntity();
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $order->setPayTime($date);
        $this->assertSame($date, $order->getPayTime());

        $order->setPayTime(null);
        $this->assertNull($order->getPayTime());
    }

    /**
     * 测试创建时间的getter和setter
     */
    public function testCreateTimeGetterSetter(): void
    {
        $order = $this->createEntity();
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $order->setCreateTime($date);
        $this->assertSame($date, $order->getCreateTime());

        $order->setCreateTime(null);
        $this->assertNull($order->getCreateTime());
    }

    /**
     * 测试更新时间的getter和setter
     */
    public function testUpdateTimeGetterSetter(): void
    {
        $order = $this->createEntity();
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $order->setUpdateTime($date);
        $this->assertSame($date, $order->getUpdateTime());

        $order->setUpdateTime(null);
        $this->assertNull($order->getUpdateTime());
    }

    /**
     * 测试创建人的getter和setter
     */
    public function testCreatedByGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setCreatedBy('user1');
        $this->assertSame('user1', $order->getCreatedBy());

        $order->setCreatedBy(null);
        $this->assertNull($order->getCreatedBy());
    }

    /**
     * 测试更新人的getter和setter
     */
    public function testUpdatedByGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setUpdatedBy('user2');
        $this->assertSame('user2', $order->getUpdatedBy());

        $order->setUpdatedBy(null);
        $this->assertNull($order->getUpdatedBy());
    }

    /**
     * 测试创建IP的getter和setter
     */
    public function testCreatedFromIpGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setCreatedFromIp('127.0.0.1');
        $this->assertSame('127.0.0.1', $order->getCreatedFromIp());

        $order->setCreatedFromIp(null);
        $this->assertNull($order->getCreatedFromIp());
    }

    /**
     * 测试更新IP的getter和setter
     */
    public function testUpdatedFromIpGetterSetter(): void
    {
        $order = $this->createEntity();
        $order->setUpdatedFromIp('192.168.1.1');
        $this->assertSame('192.168.1.1', $order->getUpdatedFromIp());

        $order->setUpdatedFromIp(null);
        $this->assertNull($order->getUpdatedFromIp());
    }

    /**
     * 测试账单项目集合
     */
    public function testItemsCollection(): void
    {
        $order = $this->createEntity();

        // 初始应为空集合
        $this->assertCount(0, $order->getItems());

        // 添加项目
        $item1 = new BillItem();
        $order->addItem($item1);
        $this->assertCount(1, $order->getItems());
        $this->assertSame($order, $item1->getBill());

        // 添加相同项目不会重复添加
        $order->addItem($item1);
        $this->assertCount(1, $order->getItems());

        // 添加第二个项目
        $item2 = new BillItem();
        $order->addItem($item2);
        $this->assertCount(2, $order->getItems());

        // 移除项目
        $order->removeItem($item1);
        $this->assertCount(1, $order->getItems());
        $this->assertNull($item1->getBill());

        // 移除已移除的项目不会报错
        $order->removeItem($item1);
        $this->assertCount(1, $order->getItems());
    }

    /**
     * 测试计算总金额方法
     */
    public function testCalculateTotalAmount(): void
    {
        $order = $this->createEntity();

        // 创建两个模拟的账单项目
        // 必须使用具体类 BillItem 的 Mock，原因：
        // 1. BillItem 是 Doctrine Entity 类，没有对应的接口
        // 2. 测试需要验证与 getSubtotal 方法的具体交互行为
        // 3. Entity 类是数据模型，使用具体类模拟符合单元测试需求
        $item1 = $this->createMock(BillItem::class);
        $item1->method('getSubtotal')->willReturn('100.50');

        // 必须使用具体类 BillItem 的 Mock，原因：
        // 1. BillItem 是 Doctrine Entity 类，没有对应的接口
        // 2. 测试需要验证与 getSubtotal 方法的具体交互行为
        // 3. Entity 类是数据模型，使用具体类模拟符合单元测试需求
        $item2 = $this->createMock(BillItem::class);
        $item2->method('getSubtotal')->willReturn('200.25');

        // 设置items属性
        $items = new ArrayCollection([$item1, $item2]);
        $reflection = new \ReflectionClass($order);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        $property->setValue($order, $items);

        // 直接设置总金额为0
        $order->setTotalAmount('0.00');

        // 调用calculateTotalAmount方法
        $order->calculateTotalAmount();

        // 验证总金额
        $this->assertEquals('300.75', $order->getTotalAmount());
    }
}
