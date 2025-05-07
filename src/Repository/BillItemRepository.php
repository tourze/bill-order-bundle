<?php

namespace Tourze\Symfony\BillOrderBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;

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
}
