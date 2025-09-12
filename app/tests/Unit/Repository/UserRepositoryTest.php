<?php

/**
 * User repository Test.
 */

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Class User repository Test.
 */
class UserRepositoryTest extends TestCase
{
    private UserRepository $userRepository;
    private $entityManagerMock;

    /**
     * Tests the upgradePassword() method for handling an unsupported user type.
     */
    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        $unsupportedUser = $this->createMock(PasswordAuthenticatedUserInterface::class);
        $newHashedPassword = 'new_hashed_password';

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Instances of "%s" are not supported.', $unsupportedUser::class));

        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');

        $this->userRepository->upgradePassword($unsupportedUser, $newHashedPassword);
    }

    /**
     * Tests the upgradePassword() method for successful password updates.
     */
    public function testUpgradePasswordSuccessfully(): void
    {
        $user = new User();
        $user->setPassword('old_hashed_password');
        $newHashedPassword = 'new_hashed_password';

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $this->userRepository->upgradePassword($user, $newHashedPassword);

        $this->assertEquals($newHashedPassword, $user->getPassword());
    }

    /**
     * Tests the delete() method for successful user deletion.
     */
    public function testDeleteUser(): void
    {
        $user = new User();

        $this->entityManagerMock->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $this->userRepository->delete($user);
    }

    /**
     * Tests the save() method for successful user enrolment.
     */
    public function testSaveUser(): void
    {
        $user = new User();

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $this->userRepository->save($user);
    }

    /**
     * Tests the toggleBlock() method.
     */
    public function testToggleBlock(): void
    {
        $user = new User();
        $user->setIsBlocked(false);

        $this->entityManagerMock->expects($this->exactly(2))
            ->method('flush');

        $this->userRepository->toggleBlock($user);

        $this->assertTrue($user->getIsBlocked());

        $this->userRepository->toggleBlock($user);
    }

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $reflectionProperty = new \ReflectionProperty(ClassMetadata::class, 'name');
        $reflectionProperty->setValue($classMetadataMock, User::class);

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $this->entityManagerMock->expects($this->any())
            ->method('getClassMetadata')
            ->with(User::class)
            ->willReturn($classMetadataMock);

        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $managerRegistryMock->expects($this->any())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->entityManagerMock);

        $this->userRepository = new UserRepository($managerRegistryMock);
    }
}
