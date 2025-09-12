<?php

/**
 * Avatar repository.
 */

namespace App\Repository;

use App\Entity\Avatar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class Avatar repository.
 */
class AvatarRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avatar::class);
    }

    /**
     * Save avatars.
     *
     * @param Avatar $avatar
     */
    public function save(Avatar $avatar): void
    {
        $this->getEntityManager()->persist($avatar);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete avatars.
     *
     * @param Avatar $avatar
     */
    public function delete(Avatar $avatar): void
    {
        $em = $this->getEntityManager();
        $em->remove($avatar);
        $em->flush();
    }
}
