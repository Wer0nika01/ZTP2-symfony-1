<?php

/**
 * Event  repository.
 */

namespace App\Repository;

use App\Dto\EventListFiltersDto;
use App\Dto\EventListInputFiltersDto;
use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class EventRepository.
 *
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    /**
     * Items per page.
     *
     * Use constants to define configuration options that rarely change instead
     * of specifying them in configuration files.
     * See https://symfony.com/doc/current/best_practices.html#configuration
     *
     * @constant int
     */
    public const PAGINATOR_ITEMS_PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
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
                'partial event.{id, createdAt, updatedAt, title, status}',
                'partial category.{id, title}',
                'partial tags.{id, title}'
            )
            ->join('event.category', 'category')
            ->leftJoin('event.tags', 'tags')
            ->andWhere('event.author = :author')
            ->setParameter('author', $author);

        return $this->applyFiltersToList($queryBuilder, $filters);
    }

    /**
     * Count events by category.
     *
     * @param Category $category Category
     *
     * @return int Number of events in category
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countByCategory(Category $category): int
    {
        $qb = $this->createQueryBuilder('event');

        return $qb->select($qb->expr()->countDistinct('event.id'))
            ->where('event.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Save entity.
     *
     * @param Event $event Event entity
     * @return void
     */
    public function save(Event $event): void
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete entity.
     *
     * @param Event $event Event entity
     */
    public function delete(Event $event): void
    {
        $this->getEntityManager()->remove($event);
        $this->getEntityManager()->flush();
    }

    /**
     * Apply filters to paginated list.
     *
     * @param QueryBuilder       $queryBuilder Query builder
     * @param EventListFiltersDto $filters      Filters
     *
     * @return QueryBuilder Query builder
     */
    private function applyFiltersToList(QueryBuilder $queryBuilder, EventListFiltersDto $filters): QueryBuilder
    {
        if ($filters->category instanceof Category) {
            $queryBuilder->andWhere('category = :category')
                ->setParameter('category', $filters->category);
        }

        if ($filters->tag instanceof Tag) {
            $queryBuilder->andWhere('tags IN (:tag)')
                ->setParameter('tag', $filters->tag);
        }

        if ($filters->eventStatus instanceof EventStatus) {
            $queryBuilder->andWhere('event.status = :status')
                ->setParameter('status', $filters->eventStatus->value, Types::INTEGER);
        }

        return $queryBuilder;
    }

}
