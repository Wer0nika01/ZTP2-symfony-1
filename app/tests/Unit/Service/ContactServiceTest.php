<?php

namespace App\Tests\Unit\Service;

use App\Dto\ContactListFiltersDto; // Import the DTO
use App\Entity\Contact;
use App\Entity\User;
use App\Repository\ContactRepository;
use App\Service\ContactService; // The service under test
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\QueryBuilder; // Needed for queryAll mock

class ContactServiceTest extends TestCase
{
    private MockObject|ContactRepository $contactRepository;
    private MockObject|PaginatorInterface $paginator;
    private ContactService $contactService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->contactRepository = $this->createMock(ContactRepository::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);

        // Instantiate the service with its mocked dependencies
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
        $author = $this->createMock(User::class); // Mock the User entity
        $filters = new ContactListFiltersDto(); // Create a real DTO instance
        $itemsPerPage = ContactService::PAGINATOR_ITEMS_PER_PAGE;

        // Mock a QueryBuilder that contactRepository->queryAll() would return
        $queryBuilder = $this->createMock(QueryBuilder::class);

        // Configure ContactRepository to return the mocked QueryBuilder
        $this->contactRepository->expects($this->once())
            ->method('queryAll')
            ->with($author, $filters) // Ensure DTO is passed correctly
            ->willReturn($queryBuilder);

        // Configure Paginator to return a PaginationInterface mock
        $pagination = $this->createMock(PaginationInterface::class);
        $this->paginator->expects($this->once())
            ->method('paginate')
            ->with(
                $queryBuilder,
                $page,
                $itemsPerPage,
                $this->callback(function (array $options) {
                    // Assert pagination options
                    $this->assertArrayHasKey('sortFieldAllowList', $options);
                    $this->assertEquals(['contact.id', 'contact.firstName', 'contact.lastName', 'contact.email', 'contact.company', 'contact.updatedAt', 'contact.tags'], $options['sortFieldAllowList']);
                    $this->assertEquals('contact.id', $options['defaultSortFieldName']);
                    $this->assertEquals('asc', $options['defaultSortDirection']);
                    return true;
                })
            )
            ->willReturn($pagination);

        // Call the method under test
        $result = $this->contactService->getPaginatedList($page, $author, $filters);

        // Assert that the result is an instance of PaginationInterface
        $this->assertInstanceOf(PaginationInterface::class, $result);
        $this->assertSame($pagination, $result); // Assert it's the specific mock returned
    }

    /**
     * Test save method.
     */
    public function testSave(): void
    {
        // Create a mock Contact entity
        $contact = $this->createMock(Contact::class);

        // Expect ContactRepository's save method to be called once with the contact and true
        $this->contactRepository->expects($this->once())
            ->method('save')
            ->with($contact, true);

        // Call the method under test
        $this->contactService->save($contact);
    }

    /**
     * Test remove method.
     */
    public function testRemove(): void
    {
        // Create a mock Contact entity
        $contact = $this->createMock(Contact::class);

        // Expect ContactRepository's remove method to be called once with the contact and true
        $this->contactRepository->expects($this->once())
            ->method('remove')
            ->with($contact, true);

        // Call the method under test
        $this->contactService->remove($contact);
    }
}
