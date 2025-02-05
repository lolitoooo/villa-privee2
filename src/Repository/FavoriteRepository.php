<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\User;
use App\Entity\Villa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findByUserAndVilla(User $user, Villa $villa): ?Favorite
    {
        return $this->findOneBy(['user' => $user, 'villa' => $villa]);
    }

    public function save(Favorite $favorite): void
    {
        $this->getEntityManager()->persist($favorite);
        $this->getEntityManager()->flush();
    }

    public function remove(Favorite $favorite): void
    {
        $this->getEntityManager()->remove($favorite);
        $this->getEntityManager()->flush();
    }
}
