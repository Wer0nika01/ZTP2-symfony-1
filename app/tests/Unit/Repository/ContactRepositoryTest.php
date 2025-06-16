<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\Tag; // Assuming Tag entity is used by ContactListFiltersDto
use App\Dto\ContactListFiltersDto;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr; // For mocking QueryBuilder->expr()
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection; // For filters->getTags()

class ContactRepositoryTest extends TestCase
{
    private MockObject|ManagerRegistry $managerRegistry;
    private MockObject|EntityManagerInterface $entityManager;
    // FIX: Change to MockObject|ContactRepository as we will mock the repository itself.
    private MockObject|ContactRepository $contactRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for EntityManager and ManagerRegistry
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        // Configure ManagerRegistry to return the mocked EntityManager
        $this->managerRegistry->method('getManagerForClass')
            ->with(Contact::class)
            ->willReturn($this->entityManager);
        $this->managerRegistry->method('getManager')
            ->willReturn($this->entityManager);

        // Mock ClassMetadata for ServiceEntityRepository constructor
        // This is typically needed if the original constructor is NOT disabled.
        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $mockClassMetadata->name = Contact::class;
        $this->entityManager->method('getClassMetadata')
            ->with(Contact::class)
            ->willReturn($mockClassMetadata);

        // FIX: Mock the ContactRepository itself, allowing its constructor to be called,
        // but explicitly specifying which methods we intend to mock.
        // 'getEntityManager' is implicitly called by persist/remove, and 'createQueryBuilder' by queryAll.
        $this->contactRepository = $this->getMockBuilder(ContactRepository::class)
            ->setConstructorArgs([$this->managerRegistry]) // Pass the mocked registry to the constructor
            ->onlyMethods(['getEntityManager', 'createQueryBuilder']) // Specify methods to override
            ->getMock();

        // FIX: Configure the mocked repository's 'getEntityManager' method to return our mocked EntityManager.
        // This is now done correctly because 'getEntityManager' is explicitly listed in onlyMethods.
        $this->contactRepository->method('getEntityManager')->willReturn($this->entityManager);
    }

    /**
     * Test save method with flush = false.
     */
    public function testSaveNoFlush(): void
    {
        $contact = $this->createMock(Contact::class);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($contact);
        $this->entityManager->expects($this->never())
            ->method('flush');

        // Call the method on the mocked repository instance
        $this->contactRepository->save($contact, false);
    }

    /**
     * Test save method with flush = true.
     */
    public function testSaveWithFlush(): void
    {
        $contact = $this->createMock(Contact::class);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($contact);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the method on the mocked repository instance
        $this->contactRepository->save($contact, true);
    }

    /**
     * Test remove method with flush = false.
     */
    public function testRemoveNoFlush(): void
    {
        $contact = $this->createMock(Contact::class);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($contact);
        $this->entityManager->expects($this->never())
            ->method('flush');

        // Call the method on the mocked repository instance
        $this->contactRepository->remove($contact, false);
    }

    /**
     * Test remove method with flush = true.
     */
    public function testRemoveWithFlush(): void
    {
        $contact = $this->createMock(Contact::class);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($contact);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the method on the mocked repository instance
        $this->contactRepository->remove($contact, true);
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

        $filters = new ContactListFiltersDto();
        $filters->setTags(new ArrayCollection([$tag1, $tag2]));

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        // FIX: Mock Expr\Func for the return type of expr()->in()
        $exprFuncMock = $this->createMock(Expr\Func::class);


        // FIX: Configure QueryBuilder and Expr mocks with explicit willReturnSelf()
        $queryBuilder->method('expr')->willReturn($expr);
        // FIX: expr()->in() should return a mock of Expr\Func
        $expr->method('in')->willReturn($exprFuncMock);


        // FIX: Expect the mocked ContactRepository's createQueryBuilder method to be called.
        $this->contactRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('contact')
            ->willReturn($queryBuilder);

        // Expectations for basic query parts + filter parts with explicit willReturnSelf()
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('contact', 't', 'a')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(3)) // contact.tags, contact.author, filterTags
        ->method('leftJoin')
            ->withConsecutive(
                ['contact.tags', 't'],
                ['contact.author', 'a'],
                ['contact.tags', 'filterTags'] // From applyFiltersToList
            )
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('contact.author = :author')
            ->willReturnSelf();

        // Expectations specific to applyFiltersToList
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with($this->isInstanceOf(Expr\Func::class)) // Expect the actual Expr\Func object
            ->willReturnSelf();


        // Expect setParameter for author and tag_ids with explicit willReturnSelf()
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['author', $author, null],
                ['tag_ids', [1, 2], null]
            )
            ->willReturnSelf();


        $result = $this->contactRepository->queryAll($author, $filters);

        $this->assertSame($queryBuilder, $result);
    }
}
