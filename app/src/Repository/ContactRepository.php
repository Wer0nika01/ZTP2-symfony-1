<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\User;
use App\Dto\ContactListFiltersDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * Saves a Contact entity.
     *
     * @param Contact $entity The Contact entity to save.
     * @param bool    $flush  Whether to flush the changes immediately.
     */
    public function save(Contact $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes a Contact entity.
     *
     * @param Contact $entity The Contact entity to remove.
     * @param bool    $flush  Whether to flush the changes immediately.
     */
    public function remove(Contact $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Query all contacts.
     *
     * @param User                  $author  Contacts author
     * @param ContactListFiltersDto $filters Filters
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(User $author, ContactListFiltersDto $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('contact')
            ->select('contact', 't', 'a')
            ->leftJoin('contact.tags', 't')
            ->leftJoin('contact.author', 'a');

        $queryBuilder
            ->where('contact.author = :author')
            ->setParameter('author', $author);

        $this->applyFiltersToList($queryBuilder, $filters);

        return $queryBuilder;
    }

    /**
     * Applies filters to the query builder for the list.
     *
     * @param QueryBuilder          $queryBuilder Query builder
     * @param ContactListFiltersDto $filters      Filters DTO
     *
     * @return QueryBuilder Query builder
     */
    private function applyFiltersToList(QueryBuilder $queryBuilder, ContactListFiltersDto $filters): QueryBuilder
    {

        if (!$filters->getTags()->isEmpty()) {
            $queryBuilder->leftJoin('contact.tags', 'filterTags')
            ->andWhere($queryBuilder->expr()->in('filterTags.id', ':tag_ids'))
                ->setParameter('tag_ids', $filters->getTags()->map(fn ($tag) => $tag->getId())->toArray());
        }

        return $queryBuilder;
    }
}
