<?php

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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface; // For addFlash messages
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ContactControllerTest extends TestCase
{
    private MockObject|ContactService $contactService;
    private MockObject|ContactController $controller;
    private MockObject|FormView $mockFormView;
    private MockObject|TranslatorInterface $translator; // For addFlash messages

    // Used to capture the Contact entity created in create/edit actions
    // This will now be primarily used for tests that do NOT involve form submission leading to data manipulation
    private $capturedContact;

    protected function setUp(): void
    {
        // FIX: Corrected syntax to call parent's setUp method
        parent::setUp();

        $this->contactService = $this->createMock(ContactService::class);
        $this->translator = $this->createMock(TranslatorInterface::class); // Mock the translator
        $this->mockFormView = $this->createMock(FormView::class);

        // Create a partial mock for the controller to override its base methods.
        $this->controller = $this->getMockBuilder(ContactController::class)
            ->setConstructorArgs([$this->contactService])
            ->onlyMethods(['createForm', 'getUser', 'addFlash', 'redirectToRoute', 'render', 'createFormBuilder'])
            ->getMock();

        // FIX: Remove default configure for createForm and createFormBuilder in setUp.
        // Each test will now explicitly configure the form behavior it needs.

        $this->controller->method('addFlash'); // addFlash is void
        $this->controller->method('render')->willReturn(new Response());
        $this->controller->method('redirectToRoute')->willReturnCallback(function ($route, $params = []) {
            return new RedirectResponse('/' . $route); // Simple mock for redirect
        });

        // Mock `getUser` to return a mock User object by default, or null for anonymous tests
        $mockUser = $this->createMock(User::class);
        $mockUser->method('getId')->willReturn(1);
        // Ensure getUser() returns a valid User object by default.
        $this->controller->method('getUser')->willReturn($mockUser);

        // Mock translator for flash messages
        $this->translator->method('trans')->willReturnArgument(0); // Returns the translation key itself

        // Initialize capturedContact to null for each test
        $this->capturedContact = null;
    }

    /**
     * Helper to mock a logged-in user.
     */
    private function mockLoggedInUser(?int $id = 1): MockObject|User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $this->controller->method('getUser')->willReturn($user);
        return $user;
    }

    /**
     * Helper to mock an anonymous user.
     */
    private function mockAnonymousUser(): void
    {
        $this->controller->method('getUser')->willReturn(null);
    }

    // --- Index Action Tests ---

    public function testIndexActionGetRequest(): void
    {
        $request = Request::create('/contact', 'GET');
        $user = $this->mockLoggedInUser();

        // FIX: Explicitly configure the form mock for this test
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

        $response = $this->controller->index($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testIndexActionGetRequestWithFilters(): void
    {
        $request = Request::create('/contact?page=2&tags=tag1,tag2', 'GET');
        $user = $this->mockLoggedInUser();

        // FIX: Explicitly configure the form mock for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true); // Assume valid submission for this test
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')->willReturn($form);

        $pagination = $this->createMock(PaginationInterface::class);
        $this->contactService->expects($this->once())
            ->method('getPaginatedList')
            ->with(2, $user, $this->isInstanceOf(ContactListFiltersDto::class)) // page 2
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/index.html.twig', [
                'pagination' => $pagination,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $response = $this->controller->index($request);
        $this->assertInstanceOf(Response::class, $response);
    }


    // --- Show Action Tests ---

    public function testShowAction(): void
    {
        $contact = $this->createMock(Contact::class);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/view.html.twig', ['contact' => $contact])
            ->willReturn(new Response());

        $response = $this->controller->show($contact);
        $this->assertInstanceOf(Response::class, $response);
    }

    // --- Create Action Tests ---

    public function testCreateActionGetRequest(): void
    {
        $request = Request::create('/contact/create', 'GET');

        // FIX: Explicitly configure the form mock for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(ContactType::class, $this->callback(function (Contact $contact) {
                // Capture the Contact instance created by the controller
                $this->capturedContact = $contact;
                return true;
            }))
            ->willReturn($form);

        // FIX: Use a callback to assert the `contact` parameter is the actual Contact instance created by the controller.
        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/create.html.twig', $this->callback(function ($args) {
                $this->assertArrayHasKey('contact', $args);
                $this->assertSame($this->capturedContact, $args['contact']); // Assert it's the exact captured instance
                $this->assertInstanceOf(Contact::class, $args['contact']);
                $this->assertArrayHasKey('form', $args);
                $this->assertSame($this->mockFormView, $args['form']);
                return true;
            }))
            ->willReturn(new Response());

        $this->contactService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $response = $this->controller->create($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateActionPostRequestInvalidForm(): void
    {
        $request = Request::create('/contact/create', 'POST');
        $user = $this->mockLoggedInUser(); // A user must be logged in for setAuthor

        // FIX: Explicitly configure the form mock for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->method('createForm')
            ->with(ContactType::class, $this->callback(function (Contact $contact) {
                // Capture the Contact instance created by the controller
                $this->capturedContact = $contact;
                return true;
            }))
            ->willReturn($form);

        $this->contactService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        // FIX: Use a callback to assert the `contact` parameter is the actual Contact instance created by the controller.
        $this->controller->expects($this->once())
            ->method('render')
            ->with('contact/create.html.twig', $this->callback(function ($args) {
                $this->assertArrayHasKey('contact', $args);
                $this->assertSame($this->capturedContact, $args['contact']); // Assert it's the exact captured instance
                $this->assertInstanceOf(Contact::class, $args['contact']);
                $this->assertArrayHasKey('form', $args);
                $this->assertSame($this->mockFormView, $args['form']);
                return true;
            }))
            ->willReturn(new Response());

        $response = $this->controller->create($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateActionPostRequestValidForm(): void
    {
        $request = Request::create('/contact/create', 'POST');
        $user = $this->mockLoggedInUser();

        // Configure the form mock.
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        // The controller's create method passes `new Contact()` to the form,
        // and form's `handleRequest` populates that same object.
        // We do not need to mock `getData()` here if the controller passes the original entity to the service.

        // Mock the file input form (if the main form expects it)
        $fileForm = $this->createMock(FormInterface::class);
        $fileForm->method('getData')->willReturn($this->createMock(UploadedFile::class));
        $form->method('get')->with('file')->willReturn($fileForm);

        // Expect createForm to be called with a ContactType and *any* instance of Contact.
        // The controller will create a new Contact() and pass it here.
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(ContactType::class, $this->isInstanceOf(Contact::class))
            ->willReturn($form);

        // Configure the contactService->save() expectation.
        // It should be called with an instance of Contact, and that instance should have the correct author.
        $this->contactService->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Contact $contact) use ($user) {
                // Assert that the object passed to save is an instance of Contact.
                $this->assertInstanceOf(Contact::class, $contact);
                // Assert that the author was set correctly on this object.
                // This checks the behavior of $contact->setAuthor($this->getUser()).
                $this->assertSame($user, $contact->getAuthor());
                return true;
            }));

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.created_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('contact_index');

        $this->controller->expects($this->never())->method('render');

        $response = $this->controller->create($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/contact_index', $response->getTargetUrl());
    }

    // --- Edit Action Tests ---

    public function testEditActionGetRequest(): void
    {
        $request = Request::create('/contact/1/edit', 'GET');
        $contact = $this->createMock(Contact::class);

        // FIX: Explicitly configure the form mock for this test
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

        $response = $this->controller->edit($request, $contact);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditActionPostRequestInvalidForm(): void
    {
        $request = Request::create('/contact/1/edit', 'POST');
        $contact = $this->createMock(Contact::class);

        // FIX: Explicitly configure the form mock for this test
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

        $response = $this->controller->edit($request, $contact);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditActionPostRequestValidForm(): void
    {
        $request = Request::create('/contact/1/edit', 'POST');
        $contact = $this->createMock(Contact::class);

        // FIX: Explicitly configure the form mock for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        // We don't expect createView on success because it redirects
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

        // FIX: Ensure render is never called when redirecting
        $this->controller->expects($this->never())->method('render');

        $response = $this->controller->edit($request, $contact);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/contact_index', $response->getTargetUrl()); // Matches simple mock behavior
    }

    // --- Delete Action Tests ---

    public function testDeleteActionGetRequest(): void
    {
        $request = Request::create('/contact/1/delete', 'GET');
        $contact = $this->createMock(Contact::class);

        // FIX: Explicitly configure the form builder and form mock for this test
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

        $response = $this->controller->delete($request, $contact);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDeleteActionPostRequestInvalidForm(): void
    {
        $request = Request::create('/contact/1/delete', 'POST');
        $contact = $this->createMock(Contact::class);

        // FIX: Explicitly configure the form builder and form mock for this test
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

        $response = $this->controller->delete($request, $contact);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDeleteActionPostRequestValidForm(): void
    {
        $request = Request::create('/contact/1/delete', 'POST');
        $contact = $this->createMock(Contact::class);

        // FIX: Explicitly configure the form builder and form mock for this test
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

        // FIX: Ensure render is never called when redirecting
        $this->controller->expects($this->never())->method('render');

        $response = $this->controller->delete($request, $contact);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/contact_index', $response->getTargetUrl()); // Matches simple mock behavior
    }
}
