<?php

namespace Tourze\Symfony\BillOrderBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;

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
}
