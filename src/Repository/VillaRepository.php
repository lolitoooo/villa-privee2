<?php

namespace App\Repository;

use App\Entity\Villa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Villa>
 *
 * @method Villa|null find($id, $lockMode = null, $lockVersion = null)
 * @method Villa|null findOneBy(array $criteria, array $orderBy = null)
 * @method Villa[]    findAll()
 * @method Villa[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VillaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Villa::class);
    }

    public function save(Villa $villa): void
    {
        $this->getEntityManager()->persist($villa);
        $this->getEntityManager()->flush();
    }

    public function remove(Villa $villa): void
    {
        $this->getEntityManager()->remove($villa);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Villa[] Returns an array of active Villa objects
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.isActive = :val')
            ->setParameter('val', true)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Villa[] Returns an array of Villa objects owned by a specific user
     */
    public function findByOwner(int $ownerId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.owner = :val')
            ->setParameter('val', $ownerId)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Villa[] Returns an array of Villa objects filtered by search criteria
     */
    public function findByFilters(array $filters, int $page = 1): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.isActive = :active')
            ->setParameter('active', true);

        if (!empty($filters['q'])) {
            $qb->andWhere('v.title LIKE :search OR v.description LIKE :search OR v.location LIKE :search')
               ->setParameter('search', '%' . $filters['q'] . '%');
        }

        if (!empty($filters['max_price'])) {
            $qb->andWhere('v.price <= :maxPrice')
               ->setParameter('maxPrice', $filters['max_price']);
        }

        if (!empty($filters['capacity'])) {
            $qb->andWhere('v.capacity >= :capacity')
               ->setParameter('capacity', $filters['capacity']);
        }

        if (!empty($filters['bedrooms'])) {
            $qb->andWhere('v.bedrooms >= :bedrooms')
               ->setParameter('bedrooms', $filters['bedrooms']);
        }

        $itemsPerPage = 4;
        $firstResult = ($page - 1) * $itemsPerPage;

        $totalItems = (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $villas = $qb->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($itemsPerPage)
            ->setFirstResult($firstResult)
            ->getQuery()
            ->getResult();

        return [
            'villas' => $villas,
            'totalItems' => $totalItems,
            'itemsPerPage' => $itemsPerPage,
            'currentPage' => $page,
            'totalPages' => ceil($totalItems / $itemsPerPage)
        ];
    }
}
