<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\User;
use App\Repository\ContactRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Dto\ContactListFiltersDto;

/**
 * Class ContactService.
 */
class ContactService implements ContactServiceInterface
{
    public const PAGINATOR_ITEMS_PER_PAGE = 10;

    private ContactRepository $contactRepository;
    private PaginatorInterface $paginator;

    public function __construct(ContactRepository $contactRepository, PaginatorInterface $paginator)
    {
        $this->contactRepository = $contactRepository;
        $this->paginator = $paginator;
    }

    /**
     * Get paginated list.
     *
     * @param int                   $page    Page number
     * @param User                  $author  Current user
     * @param ContactListFiltersDto $filters Filters DTO // ZMIANA TYPU: Z array na DTO
     *
     * @return PaginationInterface PaginationInterface
     */
    public function getPaginatedList(int $page, User $author, ContactListFiltersDto $filters): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->contactRepository->queryAll($author, $filters), // Przekaż DTO do repozytorium
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => ['contact.id', 'contact.firstName', 'contact.lastName', 'contact.email', 'contact.company', 'contact.updatedAt', 'contact.tags'],
                'defaultSortFieldName' => 'contact.id',
                'defaultSortDirection' => 'asc',
            ]
        );
    }

    /**
     * Save entity.
     *
     * @param Contact $contact Contact entity
     */
    public function save(Contact $contact): void
    {
        $this->contactRepository->save($contact, true);
    }

    /**
     * Delete entity.
     *
     * @param Contact $contact Contact entity
     */
    public function remove(Contact $contact): void
    {
        $this->contactRepository->remove($contact, true);
    }
}