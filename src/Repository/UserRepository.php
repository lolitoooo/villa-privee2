<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getMonthlyRegistrations(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT TO_CHAR(create_at, 'YYYY-MM') as month,
                      COUNT(id) as count
               FROM \"user\"
               WHERE create_at >= :yearAgo
               GROUP BY month
               ORDER BY month ASC";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'yearAgo' => (new \DateTime('-1 year'))->format('Y-m-d')
        ]);

        $monthlyData = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $monthlyData[] = [
                'month' => $row['month'],
                'count' => (int)$row['count']
            ];
        }

        return $monthlyData;
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
