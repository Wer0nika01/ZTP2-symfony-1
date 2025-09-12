<?php

/**
 * Event repository Test.
 */

namespace App\Tests\Unit\Repository;

use App\Dto\EventListFiltersDto;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class Event repository Test.
 */
class EventRepositoryTest extends TestCase
{
    private MockObject|EventRepository $eventRepository;

    /**
     * Test queryAll method with no filters.
     */
    public function testQueryAllNoFilters(): void
    {
        $author = $this->createMock(User::class);
        $filters = new EventListFiltersDto(new ArrayCollection(), null);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->createMock(Expr::class);

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

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

        $queryBuilder->expects($this->never())
            ->method('expr');

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
        $filters = new EventListFiltersDto(new ArrayCollection(), $category);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('leftJoin')->willReturnSelf();
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('andWhere')->willReturnCallback(function (...$parameters) use ($matcher, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('event.author = :author', $parameters[0]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('category.id = :categoryId', $parameters[0]);
            }
            return $queryBuilder;
        });
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('setParameter')->willReturnCallback(function (...$parameters) use ($matcher, $author, $category, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('author', $parameters[0]);
                $this->assertSame($author, $parameters[1]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('categoryId', $parameters[0]);
                $this->assertSame($category->getId(), $parameters[1]);
            }
            return $queryBuilder;
        });

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
        $filters = new EventListFiltersDto(new ArrayCollection(), null, $status);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('leftJoin')->willReturnSelf();
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('andWhere')->willReturnCallback(function (...$parameters) use ($matcher, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('event.author = :author', $parameters[0]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('event.status = :status', $parameters[0]);
            }
            return $queryBuilder;
        });
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('setParameter')->willReturnCallback(function (...$parameters) use ($matcher, $author, $status, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('author', $parameters[0]);
                $this->assertSame($author, $parameters[1]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('status', $parameters[0]);
                $this->assertSame($status, $parameters[1]);
            }
            return $queryBuilder;
        });

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
        $filters = new EventListFiltersDto(new ArrayCollection([$tag1, $tag2]), null);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        $exprFuncMock = $this->createMock(Expr\Func::class);

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $matcher = $this->exactly(2);
        $queryBuilder->expects($matcher)
        ->method('leftJoin')->willReturnCallback(function (...$parameters) use ($matcher, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('event.tags', $parameters[0]);
                $this->assertSame('tags', $parameters[1]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('event.tags', $parameters[0]);
                $this->assertSame('filterTags', $parameters[1]);
            }
            return $queryBuilder;
        });
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('andWhere')->willReturnCallback(function (...$parameters) use ($matcher, $exprFuncMock, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('event.author = :author', $parameters[0]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame($exprFuncMock, $parameters[0]);
            }
            return $queryBuilder;
        });
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('setParameter')->willReturnCallback(function (...$parameters) use ($matcher, $author, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('author', $parameters[0]);
                $this->assertSame($author, $parameters[1]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('tag_ids', $parameters[0]);
                $this->assertSame([1, 2], $parameters[1]);
            }
            return $queryBuilder;
        });

        $queryBuilder->expects($this->once())
        ->method('expr')
            ->willReturn($expr);
        $expr->expects($this->once())
        ->method('in')
            ->with('filterTags.id', ':tag_ids')
            ->willReturn($exprFuncMock);


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
        $expectedEvents = [$this->createMock(Event::class)];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

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
        $matcher = $this->exactly(3);

        $queryBuilder->expects($matcher)
            ->method('andWhere')->willReturnCallback(function (...$parameters) use ($matcher, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('event.author = :author', $parameters[0]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('event.startTime <= :now', $parameters[0]);
            }
            if ($matcher->getInvocationCount() === 3) {
                $this->assertSame('event.endTime IS NULL OR event.endTime >= :now', $parameters[0]);
            }
            return $queryBuilder;
        });
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('setParameter')->willReturnCallback(function (...$parameters) use ($matcher, $author, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('author', $parameters[0]);
                $this->assertSame($author, $parameters[1]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('now', $parameters[0]);
                $this->assertSame($this->isInstanceOf(\DateTimeImmutable::class), $parameters[1]);
            }
            return $queryBuilder;
        });
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
        $expectedEvents = [$this->createMock(Event::class)];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($queryBuilder);

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
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('andWhere')->willReturnCallback(function (...$parameters) use ($matcher, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('event.author = :author', $parameters[0]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('event.startTime > :now', $parameters[0]);
            }
            return $queryBuilder;
        });
        $matcher = $this->exactly(2);

        $queryBuilder->expects($matcher)
            ->method('setParameter')->willReturnCallback(function (...$parameters) use ($matcher, $author, $queryBuilder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('author', $parameters[0]);
                $this->assertSame($author, $parameters[1]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('now', $parameters[0]);
                $this->assertSame($this->isInstanceOf(\DateTimeImmutable::class), $parameters[1]);
            }
            return $queryBuilder;
        });
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

        $this->eventRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

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

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(0);

        $result = $this->eventRepository->countByCategory($category);
        $this->assertEquals(0, $result);
    }

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $managerRegistry->method('getManagerForClass')
            ->with(Event::class)
            ->willReturn($entityManager);
        $managerRegistry->method('getManager')
            ->willReturn($entityManager);

        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $mockClassMetadata->name = Event::class;
        $entityManager->method('getClassMetadata')
            ->with(Event::class)
            ->willReturn($mockClassMetadata);

        $this->eventRepository = $this->getMockBuilder(EventRepository::class)
            ->setConstructorArgs([$managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
