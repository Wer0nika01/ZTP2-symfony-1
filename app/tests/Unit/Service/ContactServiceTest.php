<?php

/**
 * Contact service Test.
 */

namespace App\Tests\Unit\Service;

use App\Dto\ContactListFiltersDto;
use App\Entity\Contact;
use App\Entity\User;
use App\Repository\ContactRepository;
use App\Service\ContactService;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Contact service Test.
 */
class ContactServiceTest extends TestCase
{
    private MockObject|ContactRepository $contactRepository;
    private MockObject|PaginatorInterface $paginator;
    private ContactService $contactService;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->contactRepository = $this->createMock(ContactRepository::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);

        $this->contactService = new ContactService(
            $this->contactRepository,
            $this->paginator
        );
    }

    /**
     * Test getPaginatedList method.
     */
    public function testGetPaginatedList(): void
    {
        $page = 1;
        $author = $this->createMock(User::class);
        $filters = new ContactListFiltersDto();
        $itemsPerPage = ContactService::PAGINATOR_ITEMS_PER_PAGE;

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->contactRepository->expects($this->once())
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
                    $this->assertEquals(['contact.id', 'contact.firstName', 'contact.lastName', 'contact.email', 'contact.company', 'contact.updatedAt', 'contact.tags'], $options['sortFieldAllowList']);
                    $this->assertEquals('contact.id', $options['defaultSortFieldName']);
                    $this->assertEquals('asc', $options['defaultSortDirection']);

                    return true;
                })
            )
            ->willReturn($pagination);

        $result = $this->contactService->getPaginatedList($page, $author, $filters);

        $this->assertSame($pagination, $result);
    }

    /**
     * Test save method.
     */
    public function testSave(): void
    {
        $contact = $this->createMock(Contact::class);

        $this->contactRepository->expects($this->once())
            ->method('save')
            ->with($contact, true);

        $this->contactService->save($contact);
    }

    /**
     * Test remove method.
     */
    public function testRemove(): void
    {
        $contact = $this->createMock(Contact::class);

        $this->contactRepository->expects($this->once())
            ->method('remove')
            ->with($contact, true);

        $this->contactService->remove($contact);
    }
}
