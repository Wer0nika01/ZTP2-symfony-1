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
     * Get paginated list
     *
     * @param int                 $page
     * @param User                $author
     * @param EventListFiltersDto $filters
     *
     * @return PaginationInterface
     */
    public function getPaginatedList(int $page, User $author, EventListFiltersDto $filters): PaginationInterface;

    /**
     * Save.
     *
     * @param Event $event
     */
    public function save(Event $event): void;

    /**
     * Delete.
     *
     * @param Event $event
     */
    public function delete(Event $event): void;
}
