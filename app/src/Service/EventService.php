<?php

/**
 * Evemt service.
 */

namespace App\Service;

use App\Dto\EventListFiltersDto;
use App\Dto\EventListInputFiltersDto;
use App\Entity\Enum\EventStatus;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class EventService.
 */
class EventService implements EventServiceInterface
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
     * @param CategoryServiceInterface $categoryService Category service
     * @param PaginatorInterface       $paginator       Paginator
     * @param TagServiceInterface      $tagService      Tag service
     * @param EventRepository          $eventRepository  Event repository
     */
    public function __construct(private readonly CategoryServiceInterface $categoryService, private readonly PaginatorInterface $paginator, private readonly TagServiceInterface $tagService, private readonly EventRepository $eventRepository)
    {
    }

    /**
     * Get paginated list.
     *
     * @param int                     $page    Page number
     * @param User                    $author  Events author
     * @param EventListInputFiltersDto $filters Filters
     *
     * @return PaginationInterface<SlidingPagination> Paginated list
     */
    public function getPaginatedList(int $page, User $author, EventListInputFiltersDto $filters): PaginationInterface
    {
        $filters = $this->prepareFilters($filters);

        return $this->paginator->paginate(
            $this->eventRepository->queryAll($author, $filters),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => [ 'event.id', 'event.startTime', 'event.endTime','event.location', 'event.isAllDay', 'event.title', 'category.title', 'event.status'],
                'defaultSortFieldName' => 'event.startTime',
                'defaultSortDirection' => 'asc',
            ]
        );
    }

    /**
     * Save entity.
     *
     * @param Event $event
     *
     * @return void
     */
    public function save(Event $event): void
    {
        $this->eventRepository->save($event);
    }

    /**
     * Delete entity.
     *
     * @param Event $event
     *
     * @return void
     */
    public function delete(Event $event): void
    {
        $this->eventRepository->delete($event);
    }

    /**
     * Prepare filters for the events list.
     *
     * @param EventListInputFiltersDto $filters Raw filters from request
     *
     * @return EventListFiltersDto Result filters
     */
    private function prepareFilters(EventListInputFiltersDto $filters): EventListFiltersDto
    {
        return new EventListFiltersDto(
            null !== $filters->categoryId ? $this->categoryService->findOneById($filters->categoryId) : null,
            null !== $filters->tagId ? $this->tagService->findOneById($filters->tagId) : null,
            EventStatus::tryFrom($filters->statusId)
        );
    }
}
