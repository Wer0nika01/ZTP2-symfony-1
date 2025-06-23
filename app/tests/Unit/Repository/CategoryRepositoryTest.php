<?php

/**
 * Category repository Test.
 */

namespace App\Tests\Unit\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class Category repository Test.
 */
class CategoryRepositoryTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|CategoryRepository $categoryRepository;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $mockClassMetadata->name = Category::class;
        $this->entityManager->method('getClassMetadata')
            ->with(Category::class)
            ->willReturn($mockClassMetadata);

        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->setConstructorArgs([$managerRegistry])
            ->setMethods(['getEntityManager', 'createQueryBuilder'])
            ->getMock();

        $this->categoryRepository->method('getEntityManager')->willReturn($this->entityManager);
    }

    /**
     * Test the queryAll method of the CategoryRepository.
     */
    public function testQueryAll(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->categoryRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('category')
            ->willReturn($queryBuilder);

        $result = $this->categoryRepository->queryAll();

        $this->assertSame($queryBuilder, $result);
    }

    /**
     * Test the save method of the CategoryRepository.
     */
    public function testSave(): void
    {
        $category = $this->createMock(Category::class);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($category);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->categoryRepository->save($category);
    }

    /**
     * Test the delete method of the CategoryRepository.
     */
    public function testDelete(): void
    {
        $category = $this->createMock(Category::class);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($category);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->categoryRepository->delete($category);
    }
}
