<?php

/**
 * User type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\UserType;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class User type Test.
 */
class UserTypeTest extends TypeTestCase
{
    /** @var Security&MockObject */
    private MockObject $securityMock;

    /**
     * Test form fields.
     */
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
        $this->assertEquals(['ROLE_USER'], $model->getRoles());
    }

    /**
     * Test form with admin editing another user.
     */
    public function testFormWithAdminEditingAnotherUser(): void
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $this->setUserId($admin, 1);

        $editedUser = new User();
        $editedUser->setEmail('user@example.com');
        $this->setUserId($editedUser, 2);

        $this->securityMock->method('isGranted')->willReturnCallback(function () use ($admin) {
            return true;
        });

        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($admin);
        $tokenStorageMock->method('getToken')->willReturn($tokenMock);

        $this->securityMock->method('getUser')->willReturn($admin);


        $formData = [
            'email' => 'new@example.com',
            'roles' => ['ROLE_ADMIN'],
        ];

        $form = $this->factory->create(UserType::class, $editedUser);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('new@example.com', $editedUser->getEmail());

        $expectedRoles = ['ROLE_ADMIN', 'ROLE_USER'];
        $actualRoles = $editedUser->getRoles();

        sort($expectedRoles);
        sort($actualRoles);

        $this->assertEquals($expectedRoles, $actualRoles);
    }

    /**
     * Test roles field is disabled for admin editing self without role change permission.
     */
    public function testRolesFieldIsDisabledForAdminEditingSelfWithoutRoleChangePermission(): void
    {
        $adminSelf = new User();
        $adminSelf->setEmail('admin@example.com');
        $adminSelf->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $this->setUserId($adminSelf, 1);

        $this->securityMock->method('isGranted')->willReturnCallback(function ($attribute, $subject = null) use ($adminSelf) {
            if ($attribute === 'ROLE_ADMIN') {
                return true;
            }
            if ($attribute === UserVoter::CAN_CHANGE_ROLES && $subject === $adminSelf) {
                return false;
            }

            return true;
        });

        $this->securityMock->method('getUser')->willReturn($adminSelf);

        $formData = [
            'email' => 'admin_new@example.com',
        ];

        $form = $this->factory->create(UserType::class, $adminSelf);
        $form->submit($formData, false);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('admin_new@example.com', $adminSelf->getEmail());

        $rolesField = $form->get('roles');
        $this->assertTrue($rolesField->isDisabled(), 'The roles field should be disabled when admin edits self without CAN_CHANGE_ROLES permission.');

        $expectedRoles = ['ROLE_ADMIN', 'ROLE_USER'];
        sort($expectedRoles);
        $actualRoles = $adminSelf->getRoles();
        sort($actualRoles);
        $this->assertEquals($expectedRoles, $actualRoles);
    }

    /**
     * Test form options.
     */
    public function testFormOptions(): void
    {
        $resolver = new OptionsResolver();
        $formType = new UserType($this->createMock(Security::class));
        $formType->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertEquals(User::class, $options['data_class']);
    }

    /**
     * Specify mock Security when creating the UserType,
     * because the UserType constructor requires this dependency.
     *
     * @return PreloadedExtension[]
     */
    protected function getExtensions(): array
    {
        $this->securityMock = $this->createMock(Security::class);

        return [
            new PreloadedExtension([new UserType($this->securityMock)], []),
        ];
    }

    /**
     * Set user Id.
     *
     * @param User $user
     * @param int  $id
     */
    private function setUserId(User $user, int $id): void
    {
        $ref = new ReflectionClass($user);
        $prop = $ref->getProperty('id');
        $prop->setValue($user, $id);
    }
}
