<?php

namespace App\Repository;

use App\Entity\QuoteLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteLine>
 *
 * @method QuoteLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuoteLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuoteLine[]    findAll()
 * @method QuoteLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteLine::class);
    }
}
