<?php

/**
 * Avatar controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\AvatarController;
use App\Entity\Avatar;
use App\Entity\User;
use App\Service\AvatarServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class Avatar controller Test.
 */
class AvatarControllerTest extends TestCase
{
    private MockObject|AvatarServiceInterface $avatarService;
    private MockObject|AvatarController $controller;
    private MockObject|FormView $mockFormView;
    private MockObject|UrlGeneratorInterface $mockUrlGenerator;

    /**
     * Test create action redirects if user has avatar.
     */
    public function testCreateActionRedirectsIfUserHasAvatar(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);

        $this->mockUserWithAvatar($mockAvatar);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('avatar_edit', ['id' => $avatarId]);

        $this->mockUrlGenerator->expects($this->once())
            ->method('generate')
            ->with('avatar_edit', ['id' => $avatarId], 1)
            ->willReturn('/avatar/'.$avatarId.'/edit');

        $request = Request::create('/avatar/create');
        $response = $this->controller->create($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/avatar/'.$avatarId.'/edit', $response->getTargetUrl());
    }

    /**
     * Test create action get request renders form.
     */
    public function testCreateActionGetRequestRendersForm(): void
    {
        $this->mockUserWithoutAvatar();
        $form = $this->createMockForm(false, false);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('avatar/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $request = Request::create('/avatar/create');
        $this->controller->create($request);
    }

    /**
     * Test create action post request invalid form.
     */
    public function testCreateActionPostRequestInvalidForm(): void
    {
        $this->mockUserWithoutAvatar();
        $form = $this->createMockForm(true, false);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->avatarService->expects($this->never())->method('create');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('avatar/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $request = Request::create('/avatar/create', \Symfony\Component\HttpFoundation\Request::METHOD_POST);
        $this->controller->create($request);
    }

    /**
     * Test edit action redirects if user has no avatar.
     */
    public function testEditActionRedirectsIfUserHasNoAvatar(): void
    {
        $avatar = $this->createMock(Avatar::class);

        $this->mockUserWithoutAvatar();

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('avatar_create');

        $this->mockUrlGenerator->expects($this->once())
            ->method('generate')
            ->with('avatar_create', [], 1)
            ->willReturn('/avatar/create');

        $request = Request::create('/avatar/1/edit');
        $response = $this->controller->edit($request, $avatar);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/avatar/create', $response->getTargetUrl());
    }

    /**
     * Test edit action get request renders form.
     */
    public function testEditActionGetRequestRendersForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);

        $this->mockUserWithAvatar($mockAvatar);
        $form = $this->createMockForm(false, false);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with(
                'avatar/edit.html.twig',
                $this->callback(fn($args) => $args['form'] === $this->mockFormView && $args['avatar'] === $mockAvatar)
            )
            ->willReturn(new Response());

        $request = Request::create('/avatar/1/edit');
        $this->controller->edit($request, $mockAvatar);
    }

