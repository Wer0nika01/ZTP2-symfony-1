<?php

/**
 * Contact controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\ContactController;
use App\Dto\ContactListFiltersDto;
use App\Entity\Contact;
use App\Entity\User;
use App\Form\Type\ContactListFilterType;
use App\Form\Type\ContactType;
use App\Service\ContactService;
use Knp\Component\Pager\Pagination\PaginationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class Contact controller Test.
 */
class ContactControllerTest extends TestCase
{
    private MockObject|ContactService $contactService;
    private MockObject|ContactController $controller;
    private MockObject|FormView $mockFormView;

    private $capturedContact;

    /**
     * test index action get request.
     */
    public function testIndexActionGetRequest(): void
    {
        $request = Request::create('/contact');
        $user = $this->mockLoggedInUser();

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')
            ->with(ContactListFilterType::class, $this->isInstanceOf(ContactListFiltersDto::class), ['method' => 'GET'])
            ->willReturn($form);

        $pagination = $this->createMock(PaginationInterface::class);
        $this->contactService->expects($this->once())
            ->method('getPaginatedList')
            ->with(1, $user, $this->isInstanceOf(ContactListFiltersDto::class))
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/index.html.twig', [
                'pagination' => $pagination,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $this->controller->index($request);
    }

    /**
     * Test index action get request with filters.
     */
    public function testIndexActionGetRequestWithFilters(): void
    {
        $request = Request::create('/contact?page=2&tags=tag1,tag2');
        $user = $this->mockLoggedInUser();

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')->willReturn($form);

        $pagination = $this->createMock(PaginationInterface::class);
        $this->contactService->expects($this->once())
            ->method('getPaginatedList')
            ->with(2, $user, $this->isInstanceOf(ContactListFiltersDto::class))
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/index.html.twig', [
                'pagination' => $pagination,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $this->controller->index($request);
    }

    /**
     * Test show action.
     */
    public function testShowAction(): void
    {
        $contact = $this->createMock(Contact::class);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/view.html.twig', ['contact' => $contact])
            ->willReturn(new Response());

        $this->controller->show($contact);
    }

