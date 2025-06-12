<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;
use App\Dto\EventListFiltersDto;

interface EventServiceInterface
{
    public function getPaginatedList(int $page, User $author, EventListFiltersDto $filters): PaginationInterface;
    public function save(Event $event): void;
    public function delete(Event $event): void;
}