    /**
     * Test edit action put request invalid form.
     */
    public function testEditActionPutRequestInvalidForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);

        $this->mockUserWithAvatar($mockAvatar);
        $form = $this->createMockForm(true, false);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->avatarService->expects($this->never())->method('update');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with(
                'avatar/edit.html.twig',
                $this->callback(fn($args) => $args['form'] === $this->mockFormView && $args['avatar'] === $mockAvatar)
            )
            ->willReturn(new Response());

        $request = Request::create('/avatar/1/edit', \Symfony\Component\HttpFoundation\Request::METHOD_PUT);
        $this->controller->edit($request, $mockAvatar);
    }

    /**
     * Test delete action invalid form.
     */
    public function testDeleteActionInvalidForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);

        $form = $this->createMockForm(true, false);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);
        $this->controller->expects($this->once())
            ->method('generateUrl')
            ->with('avatar_delete', ['id' => $avatarId]);

        $this->mockUrlGenerator->expects($this->once())
            ->method('generate')
            ->with('avatar_delete', ['id' => $avatarId], 1)
            ->willReturn("/avatar/$avatarId/delete");


        $this->avatarService->expects($this->never())->method('delete');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with(
                'avatar/delete.html.twig',
                $this->callback(fn($args) => $args['form'] === $this->mockFormView && $args['avatar'] === $mockAvatar)
            )
            ->willReturn(new Response());

        $request = Request::create('/avatar/1/delete', \Symfony\Component\HttpFoundation\Request::METHOD_DELETE);
        $this->controller->delete($request, $mockAvatar);
    }

    /**
     * Test delete action with valid form submission.
     */
    public function testDeleteActionValidForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);

        $form = $this->createMockForm(true, true);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);
        $this->controller->expects($this->once())
            ->method('generateUrl')
            ->with('avatar_delete', ['id' => $avatarId]);

        $this->mockUrlGenerator->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap([
                ['avatar_delete', ['id' => $avatarId], 1, "/avatar/$avatarId/delete"],
                ['app_profile', [], 1, '/app/profile'],
            ]);

        $this->avatarService->expects($this->once())->method('delete')->with($mockAvatar);
        $this->controller->expects($this->once())->method('addFlash')->with('success', 'message.deleted_successfully');
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_profile');
        $this->controller->expects($this->never())->method('render');

        $request = Request::create('/avatar/1/delete', \Symfony\Component\HttpFoundation\Request::METHOD_DELETE);
        $response = $this->controller->delete($request, $mockAvatar);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/app/profile', $response->getTargetUrl());
    }

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->avatarService = $this->createMock(AvatarServiceInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $this->mockFormView = $this->createMock(FormView::class);

        $mockFormFactory = $this->createMock(FormFactoryInterface::class);
        $this->mockUrlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $mockFlashBag = $this->createMock(FlashBagInterface::class);

        $mockSession = $this->createMock(Session::class);
        $mockSession->method('getFlashBag')->willReturn($mockFlashBag);

        $this->controller = $this->getMockBuilder(AvatarController::class)
            ->setConstructorArgs([$this->avatarService, $translator])
            ->onlyMethods([
                'createForm',
                'getUser',
                'addFlash',
                'redirectToRoute',
                'render',
                'generateUrl',
            ])
            ->addMethods(['get'])
            ->getMock();

        $this->controller->method('get')->willReturnMap([
            ['form.factory', 1, $mockFormFactory],
            ['router', 1, $this->mockUrlGenerator],
            ['session', 1, $mockSession],
        ]);

        $this->controller->method('addFlash');

        $this->controller->method('redirectToRoute')->willReturnCallback(function ($route, $params = [], $status = 302) {
            $url = $this->mockUrlGenerator->generate($route, $params);

            return new RedirectResponse($url, $status);
        });

        $this->controller->method('generateUrl')->willReturnCallback(fn($route, $params = []) => $this->mockUrlGenerator->generate($route, $params));

        $this->controller->method('render')->willReturn(new Response());
    }

    /**
     * Helper to mock a user with a specific avatar.
     */
    private function mockUserWithAvatar(?Avatar $avatar = null): void
    {
        $user = $this->createMock(User::class);
        $user->method('getAvatar')->willReturn($avatar);
        $this->controller->expects($this->any())->method('getUser')->willReturn($user);
    }

    /**
     * Helper to mock a user without an avatar.
     */
    private function mockUserWithoutAvatar(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getAvatar')->willReturn(null);
        $this->controller->expects($this->any())->method('getUser')->willReturn($user);
    }

    /**
     * Helper to create a mocked form with necessary behaviors.
     */
    private function createMockForm(bool $isSubmitted, bool $isValid): MockObject|FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn($isSubmitted);
        $form->method('isValid')->willReturn($isValid);
        $form->method('createView')->willReturn($this->mockFormView);

        $fileForm = $this->createMock(FormInterface::class);
        $fileForm->method('getData')->willReturn(null);
        $form->method('get')->with('file')->willReturn($fileForm);

        return $form;
    }
}
