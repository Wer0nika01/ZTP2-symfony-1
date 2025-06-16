<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use PHPUnit\Framework\MockObject\MockObject;

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
        // Create mock objects for the dependencies
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        // Instantiate the service with the mocked dependencies
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
        // 1. Arrange: Set up the test data and expectations
        $user = new User();
        $plainPassword = 'a-very-secure-password';
        $hashedPassword = 'this-is-a-hashed-password';

        // Expect the password hasher to be called exactly once
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword) // It should be called with our user and plain password
            ->willReturn($hashedPassword); // It should return the predefined hashed password

        // Expect the entity manager to be called to persist the user
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user); // The argument should be our user object

        // Expect the entity manager to be called to flush changes to the database
        $this->entityManager->expects($this->once())
            ->method('flush');

        // 2. Act: Call the method that we are testing
        $this->registrationService->register($user, $plainPassword);

        // 3. Assert: Verify that the outcomes are as expected
        // Check that the user's password was set to the hashed password
        $this->assertSame($hashedPassword, $user->getPassword());
        // Check that the user was assigned the 'ROLE_USER'
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }
}