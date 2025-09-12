<?php

/**
 * Event repository.
 */

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Event;
use App\Entity\User;
use App\Dto\EventListFiltersDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Event repository.
 */
class EventRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Query all records.
     *
     * @param User                $author  User entity
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

    /**
     * Finds active events for a specific user.
     * An event is considered active if its start time is in the past or now, and its end time is in the future or null.
     *
     * @param User $author User entity
     * @param int  $limit  Max number of results
     *
     * @return Event[]
     */
    public function findActiveEvents(User $author, int $limit = 5): array
    {
        $now = new \DateTimeImmutable();

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
     * @param int  $limit  Max number of results
     *
     * @return Event[]
     */
    public function findUpcomingEvents(User $author, int $limit = 5): array
    {
        $now = new \DateTimeImmutable();

        return $this->createBaseQueryBuilder($author)
            ->andWhere('event.startTime > :now')
            ->setParameter('now', $now)
            ->orderBy('event.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Counts the number of events associated with a given category.
     *
     * @param Category $category The category entity to count events for
     *
     * @return int The number of events
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countByCategory(Category $category): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.category = :category')
            ->setParameter('category', $category);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Create base query builder.
     *
     * @param User $author
     *
     * @return QueryBuilder
     */
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
     * Applies filters to the query builder for the list.
     *
     * @param QueryBuilder        $queryBuilder Query builder
     * @param EventListFiltersDto $filters      Filters
     *
     * @return QueryBuilder Query builder
     */
    private function applyFiltersToList(QueryBuilder $queryBuilder, EventListFiltersDto $filters): QueryBuilder
    {
        if ($filters->getCategory() instanceof Category) {
            $queryBuilder->andWhere('category.id = :categoryId')
                ->setParameter('categoryId', $filters->getCategory()->getId());
        }

        if ($filters->getStatus() instanceof EventStatus) {
            $queryBuilder->andWhere('event.status = :status')
                ->setParameter('status', $filters->getStatus());
        }

        if (!$filters->getTags()->isEmpty()) {
            $queryBuilder->leftJoin('event.tags', 'filterTags')
                ->andWhere($queryBuilder->expr()->in('filterTags.id', ':tag_ids'))
                ->setParameter('tag_ids', $filters->getTags()->map(fn ($tag) => $tag->getId())->toArray());
        }

        return $queryBuilder;
    }
}
