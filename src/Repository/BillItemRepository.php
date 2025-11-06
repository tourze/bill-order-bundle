<?php

namespace Tourze\Symfony\BillOrderBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;

/**
 * @extends ServiceEntityRepository<BillItem>
 */
#[AsRepository(entityClass: BillItem::class)]
class BillItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillItem::class);
    }

    /**
     * 保存实体
     */
    public function save(BillItem $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(BillItem $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 计算指定账单的总金额
     *
     * @param string $billId 账单ID
     * @return string 总金额
     */
    public function calculateBillTotal(string $billId): string
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT COALESCE(SUM(subtotal), 0) as total_amount
            FROM order_bill_item
            WHERE bill_id = :billId
        ";

        $stmt = $conn->executeQuery($sql, ['billId' => $billId]);
        $result = $stmt->fetchAssociative();

        $totalAmount = $result['total_amount'] ?? '0';

        return number_format((float) $totalAmount, 2, '.', '');
    }

    /**
     * 获取指定账单的项目数量
     *
     * @param string $billId 账单ID
     * @return int 项目数量
     */
    public function countByBillId(string $billId): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT COUNT(*) as count
            FROM order_bill_item
            WHERE bill_id = :billId
        ";

        $stmt = $conn->executeQuery($sql, ['billId' => $billId]);
        $result = $stmt->fetchAssociative();

        return (int) ($result['count'] ?? 0);
    }

    /**
     * 查找指定账单和产品的项目
     *
     * @param string $billId 账单ID
     * @param string $productId 产品ID
     * @return BillItem|null
     */
    public function findByBillAndProduct(string $billId, string $productId): ?BillItem
    {
        return $this->findOneBy([
            'bill' => $billId,
            'productId' => $productId,
        ]);
    }
}
