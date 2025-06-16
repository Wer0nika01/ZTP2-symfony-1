<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ProfileController;
use App\Entity\User;
use App\Form\Type\ChangePasswordType;
use App\Form\Type\ProfileEditType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse; // Import RedirectResponse
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Twig\Environment;

class ProfileControllerTest extends TestCase
{
    private $entityManager;
    private $passwordHasher;
    private $tokenStorage;
    private $twig;
    private $formFactory;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->twig = $this->createMock(Environment::class); // Mock Twig Environment

        // Mock TokenStorageInterface for getUser() - useful for initial setup but overridden per test
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        // Will set return value for getUser() in createController or individual tests
        $this->tokenStorage->method('getToken')->willReturn($token);

        // Mock the FormFactory to control form creation in the controller
        $this->formFactory = $this->createMock(\Symfony\Component\Form\FormFactoryInterface::class);
    }

    /**
     * Helper method to create a ProfileController instance with mocked dependencies.
     *
     * @param FormInterface|null $formMock Specific form mock to return from createForm(). If null, a default mock is created via formFactory.
     */
    private function createController(?FormInterface $formMock = null): ProfileController
    {
        $controller = $this->getMockBuilder(ProfileController::class)
            ->setConstructorArgs([])
            ->onlyMethods(['getUser', 'render', 'createForm', 'addFlash', 'redirectToRoute'])
            ->getMock();

        // Default getUser behavior will be set by individual tests, or an explicit mock will be injected.
        // This setup ensures we have fine-grained control over the user mock per test case.

        $controller->method('render')
            ->willReturnCallback(function (string $view, array $parameters = []) {
                $content = $this->twig->render($view, $parameters); // Delegate to mocked Twig
                return new Response($content);
            });

        // If a specific form mock is provided, createForm will return it directly.
        // Otherwise, it will call the formFactory mock (though typically we want direct control in tests).
        if ($formMock) {
            $controller->method('createForm')->willReturn($formMock);
        } else {
            // Default behavior if no specific form mock is passed to createController.
            // This is primarily for fallback, direct injection is preferred for tests needing form control.
            $controller->method('createForm')->willReturnCallback(function (string $type, $data = null, array $options = []) {
                return $this->formFactory->create($type, $data, $options);
            });
        }

        // addFlash has a void return type, so no return value should be specified
        $controller->method('addFlash');

        // Corrected: redirectToRoute returns a RedirectResponse and we must provide the resolved URL
        $controller->method('redirectToRoute')->willReturnCallback(function (string $route, array $parameters = []) {
            // Manually resolve common routes to URLs for RedirectResponse
            $targetUrl = match ($route) {
                'app_profile' => '/profile',
                'app_login' => '/login',
                default => $route, // Fallback if not a known route
            };
            return new RedirectResponse($targetUrl);
        });

        return $controller;
    }

    public function testProfile(): void
    {
        $user = $this->createMock(User::class);
        $controller = $this->createController();
        $controller->method('getUser')->willReturn($user); // Set specific user mock for this test

        // Configure Twig mock
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/index.html.twig',
                $this->equalTo(['user' => $user]) // Use the passed user mock here
            )
            ->willReturn('<html>profile content</html>');

        $response = $controller->profile();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode()); // Expect 200 OK for render
        $this->assertStringContainsString('profile content', $response->getContent());
    }

    public function testEditProfileValidForm(): void
    {
        $user = $this->createMock(User::class);

        // Create the form mock and configure its behavior
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        // Pass the form mock directly to createController
        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($user); // Set specific user mock for this test

        // Create a Request with dummy POST data that the form would process
        // 'profile_edit_type' should match the name of your form type
        $request = Request::create('/profile/edit', 'POST', ['profile_edit_type' => ['email' => 'new@example.com']]);

        $this->entityManager->expects($this->once())->method('flush');
        $controller->expects($this->once())->method('addFlash')->with('success', 'message.profile_updated_successfully');
        $controller->expects($this->once())->method('redirectToRoute')->with('app_profile'); // Still expect route name here

        $response = $controller->editProfile($request, $this->entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response); // Assert RedirectResponse
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode()); // Expect 302 Found for redirect
        $this->assertEquals('/profile', $response->getTargetUrl()); // Check target URL which is resolved from 'app_profile'
    }

    public function testEditProfileInvalidForm(): void
    {
        $user = $this->createMock(User::class);

        // Create the FormView mock instance once
        $formViewMock = $this->createMock(FormView::class);

        // Create the form mock and configure its behavior
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true); // handleRequest will set this
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($formViewMock); // Form returns this specific mock

        // Pass the form mock directly to createController
        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($user); // Set specific user mock for this test

        $request = Request::create('/profile/edit', 'POST'); // No need for specific data for invalid form

        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/edit.html.twig',
                $this->equalTo(['form' => $formViewMock, 'user' => $user]) // Use the same FormView mock here
            )
            ->willReturn('<html>edit profile form</html>');

        $response = $controller->editProfile($request, $this->entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode()); // Expect 200 OK for render
        $this->assertStringContainsString('edit profile form', $response->getContent());
    }

    public function testEditProfileGetRequest(): void
    {
        $user = $this->createMock(User::class);

        // Create the FormView mock instance once
        $formViewMock = $this->createMock(FormView::class);

        // Create the form mock and configure its behavior
        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($formViewMock); // Form returns this specific mock
        $form->method('handleRequest')->willReturnSelf(); // handleRequest is always called
        $form->method('isSubmitted')->willReturn(false); // For GET requests, handleRequest will make isSubmitted return false.
        $form->method('isValid')->willReturn(false); // isValid will not be called, but setting default just in case

        // Pass the form mock directly to createController
        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($user); // Set specific user mock for this test

        $request = Request::create('/profile/edit', 'GET');

        // isSubmitted will be called internally by handleRequest, but will return false for GET
        $form->expects($this->once())->method('handleRequest')->with($request);
        $form->expects($this->never())->method('isValid'); // isValid is only called if isSubmitted is true

        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/edit.html.twig',
                $this->equalTo(['form' => $formViewMock, 'user' => $user]) // Use the same FormView mock here
            )
            ->willReturn('<html>edit profile form</html>');

        $response = $controller->editProfile($request, $this->entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode()); // Expect 200 OK for render
        $this->assertStringContainsString('edit profile form', $response->getContent());
    }

    public function testChangePasswordUserNotPasswordAuthenticated(): void
    {
        // For this test, assume `getUser()` returns null, leading to a redirect to login.
        // This is a common interpretation of the `if (!user instanceof ...)` check if user might be unauthenticated.
        $controller = $this->createController();
        $controller->method('getUser')->willReturn(null); // Explicitly return null for this test case

        $request = Request::create('/profile/change-password', 'GET');

        $controller->expects($this->once())->method('redirectToRoute')->with('app_login');
        $controller->expects($this->never())->method('createForm'); // Should not be called if user is null
        $this->entityManager->expects($this->never())->method('flush');

        $response = $controller->changePassword($request, $this->passwordHasher, $this->entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response); // Assert RedirectResponse
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/login', $response->getTargetUrl()); // Check target URL
    }

    public function testChangePasswordValidForm(): void
    {
        // Manually create a user mock that implements PasswordAuthenticatedUserInterface
        $mockUserWithInterface = new class extends User implements PasswordAuthenticatedUserInterface {
            private $passwordValue;

            public function getPassword(): ?string { return $this->passwordValue; }
            public function setPassword(string $password): void { $this->passwordValue = $password; } // Corrected return type
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
            public function getUserIdentifier(): string { return 'test@example.com'; }
            public function getId(): ?int { return 1; }
            public function getEmail(): ?string { return $this->getUserIdentifier(); }
        };
        $mockUserWithInterface->setPassword('hashed_old_password');

        // Create the form mock and configure its behavior
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        // Mock the 'get' method for the 'plainPassword' field correctly
        $plainPasswordFieldMock = $this->createMock(FormInterface::class);
        $plainPasswordFieldMock->method('getData')->willReturn('newPassword123');
        $form->method('get')->with('plainPassword')->willReturn($plainPasswordFieldMock);

        // Pass the form mock directly to createController
        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($mockUserWithInterface); // Set specific user mock for this test

        // Create a Request with dummy POST data
        // 'change_password' should match the name of your form type
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

        $this->assertInstanceOf(RedirectResponse::class, $response); // Assert RedirectResponse
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode()); // Expect 302 Found for redirect
        $this->assertEquals('/profile', $response->getTargetUrl()); // Check target URL
    }

    public function testChangePasswordInvalidForm(): void
    {
        // Manually create a user mock that implements PasswordAuthenticatedUserInterface
        $mockUserWithInterface = new class extends User implements PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string { return 'hashed_old_password'; }
            public function setPassword(string $password): void {} // Corrected return type
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
            public function getUserIdentifier(): string { return 'test@example.com'; }
            public function getId(): ?int { return 1; }
            public function getEmail(): ?string { return $this->getUserIdentifier(); }
        };

        // Create the FormView mock instance once
        $formViewMock = $this->createMock(FormView::class);

        // Create the form mock and configure its behavior
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true); // handleRequest will set this
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($formViewMock);

        // Pass the form mock directly to createController
        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($mockUserWithInterface); // Set specific user mock for this test

        $request = Request::create('/profile/change-password', 'POST');

        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/change_password.html.twig',
                $this->equalTo(['form' => $formViewMock]) // Use the same FormView mock here
            )
            ->willReturn('<html>change password form</html>');

        $response = $controller->changePassword($request, $this->passwordHasher, $this->entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode()); // Expect 200 OK for render
        $this->assertStringContainsString('change password form', $response->getContent());
    }

    public function testChangePasswordGetRequest(): void
    {
        // Manually create a user mock that implements PasswordAuthenticatedUserInterface
        $mockUserWithInterface = new class extends User implements PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string { return 'hashed_old_password'; }
            public function setPassword(string $password): void {} // Corrected return type
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
            public function getUserIdentifier(): string { return 'test@example.com'; }
            public function getId(): ?int { return 1; }
            public function getEmail(): ?string { return $this->getUserIdentifier(); }
        };

        // Create the FormView mock instance once
        $formViewMock = $this->createMock(FormView::class);

        // Create the form mock and configure its behavior
        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($formViewMock);
        $form->method('handleRequest')->willReturnSelf(); // handleRequest is always called
        $form->method('isSubmitted')->willReturn(false); // For GET requests, handleRequest will make isSubmitted return false.
        $form->method('isValid')->willReturn(false); // isValid will not be called, but setting default just in case

        // Pass the form mock directly to createController
        $controller = $this->createController($form);
        $controller->method('getUser')->willReturn($mockUserWithInterface); // Set specific user mock for this test

        $request = Request::create('/profile/change-password', 'GET');

        $form->expects($this->once())->method('handleRequest')->with($request);
        $form->expects($this->never())->method('isValid'); // isValid is only called if isSubmitted is true

        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'profile/change_password.html.twig',
                $this->equalTo(['form' => $formViewMock]) // Use the same FormView mock here
            )
            ->willReturn('<html>change password form</html>');

        $response = $controller->changePassword($request, $this->passwordHasher, $this->entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode()); // Expect 200 OK for render
        $this->assertStringContainsString('change password form', $response->getContent());
    }
}
