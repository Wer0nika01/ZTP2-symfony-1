<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findUpcomingEventsForUser($user): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->andWhere('e.start > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.start', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
