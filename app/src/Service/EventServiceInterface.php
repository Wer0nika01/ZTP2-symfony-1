<?php

/**
 * Event Service Interface.
 */

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;
use App\Dto\EventListFiltersDto;

/**
 * Interface of Event service.
 */
interface EventServiceInterface
{
    /**
     * Get paginated list.
     *
     * @param int                 $page    Page number
     * @param User                $author  Current user
     * @param EventListFiltersDto $filters Create new scratch file from selection
     *
     * @return PaginationInterface PaginationInterface
     */
    public function getPaginatedList(int $page, User $author, EventListFiltersDto $filters): PaginationInterface;

    /**
     * Save event.
     *
     * @param Event $event Event entity
     */
    public function save(Event $event): void;

    /**
     * Delete event.
     *
     * @param Event $event Event entity
     */
    public function delete(Event $event): void;
}
