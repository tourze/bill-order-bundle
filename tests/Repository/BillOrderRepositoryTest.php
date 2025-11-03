<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Repository;

use BizUserBundle\Entity\BizUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;

/**
 * @internal
 */
#[CoversClass(BillOrderRepository::class)]
#[RunTestsInSeparateProcesses]
final class BillOrderRepositoryTest extends AbstractRepositoryTestCase
{
    private BillOrderRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(BillOrderRepository::class);

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
        self::getEntityManager()->flush();
    }

    protected function createNewEntity(): BillOrder
    {
        $user = $this->createNormalUser('entity@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        $bill->setTotalAmount('50.00');
        $bill->setTitle('New Entity Bill');
        $bill->setBillNumber('BILL-NEW-ENTITY');
        $bill->setCreatedBy($user->getUserIdentifier());

        return $bill;
    }

    protected function getRepository(): BillOrderRepository
    {
        return $this->repository;
    }

    // ====================== save 和 remove 方法测试 ======================

    public function testCustomSaveWithoutFlush(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        $bill->setTotalAmount('50.00');
        $bill->setTitle('Test Bill - No Flush');
        $bill->setBillNumber('BILL-NO-FLUSH');
        $bill->setCreatedBy($user->getUserIdentifier());

        // 保存但不刷新
        $this->repository->save($bill, false);

        // 此时实体管理器中有数据，但数据库中还没有
        self::getEntityManager()->flush();

        // 验证数据已保存
        $savedBill = $this->repository->find($bill->getId());
        $this->assertNotNull($savedBill);
        $this->assertEquals('Test Bill - No Flush', $savedBill->getTitle());
    }

    public function testCustomRemoveWithFlush(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        $bill->setTotalAmount('75.00');
        $bill->setTitle('Test Bill - Remove');
        $bill->setBillNumber('BILL-REMOVE');
        $bill->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($bill);
        self::getEntityManager()->flush();

        $billId = $bill->getId();
        $this->assertNotNull($billId);

        // 删除并刷新
        $this->repository->remove($bill, true);

        // 验证数据已删除
        $removedBill = $this->repository->find($billId);
        $this->assertNull($removedBill);
    }

    public function testCustomRemoveWithoutFlush(): void
    {
        $user = $this->createNormalUser('test@example.com');
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);

        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        $bill->setTotalAmount('25.00');
        $bill->setTitle('Test Bill - Remove No Flush');
        $bill->setBillNumber('BILL-REMOVE-NO-FLUSH');
        $bill->setCreatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($bill);
        self::getEntityManager()->flush();

        $billId = $bill->getId();
        $this->assertNotNull($billId);

        // 删除但不刷新
        $this->repository->remove($bill, false);

        // 此时实体管理器标记为删除，但数据库中还有
        // 手动刷新
        self::getEntityManager()->flush();

        // 验证数据已删除
        $removedBill = $this->repository->find($billId);
        $this->assertNull($removedBill);
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
        self::getEntityManager()->flush();

        // 测试基本查询功能
        $bills = $this->repository->findBy(['status' => BillOrderStatus::PENDING]);
        $this->assertCount(2, $bills); // fixture中有一个 + 新创建的一个

        // 测试findOneBy
        $oneBill = $this->repository->findOneBy(['billNumber' => 'BILL-TEST']);
        $this->assertNotNull($oneBill);
        $this->assertEquals('Test Bill', $oneBill->getTitle());

        // 测试count方法
        $count = $this->repository->count(['status' => BillOrderStatus::PENDING]);
        $this->assertEquals(2, $count);
    }
}
