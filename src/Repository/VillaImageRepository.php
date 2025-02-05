<?php

namespace App\Repository;

use App\Entity\VillaImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VillaImage>
 *
 * @method VillaImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method VillaImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method VillaImage[]    findAll()
 * @method VillaImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VillaImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VillaImage::class);
    }
}