    /**
     * Test create action get request.
     */
    public function testCreateActionGetRequest(): void
    {
        $request = Request::create('/contact/create');

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(ContactType::class, $this->callback(function (Contact $contact) {
                $this->capturedContact = $contact;

                return true;
            }))
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/create.html.twig', $this->callback(function ($args) {
                $this->assertArrayHasKey('contact', $args);
                $this->assertSame($this->capturedContact, $args['contact']);
                $this->assertInstanceOf(Contact::class, $args['contact']);
                $this->assertArrayHasKey('form', $args);
                $this->assertSame($this->mockFormView, $args['form']);

                return true;
            }))
            ->willReturn(new Response());

        $this->contactService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->create($request);
    }

    /**
     * Test create action post request invalid form.
     */
    public function testCreateActionPostRequestInvalidForm(): void
    {
        $request = Request::create('/contact/create', 'POST');
        $this->mockLoggedInUser();

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')
            ->with(ContactType::class, $this->callback(function (Contact $contact) {
                $this->capturedContact = $contact;

                return true;
            }))
            ->willReturn($form);

        $this->contactService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/create.html.twig', $this->callback(function ($args) {
                $this->assertArrayHasKey('contact', $args);
                $this->assertSame($this->capturedContact, $args['contact']);
                $this->assertInstanceOf(Contact::class, $args['contact']);
                $this->assertArrayHasKey('form', $args);
                $this->assertSame($this->mockFormView, $args['form']);

                return true;
            }))
            ->willReturn(new Response());

        $this->controller->create($request);
    }


    /**
     * Test edit action get request.
     */
    public function testEditActionGetRequest(): void
    {
        $request = Request::create('/contact/1/edit');
        $contact = $this->createMock(Contact::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')
            ->with(ContactType::class, $contact)
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/edit.html.twig', [
                'contact' => $contact,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $this->contactService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->edit($request, $contact);
    }

    /**
     * Test edit action post request invalid form.
     */
    public function testEditActionPostRequestInvalidForm(): void
    {
        $request = Request::create('/contact/1/edit', 'POST');
        $contact = $this->createMock(Contact::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')
            ->with(ContactType::class, $contact)
            ->willReturn($form);

        $this->contactService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/edit.html.twig', [
                'contact' => $contact,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $this->controller->edit($request, $contact);
    }

    /**
     * Test edit action post request valid form.
     */
    public function testEditActionPostRequestValidForm(): void
    {
        $request = Request::create('/contact/1/edit', 'POST');
        $contact = $this->createMock(Contact::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $this->controller->method('createForm')
            ->with(ContactType::class, $contact)
            ->willReturn($form);

        $this->contactService->expects($this->once())
            ->method('save')
            ->with($contact);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.edited_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('contact_index');

        $this->controller->expects($this->never())->method('render');

        $response = $this->controller->edit($request, $contact);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/contact_index', $response->getTargetUrl());
    }



    /**
     * Test delete action get request.
     */
    public function testDeleteActionGetRequest(): void
    {
        $request = Request::create('/contact/1/delete');
        $contact = $this->createMock(Contact::class);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $formBuilder->method('getForm')->willReturn($form);

        $this->controller->expects($this->once())
            ->method('createFormBuilder')
            ->with($contact)
            ->willReturn($formBuilder);


        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/delete.html.twig', [
                'contact' => $contact,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $this->contactService->expects($this->never())->method('remove');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->delete($request, $contact);
    }

    /**
     * Test delete action post request invalid form.
     */
    public function testDeleteActionPostRequestInvalidForm(): void
    {
        $request = Request::create('/contact/1/delete', 'POST');
        $contact = $this->createMock(Contact::class);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $formBuilder->method('getForm')->willReturn($form);

        $this->controller->method('createFormBuilder')
            ->with($contact)
            ->willReturn($formBuilder);


        $this->contactService->expects($this->never())->method('remove');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/delete.html.twig', [
                'contact' => $contact,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $this->controller->delete($request, $contact);
    }

    /**
     * Test delete action post request valid form.
     */
    public function testDeleteActionPostRequestValidForm(): void
    {
        $request = Request::create('/contact/1/delete', 'POST');
        $contact = $this->createMock(Contact::class);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        // We don't expect createView on success because it redirects
        $formBuilder->method('getForm')->willReturn($form);

        $this->controller->method('createFormBuilder')
            ->with($contact)
            ->willReturn($formBuilder);

        $this->contactService->expects($this->once())
            ->method('remove')
            ->with($contact);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.deleted_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('contact_index');

        $this->controller->expects($this->never())->method('render');

        $response = $this->controller->delete($request, $contact);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/contact_index', $response->getTargetUrl()); // Matches simple mock behavior
    }


    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->contactService = $this->createMock(ContactService::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->mockFormView = $this->createMock(FormView::class);

        $this->controller = $this->getMockBuilder(ContactController::class)
            ->setConstructorArgs([$this->contactService])
            ->onlyMethods(['createForm', 'getUser', 'addFlash', 'redirectToRoute', 'render', 'createFormBuilder'])
            ->getMock();

        $this->controller->method('addFlash');
        $this->controller->method('render')->willReturn(new Response());
        $this->controller->method('redirectToRoute')->willReturnCallback(function ($route) {
            return new RedirectResponse('/'.$route);
        });

        $mockUser = $this->createMock(User::class);
        $mockUser->method('getId')->willReturn(1);
        $this->controller->method('getUser')->willReturn($mockUser);

        // Mock translator for flash messages
        $translator->method('trans')->willReturnArgument(0);

        $this->capturedContact = null;
    }

    /**
     * Helper to mock a logged-in user.
     *
     *
     * @return MockObject|User
     */
    private function mockLoggedInUser(): MockObject|User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $this->controller->method('getUser')->willReturn($user);

        return $user;
    }
}
