<?php

namespace App\Tests\Unit\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Service\CategoryService; // The service under test
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface; // Poprawiona ścieżka
use Knp\Component\Pager\PaginatorInterface; // Poprawiona ścieżka
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryServiceTest extends TestCase
{
    private MockObject|CategoryRepository $categoryRepository;
    private MockObject|PaginatorInterface $paginator;
    private MockObject|EventRepository $eventRepository;
    private CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);
        $this->eventRepository = $this->createMock(EventRepository::class);

        // Instantiate the service with its mocked dependencies
        $this->categoryService = new CategoryService(
            $this->categoryRepository,
            $this->paginator,
            $this->eventRepository
        );
    }

    /**
     * Test getPaginatedList method.
     */
    public function testGetPaginatedList(): void
    {
        $page = 1;
        $itemsPerPage = 10; // Corresponds to CategoryService::PAGINATOR_ITEMS_PER_PAGE

        // Mock a QueryBuilder
        $queryBuilder = $this->createMock(QueryBuilder::class);

        // Configure CategoryRepository to return the mocked QueryBuilder
        // IMPORTANT: Ensure public function queryAll(): QueryBuilder exists in App\Repository\CategoryRepository
        // and is not final/static.
        $this->categoryRepository->expects($this->once())
            ->method('queryAll')
            ->willReturn($queryBuilder);

        // Configure Paginator to return a PaginationInterface mock
        $pagination = $this->createMock(PaginationInterface::class);
        $this->paginator->expects($this->once())
            ->method('paginate')
            ->with($queryBuilder, $page, $itemsPerPage)
            ->willReturn($pagination);

        // Call the method under test
        $result = $this->categoryService->getPaginatedList($page);

        // Assert that the result is an instance of PaginationInterface
        $this->assertInstanceOf(PaginationInterface::class, $result);
        $this->assertSame($pagination, $result); // Assert it's the specific mock returned
    }

    /**
     * Test save method.
     */
    public function testSave(): void
    {
        // Create a mock Category entity
        $category = $this->createMock(Category::class);

        // Expect CategoryRepository's save method to be called once with the category
        // IMPORTANT: Ensure public function save(Category $category): void exists in App\Repository\CategoryRepository
        // and is not final/static.
        $this->categoryRepository->expects($this->once())
            ->method('save')
            ->with($category);

        // Call the method under test
        $this->categoryService->save($category);
    }

    /**
     * Test delete method.
     */
    public function testDelete(): void
    {
        // Create a mock Category entity
        $category = $this->createMock(Category::class);

        // Expect CategoryRepository's delete method to be called once with the category
        // IMPORTANT: Ensure public function delete(Category $category): void exists in App\Repository\CategoryRepository
        // and is not final/static.
        $this->categoryRepository->expects($this->once())
            ->method('delete')
            ->with($category);

        // Call the method under test
        $this->categoryService->delete($category);
    }

    /**
     * Data provider for canBeDeleted method tests.
     * [eventCount, expectedResult]
     */
    public function provideCanBeDeletedData(): array
    {
        return [
            'no_events_can_be_deleted' => [0, true],
            'one_event_cannot_be_deleted' => [1, false],
            'multiple_events_cannot_be_deleted' => [5, false],
        ];
    }

    /**
     * Test canBeDeleted method when event count is 0 or more.
     * @dataProvider provideCanBeDeletedData
     */
    public function testCanBeDeleted(int $eventCount, bool $expectedResult): void
    {
        // Create a mock Category entity
        $category = $this->createMock(Category::class);

        // Configure EventRepository to return the specified event count
        // IMPORTANT: Ensure public function countByCategory(Category $category): int exists in App\Repository\EventRepository
        // and is not final/static.
        $this->eventRepository->expects($this->once())
            ->method('countByCategory')
            ->with($category)
            ->willReturn($eventCount);

        // Call the method under test and assert the result
        $this->assertEquals($expectedResult, $this->categoryService->canBeDeleted($category));
    }

    /**
     * Test canBeDeleted method when NoResultException is thrown.
     */
    public function testCanBeDeletedThrowsNoResultException(): void
    {
        // Create a mock Category entity
        $category = $this->createMock(Category::class);

        // Configure EventRepository to throw NoResultException
        // IMPORTANT: Ensure public function countByCategory(Category $category): int exists in App\Repository\EventRepository
        // and is not final/static.
        $this->eventRepository->expects($this->once())
            ->method('countByCategory')
            ->with($category)
            ->willThrowException(new NoResultException());

        // Call the method under test and assert it returns false
        $this->assertFalse($this->categoryService->canBeDeleted($category));
    }

    /**
     * Test canBeDeleted method when NonUniqueResultException is thrown.
     */
    public function testCanBeDeletedThrowsNonUniqueResultException(): void
    {
        // Create a mock Category entity
        $category = $this->createMock(Category::class);

        // Configure EventRepository to throw NonUniqueResultException
        // IMPORTANT: Ensure public function countByCategory(Category $category): int exists in App\Repository\EventRepository
        // and is not final/static.
        $this->eventRepository->expects($this->once())
            ->method('countByCategory')
            ->with($category)
            ->willThrowException(new NonUniqueResultException());

        // Call the method under test and assert it returns false
        $this->assertFalse($this->categoryService->canBeDeleted($category));
    }
}
