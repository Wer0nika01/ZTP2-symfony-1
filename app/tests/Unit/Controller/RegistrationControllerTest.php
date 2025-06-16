<?php

namespace App\Tests\Unit\Controller;

use App\Controller\RegistrationController;
use App\Entity\User;
use App\Form\Type\RegistrationType;
use App\Service\RegistrationServiceInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface; // Alias for clarity
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RegistrationControllerTest extends TestCase
{
    private $registrationService;
    private $authenticationUtils;
    private $translator;
    private $twig;
    private $formFactory;

    protected function setUp(): void
    {
        $this->registrationService = $this->createMock(RegistrationServiceInterface::class);
        $this->authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->formFactory = $this->createMock(\Symfony\Component\Form\FormFactoryInterface::class);
    }

    /**
     * Helper method to create a RegistrationController instance with mocked dependencies.
     *
     * @param UserInterface|null $userMock Specific user mock to return from getUser().
     * @param FormInterface|null $formMock Specific form mock to return from createForm().
     */
    private function createController(?UserInterface $userMock = null, ?FormInterface $formMock = null): RegistrationController
    {
        $controller = $this->getMockBuilder(RegistrationController::class)
            ->setConstructorArgs([])
            ->onlyMethods(['getUser', 'createForm', 'render', 'addFlash', 'redirectToRoute'])
            ->getMock();

        // Configure getUser behavior
        $controller->method('getUser')->willReturn($userMock);

        // Configure createForm behavior
        if ($formMock) {
            $controller->method('createForm')->willReturn($formMock);
        } else {
            $controller->method('createForm')->willReturnCallback(function (string $type, $data = null, array $options = []) {
                return $this->formFactory->create($type, $data, $options);
            });
        }

        // Configure render behavior
        $controller->method('render')
            ->willReturnCallback(function (string $view, array $parameters = []) {
                $content = $this->twig->render($view, $parameters);
                return new Response($content);
            });

        // Configure addFlash behavior (void return type)
        $controller->method('addFlash');

        // Configure redirectToRoute behavior (RedirectResponse return type)
        $controller->method('redirectToRoute')->willReturnCallback(function (string $route, array $parameters = []) {
            // Manually map route names to expected URLs for assertions
            $targetUrl = match ($route) {
                'dashboard_index' => '/dashboard',
                'app_login' => '/login',
                default => $route, // Fallback if not a known route, though should be covered by tests
            };
            return new RedirectResponse($targetUrl);
        });

        return $controller;
    }

    public function testRegisterWhenUserIsLoggedIn(): void
    {
        $user = $this->createMock(User::class);
        $controller = $this->createController($user); // User is logged in

        $request = Request::create('/register', 'GET');

        // Expect redirectToRoute to be called with 'dashboard_index'
        $controller->expects($this->once())->method('redirectToRoute')->with('dashboard_index');
        $controller->expects($this->never())->method('createForm');
        $this->authenticationUtils->expects($this->never())->method('getLastAuthenticationError');

        $response = $controller->register(
            $request,
            $this->registrationService,
            $this->authenticationUtils,
            $this->translator
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/dashboard', $response->getTargetUrl()); // Assert against the resolved URL
    }

    public function testRegisterGetRequest(): void
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
        $form->method('isSubmitted')->willReturn(false); // For GET requests, handleRequest will make isSubmitted return false.
        $form->method('isValid')->willReturn(false); // Not relevant for GET, but setting default

        $controller->method('createForm')->willReturn($form);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'security/register.html.twig',
                $this->callback(function ($parameters) use ($lastUsername, $lastAuthenticationError) {
                    // Assert specific scalar values and that 'form' is an instance of FormView
                    $this->assertEquals($lastUsername, $parameters['last_username']);
                    $this->assertNull($parameters['error']); // Check for null explicitly
                    $this->assertInstanceOf(FormView::class, $parameters['form']);
                    return true; // Return true if all checks pass
                })
            )
            ->willReturn('<html>registration form</html>');

        $request = Request::create('/register', 'GET');

        $response = $controller->register(
            $request,
            $this->registrationService,
            $this->authenticationUtils,
            $this->translator
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('registration form', $response->getContent());
    }

    public function testRegisterValidFormSubmission(): void
    {
        $controller = $this->createController(null); // User is not logged in
        $request = Request::create('/register', 'POST', [
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

        $passwordFieldMock = $this->createMock(FormInterface::class);
        $passwordFieldMock->method('getData')->willReturn('password123');
        $form->method('get')->with('password')->willReturn($passwordFieldMock);

        $controller->method('createForm')->willReturn($form);

        $this->registrationService->expects($this->once())
            ->method('register')
            ->with($this->isInstanceOf(User::class), 'password123');

        $controller->expects($this->once())->method('addFlash')->with('success', 'message.registration_successful');
        $controller->expects($this->once())->method('redirectToRoute')->with('app_login');

        $response = $controller->register(
            $request,
            $this->registrationService,
            $this->authenticationUtils,
            $this->translator
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/login', $response->getTargetUrl()); // Assert against the resolved URL
    }

    public function testRegisterInvalidFormSubmission(): void
    {
        $controller = $this->createController(null); // User is not logged in
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
            ->willReturn('<html>registration form with errors</html>');

        $response = $controller->register(
            $request,
            $this->registrationService,
            $this->authenticationUtils,
            $this->translator
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('registration form with errors', $response->getContent());
    }

    public function testRegisterUniqueConstraintViolation(): void
    {
        $controller = $this->createController(null); // User is not logged in
        $request = Request::create('/register', 'POST', [
            'registration_type' => [
                'email' => 'existing@example.com',
                'password' => [
                    'first' => 'password123',
                    'second' => 'password123',
                ],
            ],
        ]);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true); // Form is valid from Symfony's perspective, but DB throws error

        $passwordFieldMock = $this->createMock(FormInterface::class);
        $passwordFieldMock->method('getData')->willReturn('password123');
        $form->method('get')->with('password')->willReturn($passwordFieldMock);

        // Mock the email field to allow adding a form error
        $emailFieldMock = $this->createMock(FormInterface::class);
        $emailFieldMock->expects($this->once())
            ->method('addError')
            ->with($this->isInstanceOf(FormError::class));
        $form->method('get')->with('email')->willReturn($emailFieldMock);

        $controller->method('createForm')->willReturn($form);

        // Create an anonymous class that implements DriverExceptionInterface and extends \Exception
        $concreteDriverException = new class('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry', 0) extends \Exception implements DriverExceptionInterface {
            public function getSQLState(): ?string {
                return '23000'; // Or the appropriate SQLSTATE for your test case
            }
        };

        // Corrected: UniqueConstraintViolationException constructor expects DriverException as first arg, Query (can be null) as second, and then a message string
        $this->registrationService->expects($this->once())
            ->method('register')
            ->willThrowException(new UniqueConstraintViolationException($concreteDriverException, null, 'A unique constraint violation occurred.'));

        // Mock translator for the error message
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('message.email_already_used')
            ->willReturn('The email address is already in use.');

        $this->authenticationUtils->expects($this->once())->method('getLastAuthenticationError')->willReturn(null);
        $this->authenticationUtils->expects($this->once())->method('getLastUsername')->willReturn('existing@example.com');

        $controller->expects($this->never())->method('addFlash');
        $controller->expects($this->never())->method('redirectToRoute');

        $formViewMock = $this->createMock(FormView::class);
        $form->method('createView')->willReturn($formViewMock);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'security/register.html.twig',
                $this->callback(function ($parameters) {
                    $this->assertEquals('existing@example.com', $parameters['last_username']);
                    $this->assertNull($parameters['error']);
                    $this->assertInstanceOf(FormView::class, $parameters['form']);
                    return true;
                })
            )
            ->willReturn('<html>registration form with unique constraint error</html>');


        $response = $controller->register(
            $request,
            $this->registrationService,
            $this->authenticationUtils,
            $this->translator
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('registration form with unique constraint error', $response->getContent());
    }
}
