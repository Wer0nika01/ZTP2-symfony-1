<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

// Needed for mocking QueryBuilder
// IMPORTANT: Changed from AbstractQuery to Query

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private $userRepositoryMock; // Mock for UserRepository
    private $translatorMock;     // Mock for TranslatorInterface
    private $paginatorMock;      // Mock for PaginatorInterface

    protected function setUp(): void
    {
        // Create mocks for all dependencies of UserService
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->paginatorMock = $this->createMock(PaginatorInterface::class);

        // Create an instance of UserService, injecting its mocks as dependencies
        $this->userService = new UserService(
            $this->userRepositoryMock,
            $this->translatorMock,
            $this->paginatorMock
        );
    }

    /**
     * Tests the getPaginatedList() method.
     * Checks if it creates a QueryBuilder and correctly calls the paginator.
     */
    public function testGetPaginatedList(): void
    {
        $page = 1;
        $expectedPagination = $this->createMock(PaginationInterface::class); // Expected pagination object

        // Mocking QueryBuilder and its methods
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())
            ->method('orderBy')
            ->with('u.email', 'ASC')
            ->willReturn($queryBuilderMock); // orderBy method should return the QueryBuilder itself

        // Expect UserRepository to return our QueryBuilder mock
        $this->userRepositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($queryBuilderMock);

        // Expect PaginatorInterface to be called once with correct arguments
        $this->paginatorMock->expects($this->once())
            ->method('paginate')
            ->with(
                $queryBuilderMock, // Expect our QueryBuilder mock
                $page,
                UserRepository::PAGINATOR_ITEMS_PER_PAGE
            )
            ->willReturn($expectedPagination); // Return the expected pagination result

        // Call the service method
        $result = $this->userService->getPaginatedList($page);

        // Assertions: Check if the result is what we expect
        $this->assertSame($expectedPagination, $result);
    }

    /**
     * Tests the getAllUsers() method.
     * Checks if it correctly calls findAll() from the repository.
     */
    public function testGetAllUsers(): void
    {
        $expectedUsers = [new User(), new User()]; // Simulated results

        // Expect UserRepository->findAll() to be called once and return our simulated data
        $this->userRepositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedUsers);

        // Call the service method
        $result = $this->userService->getAllUsers();

        // Assertions: Check if the result is what we expect
        $this->assertSame($expectedUsers, $result);
    }

    /**
     * Tests the delete() method for a user who is NOT an administrator.
     * Checks if the repository is called without checking the admin count.
     */
    public function testDeleteUserSuccessfullyWhenNotAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']); // User is not an admin

        // Expect UserRepository->countAdmins() NOT to be called
        $this->userRepositoryMock->expects($this->never())
            ->method('countAdmins');

        // Expect UserRepository->delete() to be called once with the correct user
        $this->userRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($user);

        // Call the service method
        $this->userService->delete($user);
    }

    /**
     * Tests the delete() method for a user who IS an administrator, but NOT the last one.
     * Checks if the repository is called after checking the admin count.
     */
    public function testDeleteAdminUserSuccessfullyWhenNotLastAdmin(): void
    {
        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN']); // User is an admin

        // Expect UserRepository->countAdmins() to be called once and return more than 1 admin
        $this->userRepositoryMock->expects($this->once())
            ->method('countAdmins')
            ->willReturn(2); // Simulate more than 1 admin

        // Expect TranslatorInterface NOT to be called (no exception)
        $this->translatorMock->expects($this->never())
            ->method('trans');

        // Expect UserRepository->delete() to be called once
        $this->userRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($adminUser);

        // Call the service method
        $this->userService->delete($adminUser);
    }

    /**
     * Tests the delete() method for a user who IS the last administrator.
     * Checks if RuntimeException is thrown and TranslatorInterface is called.
     */
    public function testDeleteLastAdminUserThrowsException(): void
    {
        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN']); // User is an admin

        // Expect UserRepository->countAdmins() to be called once and return 1 admin
        $this->userRepositoryMock->expects($this->once())
            ->method('countAdmins')
            ->willReturn(1); // Simulate this is the last admin

        // Expect TranslatorInterface->trans() to be called once
        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('message.cannot_delete_last_admin')
            ->willReturn('Cannot delete the last admin.'); // Simulated translation message

        // Expect RuntimeException to be thrown
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete the last admin.'); // Also check the exception message

        // Expect UserRepository->delete() NOT to be called
        $this->userRepositoryMock->expects($this->never())
            ->method('delete');

        // Call the service method
        $this->userService->delete($adminUser);
    }

    /**
     * Tests the save() method.
     * Checks if it correctly calls save() from the repository.
     */
    public function testSaveUser(): void
    {
        $user = new User();

        // Expect UserRepository->save() to be called once with the correct user
        $this->userRepositoryMock->expects($this->once())
            ->method('save')
            ->with($user);

        // Call the service method
        $this->userService->save($user);
    }

    /**
     * Tests the isEmailUnique() method for a unique email (without excluding ID).
     */
    public function testIsEmailUniqueWithoutExclusionAndIsUnique(): void
    {
        $email = 'unique@example.com';

        // Mock QueryBuilder and its methods
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())->method('where')->with('u.email = :email')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('setParameter')->with('email', $email)->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->never())->method('andWhere'); // No andWhere should be called

        // Mock Query (IMPORTANT: Changed from AbstractQuery to Query)
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())->method('getResult')->willReturn([]); // Empty result = unique

        $queryBuilderMock->expects($this->once())->method('getQuery')->willReturn($queryMock);

        // Expect UserRepository to create QueryBuilder
        $this->userRepositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($queryBuilderMock);

        // Call the service method
        $result = $this->userService->isEmailUnique($email);

        // Assertions
        $this->assertTrue($result);
    }

    /**
     * Tests the isEmailUnique() method for a non-unique email (without excluding ID).
     */
    public function testIsEmailUniqueWithoutExclusionAndIsNotUnique(): void
    {
        $email = 'existing@example.com';

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())->method('where')->with('u.email = :email')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('setParameter')->with('email', $email)->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->never())->method('andWhere');

        // Mock Query (IMPORTANT: Changed from AbstractQuery to Query)
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())->method('getResult')->willReturn([new User()]); // Result with existing user

        $queryBuilderMock->expects($this->once())->method('getQuery')->willReturn($queryMock);

        $this->userRepositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($queryBuilderMock);

        $result = $this->userService->isEmailUnique($email);

        $this->assertFalse($result);
    }

    /**
     * Tests the isEmailUnique() method with ID exclusion and a unique email.
     */
    public function testIsEmailUniqueWithExclusionAndIsUnique(): void
    {
        $email = 'unique_but_excluded@example.com';
        $excludeId = 123;

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())->method('where')->with('u.email = :email')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('andWhere')->with('u.id != :id')->willReturn($queryBuilderMock);

        // ZMIANA TUTAJ: Używamy withConsecutive() dla wielu wywołań setParameter
        $queryBuilderMock->expects($this->exactly(2)) // Oczekujemy dokładnie 2 wywołań setParameter
        ->method('setParameter')
            ->withConsecutive( // Oczekiwane argumenty dla kolejnych wywołań
                ['email', $email],
                ['id', $excludeId]
            )
            ->willReturn($queryBuilderMock);


        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())->method('getResult')->willReturn([]);

        $queryBuilderMock->expects($this->once())->method('getQuery')->willReturn($queryMock);

        $this->userRepositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($queryBuilderMock);

        $result = $this->userService->isEmailUnique($email, $excludeId);

        $this->assertTrue($result);
    }

    /**
     * Tests the findOneById() method.
     * Checks if it correctly calls find() from the repository and returns a user.
     */
    public function testFindOneByIdFound(): void
    {
        $userId = 1;
        $expectedUser = new User();

        // Expect UserRepository->find() to be called once with the correct ID
        $this->userRepositoryMock->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($expectedUser);

        // Call the service method
        $result = $this->userService->findOneById($userId);

        // Assertions: Check if the result is what we expect
        $this->assertSame($expectedUser, $result);
    }

    /**
     * Tests the findOneById() method for the case where a user is not found.
     */
    public function testFindOneByIdNotFound(): void
    {
        $userId = 999;

        // Expect UserRepository->find() to be called once and return null
        $this->userRepositoryMock->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        // Call the service method
        $result = $this->userService->findOneById($userId);

        // Assertions: Check if the result is null
        $this->assertNull($result);
    }

    /**
     * Tests the toggleBlock() method.
     * Checks if it correctly calls toggleBlock() from the repository.
     */
    public function testToggleBlock(): void
    {
        $user = new User();

        // Expect UserRepository->toggleBlock() to be called once with the correct user
        $this->userRepositoryMock->expects($this->once())
            ->method('toggleBlock')
            ->with($user);

        // Call the service method
        $this->userService->toggleBlock($user);
    }
}
