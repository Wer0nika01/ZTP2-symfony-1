<?php

/**
 * Event service.
 */

namespace App\Service;

use App\Dto\EventListFiltersDto;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class Event service.
 */
class EventService implements EventServiceInterface
{
    private const PAGINATOR_ITEMS_PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param EventRepository        $eventRepository
     * @param PaginatorInterface     $paginator
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(private readonly EventRepository $eventRepository, private readonly PaginatorInterface $paginator, private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Get paginated list.
     *
     * @param int                 $page    Page number
     * @param User                $author  Current user
     * @param EventListFiltersDto $filters Filters DTO
     *
     * @return PaginationInterface PaginationInterface
     */
    public function getPaginatedList(int $page, User $author, EventListFiltersDto $filters): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->eventRepository->queryAll($author, $filters),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => ['event.id', 'event.startTime', 'event.endTime', 'event.location', 'event.isAllDay', 'event.title', 'category.title', 'event.status', 'tags.name'],
                'defaultSortFieldName' => 'event.startTime',
                'defaultSortDirection' => 'asc',
            ]
        );
    }

    /**
     * Save entity.
     *
     * @param Event $event
     */
    public function save(Event $event): void
    {
        if (null === $event->getId()) {
            $this->entityManager->persist($event);
        }
        $this->entityManager->flush();
    }

    /**
     * Delete entity.
     *
     * @param Event $event
     */
    public function delete(Event $event): void
    {
        $this->entityManager->remove($event);
        $this->entityManager->flush();
    }
}
