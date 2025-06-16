<?php

namespace App\Tests\Unit\Service;

use App\Dto\EventListFiltersDto;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Service\EventService; // The service under test
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder; // Needed for queryAll mock
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\Repository\TagRepository; // FIX: Added import for TagRepository
use App\Repository\CategoryRepository; // FIX: Added import for CategoryRepository
use Doctrine\Common\Collections\ArrayCollection; // FIX: Added import for ArrayCollection
use App\Entity\Category; // FIX: Added import for Category entity

class EventServiceTest extends TestCase
{
    private MockObject|EventRepository $eventRepository;
    private MockObject|PaginatorInterface $paginator;
    private MockObject|EntityManagerInterface $entityManager;
    private EventService $eventService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies of EventService
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Instantiate the EventService with its mocked dependencies
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
        $author = $this->createMock(User::class); // Mock the User entity

        // FIX: Instantiate EventListFiltersDto by passing ArrayCollection for tags and null for category.
        // This aligns with the error message indicating Argument #2 expects ?App\Entity\Category.
        $filters = new EventListFiltersDto(new ArrayCollection(), null);
        $itemsPerPage = 10; // Corresponds to EventService::PAGINATOR_ITEMS_PER_PAGE constant

        // Mock a QueryBuilder object that the EventRepository's queryAll method is expected to return.
        $queryBuilder = $this->createMock(QueryBuilder::class);

        // Configure the EventRepository mock:
        // Expect queryAll to be called once with the specific author and filters,
        // and return our mocked QueryBuilder.
        $this->eventRepository->expects($this->once())
            ->method('queryAll')
            ->with($author, $filters)
            ->willReturn($queryBuilder);

        // Configure the PaginatorInterface mock:
        // Expect paginate to be called once with the mocked QueryBuilder, page number, items per page,
        // and a callback to assert the pagination options.
        // It should return a mocked PaginationInterface.
        $pagination = $this->createMock(PaginationInterface::class);
        $this->paginator->expects($this->once())
            ->method('paginate')
            ->with(
                $queryBuilder,
                $page,
                $itemsPerPage,
                $this->callback(function (array $options) {
                    // Assert the expected sorting options that are passed to the paginator.
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

        // Call the method under test.
        $result = $this->eventService->getPaginatedList($page, $author, $filters);

        // Assert that the result is an instance of PaginationInterface and is the specific mock returned.
        $this->assertInstanceOf(PaginationInterface::class, $result);
        $this->assertSame($pagination, $result);
    }

    /**
     * Test the save method for a new Event entity (when getId() returns null).
     * This scenario should trigger a `persist` call on the EntityManager.
     */
    public function testSaveNewEvent(): void
    {
        // Create a mock Event entity.
        $event = $this->createMock(Event::class);

        // Configure the Event mock's getId method to return null, simulating a new entity.
        $event->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        // Expect the EntityManager's persist method to be called once with the event.
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($event);

        // Expect the EntityManager's flush method to be called once.
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the save method under test.
        $this->eventService->save($event);
    }

    /**
     * Test the save method for an existing Event entity (when getId() returns a non-null value).
     * This scenario should NOT trigger a `persist` call, only `flush`.
     */
    public function testSaveExistingEvent(): void
    {
        // Create a mock Event entity.
        $event = $this->createMock(Event::class);

        // Configure the Event mock's getId method to return a non-null value, simulating an existing entity.
        $event->expects($this->once())
            ->method('getId')
            ->willReturn(1); // Simulate an existing ID.

        // Expect the EntityManager's persist method to NOT be called.
        $this->entityManager->expects($this->never())
            ->method('persist');

        // Expect the EntityManager's flush method to be called once.
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the save method under test.
        $this->eventService->save($event);
    }

    /**
     * Test the delete method.
     * This method removes an Event entity via the EntityManager.
     */
    public function testDelete(): void
    {
        // Create a mock Event entity.
        $event = $this->createMock(Event::class);

        // Expect the EntityManager's remove method to be called once with the event.
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($event);

        // Expect the EntityManager's flush method to be called once.
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the delete method under test.
        $this->eventService->delete($event);
    }
}
