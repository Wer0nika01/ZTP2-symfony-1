<?php

/**
 * User controller Test.
 */

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\UserController;
use App\Entity\User;
use App\Form\Type\UserType;
use App\Service\UserServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundle;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class User controller Test.
 */
class UserControllerTest extends TestCase
{
    private UserServiceInterface&MockObject $userService;
    private TranslatorInterface&MockObject $translator;
    private SecurityBundle&MockObject $securityBundle;
    private FormFactoryInterface&MockObject $formFactory;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private TokenStorageInterface&MockObject $tokenStorage;
    private EntityManagerInterface&MockObject $entityManager;
    protected ?SessionInterface $session = null;
    protected ?FlashBagInterface $flashBag = null;

    /**
     * Test method index.
     */
    public function testIndex(): void
    {
        $page = 1;
        $paginationMock = $this->createMock(PaginationInterface::class);

        $this->userService->expects($this->once())
            ->method('getPaginatedList')
            ->with($page)
            ->willReturn($paginationMock);

        $controllerMock = $this->getMockBuilder(UserController::class)
            ->setConstructorArgs([$this->userService, $this->translator, $this->securityBundle])
            ->onlyMethods(['render'])
            ->getMock();

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')
            ->willReturnMap([
                ['security.token_storage', 1, $this->tokenStorage],
                ['router', 1, $this->urlGenerator],
                ['form.factory', 1, $this->formFactory],
                ['doctrine.orm.entity_manager', 1, $this->entityManager],
            ]);
        $containerMock->method('has')
            ->willReturnMap([
                ['security.token_storage', true],
                ['router', true],
                ['form.factory', true],
                ['doctrine.orm.entity_manager', true],
            ]);
        $controllerMock->setContainer($containerMock);

        $controllerMock->expects($this->once())
            ->method('render')
            ->with('admin/user/index.html.twig', ['pagination' => $paginationMock])
            ->willReturn(new Response(''));

        $response = $controllerMock->index($page);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test method show.
     */
    public function testShow(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $controllerMock = $this->getMockBuilder(UserController::class)
            ->setConstructorArgs([$this->userService, $this->translator, $this->securityBundle])
            ->onlyMethods(['render'])
            ->getMock();

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')
            ->willReturnMap([
                ['security.token_storage', 1, $this->tokenStorage],
                ['router', 1, $this->urlGenerator],
                ['form.factory', 1, $this->formFactory],
                ['doctrine.orm.entity_manager', 1, $this->entityManager],
            ]);
        $containerMock->method('has')
            ->willReturnMap([
                ['security.token_storage', true],
                ['router', true],
                ['form.factory', true],
                ['doctrine.orm.entity_manager', true],
            ]);
        $controllerMock->setContainer($containerMock);

        $controllerMock->expects($this->once())
            ->method('render')
            ->with('admin/user/view.html.twig', ['user' => $user])
            ->willReturn(new Response(''));

        $response = $controllerMock->show($user);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test method edit.
     */
    public function testEdit(): void
    {
        $request = $this->createMock(Request::class);
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getEmail')->willReturn('old@example.com');

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request)->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->createMock(FormView::class));

        $emailFormElement = $this->createMock(FormInterface::class);
        $emailFormElement->method('getData')->willReturn('new@example.com');
        $emailFormElement->method('addError')->willReturnSelf();

        $form->method('get')->with('email')->willReturn($emailFormElement);

        $controllerMock = $this->getMockBuilder(UserController::class)
            ->setConstructorArgs([$this->userService, $this->translator, $this->securityBundle])
            ->onlyMethods(['createForm', 'addFlash', 'redirectToRoute', 'render'])
            ->getMock();

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')
            ->willReturnMap([
                ['security.token_storage', 1, $this->tokenStorage],
                ['router', 1, $this->urlGenerator],
                ['form.factory', 1, $this->formFactory],
                ['doctrine.orm.entity_manager', 1, $this->entityManager],
            ]);
        $containerMock->method('has')
            ->willReturnMap([
                ['security.token_storage', true],
                ['router', true],
                ['form.factory', true],
                ['doctrine.orm.entity_manager', true],
            ]);
        $controllerMock->setContainer($containerMock);


        $controllerMock->expects($this->once())
            ->method('createForm')
            ->with(UserType::class, $user)
            ->willReturn($form);

        $this->userService->expects($this->once())
            ->method('isEmailUnique')
            ->with('new@example.com', 1)
            ->willReturn(true);
        $this->userService->expects($this->once())
            ->method('save')
            ->with($user);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('flash.user_saved')
            ->willReturn('Użytkownik zapisany');
        $controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Użytkownik zapisany');
        $controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_user_index')
            ->willReturn(new RedirectResponse('/admin/users'));

        $response = $controllerMock->edit($request, $user, $this->translator);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin/users', $response->getTargetUrl());
    }

