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

    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.totalPrice) as total')
            ->where('r.status != :canceledStatus')
            ->setParameter('canceledStatus', 'canceled')
            ->getQuery()
            ->getSingleResult();

        return $result['total'] ?? 0;
    }

    public function getMonthlyRevenue(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT TO_CHAR(created_at, 'YYYY-MM') as month,
                      SUM(total_price) as revenue
               FROM reservation
               WHERE status != :canceledStatus
               AND created_at >= :yearAgo
               GROUP BY month
               ORDER BY month ASC";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'canceledStatus' => 'canceled',
            'yearAgo' => (new \DateTime('-1 year'))->format('Y-m-d')
        ]);

        $monthlyData = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $monthlyData[] = [
                'month' => $row['month'],
                'revenue' => (float)$row['revenue']
            ];
        }

        return $monthlyData;
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
