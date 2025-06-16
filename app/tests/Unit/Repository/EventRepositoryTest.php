<?php

namespace App\Tests\Unit\Repository;

use App\Dto\EventListFiltersDto;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\EventRepository; // The repository under test
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection; // For DTO filters
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata; // For ServiceEntityRepository constructor mock
use Doctrine\ORM\NonUniqueResultException; // For countByCategory exception
use Doctrine\ORM\NoResultException; // For countByCategory exception
use Doctrine\ORM\Query; // For getQuery and getResult/getSingleScalarResult mocks
use Doctrine\ORM\Query\Expr; // For mocking QueryBuilder->expr()
use Doctrine\ORM\QueryBuilder; // FIX: Correctly imported QueryBuilder
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable; // For date comparisons

class EventRepositoryTest extends TestCase
{
    private MockObject|ManagerRegistry $managerRegistry;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|EventRepository $eventRepository; // The repository under test, mocked partially

    protected function setUp(): void
    {
        parent::setUp();

        // Mock EntityManagerInterface
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Mock ManagerRegistry
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        // Configure ManagerRegistry to return the mocked EntityManager
        $this->managerRegistry->method('getManagerForClass')
            ->with(Event::class)
            ->willReturn($this->entityManager);
        $this->managerRegistry->method('getManager')
            ->willReturn($this->entityManager);

        // Mock ClassMetadata for ServiceEntityRepository constructor
        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $mockClassMetadata->name = Event::class;
        $this->entityManager->method('getClassMetadata')
            ->with(Event::class)
            ->willReturn($mockClassMetadata);

        // Partially mock EventRepository to control inherited methods like createQueryBuilder
        $this->eventRepository = $this->getMockBuilder(EventRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder']) // We will mock createQueryBuilder
            ->getMock();
    }

    /**
     * Test queryAll method with no filters.
     */
    public function testQueryAllNoFilters(): void
    {
        $author = $this->createMock(User::class);
        // Assuming EventListFiltersDto requires TagRepository and CategoryRepository for its constructor
        // For unit tests of the repository, we can just pass mocks or dummy collections.
        // The DTO itself is a data holder here.
        $filters = new EventListFiltersDto(new ArrayCollection(), null); // Assuming tags is first, category second and nullable

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class); // For expr() call, if any, within applyFiltersToList

        // Configure the mocked repository's createQueryBuilder method
        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        // Configure QueryBuilder chain methods for the base query in queryAll
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with(
                'partial event.{id, title, description, startTime, endTime, location, isAllDay, status}',
                'partial category.{id, title}',
                'partial tags.{id, name}'
            )
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('join')
            ->with('event.category', 'category')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('event.tags', 'tags')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('event.author = :author')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('author', $author)
            ->willReturnSelf();

        // Ensure applyFiltersToList does not add further conditions for no filters
        $queryBuilder->expects($this->never())
            ->method('expr'); // No expr() calls for empty filters
        // FIX: Removed conflicting 'never' expectations for andWhere and setParameter
        // The existing 'once' expectations are sufficient to ensure no *additional* calls occur.

        $result = $this->eventRepository->queryAll($author, $filters);

        $this->assertSame($queryBuilder, $result);
    }

    /**
     * Test queryAll method with category filter.
     */
    public function testQueryAllWithCategoryFilter(): void
    {
        $author = $this->createMock(User::class);
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(1);
        $filters = new EventListFiltersDto(new ArrayCollection(), $category); // Category filter applied

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        // Configure QueryBuilder chain methods (initial part of queryAll)
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('leftJoin')->willReturnSelf();

        // FIX: Use withConsecutive for andWhere calls
        $queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['event.author = :author'], // First call from queryAll()
                ['category.id = :categoryId'] // Second call from applyFiltersToList()
            )
            ->willReturnSelf();

