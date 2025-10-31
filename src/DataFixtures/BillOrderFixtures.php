<?php

namespace Tourze\Symfony\BillOrderBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;

#[When(env: 'test')]
#[When(env: 'dev')]
class BillOrderFixtures extends Fixture
{
    public const BILL_ORDER_1_REFERENCE = 'bill-order-1';
    public const BILL_ORDER_2_REFERENCE = 'bill-order-2';
    public const BILL_ORDER_3_REFERENCE = 'bill-order-3';

    public function load(ObjectManager $manager): void
    {
        // 账单 1 - 草稿状态
        $billOrder1 = new BillOrder();
        $billOrder1->setStatus(BillOrderStatus::DRAFT);
        $billOrder1->setTotalAmount('699.48');
        $billOrder1->setTitle('测试账单 - 草稿');
        $billOrder1->setBillNumber('BO202412270001');
        $billOrder1->setRemark('测试草稿账单，包含多个商品');
        $manager->persist($billOrder1);

        // 账单 2 - 待付款状态
        $billOrder2 = new BillOrder();
        $billOrder2->setStatus(BillOrderStatus::PENDING);
        $billOrder2->setTotalAmount('1299.80');
        $billOrder2->setTitle('测试账单 - 待付款');
        $billOrder2->setBillNumber('BO202412270002');
        $billOrder2->setRemark('客户下单，等待付款');
        $manager->persist($billOrder2);

        // 账单 3 - 已完成状态
        $billOrder3 = new BillOrder();
        $billOrder3->setStatus(BillOrderStatus::COMPLETED);
        $billOrder3->setTotalAmount('299.90');
        $billOrder3->setTitle('测试账单 - 已完成');
        $billOrder3->setBillNumber('BO202412270003');
        $billOrder3->setRemark('订单已完成，客户满意');
        $billOrder3->setPayTime(new \DateTimeImmutable('2024-12-27 10:30:00'));
        $manager->persist($billOrder3);

        $manager->flush();

        $this->addReference(self::BILL_ORDER_1_REFERENCE, $billOrder1);
        $this->addReference(self::BILL_ORDER_2_REFERENCE, $billOrder2);
        $this->addReference(self::BILL_ORDER_3_REFERENCE, $billOrder3);
    }
}
