<?php

namespace Tourze\Symfony\BillOrderBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;

/**
 * @method BillItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method BillItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method BillItem[] findAll()
 * @method BillItem[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BillItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillItem::class);
    }
    
    /**
     * 获取基础查询构建器
     */
    private function getBaseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.bill', 'b')
            ->addSelect('b');
    }
    
    /**
     * 按账单ID查询明细
     *
     * @param string $billId 账单ID
     * @return BillItem[]
     */
    public function findByBillId(string $billId): array
    {
        $result = $this->getBaseQueryBuilder()
            ->andWhere('i.bill = :billId')
            ->setParameter('billId', $billId)
            ->orderBy('i.createTime', 'ASC')
            ->getQuery()
            ->getResult();
            
        return $result ?: [];
    }
    
    /**
     * 按产品ID查询明细
     *
     * @param string $productId 产品ID
     * @return BillItem[]
     */
    public function findByProductId(string $productId): array
    {
        $result = $this->getBaseQueryBuilder()
            ->andWhere('i.productId = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('i.createTime', 'DESC')
            ->getQuery()
            ->getResult();
            
        return $result ?: [];
    }
    
    /**
     * 按状态查询明细
     *
     * @param BillItemStatus $status 明细状态
     * @return BillItem[]
     */
    public function findByStatus(BillItemStatus $status): array
    {
        $result = $this->getBaseQueryBuilder()
            ->andWhere('i.status = :status')
            ->setParameter('status', $status->value)
            ->orderBy('i.createTime', 'DESC')
            ->getQuery()
            ->getResult();
            
        return $result ?: [];
    }
    
    /**
     * 获取指定账单下所有明细的总金额
     *
     * @param string $billId 账单ID
     * @return string 总金额
     */
    public function getTotalAmountByBillId(string $billId): string
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.subtotal) as total')
            ->andWhere('i.bill = :billId')
            ->setParameter('billId', $billId)
            ->getQuery()
            ->getSingleScalarResult();
            
        return $result ?: '0';
    }
    
    /**
     * 获取热门产品统计（按产品被下单次数排序）
     *
     * @param int $limit 限制返回数量
     * @return array 包含产品ID、名称和数量的数组
     */
    public function getPopularProducts(int $limit = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->select('i.productId, i.productName, COUNT(i.id) as orderCount, SUM(i.quantity) as totalQuantity')
            ->groupBy('i.productId, i.productName')
            ->orderBy('orderCount', 'DESC')
            ->setMaxResults($limit);
            
        $result = $queryBuilder->getQuery()->getResult();
        return $result ?: [];
    }
    
    /**
     * 查找指定账单下是否已有特定产品
     *
     * @param string $billId 账单ID
     * @param string $productId 产品ID
     * @return BillItem|null 如果找到则返回项目，否则返回null
     */
    public function findOneByBillAndProduct(string $billId, string $productId): ?BillItem
    {
        return $this->getBaseQueryBuilder()
            ->andWhere('i.bill = :billId')
            ->andWhere('i.productId = :productId')
            ->setParameter('billId', $billId)
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
