<?php

/**
 * Contact repository Test.
 */

namespace App\Tests\Unit\Repository;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\Tag;
use App\Dto\ContactListFiltersDto;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Contact repository Test.
 */
class ContactRepositoryTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|ContactRepository $contactRepository;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $managerRegistry->method('getManagerForClass')
            ->with(Contact::class)
            ->willReturn($this->entityManager);
        $managerRegistry->method('getManager')
            ->willReturn($this->entityManager);

        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $mockClassMetadata->name = Contact::class;
        $this->entityManager->method('getClassMetadata')
            ->with(Contact::class)
            ->willReturn($mockClassMetadata);

        $this->contactRepository = $this->getMockBuilder(ContactRepository::class)
            ->setConstructorArgs([$managerRegistry])
            ->onlyMethods(['getEntityManager', 'createQueryBuilder'])
            ->getMock();

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

        $this->contactRepository->save($contact);
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

        $this->contactRepository->remove($contact);
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
        $exprFuncMock = $this->createMock(Expr\Func::class);


        $queryBuilder->method('expr')->willReturn($expr);
        $expr->method('in')->willReturn($exprFuncMock);


        $this->contactRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('contact')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('contact', 't', 'a')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(3))
        ->method('leftJoin')
            ->withConsecutive(
                ['contact.tags', 't'],
                ['contact.author', 'a'],
                ['contact.tags', 'filterTags']
            )
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('contact.author = :author')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with($this->isInstanceOf(Expr\Func::class))
            ->willReturnSelf();


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
