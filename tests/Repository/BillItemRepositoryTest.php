<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Repository;

use BizUserBundle\Entity\BizUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Repository\BillItemRepository;

/**
 * @internal
 */
#[CoversClass(BillItemRepository::class)]
#[RunTestsInSeparateProcesses]
final class BillItemRepositoryTest extends AbstractRepositoryTestCase
{
    private BillItemRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(BillItemRepository::class);

        // 清理测试数据
        self::getEntityManager()->createQuery('DELETE FROM ' . BillItem::class)->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . BillOrder::class)->execute();

        // 创建基础测试数据以支持继承的测试方法
        $user = $this->createNormalUser('fixture@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::PENDING);
        $bill->setTotalAmount('100.00');
        $bill->setTitle('Fixture Bill');
        $bill->setBillNumber('BILL-FIXTURE');
        $bill->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($bill);

        $item = new BillItem();
        $item->setBill($bill);
        $item->setProductId('FIXTURE001');
        $item->setProductName('Fixture Product');
        $item->setQuantity(1);
        $item->setPrice('100.00');
        $item->setSubtotal('100.00');
        $item->setStatus(BillItemStatus::PENDING);
        $item->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($item);
        self::getEntityManager()->flush();
    }

    protected function createNewEntity(): BillItem
    {
        $user = $this->createNormalUser('entity@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::PENDING);
        $bill->setTotalAmount('100.00');
        $bill->setTitle('Test Bill');
        $bill->setBillNumber('BILL-NEW-ENTITY');
        $bill->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($bill);
        self::getEntityManager()->flush();

        $item = new BillItem();
        $item->setBill($bill);
        $item->setProductId('NEW-ENTITY-001');
        $item->setProductName('New Entity Product');
        $item->setQuantity(1);
        $item->setPrice('100.00');
        $item->setSubtotal('100.00');
        $item->setStatus(BillItemStatus::PENDING);
        $item->setCreatedBy($user->getUserIdentifier());

        return $item;
    }

    protected function getRepository(): BillItemRepository
    {
        return $this->repository;
    }

    // ====================== save 和 remove 方法测试 ======================

    public function testCustomSave(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        // 创建测试账单
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::PENDING);
        $bill->setTotalAmount('100.00');
        $bill->setTitle('Test Bill');
        $bill->setBillNumber('BILL-TEST');
        $bill->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($bill);
        self::getEntityManager()->flush();

        // 创建新的账单项
        $item = new BillItem();
        $item->setBill($bill);
        $item->setProductId('PROD001');
        $item->setProductName('Product 1');
        $item->setQuantity(2);
        $item->setPrice('50.00');
        $item->setSubtotal('100.00');
        $item->setStatus(BillItemStatus::PENDING);
        $item->setCreatedBy($user->getUserIdentifier());

        // 测试 save 方法
        $this->repository->save($item);

        // 验证数据已保存
        $savedItem = $this->repository->find($item->getId());
        $this->assertNotNull($savedItem);
        $this->assertEquals('PROD001', $savedItem->getProductId());
    }

    public function testCustomRemove(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        // 创建测试账单
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::PENDING);
        $bill->setTotalAmount('100.00');
        $bill->setTitle('Test Bill');
        $bill->setBillNumber('BILL-TEST');
        $bill->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($bill);

        // 创建测试账单项
        $item = new BillItem();
        $item->setBill($bill);
        $item->setProductId('PROD001');
        $item->setProductName('Product 1');
        $item->setQuantity(2);
        $item->setPrice('50.00');
        $item->setSubtotal('100.00');
        $item->setStatus(BillItemStatus::PENDING);
        $item->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($item);
        self::getEntityManager()->flush();

        $itemId = $item->getId();
        $this->assertNotNull($itemId);

        // 测试 remove 方法
        $this->repository->remove($item);

        // 验证数据已删除
        $removedItem = $this->repository->find($itemId);
        $this->assertNull($removedItem);
    }

    // ====================== 基础查询功能测试 ======================

    public function testBasicFindOperations(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        // 创建测试账单
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::PENDING);
        $bill->setTotalAmount('100.00');
        $bill->setTitle('Test Bill');
        $bill->setBillNumber('BILL-TEST');
        $bill->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($bill);

        // 创建测试账单项
        $item = new BillItem();
        $item->setBill($bill);
        $item->setProductId('PROD001');
        $item->setProductName('Product 1');
        $item->setQuantity(2);
        $item->setPrice('50.00');
        $item->setSubtotal('100.00');
        $item->setStatus(BillItemStatus::PENDING);
        $item->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($item);
        self::getEntityManager()->flush();

        // 测试基本查询功能
        $items = $this->repository->findBy(['bill' => $bill]);
        $this->assertCount(1, $items);
        $this->assertEquals('PROD001', $items[0]->getProductId());

        // 测试findOneBy
        $oneItem = $this->repository->findOneBy(['productId' => 'PROD001']);
        $this->assertNotNull($oneItem);
        $this->assertEquals('Product 1', $oneItem->getProductName());
    }
}
