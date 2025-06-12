<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

interface ContactServiceInterface
{
    public function getPaginatedList(int $page, User $author, array $filters = []): PaginationInterface;
    public function save(Contact $contact): void;
    public function remove(Contact $contact): void;
}