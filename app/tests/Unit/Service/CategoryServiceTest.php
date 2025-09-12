<?php

/**
 * Category service Test.
 */

namespace App\Tests\Unit\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Service\CategoryService;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class Category service Test.
 */
class CategoryServiceTest extends TestCase
{
    private MockObject|CategoryRepository $categoryRepository;
    private MockObject|PaginatorInterface $paginator;
    private MockObject|EventRepository $eventRepository;
    private CategoryService $categoryService;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);
        $this->eventRepository = $this->createMock(EventRepository::class);

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
        $itemsPerPage = 10;

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->categoryRepository->expects($this->once())
            ->method('queryAll')
            ->willReturn($queryBuilder);

        $pagination = $this->createMock(PaginationInterface::class);
        $this->paginator->expects($this->once())
            ->method('paginate')
            ->with($queryBuilder, $page, $itemsPerPage)
            ->willReturn($pagination);

        $result = $this->categoryService->getPaginatedList($page);

        $this->assertSame($pagination, $result);
    }

    /**
     * Test save method.
     */
    public function testSave(): void
    {
        $category = $this->createMock(Category::class);

        $this->categoryRepository->expects($this->once())
            ->method('save')
            ->with($category);

        $this->categoryService->save($category);
    }

    /**
     * Test delete method.
     */
    public function testDelete(): void
    {
        $category = $this->createMock(Category::class);

        $this->categoryRepository->expects($this->once())
            ->method('delete')
            ->with($category);

        $this->categoryService->delete($category);
    }

    /**
     * Data provider for canBeDeleted method tests.
     * [eventCount, expectedResult].
     *
     * @return array[]
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
     *
     * @param int  $eventCount     Int event counter
     * @param bool $expectedResult Bool Expected result
     *
     * @dataProvider provideCanBeDeletedData
     */
    public function testCanBeDeleted(int $eventCount, bool $expectedResult): void
    {
        $category = $this->createMock(Category::class);

        $this->eventRepository->expects($this->once())
            ->method('countByCategory')
            ->with($category)
            ->willReturn($eventCount);

        $this->assertEquals($expectedResult, $this->categoryService->canBeDeleted($category));
    }

    /**
     * Test canBeDeleted method when NoResultException is thrown.
     */
    public function testCanBeDeletedThrowsNoResultException(): void
    {
        $category = $this->createMock(Category::class);

        $this->eventRepository->expects($this->once())
            ->method('countByCategory')
            ->with($category)
            ->willThrowException(new NoResultException());

        $this->assertFalse($this->categoryService->canBeDeleted($category));
    }

    /**
     * Test canBeDeleted method when NonUniqueResultException is thrown.
     */
    public function testCanBeDeletedThrowsNonUniqueResultException(): void
    {
        $category = $this->createMock(Category::class);

        $this->eventRepository->expects($this->once())
            ->method('countByCategory')
            ->with($category)
            ->willThrowException(new NonUniqueResultException());

        $this->assertFalse($this->categoryService->canBeDeleted($category));
    }
}
