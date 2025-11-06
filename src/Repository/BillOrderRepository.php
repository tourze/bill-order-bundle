<?php

namespace Tourze\Symfony\BillOrderBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;

/**
 * @extends ServiceEntityRepository<BillOrder>
 */
#[AsRepository(entityClass: BillOrder::class)]
class BillOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillOrder::class);
    }

    /**
     * 保存实体
     */
    public function save(BillOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(BillOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 获取按状态分组的账单统计
     *
     * @return array<string, array{count: int, totalAmount: string}>
     */
    public function getStatisticsGroupByStatus(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                status,
                COUNT(*) as count,
                COALESCE(SUM(total_amount), 0) as total_amount
            FROM order_bill_order
            GROUP BY status
        ";

        $stmt = $conn->executeQuery($sql);
        $results = $stmt->fetchAllAssociative();

        $statistics = [];
        foreach ($results as $row) {
            $statistics[$row['status']] = [
                'count' => (int) $row['count'],
                'totalAmount' => number_format((float) $row['total_amount'], 2, '.', ''),
            ];
        }

        return $statistics;
    }

    /**
     * 根据状态列表获取账单数量统计
     *
     * @param array<string> $statuses
     * @return array<string, int>
     */
    public function countByStatuses(array $statuses): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT status, COUNT(*) as count
            FROM order_bill_order
            WHERE status IN (:statuses)
            GROUP BY status
        ";

        $stmt = $conn->executeQuery($sql, ['statuses' => $statuses]);
        $results = $stmt->fetchAllAssociative();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }
}
