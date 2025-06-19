<?php

/**
 * Profile controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\ProfileController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Twig\Environment;

/**
 * Class Profile controller Test.
 */
class ProfileControllerTest extends TestCase
{
    private $entityManager;
    private $passwordHasher;
    private $twig;
    private $formFactory;

    /**
     * Test profile.
     */
    public function testProfile(): void
    {
        $user = $this->createMock(User::class);
        $controller = $this->createController();
        $controller->method('getUser')->willReturn($user);

        // Configure Twig mock to return the expected content
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/index.html.twig',
                $this->equalTo(['user' => $user])
            )
            ->willReturn('profile content'); // <-- This line is added

        $response = $controller->profile();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('profile content', $response->getContent());
    }

    /**
     * Test edit profile valid form.
     */
    public function testEditProfileValidForm(): void
    {
        $user = $this->createMock(User::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($user);

        $request = Request::create('/profile/edit', 'POST', ['profile_edit_type' => ['email' => 'new@example.com']]);

        $this->entityManager->expects($this->once())->method('flush');
        $controller->expects($this->once())->method('addFlash')->with('success', 'message.profile_updated_successfully');
        $controller->expects($this->once())->method('redirectToRoute')->with('app_profile');

        $response = $controller->editProfile($request, $this->entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/profile', $response->getTargetUrl());
    }

    /**
     * Test edit profile invalid form.
     */
    public function testEditProfileInvalidForm(): void
    {
        $user = $this->createMock(User::class);

        $formViewMock = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($formViewMock);

        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($user);

        $request = Request::create('/profile/edit', 'POST');

        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/edit.html.twig',
                $this->equalTo(['form' => $formViewMock, 'user' => $user])
            )
            ->willReturn('edit profile form'); // <-- This line is added

        $response = $controller->editProfile($request, $this->entityManager);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('edit profile form', $response->getContent());
    }

    /**
     * Test edit profile get request.
     */
    public function testEditProfileGetRequest(): void
    {
        $user = $this->createMock(User::class);

        $formViewMock = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($formViewMock);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);

        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($user);

        $request = Request::create('/profile/edit');

        $form->expects($this->once())->method('handleRequest')->with($request);
        $form->expects($this->never())->method('isValid');

        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/edit.html.twig',
                $this->equalTo(['form' => $formViewMock, 'user' => $user])
            )
            ->willReturn('edit profile form'); // <-- This line is added

        $response = $controller->editProfile($request, $this->entityManager);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('edit profile form', $response->getContent());
    }

    /**
     * Test change password user not password authenticated.
     */
    public function testChangePasswordUserNotPasswordAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->method('getUser')->willReturn(null);

        $request = Request::create('/profile/change-password');

        $controller->expects($this->once())->method('redirectToRoute')->with('app_login');
        $controller->expects($this->never())->method('createForm');
        $this->entityManager->expects($this->never())->method('flush');

        $response = $controller->changePassword($request, $this->passwordHasher, $this->entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/login', $response->getTargetUrl());
    }

    /**
     * Test change password valid form.
     *
     * @return void
     */
    public function testChangePasswordValidForm(): void
    {
        $mockUserWithInterface = new class() extends User implements PasswordAuthenticatedUserInterface {
            private $passwordValue;

            /**
             * Get password.
             *
             * @return string|null
             */
            public function getPassword(): ?string
            {
                return $this->passwordValue;
            }

            /**
             * Set password.
             *
             * @param string $password The new password.
             */
            public function setPassword(string $password): void
            {
                $this->passwordValue = $password;
            }

            /**
             * Erase credentials.
             */
            public function eraseCredentials(): void
            {
            }

            /**
             * Get user identifier.
             *
             * @return string
             */
            public function getUserIdentifier(): string
            {
                return 'test@example.com';
            }

            /**
             * Get ID.
             *
             * @return int|null
             */
            public function getId(): ?int
            {
                return 1;
            }

            /**
             * Get email.
             *
             * @return string|null
             */
            public function getEmail(): ?string
            {
                return $this->getUserIdentifier();
            }
        };
        $mockUserWithInterface->setPassword('hashed_old_password');

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $plainPasswordFieldMock = $this->createMock(FormInterface::class);
        $plainPasswordFieldMock->method('getData')->willReturn('newPassword123');
        $form->method('get')->with('plainPassword')->willReturn($plainPasswordFieldMock);

        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($mockUserWithInterface);

        $request = Request::create('/profile/change-password', 'POST', [
            'change_password' => [
                'plainPassword' => [
                    'first' => 'newPassword123',
                    'second' => 'newPassword123',
                ],
            ],
        ]);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($mockUserWithInterface, 'newPassword123')
            ->willReturn('hashed_new_password');

        $this->entityManager->expects($this->once())->method('flush');
        $controller->expects($this->once())->method('addFlash')->with('success', 'message.password_changed_successfully');
        $controller->expects($this->once())->method('redirectToRoute')->with('app_profile');

        $response = $controller->changePassword($request, $this->passwordHasher, $this->entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/profile', $response->getTargetUrl());
    }

    /**
     * Test change password invalid form
     *
     * @return void
     */
    public function testChangePasswordInvalidForm(): void
    {
        $mockUserWithInterface = new class() extends User implements PasswordAuthenticatedUserInterface
        {
            /**
             * Get password.
             *
             * @return string|null
             */
            public function getPassword(): ?string
            {
                return 'hashed_old_password';
            }

            /**
             * Set password.
             *
             * @param string $password The new password.
             */
            public function setPassword(string $password): void
            {
            }

            /**
             * Erase credentials.
             */
            public function eraseCredentials(): void
            {
            }

            /**
             * Get user identifier.
             *
             * @return string
             */
            public function getUserIdentifier(): string
            {
                return 'test@example.com';
            }

            /**
             * Get ID.
             *
             * @return int|null
             */
            public function getId(): ?int
            {
                return 1;
            }

            /**
             * Get email.
             *
             * @return string|null
             */
            public function getEmail(): ?string
            {
                return $this->getUserIdentifier();
            }
        };

        $formViewMock = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($formViewMock);

        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($mockUserWithInterface);

        $request = Request::create('/profile/change-password', 'POST');

        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/change_password.html.twig',
                $this->equalTo(['form' => $formViewMock])
            )
            ->willReturn('change password form'); // <-- This line is added

        $response = $controller->changePassword($request, $this->passwordHasher, $this->entityManager);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('change password form', $response->getContent());
    }

    /**
     * Test change password get request.
     *
     * @return void
     */
    public function testChangePasswordGetRequest(): void
    {
        $mockUserWithInterface = new class() extends User implements PasswordAuthenticatedUserInterface {

            /**
             * Get password.
             *
             * @return string|null
             */
            public function getPassword(): ?string
            {
                return 'hashed_old_password';
            }

            /**
             * Set password.
             *
             * @param string $password The new password.
             */
            public function setPassword(string $password): void
            {
            }

            /**
             * Erase credentials.
             */
            public function eraseCredentials(): void
            {
            }

            /**
             * Get user identifier.
             *
             * @return string
             */
            public function getUserIdentifier(): string
            {
                return 'test@example.com';
            }

            /**
             * Get ID.
             *
             * @return int|null
             */
            public function getId(): ?int
            {
                return 1;
            }

            /**
             * Get email.
             *
             * @return string|null
             */
            public function getEmail(): ?string
            {
                return $this->getUserIdentifier();
            }
        };

        $formViewMock = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($formViewMock);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);

        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($mockUserWithInterface);

        $request = Request::create('/profile/change-password');

        $form->expects($this->once())->method('handleRequest')->with($request);
        $form->expects($this->never())->method('isValid');

        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/change_password.html.twig',
                $this->equalTo(['form' => $formViewMock])
            )
            ->willReturn('change password form'); // <-- This line is added

        $response = $controller->changePassword($request, $this->passwordHasher, $this->entityManager);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('change password form', $response->getContent());
    }

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
    }

    /**
     * Helper method to create a ProfileController instance with mocked dependencies.
     *
     * @param FormInterface|null $formMock
     *
     * @return ProfileController
     */
    private function createController(?FormInterface $formMock = null): ProfileController
    {
        $controller = $this->getMockBuilder(ProfileController::class)
            ->setConstructorArgs([])
            ->onlyMethods(['getUser', 'render', 'createForm', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('render')
            ->willReturnCallback(function (string $view, array $parameters = []) {
                $content = $this->twig->render($view, $parameters); // Delegate to mocked Twig

                return new Response($content);
            });

        if ($formMock) {
            $controller->method('createForm')->willReturn($formMock);
        } else {
            $controller->method('createForm')->willReturnCallback(function (string $type, $data = null, array $options = []) {
                return $this->formFactory->create($type, $data, $options);
            });
        }

        $controller->method('addFlash');

        $controller->method('redirectToRoute')->willReturnCallback(function (string $route) {
            $targetUrl = match ($route) {
                'app_profile' => '/profile',
                'app_login' => '/login',
                default => $route,
            };

            return new RedirectResponse($targetUrl);
        });

        return $controller;
    }
}
