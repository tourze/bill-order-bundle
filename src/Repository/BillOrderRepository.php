<?php

namespace Tourze\Symfony\BillOrderBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;

/**
 * @method BillOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method BillOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method BillOrder[] findAll()
 * @method BillOrder[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BillOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillOrder::class);
    }
    
    /**
     * 获取基础查询构建器
     */
    private function getBaseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')
            ->addSelect('i');
    }
    
    /**
     * 按状态查询账单
     *
     * @param BillOrderStatus $status 账单状态
     * @return BillOrder[]
     */
    public function findByStatus(BillOrderStatus $status): array
    {
        $result = $this->getBaseQueryBuilder()
            ->andWhere('o.status = :status')
            ->setParameter('status', $status->value)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
            
        return $result ?? [];
    }
    
    /**
     * 按时间范围查询账单
     *
     * @param \DateTimeInterface $startDate 开始日期
     * @param \DateTimeInterface $endDate 结束日期
     * @param BillOrderStatus|null $status 可选的状态过滤
     * @return BillOrder[]
     */
    public function findByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?BillOrderStatus $status = null
    ): array {
        $qb = $this->getBaseQueryBuilder()
            ->andWhere('o.createTime >= :startDate')
            ->andWhere('o.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('o.createTime', 'DESC');
            
        if ($status !== null) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status->value);
        }
        
        $result = $qb->getQuery()->getResult();
        return $result ?? [];
    }
    
    /**
     * 查找未付款的账单
     *
     * @return BillOrder[]
     */
    public function findUnpaidBills(): array
    {
        $result = $this->getBaseQueryBuilder()
            ->andWhere('o.status = :status')
            ->setParameter('status', BillOrderStatus::PENDING->value)
            ->orderBy('o.createTime', 'ASC')
            ->getQuery()
            ->getResult();
            
        return $result ?? [];
    }
    
    /**
     * 查找已付款但未完成的账单
     *
     * @return BillOrder[]
     */
    public function findPaidButNotCompletedBills(): array
    {
        $result = $this->getBaseQueryBuilder()
            ->andWhere('o.status = :status')
            ->setParameter('status', BillOrderStatus::PAID->value)
            ->orderBy('o.payTime', 'ASC')
            ->getQuery()
            ->getResult();
            
        return $result ?? [];
    }
    
    /**
     * 按创建者查询账单
     *
     * @param string $createdBy 创建者ID
     * @return BillOrder[]
     */
    public function findByCreator(string $createdBy): array
    {
        $result = $this->getBaseQueryBuilder()
            ->andWhere('o.createdBy = :createdBy')
            ->setParameter('createdBy', $createdBy)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
            
        return $result ?? [];
    }
    
    /**
     * 搜索账单
     *
     * @param string $keyword 关键词（支持账单编号、标题）
     * @return BillOrder[]
     */
    public function searchBills(string $keyword): array
    {
        $result = $this->getBaseQueryBuilder()
            ->andWhere('o.billNumber LIKE :keyword OR o.title LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
            
        return $result ?? [];
    }
    
    /**
     * 获取统计信息
     *
     * @return array 包含各状态账单数量和总金额的统计信息
     */
    public function getStatistics(): array
    {
        $statuses = [
            BillOrderStatus::DRAFT->value,
            BillOrderStatus::PENDING->value,
            BillOrderStatus::PAID->value,
            BillOrderStatus::COMPLETED->value,
            BillOrderStatus::CANCELLED->value
        ];
        
        $statistics = [];
        
        foreach ($statuses as $status) {
            $count = $this->count(['status' => $status]);
            
            $totalAmount = $this->createQueryBuilder('o')
                ->select('SUM(o.totalAmount) as total')
                ->andWhere('o.status = :status')
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
                
            $statistics[$status] = [
                'count' => $count,
                'totalAmount' => $totalAmount,
            ];
        }
        
        return $statistics;
    }
}
