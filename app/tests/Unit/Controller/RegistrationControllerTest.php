<?php

/**
 * Registration controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\RegistrationController;
use App\Entity\User;
use App\Service\RegistrationServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Class Registration controller Test.
 */
class RegistrationControllerTest extends TestCase
{
    private $registrationService;
    private $authenticationUtils;
    private $twig;
    private $formFactory;

    /**
     * Test register when user is logged in.
     */
    public function testRegisterWhenUserIsLoggedIn(): void
    {
        $user = $this->createMock(User::class);
        $controller = $this->createController($user);

        $request = Request::create('/register');

        $controller->expects($this->once())->method('redirectToRoute')->with('dashboard_index');
        $controller->expects($this->never())->method('createForm');
        $this->authenticationUtils->expects($this->never())->method('getLastAuthenticationError');

        $response = $controller->register(
            $request,
            $this->registrationService,
            $this->authenticationUtils
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/dashboard', $response->getTargetUrl());
    }

    /**
     * Test register get request.
     */
    public function testRegisterGetRequest(): void
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
                'security/register.html.twig',
                $this->callback(function ($parameters) use ($lastUsername, $lastAuthenticationError) {
                    $this->assertEquals($lastUsername, $parameters['last_username']);
                    $this->assertNull($parameters['error']);
                    $this->assertInstanceOf(FormView::class, $parameters['form']);

                    return true;
                })
            )
            ->willReturn(value: '<html lang="">registration form</html>');

        $request = Request::create('/register');

        $response = $controller->register(
            $request,
            $this->registrationService,
            $this->authenticationUtils
        );

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('registration form', $response->getContent());
    }

    /**
     * Test register invalid form submission.
     */
    public function testRegisterInvalidFormSubmission(): void
    {
        $controller = $this->createController();
        $request = Request::create('/register', 'POST', [
            'registration_type' => [
                'email' => 'invalid-email',
                'password' => [
                    'first' => 'short',
                    'second' => 'short',
                ],
            ],
        ]);

        $formViewMock = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($formViewMock);

        $controller->method('createForm')->willReturn($form);

        $this->authenticationUtils->expects($this->once())->method('getLastAuthenticationError')->willReturn(null);
        $this->authenticationUtils->expects($this->once())->method('getLastUsername')->willReturn('');

        $this->registrationService->expects($this->never())->method('register');
        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'security/register.html.twig',
                $this->callback(function ($parameters) {
                    $this->assertEquals('', $parameters['last_username']);
                    $this->assertNull($parameters['error']);
                    $this->assertInstanceOf(FormView::class, $parameters['form']);

                    return true;
                })
            )
            ->willReturn(value: '<html lang="">registration form with errors</html>');

        $response = $controller->register(
            $request,
            $this->registrationService,
            $this->authenticationUtils
        );

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('registration form with errors', $response->getContent());
    }


    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->registrationService = $this->createMock(RegistrationServiceInterface::class);
        $this->authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
    }

    /**
     * Helper method to create a RegistrationController instance with mocked dependencies.
     *
     * @param UserInterface|null $userMock
     *
     * @return RegistrationController
     */
    private function createController(?UserInterface $userMock = null): RegistrationController
    {
        $controller = $this->getMockBuilder(RegistrationController::class)
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
