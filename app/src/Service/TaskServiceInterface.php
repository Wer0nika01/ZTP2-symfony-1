<?php
/**
 * Task service interface.
 */

namespace App\Service;

use App\Dto\TaskListFiltersDto;
use App\Dto\TaskListInputFiltersDto;
use App\Entity\Task;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface TaskServiceInterface.
 */
interface TaskServiceInterface
{

    /**
     * Get paginated list.
     *
     * @param int $page
     * @param User $author
     *
     * @return PaginationInterface
     */
    public function getPaginatedList(int $page, User $author, TaskListInputFiltersDto $filters): PaginationInterface;

}
