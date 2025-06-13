<?php

namespace App\Tests\Unit\Controller\Admin;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\ContactFixtures;
use App\DataFixtures\EventFixtures;
use App\DataFixtures\TagFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface; // <-- DODANO: Import dla SluggerInterface

class UserControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@example.com';
    private const USER_EMAIL = 'user@example.com';

    private $client;
    private ?User $adminUser = null;
    private ?User $regularUser = null;
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $kernel = $this->client->getKernel();
        $container = $this->client->getContainer();

        /** @var EntityManagerInterface $this->entityManager */
        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        // --- POBIERZ ZALEŻNOŚCI Z KONTENERA I PRZEKAŻ DO FIXTUR ---
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get('security.user_password_hasher'); // Serwis do haszowania haseł

        /** @var SluggerInterface $slugger */
        $slugger = $container->get('slugger'); // <-- DODANO: Pobieranie serwisu SluggerInterface
        // Sprawdź w pliku services.yaml lub php bin/console debug:autowiring SluggerInterface
        // czy ID serwisu to 'slugger' lub pełna nazwa klasy.

        $purger = new ORMPurger($this->entityManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $executor = new ORMExecutor($this->entityManager, $purger);

        // --- Przekazywanie zależności do konstruktorów fixtur ---
        // Zakładam, że AppFixtures nie ma zależności.
        // Zakładam, że CategoryFixtures i TagFixtures dziedziczą po AbstractBaseFixtures
        // i przyjmują slugger jako argument (lub że tylko EventFixtures potrzebuje sluggera).
        // Musisz upewnić się, które fixtury faktycznie potrzebują $slugger w swoim konstruktorze.
        // Domyślnie, jeśli AbstractBaseFixtures przyjmuje slugger, to wszystkie dziedziczące też go potrzebują.

        $executor->execute([
            new AppFixtures(),
            new CategoryFixtures($slugger), // <-- ZMIENIONO: Dodano $slugger
            new UserFixtures($passwordHasher),
            new TagFixtures($slugger),      // <-- ZMIENIONO: Dodano $slugger
            new ContactFixtures(),           // Jeśli ContactFixtures też potrzebuje sluggera, dodaj go tutaj
            new EventFixtures($slugger),     // <-- ZMIENIONO: Dodano $slugger
        ], false);

        $this->entityManager->clear(); // Wyczyść EntityManager po załadowaniu fixtur

        $userRepository = $this->entityManager->getRepository(User::class);

        $this->adminUser = $userRepository->findOneByEmail(self::ADMIN_EMAIL);
        $this->regularUser = $userRepository->findOneByEmail(self::USER_EMAIL);

        self::assertNotNull($this->adminUser, 'Admin user (' . self::ADMIN_EMAIL . ') should be loaded from fixtures.');
        self::assertNotNull($this->regularUser, 'Regular user (' . self::USER_EMAIL . ') should be loaded from fixtures.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->adminUser = null;
        $this->regularUser = null;
        $this->client = null;

        if ($this->entityManager && $this->entityManager->isOpen()) {
            $this->entityManager->close();
        }
        $this->entityManager = null;
    }

    // ... reszta testów bez zmian
    public function testIndexRequiresAdminRole(): void
    {
        // Test as anonymous user (should be redirected to login)
        $this->client->request('GET', '/admin/user/');
        $this->assertResponseRedirects('/login', Response::HTTP_FOUND);

        // Test as regular user (should be denied access)
        $this->client->loginUser($this->regularUser);
        $this->client->request('GET', '/admin/user/');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Test as admin user (should grant access)
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/admin/user/');
        $this->assertResponseIsSuccessful();
    }

    public function testIndexContentDisplaysCorrectly(): void
    {
        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/user/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Użytkownicy');
        $this->assertSelectorTextContains('body', self::ADMIN_EMAIL);
        $this->assertSelectorTextContains('body', self::USER_EMAIL);
        $this->assertSelectorExists('table.table');
        $this->assertGreaterThan(1, $crawler->filter('table.table td:contains("@")')->count(), 'Expected to find multiple users on the admin user index page.');
    }

    public function testShowRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/user/' . $this->adminUser->getId());
        $this->assertResponseRedirects('/login');
    }

    public function testShowRequiresAdminRole(): void
    {
        $this->client->loginUser($this->regularUser);
        $this->client->request('GET', '/admin/user/' . $this->adminUser->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testShowAccessForAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/user/' . $this->regularUser->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Szczegóły użytkownika');
        $this->assertSelectorTextContains('body', $this->regularUser->getEmail());
        $this->assertSelectorTextContains('body', 'ROLE_USER');
        $this->assertSelectorExists('a:contains("Edytuj")');
        $this->assertSelectorExists('button:contains("Usuń")');
    }

    public function testShowNotFoundForNonExistentUser(): void
    {
        $this->client->loginUser($this->adminUser);
        $nonExistentId = 999999;
        $this->client->request('GET', '/admin/user/' . $nonExistentId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
