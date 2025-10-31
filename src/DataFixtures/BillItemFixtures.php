<?php

namespace Tourze\Symfony\BillOrderBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;

#[When(env: 'test')]
#[When(env: 'dev')]
class BillItemFixtures extends Fixture implements DependentFixtureInterface
{
    public const BILL_ITEM_1_REFERENCE = 'bill-item-1';
    public const BILL_ITEM_2_REFERENCE = 'bill-item-2';
    public const BILL_ITEM_3_REFERENCE = 'bill-item-3';

    public function load(ObjectManager $manager): void
    {
        $billOrder = $this->getReference(BillOrderFixtures::BILL_ORDER_1_REFERENCE, BillOrder::class);

        // 账单明细 1 - 商品A
        $billItem1 = new BillItem();
        $billItem1->setBill($billOrder);
        $billItem1->setStatus(BillItemStatus::PENDING);
        $billItem1->setProductId('100001');
        $billItem1->setProductName('商品A');
        $billItem1->setPrice('199.99');
        $billItem1->setQuantity(2);
        $billItem1->setSubtotal('399.98');
        $billItem1->setRemark('测试商品A备注');
        $manager->persist($billItem1);

        // 账单明细 2 - 商品B
        $billItem2 = new BillItem();
        $billItem2->setBill($billOrder);
        $billItem2->setStatus(BillItemStatus::PROCESSED);
        $billItem2->setProductId('100002');
        $billItem2->setProductName('商品B');
        $billItem2->setPrice('299.50');
        $billItem2->setQuantity(1);
        $billItem2->setSubtotal('299.50');
        $manager->persist($billItem2);

        // 账单明细 3 - 商品C（已退款）
        $billItem3 = new BillItem();
        $billItem3->setBill($billOrder);
        $billItem3->setStatus(BillItemStatus::REFUNDED);
        $billItem3->setProductId('100003');
        $billItem3->setProductName('商品C');
        $billItem3->setPrice('59.90');
        $billItem3->setQuantity(3);
        $billItem3->setSubtotal('179.70');
        $billItem3->setRemark('客户申请退款');
        $manager->persist($billItem3);

        $manager->flush();

        $this->addReference(self::BILL_ITEM_1_REFERENCE, $billItem1);
        $this->addReference(self::BILL_ITEM_2_REFERENCE, $billItem2);
        $this->addReference(self::BILL_ITEM_3_REFERENCE, $billItem3);
    }

    public function getDependencies(): array
    {
        return [
            BillOrderFixtures::class,
        ];
    }
}
