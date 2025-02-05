<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Villa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findOverlappingReservations(Villa $villa, \DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.villa = :villa')
            ->andWhere('r.status != :canceledStatus')
            ->andWhere(
                '(r.startDate <= :endDate AND r.endDate >= :startDate)'
            )
            ->setParameter('villa', $villa)
            ->setParameter('canceledStatus', 'canceled')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function save(Reservation $reservation, bool $flush = true): void
    {
        $this->getEntityManager()->persist($reservation);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
