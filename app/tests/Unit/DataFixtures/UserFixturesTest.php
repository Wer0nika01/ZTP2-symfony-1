<?php

/**
 * User data fixtures Test.
 */

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\UserFixtures;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;

/**
 * Class User data fixtures Test.
 */
class UserFixturesTest extends TestCase
{
    private MockObject|UserPasswordHasherInterface $passwordHasher;
    private MockObject|ObjectManager $objectManager;
    private UserFixtures $userFixtures;
    private MockObject|ReferenceRepository $referenceRepository;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $faker = $this->createMock(Generator::class);

        $this->userFixtures = new UserFixtures($this->passwordHasher);

        $this->referenceRepository = $this->createMock(ReferenceRepository::class);
        $this->userFixtures->setReferenceRepository($this->referenceRepository);

        $reflection = new ReflectionClass($this->userFixtures);

        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setValue($this->userFixtures, $this->objectManager);

        $fakerProperty = $reflection->getProperty('faker');
        $fakerProperty->setValue($this->userFixtures, $faker);
    }

    /**
     * Tests that fixture correctly creates and fixes users and administrators.
     */
    public function testLoadData(): void
    {
        $expectedUserCount = 10;
        $expectedAdminCount = 3;
        $totalExpectedUsers = $expectedUserCount + $expectedAdminCount;

        $persistedUsers = [];

        $this->objectManager->expects($this->exactly($totalExpectedUsers))
            ->method('persist')
            ->will($this->returnCallback(function ($user) use (&$persistedUsers) {
                $this->assertInstanceOf(User::class, $user);
                $persistedUsers[] = $user;
            }));

        $this->objectManager->expects($this->exactly(2))
            ->method('flush');

        $this->passwordHasher->expects($this->exactly($totalExpectedUsers))
            ->method('hashPassword')
            ->willReturnCallback(function (User $user, string $plainPassword) {
                return sprintf('hashed_%s_%s', $user->getEmail(), $plainPassword);
            });

        $this->referenceRepository->expects($this->never())
            ->method('setReference')
            ->with($this->matchesRegularExpression('/^user_\d+$/'), $this->isInstanceOf(User::class));

        $this->referenceRepository->expects($this->never())
        ->method('setReference')
            ->with($this->matchesRegularExpression('/^admin_\d+$/'), $this->isInstanceOf(User::class));


        $reflection = new ReflectionClass($this->userFixtures);
        $loadDataMethod = $reflection->getMethod('loadData');
        try {
            $loadDataMethod->invoke($this->userFixtures);
        } catch (ReflectionException) {
        }

        $this->assertCount($totalExpectedUsers, $persistedUsers);

        for ($i = 0; $i < $expectedUserCount; $i++) {
            $user = $persistedUsers[$i];
            $expectedEmail = sprintf('user%d@example.com', $i);
            $expectedHashedPassword = sprintf('hashed_%s_%s', $expectedEmail, 'user1234');

            $this->assertEquals($expectedEmail, $user->getEmail());
            $this->assertEquals([UserRole::ROLE_USER->value], $user->getRoles());
            $this->assertEquals($expectedHashedPassword, $user->getPassword());
        }

        for ($i = 0; $i < $expectedAdminCount; $i++) {
            $admin = $persistedUsers[$expectedUserCount + $i];
            $expectedEmail = sprintf('admin%d@example.com', $i);
            $expectedHashedPassword = sprintf('hashed_%s_%s', $expectedEmail, 'admin1234');

            $this->assertEquals($expectedEmail, $admin->getEmail());
            $this->assertEquals([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value], $admin->getRoles());
            $this->assertEquals($expectedHashedPassword, $admin->getPassword());
        }
    }
}