        // FIX: Use withConsecutive for setParameter calls
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['author', $author], // First call from queryAll()
                ['categoryId', $category->getId()] // Second call from applyFiltersToList()
            )
            ->willReturnSelf();

        $result = $this->eventRepository->queryAll($author, $filters);
        $this->assertSame($queryBuilder, $result);
    }

    /**
     * Test queryAll method with status filter.
     */
    public function testQueryAllWithStatusFilter(): void
    {
        $author = $this->createMock(User::class);
        $status = EventStatus::PERSONAL;
        $filters = new EventListFiltersDto(new ArrayCollection(), null, $status); // Status filter applied

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        // Configure QueryBuilder chain methods (initial part of queryAll)
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('leftJoin')->willReturnSelf();

        // FIX: Use withConsecutive for andWhere calls
        $queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['event.author = :author'], // First call from queryAll()
                ['event.status = :status'] // Second call from applyFiltersToList()
            )
            ->willReturnSelf();

        // FIX: Use withConsecutive for setParameter calls
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['author', $author], // First call from queryAll()
                ['status', $status] // Second call from applyFiltersToList()
            )
            ->willReturnSelf();

        $result = $this->eventRepository->queryAll($author, $filters);
        $this->assertSame($queryBuilder, $result);
    }

    /**
     * Test queryAll method with tags filter.
     */
    public function testQueryAllWithTagsFilter(): void
    {
        $author = $this->createMock(User::class);
        $tag1 = $this->createMock(Tag::class);
        $tag1->method('getId')->willReturn(1);
        $tag2 = $this->createMock(Tag::class);
        $tag2->method('getId')->willReturn(2);
        $filters = new EventListFiltersDto(new ArrayCollection([$tag1, $tag2]), null); // Tags filter applied

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        $exprFuncMock = $this->createMock(Expr\Func::class); // Mock for expr()->in() return

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        // Configure QueryBuilder chain methods (initial part of queryAll)
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->expects($this->exactly(2)) // FIX: Two leftJoin calls
        ->method('leftJoin')
            ->withConsecutive(
                ['event.tags', 'tags'], // First leftJoin from queryAll()
                ['event.tags', 'filterTags'] // Second leftJoin from applyFiltersToList()
            )
            ->willReturnSelf();

        // FIX: Use withConsecutive for andWhere calls
        $queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['event.author = :author'], // First andWhere from queryAll()
                [$exprFuncMock] // Second andWhere from applyFiltersToList() with expr()->in() result
            )
            ->willReturnSelf();

        // FIX: Use withConsecutive for setParameter calls
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['author', $author], // First setParameter from queryAll()
                ['tag_ids', [1, 2]] // Second setParameter from applyFiltersToList()
            )
            ->willReturnSelf();

        // Expectations for applyFiltersToList for tags filter
        $queryBuilder->expects($this->once()) // FIX: expr() is called once
        ->method('expr')
            ->willReturn($expr);
        $expr->expects($this->once()) // FIX: in() is called once
        ->method('in')
            ->with('filterTags.id', ':tag_ids')
            ->willReturn($exprFuncMock); // expr()->in() returns an Expr\Func object


        $result = $this->eventRepository->queryAll($author, $filters);
        $this->assertSame($queryBuilder, $result);
    }

    /**
     * Test findActiveEvents method.
     */
    public function testFindActiveEvents(): void
    {
        $author = $this->createMock(User::class);
        $limit = 5;
        $expectedEvents = [$this->createMock(Event::class)]; // Sample result

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // Configure createQueryBuilder to return our mock QueryBuilder
        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        // Configure QueryBuilder chain methods for createBaseQueryBuilder and findActiveEvents
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with(
                'partial event.{id, title, description, startTime, endTime, location, isAllDay, status}',
                'partial category.{id, title}',
                'partial tags.{id, name}'
            )
            ->willReturnSelf();
        $queryBuilder->expects($this->once())->method('join')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('leftJoin')->willReturnSelf();

        // FIX: Expect exactly three andWhere calls now
        $queryBuilder->expects($this->exactly(3))
            ->method('andWhere')
            ->withConsecutive(
                ['event.author = :author'], // From createBaseQueryBuilder
                ['event.startTime <= :now'], // First from findActiveEvents
                ['event.endTime IS NULL OR event.endTime >= :now'] // Second from findActiveEvents
            )
            ->willReturnSelf();

        // FIX: Expect exactly two setParameter calls
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['author', $author],
                ['now', $this->isInstanceOf(DateTimeImmutable::class)]
            )
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('event.startTime', 'ASC')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        // Configure Query to return results
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedEvents);

        $result = $this->eventRepository->findActiveEvents($author, $limit);
        $this->assertSame($expectedEvents, $result);
    }

    /**
     * Test findUpcomingEvents method.
     */
    public function testFindUpcomingEvents(): void
    {
        $author = $this->createMock(User::class);
        $limit = 5;
        $expectedEvents = [$this->createMock(Event::class)]; // Sample result

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // Configure createQueryBuilder to return our mock QueryBuilder
        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        // Configure QueryBuilder chain methods for createBaseQueryBuilder and findUpcomingEvents
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with(
                'partial event.{id, title, description, startTime, endTime, location, isAllDay, status}',
                'partial category.{id, title}',
                'partial tags.{id, name}'
            )
            ->willReturnSelf();
        $queryBuilder->expects($this->once())->method('join')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('leftJoin')->willReturnSelf();

        // FIX: Expect exactly two andWhere calls
        $queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['event.author = :author'], // From createBaseQueryBuilder
                ['event.startTime > :now'] // From findUpcomingEvents
            )
            ->willReturnSelf();

        // FIX: Expect exactly two setParameter calls
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['author', $author],
                ['now', $this->isInstanceOf(DateTimeImmutable::class)]
            )
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('event.startTime', 'ASC')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        // Configure Query to return results
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedEvents);

        $result = $this->eventRepository->findUpcomingEvents($author, $limit);
        $this->assertSame($expectedEvents, $result);
    }

    /**
     * Test countByCategory method.
     */
    public function testCountByCategory(): void
    {
        $category = $this->createMock(Category::class);
        $expectedCount = 5;

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // Configure createQueryBuilder to return our mock QueryBuilder
        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        // Configure QueryBuilder chain methods
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('COUNT(e.id)')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('e.category = :category')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('category', $category)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        // Configure Query to return the count
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($expectedCount);

        $result = $this->eventRepository->countByCategory($category);
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * Test countByCategory method when NoResultException is thrown (though not expected for COUNT).
     */
    public function testCountByCategoryThrowsNoResultException(): void
    {
        $category = $this->createMock(Category::class);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->eventRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // FIX: getSingleScalarResult should return 0, not throw an exception for COUNT queries
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(0);

        $result = $this->eventRepository->countByCategory($category);
        $this->assertEquals(0, $result);
    }

    /**
     * Test countByCategory method when NonUniqueResultException is thrown (though not expected for COUNT).
     */
    public function testCountByCategoryThrowsNonUniqueResultException(): void
    {
        $category = $this->createMock(Category::class);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->eventRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // FIX: getSingleScalarResult should return 0, not throw an exception for COUNT queries
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(0);

        $result = $this->eventRepository->countByCategory($category);
        $this->assertEquals(0, $result);
    }
}
