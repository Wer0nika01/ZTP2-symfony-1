<?php

/**
 * Event service Test.
 */

namespace App\Tests\Unit\Service;

use App\Dto\EventListFiltersDto;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Service\EventService;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Event service Test.
 */
class EventServiceTest extends TestCase
{
    private MockObject|EventRepository $eventRepository;
    private MockObject|PaginatorInterface $paginator;
    private MockObject|EntityManagerInterface $entityManager;
    private EventService $eventService;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->eventService = new EventService(
            $this->eventRepository,
            $this->paginator,
            $this->entityManager
        );
    }

    /**
     * Test the getPaginatedList method.
     * This method retrieves a paginated list of events based on filters and an author.
     */
    public function testGetPaginatedList(): void
    {
        $page = 1;
        $author = $this->createMock(User::class);

        $filters = new EventListFiltersDto(new ArrayCollection(), null);
        $itemsPerPage = 10;

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->eventRepository->expects($this->once())
            ->method('queryAll')
            ->with($author, $filters)
            ->willReturn($queryBuilder);

        $pagination = $this->createMock(PaginationInterface::class);
        $this->paginator->expects($this->once())
            ->method('paginate')
            ->with(
                $queryBuilder,
                $page,
                $itemsPerPage,
                $this->callback(function (array $options) {
                    $this->assertArrayHasKey('sortFieldAllowList', $options);
                    $this->assertEquals(
                        ['event.id', 'event.startTime', 'event.endTime', 'event.location', 'event.isAllDay', 'event.title', 'category.title', 'event.status', 'tags.name'],
                        $options['sortFieldAllowList']
                    );
                    $this->assertArrayHasKey('defaultSortFieldName', $options);
                    $this->assertEquals('event.startTime', $options['defaultSortFieldName']);
                    $this->assertArrayHasKey('defaultSortDirection', $options);
                    $this->assertEquals('asc', $options['defaultSortDirection']);

                    return true;
                })
            )
            ->willReturn($pagination);

        $result = $this->eventService->getPaginatedList($page, $author, $filters);

        $this->assertSame($pagination, $result);
    }

    /**
     * Test the save method for a new Event entity (when getId() returns null).
     * This scenario should trigger a `persist` call on the EntityManager.
     */
    public function testSaveNewEvent(): void
    {
        $event = $this->createMock(Event::class);

        $event->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($event);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->eventService->save($event);
    }

    /**
     * Test the save method for an existing Event entity (when getId() returns a non-null value).
     * This scenario should NOT trigger a `persist` call, only `flush`.
     */
    public function testSaveExistingEvent(): void
    {
        $event = $this->createMock(Event::class);

        $event->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->eventService->save($event);
    }

    /**
     * Test the delete method.
     * This method removes an Event entity via the EntityManager.
     */
    public function testDelete(): void
    {
        $event = $this->createMock(Event::class);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($event);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->eventService->delete($event);
    }
}
