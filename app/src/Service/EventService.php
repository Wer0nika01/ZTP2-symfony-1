<?php

namespace App\Service;

use App\Dto\EventListFiltersDto;
use App\Entity\Enum\EventStatus;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

class EventService implements EventServiceInterface
{
    private const PAGINATOR_ITEMS_PER_PAGE = 10;

    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly EventRepository $eventRepository
    ) {
    }

    public function getPaginatedList(int $page, User $author, EventListFiltersDto $filters): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->eventRepository->queryAll($author, $filters),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => [ 'event.id', 'event.startTime', 'event.endTime','event.location', 'event.isAllDay', 'event.title', 'category.title', 'event.status', 'tags.name'], // DODANE: tags.name
                'defaultSortFieldName' => 'event.startTime',
                'defaultSortDirection' => 'asc',
            ]
        );
    }

    public function save(Event $event): void
    {
        $this->eventRepository->save($event);
    }

    public function delete(Event $event): void
    {
        $this->eventRepository->remove($event);
    }
}