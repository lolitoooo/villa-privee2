<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 *
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $lastInvoice = $this->createQueryBuilder('i')
            ->where('i.number LIKE :year')
            ->setParameter('year', $year . '-%')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastInvoice) {
            return $year . '-0001';
        }

        $lastNumber = intval(substr($lastInvoice->getNumber(), 5));
        return $year . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
