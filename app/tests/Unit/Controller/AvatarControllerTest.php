<?php

namespace App\Tests\Unit\Controller;

use App\Controller\AvatarController;
use App\Entity\Avatar;
use App\Entity\User;
use App\Service\AvatarServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AvatarControllerTest extends TestCase
{
    private MockObject|AvatarServiceInterface $avatarService;
    private MockObject|TranslatorInterface $translator;
    private MockObject|AvatarController $controller;
    private MockObject|FormView $mockFormView;

    protected function setUp(): void
    {
        parent::setUp();

        $this->avatarService = $this->createMock(AvatarServiceInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->mockFormView = $this->createMock(FormView::class);

        $this->controller = $this->getMockBuilder(AvatarController::class)
            ->setConstructorArgs([$this->avatarService, $this->translator])
            ->setMethods(['createForm', 'generateUrl', 'getUser', 'addFlash', 'render', 'redirectToRoute'])
            ->getMock();

        $this->controller->method('createForm')->willReturnCallback(function($type, $data, $options) {
            $form = $this->createMock(FormInterface::class);
            $form->method('handleRequest')->willReturnSelf();
            $form->method('isSubmitted')->willReturn(false);
            $form->method('isValid')->willReturn(false);
            $form->method('createView')->willReturn($this->mockFormView);

            $fileForm = $this->createMock(FormInterface::class);
            $fileForm->method('getData')->willReturn($this->createMock(UploadedFile::class));
            $form->method('get')->with('file')->willReturn($fileForm);
            return $form;
        });

        $this->controller->method('generateUrl')->willReturnCallback(function($route, $params = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH) {
            $url = '';
            if (in_array($route, ['avatar_edit', 'avatar_delete'])) {
                $id = $params['id'] ?? null;
                if ($id !== null) {
                    $url = '/avatar/' . $id . (($route === 'avatar_edit') ? '/edit' : '/delete');
                } else {
                    $url = '/' . str_replace(['_', '.'], '/', $route);
                }
            } elseif ($route === 'dashboard_index') {
                $url = '/dashboard/index';
            } elseif ($route === 'app_profile') {
                $url = '/app/profile';
            } else {
                $url = '/' . str_replace(['_', '.'], '/', $route);
            }

            if (!empty($params) && !in_array($route, ['avatar_edit', 'avatar_delete'])) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        });

        $this->controller->method('addFlash'); // addFlash is void
        $this->controller->method('render')->willReturn(new Response());

        $this->controller->method('redirectToRoute')->willReturnCallback(function($route, $params = [], $status = 302) {
            $url = '';
            if (in_array($route, ['avatar_edit', 'avatar_delete'])) {
                $id = $params['id'] ?? null;
                if ($id !== null) {
                    $url = '/avatar/' . $id . (($route === 'avatar_edit') ? '/edit' : '/delete');
                } else {
                    $url = '/' . str_replace(['_', '.'], '/', $route);
                }
            } elseif ($route === 'dashboard_index') {
                $url = '/dashboard/index';
            } elseif ($route === 'app_profile') {
                $url = '/app/profile';
            } else {
                $url = '/' . str_replace(['_', '.'], '/', $route);
            }

            if (!empty($params) && !in_array($route, ['avatar_edit', 'avatar_delete'])) {
                $url .= '?' . http_build_query($params);
            }
            return new RedirectResponse($url, $status);
        });

        $this->translator->method('trans')->willReturnArgument(0);
    }

    /**
     * Helper to mock a user with a specific avatar.
     */
    private function mockUserWithAvatar(?Avatar $avatar = null): MockObject|User
    {
        $user = $this->createMock(User::class);
        $user->method('getAvatar')->willReturn($avatar);
        $this->controller->method('getUser')->willReturn($user);
        return $user;
    }

    /**
     * Helper to mock a user without an avatar.
     */
    private function mockUserWithoutAvatar(): MockObject|User
    {
        $user = $this->createMock(User::class);
        $user->method('getAvatar')->willReturn(null);
        $this->controller->method('getUser')->willReturn($user);
        return $user;
    }

    public function testCreateActionRedirectsIfUserHasAvatar(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);
        $this->mockUserWithAvatar($mockAvatar);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('avatar_edit', ['id' => $avatarId]);

        $request = Request::create('/avatar/create', 'GET');
        $response = $this->controller->create($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/avatar/'.$avatarId.'/edit', $response->getTargetUrl());
    }

    public function testCreateActionGetRequestRendersForm(): void
    {
        $this->mockUserWithoutAvatar();

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('avatar/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $this->controller->expects($this->once())
            ->method('generateUrl')
            ->with('avatar_create')
            ->willReturn('/avatar/create');

        $request = Request::create('/avatar/create', 'GET');
        $response = $this->controller->create($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateActionPostRequestInvalidForm(): void
    {
        $this->mockUserWithoutAvatar();

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')->willReturn($form);

        $this->avatarService->expects($this->never())->method('create');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('avatar/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $this->controller->expects($this->once())
            ->method('generateUrl')
            ->with('avatar_create')
            ->willReturn('/avatar/create');

        $request = Request::create('/avatar/create', 'POST');
        $response = $this->controller->create($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateActionPostRequestValidForm(): void
    {
        $user = $this->mockUserWithoutAvatar();
        $uploadedFile = $this->createMock(UploadedFile::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        // FIX: Explicitly configure isSubmitted and isValid to return true on this form mock.
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $fileForm = $this->createMock(FormInterface::class);
        $fileForm->method('getData')->willReturn($uploadedFile);
        $form->method('get')->with('file')->willReturn($fileForm);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->avatarService->expects($this->once())
            ->method('create')
            ->with($uploadedFile, $this->isInstanceOf(Avatar::class), $user);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.created_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('dashboard_index');

        $request = Request::create('/avatar/create', 'POST');
        $response = $this->controller->create($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/dashboard/index', $response->getTargetUrl());
    }


    public function testEditActionRedirectsIfUserHasNoAvatar(): void
    {
        $this->mockUserWithoutAvatar();
        $avatar = $this->createMock(Avatar::class);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('avatar_create');

        $request = Request::create('/avatar/1/edit', 'GET');
        $response = $this->controller->edit($request, $avatar);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/avatar/create', $response->getTargetUrl());
    }

    public function testEditActionGetRequestRendersForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);
        $this->mockUserWithAvatar($mockAvatar);

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with(
                'avatar/edit.html.twig',
                $this->callback(function($args) use ($mockAvatar) {
                    return $args['form'] === $this->mockFormView && $args['avatar'] === $mockAvatar;
                })
            )
            ->willReturn(new Response());

        $this->controller->expects($this->once())
            ->method('generateUrl')
            ->with('avatar_edit', ['id' => $avatarId])
            ->willReturn("/avatar/{$avatarId}/edit");

        $request = Request::create('/avatar/1/edit', 'GET');
        $response = $this->controller->edit($request, $mockAvatar);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditActionPutRequestInvalidForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);
        $this->mockUserWithAvatar($mockAvatar);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')->willReturn($form);

        $this->avatarService->expects($this->never())->method('update');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with(
                'avatar/edit.html.twig',
                $this->callback(function($args) use ($mockAvatar) {
                    return $args['form'] === $this->mockFormView && $args['avatar'] === $mockAvatar;
                })
            )
            ->willReturn(new Response());

        $this->controller->expects($this->once())
            ->method('generateUrl')
            ->with('avatar_edit', ['id' => $avatarId])
            ->willReturn("/avatar/{$avatarId}/edit");

        $request = Request::create('/avatar/1/edit', 'PUT');
        $response = $this->controller->edit($request, $mockAvatar);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditActionPutRequestValidForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);
        $user = $this->mockUserWithAvatar($mockAvatar);
        $uploadedFile = $this->createMock(UploadedFile::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        // FIX: Explicitly configure isSubmitted and isValid to return true on this form mock.
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $fileForm = $this->createMock(FormInterface::class);
        $fileForm->method('getData')->willReturn($uploadedFile);
        $form->method('get')->with('file')->willReturn($fileForm);
        $this->controller->method('createForm')->willReturn($form);

        $this->avatarService->expects($this->once())
            ->method('update')
            ->with($uploadedFile, $mockAvatar, $user);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.edited_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('dashboard_index');

        $request = Request::create('/avatar/1/edit', 'PUT');
        $response = $this->controller->edit($request, $mockAvatar);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/dashboard/index', $response->getTargetUrl());
    }


    public function testDeleteActionValidForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        // FIX: Explicitly configure isSubmitted and isValid to return true on this form mock.
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $this->controller->method('createForm')->willReturn($form);

        $this->avatarService->expects($this->once())
            ->method('delete')
            ->with($mockAvatar);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.deleted_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_profile');

        $request = Request::create('/avatar/1/delete', 'POST');
        $response = $this->controller->delete($request, $mockAvatar);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/app/profile', $response->getTargetUrl());
    }

    public function testDeleteActionInvalidForm(): void
    {
        $avatarId = 1;
        $mockAvatar = $this->createMock(Avatar::class);
        $mockAvatar->method('getId')->willReturn($avatarId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')->willReturn($form);

        $this->avatarService->expects($this->never())->method('delete');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with(
                'avatar/delete.html.twig',
                $this->callback(function($args) use ($mockAvatar) {
                    return $args['form'] === $this->mockFormView && $args['avatar'] === $mockAvatar;
                })
            )
            ->willReturn(new Response());

        $this->controller->expects($this->once())
            ->method('generateUrl')
            ->with('avatar_delete', ['id' => $avatarId])
            ->willReturn("/avatar/{$avatarId}/delete");

        $request = Request::create('/avatar/1/delete', 'POST');
        $response = $this->controller->delete($request, $mockAvatar);

        $this->assertInstanceOf(Response::class, $response);
    }
}
