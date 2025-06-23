<?php

/**
 * Contact Service Interface
 */

namespace App\Service;

use App\Entity\Contact;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;
use App\Dto\ContactListFiltersDto;

/**
 * Interface of Contact service.
 */
interface ContactServiceInterface
{
    /**
     * Get paginated list.
     *
     * @param int                   $page    Page number
     * @param User                  $author  Current user
     * @param ContactListFiltersDto $filters Filters DTO
     *
     * @return PaginationInterface PaginationInterface
     */
    public function getPaginatedList(int $page, User $author, ContactListFiltersDto $filters): PaginationInterface;

    /**
     * Save.
     *
     * @param Contact $contact
     */
    public function save(Contact $contact): void;

    /**
     * Remove.
     *
     * @param Contact $contact
     */
    public function remove(Contact $contact): void;
}
