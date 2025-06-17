<?php

namespace App\Tests\Unit\Controller;

use App\Controller\TagController;
use App\Entity\Tag;
use App\Form\Type\TagType;
use App\Service\TagServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; // Corrected: ensure Response is used explicitly
use Symfony\Component\HttpFoundation\RedirectResponse; // Corrected: ensure RedirectResponse is used explicitly
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface; // Added: For mocking pagination

/**
 * Class TagControllerTest.
 */
class TagControllerTest extends WebTestCase
{
    private TagServiceInterface|\PHPUnit\Framework\MockObject\MockObject $tagService;
    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;
    // Removed RouterInterface and FlashBagInterface properties as they were only written to and never read
    private TagController $tagController;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        // Removed $this->formFactory as it was only written to and never read
        // Removed $this->router and $this->flashBag as they were only written to and never read

        // Instantiate the controller with its primary dependencies
        // The methods inherited from AbstractController (like createForm, render, etc.)
        // will be mocked directly in each test method using getMockBuilder.
        $this->tagController = new TagController($this->tagService, $this->translator);
    }

    /**
     * Test index action.
     */
    public function testIndex(): void
    {
        // Mock PaginationInterface as the return type for getPaginatedList
        $pagination = $this->createMock(PaginationInterface::class);

        $this->tagService->expects($this->once())
            ->method('getPaginatedList')
            ->with(1) // Assuming page is 1 for this test, as it's the default
            ->willReturn($pagination);

        // Mock render method from AbstractController directly on the controller instance for this test
        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['render']) // Only mock 'render'
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('render')
            ->with('tag/index.html.twig', ['pagination' => $pagination, ])
            ->willReturn(new Response()); // Return a dummy response

        $response = $this->tagController->index(1); // Pass explicit page number

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test view action.
     */
    public function testView(): void
    {
        $tag = new Tag();
        // Removed setId() - ID is usually managed by ORM, not set directly in entity constructor for testing
        $tag->setName('Test Tag');

        // Mock render method
        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('render')
            ->with('tag/view.html.twig', ['tag' => $tag])
            ->willReturn(new Response());

        $response = $this->tagController->view($tag);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test create action with valid data (POST request).
     */
    public function testCreateActionValidData(): void
    {
        $request = new Request([], ['name' => 'New Tag']); // Simulate POST data
        $request->setMethod('POST');

        $tag = new Tag(); // The entity that will be passed to the form
        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(\Symfony\Component\Form\FormView::class); // Mock FormView

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        // Removed ->method('getData') expectation as it's not explicitly called by the controller
        // Removed createView expectation, as it's not called on redirect
        // $form->expects($this->never())->method('createView');


        // Mock controller methods that depend on the container (createForm, redirectToRoute, addFlash, render)
        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'redirectToRoute', 'addFlash', 'render'])
            ->getMock();

        // Configure the mocked createForm method to return our form mock
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

        // Configure the mocked addFlash method
        $this->tagController->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Tag created successfully!');

        // Configure the mocked redirectToRoute method
        $this->tagController->expects($this->once())
            ->method('redirectToRoute')
            ->with('tag_index')
            ->willReturn(new RedirectResponse('/tag')); // Fully qualify RedirectResponse

        // Expect the render call
        $this->tagController->expects($this->never()) // Should not render on valid data redirection
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
        $request = new Request([], ['name' => '']); // Simulate invalid POST data
        $request->setMethod('POST');

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(\Symfony\Component\Form\FormView::class); // Mock FormView

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false); // Form is invalid
        $form->expects($this->once()) // Ensure createView is called exactly once by the controller
        ->method('createView')
            ->willReturn($formView);

        // Mock controller methods
        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('createForm')
            ->with(TagType::class, $this->isInstanceOf(Tag::class))
            ->willReturn($form);

        $this->tagService->expects($this->never()) // Save should not be called
        ->method('save');

        $this->translator->expects($this->never()) // Flash message should not be added
        ->method('trans');

        $this->tagController->expects($this->once())
            ->method('render')
            ->with('tag/create.html.twig', ['form' => $formView]) // Use the mocked FormView object
            ->willReturn(new Response());

        $response = $this->tagController->create($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test edit action with valid data (PUT request).
     */
    public function testEditActionValidData(): void
    {
        $tag = new Tag();
        // Removed setId()
        $tag->setName('Original Tag');

        $request = new Request([], ['name' => 'Updated Tag']);
        $request->setMethod('PUT'); // Simulate PUT method

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(\Symfony\Component\Form\FormView::class); // Mock FormView


        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        // Removed ->method('getData') expectation
        // Removed createView expectation, as it's not called on redirect
        // $form->expects($this->never())->method('createView');


        // Mock controller methods
        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'redirectToRoute', 'addFlash', 'render']) // Added render to mocks
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
            ->willReturn(new RedirectResponse('/tag')); // Fully qualify RedirectResponse

        $this->tagController->expects($this->never()) // Should not render on valid data redirection
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

        $request = new Request([], ['name' => '']); // Simulate invalid data
        $request->setMethod('PUT');

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(\Symfony\Component\Form\FormView::class); // Mock FormView


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

        // Mock controller methods
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
            ->with('tag/edit.html.twig', ['form' => $formView, 'tag' => $tag]) // Use the mocked FormView object
            ->willReturn(new Response());

        $response = $this->tagController->edit($request, $tag);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test delete action with valid data (DELETE request).
     */
    public function testDeleteActionValidData(): void
    {
        $tag = new Tag();
        // Removed setId()
        $tag->setName('Tag to Delete');
        // Setting a dummy ID for generateUrl to work, since getId() is used
        // In a real entity, this would be handled by a persistent layer (ORM)
        // For unit test, we just need a value for the mock to expect.
        $reflection = new \ReflectionProperty($tag, 'id');
        $reflection->setValue($tag, 1);


        $request = new Request();
        $request->setMethod('DELETE');

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(\Symfony\Component\Form\FormView::class); // Mock FormView


        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->never()) // createView should NOT be called on successful redirect
        ->method('createView');

        // Mock controller methods
        $this->tagController = $this->getMockBuilder(TagController::class)
            ->setConstructorArgs([$this->tagService, $this->translator])
            ->onlyMethods(['createForm', 'redirectToRoute', 'addFlash', 'generateUrl', 'render']) // Render may be called if form is not valid, although not the case here
            ->getMock();

        $this->tagController->expects($this->once())
            ->method('generateUrl')
            ->with('tag_delete', ['id' => $tag->getId()])
            ->willReturn('/tag/1/delete'); // Mock the URL generation

        $this->tagController->expects($this->once())
            ->method('createForm')
            ->with(\Symfony\Component\Form\Extension\Core\Type\FormType::class, $tag, [
                'method' => 'DELETE',
                'action' => '/tag/1/delete', // Use the mocked URL here
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
            ->willReturn(new RedirectResponse('/tag')); // Fully qualify RedirectResponse

        $this->tagController->expects($this->never()) // Should not render on valid data redirection
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
        // Removed setId()
        $tag->setName('Tag to Delete');
        // Setting a dummy ID for generateUrl to work, since getId() is used
        $reflection = new \ReflectionProperty($tag, 'id');
        $reflection->setValue($tag, 1);


        $request = new Request();
        $request->setMethod('GET'); // Simulate GET request, form won't be submitted/valid

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(\Symfony\Component\Form\FormView::class); // Mock FormView


        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false); // Form not submitted on GET
        $form->expects($this->never()) // isValid should not be called if not submitted
        ->method('isValid');
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        // Mock controller methods
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
            ->with(\Symfony\Component\Form\Extension\Core\Type\FormType::class, $tag, [
                'method' => 'DELETE',
                'action' => '/tag/1/delete', // Use the mocked URL here
            ])
            ->willReturn($form);

        $this->tagService->expects($this->never()) // Delete should not be called
        ->method('delete');
        $this->translator->expects($this->never()) // Flash message should not be added
        ->method('trans');

        $this->tagController->expects($this->once())
            ->method('render')
            ->with(
                'tag/delete.html.twig',
                [
                    'form' => $formView, // Use the mocked FormView object
                    'tag' => $tag,
                ]
            )
            ->willReturn(new Response());

        $response = $this->tagController->delete($request, $tag);

        $this->assertInstanceOf(Response::class, $response);
    }
}
