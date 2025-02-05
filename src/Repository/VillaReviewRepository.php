<?php

namespace App\Repository;

use App\Entity\VillaReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VillaReview>
 *
 * @method VillaReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method VillaReview|null findOneBy(array $criteria, array $orderBy = null)
 * @method VillaReview[]    findAll()
 * @method VillaReview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VillaReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VillaReview::class);
    }

    public function save(VillaReview $review): void
    {
        $this->getEntityManager()->persist($review);
        $this->getEntityManager()->flush();
    }

    public function remove(VillaReview $review): void
    {
        $this->getEntityManager()->remove($review);
        $this->getEntityManager()->flush();
    }

    public function getAverageRating(int $villaId): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->where('r.villa = :villaId')
            ->setParameter('villaId', $villaId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['avgRating'] ?? null;
    }

    public function findByVilla(int $villaId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.villa = :villaId')
            ->setParameter('villaId', $villaId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
