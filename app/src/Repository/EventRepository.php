<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use App\Dto\EventListFiltersDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use DateTimeImmutable;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Query all records.
     *
     * @param User               $author  User entity
     * @param EventListFiltersDto $filters Filters
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(User $author, EventListFiltersDto $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('event')
            ->select(
                'partial event.{id, title, description, startTime, endTime, location, isAllDay, status}',
                'partial category.{id, title}',
                'partial tags.{id, name}'
            )
            ->join('event.category', 'category')
            ->leftJoin('event.tags', 'tags')
            ->andWhere('event.author = :author')
            ->setParameter('author', $author);

        return $this->applyFiltersToList($queryBuilder, $filters);
    }

    private function createBaseQueryBuilder(User $author): QueryBuilder
    {
        return $this->createQueryBuilder('event')
            ->select(
                'partial event.{id, title, description, startTime, endTime, location, isAllDay, status}',
                'partial category.{id, title}',
                'partial tags.{id, name}'
            )
            ->join('event.category', 'category')
            ->leftJoin('event.tags', 'tags')
            ->andWhere('event.author = :author')
            ->setParameter('author', $author);
    }


    /**
     * Finds active events for a specific user.
     * An event is considered active if its start time is in the past or now, and its end time is in the future or null.
     *
     * @param User $author User entity
     * @param int $limit Max number of results
     * @return Event[]
     */
    public function findActiveEvents(User $author, int $limit = 5): array
    {
        $now = new DateTimeImmutable();

        return $this->createBaseQueryBuilder($author)
            ->andWhere('event.startTime <= :now')
            ->andWhere('event.endTime IS NULL OR event.endTime >= :now')
            ->setParameter('now', $now)
            ->orderBy('event.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds upcoming events for a specific user.
     * An event is considered upcoming if its start time is in the future.
     *
     * @param User $author User entity
     * @param int $limit Max number of results
     * @return Event[]
     */
    public function findUpcomingEvents(User $author, int $limit = 5): array
    {
        $now = new DateTimeImmutable();

        return $this->createBaseQueryBuilder($author)
            ->andWhere('event.startTime > :now')
            ->setParameter('now', $now)
            ->orderBy('event.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Applies filters to the query builder for the list.
     *
     * @param QueryBuilder        $queryBuilder Query builder
     * @param EventListFiltersDto $filters      Filters
     *
     * @return QueryBuilder Query builder
     */
    private function applyFiltersToList(QueryBuilder $queryBuilder, EventListFiltersDto $filters): QueryBuilder
    {
        if ($filters->getCategory()) {
            $queryBuilder->andWhere('category.id = :categoryId')
                ->setParameter('categoryId', $filters->getCategory()->getId());
        }

        if ($filters->getStatus()) {
            $queryBuilder->andWhere('event.status = :status')
                ->setParameter('status', $filters->getStatus());
        }

        if (!$filters->getTags()->isEmpty()) {
            $queryBuilder->leftJoin('event.tags', 'filterTags')
            ->andWhere($queryBuilder->expr()->in('filterTags.id', ':tag_ids'))
                ->setParameter('tag_ids', $filters->getTags()->map(fn($tag) => $tag->getId())->toArray());
        }

        return $queryBuilder;
    }
}