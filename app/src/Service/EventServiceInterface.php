<?php
/**
 * Event service interface.
 */

namespace App\Service;

use App\Dto\EventListFiltersDto;
use App\Dto\EventListInputFiltersDto;
use App\Entity\Event;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface EventServiceInterface.
 */
interface EventServiceInterface
{

    /**
     * Get paginated list.
     *
     * @param int $page
     * @param User $author
     *
     * @return PaginationInterface
     */
    public function getPaginatedList(int $page, User $author, EventListInputFiltersDto $filters): PaginationInterface;

}