    /**
     * Test blocking users.
     */
    public function testToggleBlockUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getIsBlocked')->willReturn(false);

        $user->expects($this->once())->method('setIsBlocked')->with(true);

        $this->userService->expects($this->once())
            ->method('toggleBlock')
            ->with($user)
            ->will($this->returnCallback(function ($passedUser) {
                $passedUser->setIsBlocked(true);
            }));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('flash.user_unblocked')
            ->willReturn('Użytkownik zablokowany');

        $controllerMock = $this->getMockBuilder(UserController::class)
            ->setConstructorArgs([$this->userService, $this->translator, $this->securityBundle])
            ->onlyMethods(['addFlash', 'redirectToRoute'])
            ->getMock();

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')
            ->willReturnMap([
                ['security.token_storage', 1, $this->tokenStorage],
                ['router', 1, $this->urlGenerator],
                ['form.factory', 1, $this->formFactory],
                ['doctrine.orm.entity_manager', 1, $this->entityManager],
            ]);
        $containerMock->method('has')
            ->willReturnMap([
                ['security.token_storage', true],
                ['router', true],
                ['form.factory', true],
                ['doctrine.orm.entity_manager', true],
            ]);
        $controllerMock->setContainer($containerMock);

        $controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Użytkownik zablokowany');
        $controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_user_index', ['id' => 1])
            ->willReturn(new RedirectResponse('/admin/users/1'));

        $response = $controllerMock->toggleBlock($user, $this->translator);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * Test unblocking users.
     */
    public function testToggleUnblockUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(2);
        $user->method('getIsBlocked')->willReturn(true); // Przed toggleBlock

        $user->expects($this->once())->method('setIsBlocked')->with(false);

        $this->userService->expects($this->once())
            ->method('toggleBlock')
            ->with($user)
            ->will($this->returnCallback(function ($passedUser) {
                $passedUser->setIsBlocked(false);
            }));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('flash.user_blocked')
            ->willReturn('Użytkownik odblokowany');

        $controllerMock = $this->getMockBuilder(UserController::class)
            ->setConstructorArgs([$this->userService, $this->translator, $this->securityBundle])
            ->onlyMethods(['addFlash', 'redirectToRoute'])
            ->getMock();

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')
            ->willReturnMap([
                ['security.token_storage', 1, $this->tokenStorage],
                ['router', 1, $this->urlGenerator],
                ['form.factory', 1, $this->formFactory],
                ['doctrine.orm.entity_manager', 1, $this->entityManager],
            ]);
        $containerMock->method('has')
            ->willReturnMap([
                ['security.token_storage', true],
                ['router', true],
                ['form.factory', true],
                ['doctrine.orm.entity_manager', true],
            ]);
        $controllerMock->setContainer($containerMock);

        $controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Użytkownik odblokowany');
        $controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_user_index', ['id' => 2])
            ->willReturn(new RedirectResponse('/admin/users/2'));

        $response = $controllerMock->toggleBlock($user, $this->translator);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserServiceInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->securityBundle = $this->createMock(SecurityBundle::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);


        $controller = new UserController(
            $this->userService,
            $this->translator,
            $this->securityBundle
        );

        $containerMock = $this->createMock(ContainerInterface::class);

        $containerMock->method('get')
            ->willReturnMap([
                ['security.token_storage', 1, $this->tokenStorage],
                ['router', 1, $this->urlGenerator],
                ['form.factory', 1, $this->formFactory],
                ['doctrine.orm.entity_manager', 1, $this->entityManager],
            ]);
        $containerMock->method('has')
            ->willReturnMap([
                ['security.token_storage', true],
                ['router', true],
                ['form.factory', true],
                ['doctrine.orm.entity_manager', true],
            ]);

        $controller->setContainer($containerMock);

        $this->urlGenerator->method('generate')->willReturnCallback(fn ($route, $params) => '/'.$route.'/'.implode('/', $params));
    }
}
