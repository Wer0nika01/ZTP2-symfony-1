<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;
use App\Dto\ContactListFiltersDto;

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
    public function getPaginatedList(int $page, User $author, ContactListFiltersDto $filters): PaginationInterface; // ZMIANA SYGNATURY
    public function save(Contact $contact): void;
    public function remove(Contact $contact): void;
}
