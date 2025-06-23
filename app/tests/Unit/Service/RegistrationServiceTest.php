<?php

/**
 * Registration service Test.
 */

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class Registration service Test.
 */
class RegistrationServiceTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|UserPasswordHasherInterface $passwordHasher;
    private RegistrationService $registrationService;

    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->registrationService = new RegistrationService(
            $this->entityManager,
            $this->passwordHasher
        );
    }

    /**
     * Test the register method to ensure it correctly processes a new user.
     */
    public function testRegister(): void
    {
        $user = new User();
        $plainPassword = 'a-very-secure-password';
        $hashedPassword = 'this-is-a-hashed-password';

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->registrationService->register($user, $plainPassword);

        $this->assertSame($hashedPassword, $user->getPassword());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }
}
