<?php

/**
 * Tag controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\TagController;
use App\Entity\Tag;
use App\Form\Type\TagType;
use App\Service\TagServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Class TagControllerTest.
 */
class TagControllerTest extends WebTestCase
{
    private TagServiceInterface|MockObject $tagService;
    private TranslatorInterface|MockObject $translator;
    private TagController $tagController;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->tagController = new TagController($this->tagService, $this->translator);
    }

    /**
     * Test index action.
     */
    public function testIndex(): void
    {
        $pagination = $this->createMock(PaginationInterface::class);

        $this->tagService->expects($this->once())
            ->method('getPaginatedList')
            ->with(1)
            ->willReturn($pagination);

        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('render')
            ->with('tag/index.html.twig', ['pagination' => $pagination, ])
            ->willReturn(new Response());

        $this->tagController->index();
    }

    /**
     * Test view action.
     */
    public function testView(): void
    {
        $tag = new Tag();
        $tag->setName('Test Tag');

        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('render')
            ->with('tag/view.html.twig', ['tag' => $tag])
            ->willReturn(new Response());

        $this->tagController->view($tag);
    }

    /**
     * Test create action with valid data (POST request).
     */
    public function testCreateActionValidData(): void
    {
        $request = new Request([], ['name' => 'New Tag']);
        $request->setMethod('POST');

        $form = $this->createMock(FormInterface::class);
        $this->createMock(FormView::class);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);


        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'redirectToRoute', 'addFlash', 'render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('createForm')
            ->with(TagType::class, $this->isInstanceOf(Tag::class))
            ->willReturn($form);

        $this->tagService->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Tag::class));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('message.created_successfully')
            ->willReturn('Tag created successfully!');

        $this->tagController->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Tag created successfully!');

        $this->tagController->expects($this->once())
            ->method('redirectToRoute')
            ->with('tag_index')
            ->willReturn(new RedirectResponse('/tag'));

        $this->tagController->expects($this->never())
        ->method('render');

        $response = $this->tagController->create($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/tag', $response->getTargetUrl());
    }

    /**
     * Test create action with invalid data (POST request).
     */
    public function testCreateActionInvalidData(): void
    {
        $request = new Request([], ['name' => '']);
        $request->setMethod('POST');

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $form->expects($this->once())
        ->method('createView')
            ->willReturn($formView);

        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('createForm')
            ->with(TagType::class, $this->isInstanceOf(Tag::class))
            ->willReturn($form);

        $this->tagService->expects($this->never())
        ->method('save');

        $this->translator->expects($this->never())
        ->method('trans');

        $this->tagController->expects($this->once())
            ->method('render')
            ->with('tag/create.html.twig', ['form' => $formView])
            ->willReturn(new Response());

        $this->tagController->create($request);
    }

    /**
     * Test edit action with valid data (PUT request).
     */
    public function testEditActionValidData(): void
    {
        $tag = new Tag();
        $tag->setName('Original Tag');

        $request = new Request([], ['name' => 'Updated Tag']);
        $request->setMethod('PUT');

        $form = $this->createMock(FormInterface::class);
        $this->createMock(FormView::class);


        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'redirectToRoute', 'addFlash', 'render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('createForm')
            ->with(TagType::class, $tag)
            ->willReturn($form);

        $this->tagService->expects($this->once())
            ->method('save')
            ->with($tag);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('message.edited_successfully')
            ->willReturn('Tag edited successfully!');

        $this->tagController->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Tag edited successfully!');

        $this->tagController->expects($this->once())
            ->method('redirectToRoute')
            ->with('tag_index')
            ->willReturn(new RedirectResponse('/tag'));

        $this->tagController->expects($this->never())
        ->method('render');


        $response = $this->tagController->edit($request, $tag);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/tag', $response->getTargetUrl());
    }

    /**
     * Test edit action with invalid data (PUT request).
     */
    public function testEditActionInvalidData(): void
    {
        $tag = new Tag();
        // Removed setId()
        $tag->setName('Original Tag');

        $request = new Request([], ['name' => '']);
        $request->setMethod('PUT');

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);


        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('createForm')
            ->with(TagType::class, $tag)
            ->willReturn($form);

        $this->tagService->expects($this->never())
            ->method('save');

        $this->translator->expects($this->never())
            ->method('trans');

        $this->tagController->expects($this->once())
            ->method('render')
            ->with('tag/edit.html.twig', ['form' => $formView, 'tag' => $tag])
            ->willReturn(new Response());

        $this->tagController->edit($request, $tag);
    }

    /**
     * Test delete action with valid data (DELETE request).
     */
    public function testDeleteActionValidData(): void
    {
        $tag = new Tag();
        $tag->setName('Tag to Delete');
        try {
            $reflection = new ReflectionProperty($tag, 'id');
        } catch (ReflectionException) {
        }
        $reflection->setValue($tag, 1);


        $request = new Request();
        $request->setMethod('DELETE');

        $form = $this->createMock(FormInterface::class);
        $this->createMock(FormView::class);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->never())
        ->method('createView');

        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'redirectToRoute', 'addFlash', 'generateUrl', 'render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('generateUrl')
            ->with('tag_delete', ['id' => $tag->getId()])
            ->willReturn('/tag/1/delete');

        $this->tagController->expects($this->once())
            ->method('createForm')
            ->with(FormType::class, $tag, [
                'method' => 'DELETE',
                'action' => '/tag/1/delete',
            ])
            ->willReturn($form);

        $this->tagService->expects($this->once())
            ->method('delete')
            ->with($tag);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('message.deleted_successfully')
            ->willReturn('Tag deleted successfully!');

        $this->tagController->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Tag deleted successfully!');

        $this->tagController->expects($this->once())
            ->method('redirectToRoute')
            ->with('tag_index')
            ->willReturn(new RedirectResponse('/tag'));

        $this->tagController->expects($this->never())
        ->method('render');

        $response = $this->tagController->delete($request, $tag);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/tag', $response->getTargetUrl());
    }

    /**
     * Test delete action with invalid data (GET request).
     */
    public function testDeleteActionInvalidData(): void
    {
        $tag = new Tag();
        $tag->setName('Tag to Delete');
        try {
            $reflection = new ReflectionProperty($tag, 'id');
        } catch (ReflectionException) {
        }
        $reflection->setValue($tag, 1);


        $request = new Request();
        $request->setMethod('GET');

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);


        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false);
        $form->expects($this->never())
        ->method('isValid');
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'generateUrl', 'render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('generateUrl')
            ->with('tag_delete', ['id' => $tag->getId()])
            ->willReturn('/tag/1/delete');

        $this->tagController->expects($this->once())
            ->method('createForm')
            ->with(FormType::class, $tag, [
                'method' => 'DELETE',
                'action' => '/tag/1/delete',
            ])
            ->willReturn($form);

        $this->tagService->expects($this->never())
        ->method('delete');
        $this->translator->expects($this->never())
        ->method('trans');

        $this->tagController->expects($this->once())
            ->method('render')
            ->with(
                'tag/delete.html.twig',
                [
                    'form' => $formView,
                    'tag' => $tag,
                ]
            )
            ->willReturn(new Response());

        $this->tagController->delete($request, $tag);
    }
}
