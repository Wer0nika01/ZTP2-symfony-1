<?php

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends TestCase
{
    private UserRepository $userRepository;
    private $managerRegistryMock;
    private $entityManagerMock;
    private $classMetadataMock;
    // Removed $queryBuilderMock and $queryMock as class properties from here.
    // They will be created locally within test methods that need them for clearer isolation.

    protected function setUp(): void
    {
        // 1. Mock ClassMetadata:
        $this->classMetadataMock = $this->createMock(ClassMetadata::class);
        $reflectionProperty = new \ReflectionProperty(ClassMetadata::class, 'name');
        $reflectionProperty->setValue($this->classMetadataMock, User::class);

        // 2. Mock EntityManagerInterface:
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // Configure getClassMetadata
        $this->entityManagerMock->expects($this->any())
            ->method('getClassMetadata')
            ->with(User::class)
            ->willReturn($this->classMetadataMock);

        // IMPORTANT: No general createQueryBuilder or Query mock configurations in setUp.
        // Each test method that uses them will configure them locally and explicitly.

        // 3. Mock ManagerRegistry:
        $this->managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $this->managerRegistryMock->expects($this->any())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->entityManagerMock);

        // 4. Create UserRepository instance:
        $this->userRepository = new UserRepository($this->managerRegistryMock);
    }

    /**
     * Testuje metodę upgradePassword() dla obsługi nieobsługiwanego typu użytkownika.
     */
    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        // Tworzymy mocka, który NIE jest instancją App\Entity\User
        $unsupportedUser = $this->createMock(PasswordAuthenticatedUserInterface::class);
        $newHashedPassword = 'new_hashed_password';

        // Oczekujemy, że rzucony zostanie UnsupportedUserException
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Instances of "%s" are not supported.', get_class($unsupportedUser)));

        // Oczekujemy, że EntityManager NIE zostanie wywołany
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');

        // Wywołujemy metodę, którą testujemy
        $this->userRepository->upgradePassword($unsupportedUser, $newHashedPassword);
    }

    /**
     * Testuje metodę upgradePassword() dla pomyślnej aktualizacji hasła.
     */
    public function testUpgradePasswordSuccessfully(): void
    {
        $user = new User();
        $user->setPassword('old_hashed_password'); // Ustawiamy stare hasło
        $newHashedPassword = 'new_hashed_password';

        // Oczekujemy, że EntityManager->persist() zostanie wywołany raz z poprawnym użytkownikiem
        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($user);

        // Oczekujemy, że EntityManager->flush() zostanie wywołany raz
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // Wywołujemy metodę, którą testujemy
        $this->userRepository->upgradePassword($user, $newHashedPassword);

        // Asercje: Sprawdzamy, czy hasło użytkownika zostało zaktualizowane
        $this->assertEquals($newHashedPassword, $user->getPassword());
    }

    /**
     * Testuje metodę delete() dla pomyślnego usunięcia użytkownika.
     */
    public function testDeleteUser(): void
    {
        $user = new User();

        // Oczekujemy, że EntityManager->remove() zostanie wywołany raz z poprawnym użytkownikiem
        $this->entityManagerMock->expects($this->once())
            ->method('remove')
            ->with($user);

        // Oczekujemy, że EntityManager->flush() zostanie wywołany raz
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // Wywołujemy metodę, którą testujemy
        $this->userRepository->delete($user);
    }

    /**
     * Testuje metodę save() dla pomyślnego zapisu użytkownika.
     */
    public function testSaveUser(): void
    {
        $user = new User();

        // Oczekujemy, że EntityManager->persist() zostanie wywołany raz z poprawnym użytkownikiem
        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($user);

        // Oczekujemy, że EntityManager->flush() zostanie wywołany raz
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // Wywołujemy metodę, którą testujemy
        $this->userRepository->save($user);
    }

    /**
     * Testuje metodę countAdmins() dla poprawnego liczenia administratorów.
     */
    public function testCountAdmins(): void
    {
        $expectedAdminCount = 5; // Symulujemy, że jest 5 adminów

        // Tworzymy MOCKI QueryBuilder i Query LOKALNIE w teście
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryMock = $this->createMock(Query::class);

        // Konfigurujemy EntityManagerMock, aby zwrócił nasz LOKALNY QueryBuilderMock
        // ZMIANA: oczekujemy argumentu 'u', bo to jest przekazywane do EntityManager
        $this->entityManagerMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($queryBuilderMock);

        // Konfigurujemy QueryBuilderMock
        // ZMIANA: Oczekujemy tylko JEDNEGO wywołania select() z 'COUNT(u.id)'
        // Zakładamy, że wewnętrzne select('u') z ServiceEntityRepository jest "niewidoczne"
        // dla naszego mocka, lub jest nadpisywane.
        $queryBuilderMock->expects($this->once())
            ->method('select')
            ->with('COUNT(u.id)')
            ->willReturn($queryBuilderMock);

        // Dodajemy oczekiwanie na wywołanie from() przez ServiceEntityRepository
        $queryBuilderMock->expects($this->once())
            ->method('from')
            ->with(User::class, 'u')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('where')
            ->with('u.roles LIKE :role')
            ->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())
            ->method('setParameter')
            ->with('role', '%"ROLE_ADMIN"%')
            ->willReturn($queryBuilderMock);

        // Konfigurujemy QueryBuilderMock, aby zwrócił nasz LOKALNY QueryMock, gdy wywołane zostanie getQuery()
        $queryBuilderMock->expects($this->once())
            ->method('getQuery')
            ->willReturn($queryMock);

        // Konfigurujemy QueryMock, aby zwrócił int, gdy wywołane zostanie getSingleScalarResult()
        $queryMock->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($expectedAdminCount);


        // Wywołujemy metodę, którą testujemy
        $result = $this->userRepository->countAdmins();

        // Asercje
        $this->assertEquals($expectedAdminCount, $result);
    }

    /**
     * Testuje metodę toggleBlock().
     */
    public function testToggleBlock(): void
    {
        $user = new User();
        $user->setIsBlocked(false); // Użytkownik jest początkowo odblokowany

        // Oczekujemy 2 wywołań flush()
        $this->entityManagerMock->expects($this->exactly(2))
            ->method('flush');

        // Wywołujemy metodę serwisu - pierwsze wywołanie
        $this->userRepository->toggleBlock($user);

        // Asercje: Sprawdzamy, czy status zablokowania został zmieniony na true
        $this->assertTrue($user->getIsBlocked());

        // Ponowne wywołanie, aby sprawdzić, czy działa przełączanie - drugie wywołanie
        $this->userRepository->toggleBlock($user);
        $this->assertFalse($user->getIsBlocked());
    }
}
