<?php

/**
 * Task service.
 */

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class TaskService.
 */
class TaskService implements TaskServiceInterface
{
    /**
     * Items per page.
     *
     * Use constants to define configuration options that rarely change instead
     * of specifying them in app/config/config.yml.
     * See https://symfony.com/doc/current/best_practices.html#configuration
     *
     * @constant int
     */
    private const PAGINATOR_ITEMS_PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param TaskRepository     $taskRepository Task repository
     * @param PaginatorInterface $paginator      Paginator
     */
    public function __construct(private readonly TaskRepository $taskRepository, private readonly PaginatorInterface $paginator)
    {
    }

    public function getPaginatedList(int $page, User $author): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->taskRepository->queryAll($author),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => ['task.id', 'task.createdAt', 'task.updatedAt', 'task.title', 'category.title'],
                'defaultSortFieldName' => 'task.updatedAt',
                'defaultSortDirection' => 'desc',
            ]
        );
    }

    /**
     * Save entity.
     *
     * @param Task $task
     *
     * @return void
     */
    public function save(Task $task): void
    {
        $this->taskRepository->save($task);
    }

    /**
     * Delete entity.
     *
     * @param Task $task
     *
     * @return void
     */
    public function delete(Task $task): void
    {
        $this->taskRepository->delete($task);
    }
}
