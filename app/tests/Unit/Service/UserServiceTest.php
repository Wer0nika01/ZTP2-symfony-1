<?php

/**
 * User service Test.
 */

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class User service Test.
 */
class UserServiceTest extends TestCase
{
    private UserService $userService;
    private $userRepositoryMock;
    private $translatorMock;
    private $paginatorMock;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->paginatorMock = $this->createMock(PaginatorInterface::class);

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
        $expectedPagination = $this->createMock(PaginationInterface::class);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())
            ->method('orderBy')
            ->with('u.email', 'ASC')
            ->willReturn($queryBuilderMock);

        $this->userRepositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($queryBuilderMock);

        $this->paginatorMock->expects($this->once())
            ->method('paginate')
            ->with(
                $queryBuilderMock,
                $page,
                UserRepository::PAGINATOR_ITEMS_PER_PAGE
            )
            ->willReturn($expectedPagination);

        $result = $this->userService->getPaginatedList($page);

        $this->assertSame($expectedPagination, $result);
    }

    /**
     * Tests the getAllUsers() method.
     * Checks if it correctly calls findAll() from the repository.
     */
    public function testGetAllUsers(): void
    {
        $expectedUsers = [new User(), new User()];

        $this->userRepositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedUsers);

        $result = $this->userService->getAllUsers();

        $this->assertSame($expectedUsers, $result);
    }

    /**
     * Tests the delete() method for a user who is NOT an administrator.
     * Checks if the repository is called without checking the admin count.
     */
    public function testDeleteUserSuccessfullyWhenNotAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $this->userRepositoryMock->expects($this->never())
            ->method('countAdmins');

        $this->userRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->userService->delete($user);
    }

    /**
     * Tests the delete() method for a user who IS an administrator, but NOT the last one.
     * Checks if the repository is called after checking the admin count.
     */
    public function testDeleteAdminUserSuccessfullyWhenNotLastAdmin(): void
    {
        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN']);

        $this->userRepositoryMock->expects($this->once())
            ->method('countAdmins')
            ->willReturn(2);

        $this->translatorMock->expects($this->never())
            ->method('trans');

        $this->userRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($adminUser);

        $this->userService->delete($adminUser);
    }

    /**
     * Tests the delete() method for a user who IS the last administrator.
     * Checks if RuntimeException is thrown and TranslatorInterface is called.
     */
    public function testDeleteLastAdminUserThrowsException(): void
    {
        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN']);

        $this->userRepositoryMock->expects($this->once())
            ->method('countAdmins')
            ->willReturn(1);

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('message.cannot_delete_last_admin')
            ->willReturn('Cannot delete the last admin.');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete the last admin.');

        $this->userRepositoryMock->expects($this->never())
            ->method('delete');

        $this->userService->delete($adminUser);
    }

    /**
     * Tests the save() method.
     * Checks if it correctly calls save() from the repository.
     */
    public function testSaveUser(): void
    {
        $user = new User();

        $this->userRepositoryMock->expects($this->once())
            ->method('save')
            ->with($user);

        $this->userService->save($user);
    }

    /**
     * Tests the isEmailUnique() method for a unique email (without excluding ID).
     */
    public function testIsEmailUniqueWithoutExclusionAndIsUnique(): void
    {
        $email = 'unique@example.com';

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())->method('where')->with('u.email = :email')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('setParameter')->with('email', $email)->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->never())->method('andWhere');

        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())->method('getResult')->willReturn([]);

        $queryBuilderMock->expects($this->once())->method('getQuery')->willReturn($queryMock);

        $this->userRepositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($queryBuilderMock);

        $result = $this->userService->isEmailUnique($email);

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

        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())->method('getResult')->willReturn([new User()]);

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

        $queryBuilderMock->expects($this->exactly(2))
        ->method('setParameter')
            ->withConsecutive(
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

        $this->userRepositoryMock->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($expectedUser);

        $result = $this->userService->findOneById($userId);

        $this->assertSame($expectedUser, $result);
    }

    /**
     * Tests the findOneById() method for the case where a user is not found.
     */
    public function testFindOneByIdNotFound(): void
    {
        $userId = 999;

        $this->userRepositoryMock->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        $result = $this->userService->findOneById($userId);

        $this->assertNull($result);
    }

    /**
     * Tests the toggleBlock() method.
     * Checks if it correctly calls toggleBlock() from the repository.
     */
    public function testToggleBlock(): void
    {
        $user = new User();

        $this->userRepositoryMock->expects($this->once())
            ->method('toggleBlock')
            ->with($user);

        $this->userService->toggleBlock($user);
    }
}
