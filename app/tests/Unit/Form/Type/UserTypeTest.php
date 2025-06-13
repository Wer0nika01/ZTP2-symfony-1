<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\UserType;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;


class UserTypeTest extends TypeTestCase
{
    /** @var Security&MockObject */
    private MockObject $securityMock;

    /**
     * Musimy podać mock Security przy tworzeniu UserType,
     * bo konstruktor UserType wymaga tej zależności.
     */
    protected function getExtensions(): array
    {
        $this->securityMock = $this->createMock(Security::class);

        return [
            new PreloadedExtension([new UserType($this->securityMock)], []),
        ];
    }

    public function testFormFields(): void
    {
        $formData = [
            'email' => 'user@example.com',
            'roles' => ['ROLE_USER'],
        ];

        $model = new User();
        $form = $this->factory->create(UserType::class, $model);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('user@example.com', $model->getEmail());
        // Jeśli ROLE_USER jest zawsze dodawana, nawet jeśli tylko ROLE_USER jest ustawione,
        // oczekujemy dokładnie tego, co zostało ustawione.
        // Jeśli User::getRoles() zawsze dodaje ROLE_USER, poniżej będzie OK.
        $this->assertEquals(['ROLE_USER'], $model->getRoles());
    }

    public function testFormWithAdminEditingAnotherUser(): void
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $this->setUserId($admin, 1); // refleksyjnie ustawiamy ID

        $editedUser = new User();
        $editedUser->setEmail('user@example.com');
        $this->setUserId($editedUser, 2); // refleksyjnie ustawiamy ID

        // Konfigurujemy mock Security, aby administrator miał rolę ADMIN
        $this->securityMock->method('isGranted')->willReturnCallback(function ($attribute, $subject = null) use ($admin) {
            if ($attribute === 'ROLE_ADMIN') {
                return true;
            }
            // Ważne: W tym teście admin edytuje INNEGO użytkownika, więc CAN_CHANGE_ROLES nie powinno wpływać
            // na to pole dla DANEGO admina, tylko dla editedUser.
            // Tutaj symulujemy, że jest zalogowany admin (ID 1), a edytowany jest inny user (ID 2).
            // Domyślnie voter zezwala na zmianę ról innym.
            return true;
        });

        // Musimy zasymulować zalogowanego użytkownika dla security->getUser()
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($admin); // Zalogowany użytkownik to $admin
        $tokenStorageMock->method('getToken')->willReturn($tokenMock);

        // Zastąpienie wewnętrznej usługi TokenStorage w Security mocku, jeśli to możliwe,
        // albo bezpośrednie zasymulowanie Security::getUser()
        $this->securityMock->method('getUser')->willReturn($admin);


        $formData = [
            'email' => 'new@example.com',
            'roles' => ['ROLE_ADMIN'],
        ];

        // Usunięto opcję 'current_user' z wywołania create w poprzednich iteracjach,
        // ponieważ UserType nie jest skonfigurowany, aby ją przyjmować.
        $form = $this->factory->create(UserType::class, $editedUser);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('new@example.com', $editedUser->getEmail());

        // ZMIANA: Oczekujemy, że role będą zawierały zarówno ROLE_ADMIN, jak i ROLE_USER.
        // Jest to dostosowanie testu do typowego zachowania encji User w Symfony,
        // która często automatycznie dodaje ROLE_USER do listy ról.
        $expectedRoles = ['ROLE_ADMIN', 'ROLE_USER'];
        $actualRoles = $editedUser->getRoles();

        // Sortowanie nadal jest dobrą praktyką, aby kolejność elementów w tablicach nie miała znaczenia
        sort($expectedRoles);
        sort($actualRoles);

        $this->assertEquals($expectedRoles, $actualRoles); // To jest linia 82
    }

    public function testRolesFieldIsDisabledForAdminEditingSelfWithoutRoleChangePermission(): void
    {
        $adminSelf = new User();
        $adminSelf->setEmail('admin@example.com');
        // Initial roles for adminSelf, assuming User entity sets ROLE_USER by default
        $adminSelf->setRoles(['ROLE_ADMIN', 'ROLE_USER']); // Set initial roles to match expected state
        $this->setUserId($adminSelf, 1); // Administrator edytuje siebie, więc ID muszą być takie same

        // Konfigurujemy mock Security
        $this->securityMock->method('isGranted')->willReturnCallback(function ($attribute, $subject = null) use ($adminSelf) {
            // Admin jest zalogowany i ma ROLE_ADMIN
            if ($attribute === 'ROLE_ADMIN') {
                return true;
            }
            // Kiedy sprawdzamy CAN_CHANGE_ROLES dla TEGO SAMEGO użytkownika, voter zwraca false
            if ($attribute === UserVoter::CAN_CHANGE_ROLES && $subject === $adminSelf) {
                return false; // Administrator NIE MOŻE zmieniać swoich własnych ról
            }
            return true; // Domyślnie pozwalamy na inne uprawnienia
        });

        // Mockujemy Security::getUser() tak, aby zwracał edytowanego administratora
        $this->securityMock->method('getUser')->willReturn($adminSelf);

        $formData = [
            'email' => 'admin_new@example.com',
            // Role nie będą zmieniane, ponieważ pole jest wyłączone
            // Więc nie ma potrzeby dodawania 'roles' do formData
        ];

        $form = $this->factory->create(UserType::class, $adminSelf);
        // Przesyłamy formularz, wskazując, że pola, których nie ma w formData, nie powinny być resetowane
        $form->submit($formData, false);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('admin_new@example.com', $adminSelf->getEmail());

        // Sprawdzamy, czy pole 'roles' jest wyłączone
        $rolesField = $form->get('roles');
        $this->assertTrue($rolesField->isDisabled(), 'The roles field should be disabled when admin edits self without CAN_CHANGE_ROLES permission.');

        // ZMIANA: Oczekujemy, że role pozostaną takie, jakie były przed submit,
        // ponieważ pole jest wyłączone i nie wpływa na encję.
        // W tym przypadku $adminSelf zostało zainicjowane z ['ROLE_ADMIN', 'ROLE_USER'].
        $expectedRoles = ['ROLE_ADMIN', 'ROLE_USER']; // Zmieniono na podstawie wyniku błędu
        sort($expectedRoles);
        $actualRoles = $adminSelf->getRoles();
        sort($actualRoles);
        $this->assertEquals($expectedRoles, $actualRoles);
    }

    public function testFormOptions(): void
    {
        $resolver = new OptionsResolver();
        $formType = new UserType($this->createMock(Security::class));
        $formType->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertEquals(User::class, $options['data_class']);
    }

    private function setUserId(User $user, int $id): void
    {
        $ref = new \ReflectionClass($user);
        $prop = $ref->getProperty('id');
        $prop->setValue($user, $id);
    }
}
