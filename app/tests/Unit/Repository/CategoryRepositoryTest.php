<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata; // Required for mocking internal Doctrine calls
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryRepositoryTest extends TestCase
{
    private MockObject|ManagerRegistry $managerRegistry;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|CategoryRepository $categoryRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for EntityManagerInterface
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Create a mock for ManagerRegistry
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        // When testing ServiceEntityRepository, it internally tries to get ClassMetadata for the entity.
        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $mockClassMetadata->name = Category::class; // This property is accessed, so it needs to be set.
        $this->entityManager->method('getClassMetadata')
            ->with(Category::class)
            ->willReturn($mockClassMetadata);


        // FIX: Mock the CategoryRepository itself.
        // Pass real dependency to the constructor.
        // IMPORTANT: Only mock `getEntityManager` and `createQueryBuilder` (inherited methods)
        // to allow their original implementations for `save` and `delete` to run.
        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->setMethods(['getEntityManager', 'createQueryBuilder']) // Removed 'save' and 'delete'
            ->getMock();

        // Configure the mocked CategoryRepository to return our mocked EntityManager.
        // This ensures save() and delete() work correctly because their original implementations
        // will call $this->getEntityManager() which now returns our mock.
        $this->categoryRepository->method('getEntityManager')->willReturn($this->entityManager);
    }

    /**
     * Test the queryAll method of the CategoryRepository.
     */
    public function testQueryAll(): void
    {
        // Create a mock QueryBuilder that is expected to be returned
        $queryBuilder = $this->createMock(QueryBuilder::class);

        // Expect the mocked CategoryRepository's createQueryBuilder method to be called once
        // and return our mocked QueryBuilder.
        $this->categoryRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('category') // Ensure it's called with the correct alias as defined in queryAll()
            ->willReturn($queryBuilder);

        // Call the queryAll method on the mocked repository instance
        $result = $this->categoryRepository->queryAll();

        // Assert that the result is an instance of QueryBuilder
        $this->assertInstanceOf(QueryBuilder::class, $result);
        // Assert that the returned QueryBuilder is our specific mock
        $this->assertSame($queryBuilder, $result);
    }

    /**
     * Test the save method of the CategoryRepository.
     */
    public function testSave(): void
    {
        // Create a mock Category entity
        $category = $this->createMock(Category::class);

        // Expect the EntityManager's persist method to be called once with the category
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($category);

        // Expect the EntityManager's flush method to be called once
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the save method on the repository. Since 'save' is no longer mocked,
        // its original code will execute, which calls getEntityManager()->persist() and ->flush().
        $this->categoryRepository->save($category);
    }

    /**
     * Test the delete method of the CategoryRepository.
     */
    public function testDelete(): void
    {
        // Create a mock Category entity
        $category = $this->createMock(Category::class);

        // Expect the EntityManager's remove method to be called once with the category
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($category);

        // Expect the EntityManager's flush method to be called once
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the delete method on the repository. Since 'delete' is no longer mocked,
        // its original code will execute, which calls getEntityManager()->remove() and ->flush().
        $this->categoryRepository->delete($category);
    }
}
