<?php

/**
 * Security controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\SecurityController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
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

/**
 * Class Security controller Test.
 */
class SecurityControllerTest extends TestCase
{
    private $authenticationUtils;
    private $passwordHasher;
    private $entityManager;
    private $translator;
    private $twig;
    private $formFactory;

    /**
     * Test login when user is logged in.
     */
    public function testLoginWhenUserIsLoggedIn(): void
    {
        $user = $this->createMock(User::class);
        $controller = $this->createController($user);

        $request = Request::create('/login');

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

    /**
     * Test login get request.
     */
    public function testLoginGetRequest(): void
    {
        $controller = $this->createController();

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
        $form->method('isSubmitted')->willReturn(false);
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
            ->willReturn('<html lang="">login form</html>');

        $request = Request::create('/login');

        $response = $controller->login(
            $this->authenticationUtils,
            $this->passwordHasher,
            $request,
            $this->entityManager,
            $this->translator
        );

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('login form', $response->getContent());
    }

    /**
     * Test login and register invalid form submission.
     */
    public function testLoginAndRegisterInvalidFormSubmission(): void
    {
        $controller = $this->createController();
        $request = Request::create('/login', 'POST', [
            'registration_type' => [
                'email' => 'invalid-email',
                'password' => [
                    'first' => 'short',
                    'second' => 'short',
                ],
            ],
        ]);

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
        $controller->expects($this->never())->method('redirectToRoute');

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
            ->willReturn(value: '<html lang="">login form with errors</html>');

        $response = $controller->login(
            $this->authenticationUtils,
            $this->passwordHasher,
            $request,
            $this->entityManager,
            $this->translator
        );

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('login form with errors', $response->getContent());
    }

    /**
     * Test logout.
     */
    public function testLogout(): void
    {
        $controller = $this->createController();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This method can be blank - it will be intercepted by the logout key on your firewall.');

        $controller->logout();
    }


    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
    }

    /**
     * Helper method to create a SecurityController instance with mocked dependencies.
     *
     * @param UserInterface|null $userMock
     *
     * @return SecurityController
     */
    private function createController(?UserInterface $userMock = null): SecurityController
    {
        $controller = $this->getMockBuilder(SecurityController::class)
            ->setConstructorArgs([])
            ->onlyMethods(['getUser', 'createForm', 'render', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($userMock);

        if (null) {
            $controller->method('createForm')->willReturn(null);
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

        $controller->method('redirectToRoute')->willReturnCallback(function (string $route) {
            $targetUrl = match ($route) {
                'dashboard_index' => '/dashboard',
                'app_login' => '/login',
                default => $route,
            };

            return new RedirectResponse($targetUrl);
        });

        return $controller;
    }
}
