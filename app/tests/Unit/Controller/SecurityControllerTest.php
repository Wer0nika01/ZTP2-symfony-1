<?php

namespace App\Tests\Unit\Controller;

use App\Controller\SecurityController;
use App\Entity\User;
use App\Form\Type\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Symfony\Component\Security\Core\Exception\AuthenticationException; // Import AuthenticationException

class SecurityControllerTest extends TestCase
{
    private $authenticationUtils;
    private $passwordHasher;
    private $entityManager;
    private $translator;
    private $twig;
    private $formFactory;

    protected function setUp(): void
    {
        $this->authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->formFactory = $this->createMock(\Symfony\Component\Form\FormFactoryInterface::class);
    }

    /**
     * Helper method to create a SecurityController instance with mocked dependencies.
     *
     * @param UserInterface|null $userMock Specific user mock to return from getUser().
     * @param FormInterface|null $formMock Specific form mock to return from createForm().
     */
    private function createController(?UserInterface $userMock = null, ?FormInterface $formMock = null): SecurityController
    {
        // Added 'redirectToRoute' back to onlyMethods to make it mockable.
        $controller = $this->getMockBuilder(SecurityController::class)
            ->setConstructorArgs([])
            ->onlyMethods(['getUser', 'createForm', 'render', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($userMock);

        if ($formMock) {
            $controller->method('createForm')->willReturn($formMock);
        } else {
            $controller->method('createForm')->willReturnCallback(function (string $type, $data = null, array $options = []) {
                return $this->formFactory->create($type, $data, $options);
            });
        }

        $controller->method('render')
            ->willReturnCallback(function (string $view, array $parameters = []) {
                $content = $this->twig->render($view, $parameters);
                return new Response($content);
            });

        $controller->method('addFlash');

        // redirectToRoute is now globally mocked within the helper.
        $controller->method('redirectToRoute')->willReturnCallback(function (string $route, array $parameters = []) {
            $targetUrl = match ($route) {
                'dashboard_index' => '/dashboard',
                'app_login' => '/login',
                default => $route, // Fallback, though ideally all routes would be mapped
            };
            return new RedirectResponse($targetUrl);
        });

        return $controller;
    }

    public function testLoginWhenUserIsLoggedIn(): void
    {
        $user = $this->createMock(User::class);
        $controller = $this->createController($user);

        $request = Request::create('/login', 'GET');

        // No need for explicit redirectToRoute mock here anymore, it's global
        // $controller->expects($this->once())
        //            ->method('redirectToRoute')
        //            ->with('dashboard_index')
        //            ->willReturn(new RedirectResponse('/dashboard'));

        $controller->expects($this->never())->method('createForm');
        $this->authenticationUtils->expects($this->never())->method('getLastAuthenticationError');

        $response = $controller->login(
            $this->authenticationUtils,
            $this->passwordHasher,
            $request,
            $this->entityManager,
            $this->translator
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/dashboard', $response->getTargetUrl());
    }

    public function testLoginGetRequest(): void
    {
        $controller = $this->createController(null); // User is not logged in

        $lastAuthenticationError = null;
        $lastUsername = 'test@example.com';
        $this->authenticationUtils->expects($this->once())
            ->method('getLastAuthenticationError')
            ->willReturn($lastAuthenticationError);
        $this->authenticationUtils->expects($this->once())
            ->method('getLastUsername')
            ->willReturn($lastUsername);

        $formViewMock = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($formViewMock);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false); // For GET requests
        $form->method('isValid')->willReturn(false);

        $controller->method('createForm')->willReturn($form);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'security/login.html.twig',
                $this->callback(function ($parameters) use ($lastUsername, $lastAuthenticationError) {
                    $this->assertEquals($lastUsername, $parameters['last_username']);
                    $this->assertNull($parameters['error']);
                    $this->assertInstanceOf(FormView::class, $parameters['registrationForm']);
                    return true;
                })
            )
            ->willReturn('<html>login form</html>');

        $request = Request::create('/login', 'GET');

        $response = $controller->login(
            $this->authenticationUtils,
            $this->passwordHasher,
            $request,
            $this->entityManager,
            $this->translator
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('login form', $response->getContent());
    }

    public function testLoginAndRegisterValidFormSubmission(): void
    {
        $controller = $this->createController(null); // User is not logged in
        $request = Request::create('/login', 'POST', [
            'registration_type' => [ // Assuming your form's root name is 'registration_type'
                'email' => 'newuser@example.com',
                'password' => [
                    'first' => 'password123',
                    'second' => 'password123',
                ],
            ],
        ]);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $plainPasswordFieldMock = $this->createMock(FormInterface::class);
        $plainPasswordFieldMock->method('getData')->willReturn('password123');
        $form->method('get')->with('plainPassword')->willReturn($plainPasswordFieldMock);

        $controller->method('createForm')->willReturn($form);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'password123')
            ->willReturn('hashed_password');

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('message.edited_successfully')
            ->willReturn('Edited successfully.');

        $controller->expects($this->once())->method('addFlash')->with('success', 'Edited successfully.');
        // Explicitly set the mock behavior for redirectToRoute for this test
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/login')); // Return RedirectResponse here

        $response = $controller->login(
            $this->authenticationUtils,
            $this->passwordHasher,
            $request,
            $this->entityManager,
            $this->translator
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/login', $response->getTargetUrl());
    }

    public function testLoginAndRegisterInvalidFormSubmission(): void
    {
        $controller = $this->createController(null); // User is not logged in
        $request = Request::create('/login', 'POST', [
            'registration_type' => [
                'email' => 'invalid-email',
                'password' => [
                    'first' => 'short',
                    'second' => 'short',
                ],
            ],
        ]);

        // Corrected: Mock AuthenticationException instead of generic Exception
        $lastAuthenticationError = $this->createMock(AuthenticationException::class);
        $lastUsername = 'test@example.com';
        $this->authenticationUtils->expects($this->once())
            ->method('getLastAuthenticationError')
            ->willReturn($lastAuthenticationError);
        $this->authenticationUtils->expects($this->once())
            ->method('getLastUsername')
            ->willReturn($lastUsername);

        $formViewMock = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($formViewMock);

        $controller->method('createForm')->willReturn($form);

        $this->passwordHasher->expects($this->never())->method('hashPassword');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute'); // This should not be called in this scenario

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'security/login.html.twig',
                $this->callback(function ($parameters) use ($lastUsername, $lastAuthenticationError) {
                    $this->assertEquals($lastUsername, $parameters['last_username']);
                    $this->assertEquals($lastAuthenticationError, $parameters['error']);
                    $this->assertInstanceOf(FormView::class, $parameters['registrationForm']);
                    return true;
                })
            )
            ->willReturn('<html>login form with errors</html>');

        $response = $controller->login(
            $this->authenticationUtils,
            $this->passwordHasher,
            $request,
            $this->entityManager,
            $this->translator
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('login form with errors', $response->getContent());
    }

    public function testLogout(): void
    {
        $controller = $this->createController(null);

        // We expect a LogicException as per the original controller method.
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method can be blank - it will be intercepted by the logout key on your firewall.');

        $controller->logout();
    }
}
