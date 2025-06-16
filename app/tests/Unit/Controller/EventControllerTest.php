<?php

namespace App\Tests\Unit\Controller;

use App\Controller\EventController;
use App\Dto\EventListFiltersDto;
use App\Entity\Event; // FIX: Corrected use statement
use App\Entity\User; // FIX: Corrected use statement
use App\Form\Type\EventListFilterType;
use App\Form\Type\EventType;
use App\Security\Voter\EventVoter;
use App\Service\EventServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Knp\Component\Pager\Pagination\PaginationInterface;
use PHPUnit\Framework\MockObject\MockObject; // FIX: Corrected use statement
use PHPUnit\Framework\TestCase; // FIX: Corrected use statement
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface; // FIX: Corrected use statement

class EventControllerTest extends TestCase
{
    private MockObject|EventServiceInterface $eventService;
    private MockObject|TranslatorInterface $translator;
    private MockObject|EventController $controller;
    private MockObject|FormView $mockFormView;
    private MockObject|User $mockUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventService = $this->createMock(EventServiceInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->mockFormView = $this->createMock(FormView::class);
        $this->mockUser = $this->createMock(User::class);

        // Create a partial mock for the controller to override its base methods.
        $this->controller = $this->getMockBuilder(EventController::class)
            ->setConstructorArgs([$this->eventService, $this->translator])
            ->setMethods(['createForm', 'render', 'redirectToRoute', 'addFlash', 'generateUrl', 'getUser'])
            ->getMock();

        // FIX: Removed the default createForm mock configuration from setUp().
        // Each test will now explicitly configure the createForm behavior.

        $this->controller->method('render')->willReturn(new Response());

        // Mock redirectToRoute to simply return a RedirectResponse with a predictable URL.
        $this->controller->method('redirectToRoute')->willReturnCallback(function($route, $params = [], $status = 302) {
            $url = '/mocked/redirect/' . str_replace('_', '/', $route);
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return new RedirectResponse($url, $status);
        });

        $this->controller->method('addFlash'); // addFlash is void

        // Mock generateUrl to return a predictable URL string
        $this->controller->method('generateUrl')->willReturnCallback(function($route, $params = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH) {
            $url = '/mocked/url/' . str_replace('_', '/', $route);
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        });

        $this->controller->method('getUser')->willReturn($this->mockUser);
        $this->translator->method('trans')->willReturnArgument(0);
    }

    // --- Index Action Tests ---

    public function testIndexAction(): void
    {
        $page = 1;
        $pagination = $this->createMock(PaginationInterface::class);

        // FIX: Explicitly configure createForm for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn(new EventListFiltersDto(new ArrayCollection(), null, null)); // Default DTO
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventListFilterType::class, $this->isInstanceOf(EventListFiltersDto::class), ['method' => 'GET'])
            ->willReturn($form);

        $this->eventService->expects($this->once())
            ->method('getPaginatedList')
            ->with($page, $this->mockUser, $this->isInstanceOf(EventListFiltersDto::class))
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/index.html.twig', [
                'pagination' => $pagination,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $request = Request::create('/event', 'GET');
        $response = $this->controller->index($request, $page);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testIndexActionWithFiltersApplied(): void
    {
        $page = 1;
        $pagination = $this->createMock(PaginationInterface::class);
        $filtersDto = new EventListFiltersDto(new ArrayCollection(), null, null); // Will be modified by form

        // FIX: Explicitly configure createForm for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($filtersDto); // Form returns the DTO
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventListFilterType::class, $this->isInstanceOf(EventListFiltersDto::class), ['method' => 'GET'])
            ->willReturn($form); // Return our configured form mock

        $this->eventService->expects($this->once())
            ->method('getPaginatedList')
            ->with($page, $this->mockUser, $filtersDto) // Expect the DTO returned by the form
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/index.html.twig', [
                'pagination' => $pagination,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $request = Request::create('/event', 'GET', ['title' => 'Test Event', 'status' => 1]); // Simulate filter data
        $response = $this->controller->index($request, $page);

        $this->assertInstanceOf(Response::class, $response);
    }

    // --- View Action Tests ---

    public function testViewAction(): void
    {
        $event = $this->createMock(Event::class);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/view.html.twig', ['event' => $event])
            ->willReturn(new Response());

        $response = $this->controller->view($event);

        $this->assertInstanceOf(Response::class, $response);
    }

    // --- Create Action Tests ---

    public function testCreateActionGetRequestRendersForm(): void
    {
        // FIX: Explicitly configure createForm for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn(new Event()); // Default Event object
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $this->isInstanceOf(Event::class))
            ->willReturn($form); // Return a form mock

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $request = Request::create('/event/create', 'GET');
        $response = $this->controller->create($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreateActionPostRequestValidForm(): void
    {
        $user = $this->mockUser; // Use the mockUser from setUp
        // $event = new Event(); // Removed: The controller creates its own Event instance.

        // FIX: Create and configure the form mock directly in the test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true); // Ensure isSubmitted is true
        $form->method('isValid')->willReturn(true);     // Ensure isValid is true
        // Important: When the controller's form->handleRequest() is called, it populates
        // the object it was initialized with. We don't need to return a *specific*
        // pre-created $event here from getData() because we're asserting on the *passedEvent*
        // which comes from the controller's internal flow.
        $form->method('createView')->willReturn($this->mockFormView);

        // FIX: Configure createForm to return this specific form mock
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $this->isInstanceOf(Event::class)) // Controller passes a new Event() here
            ->willReturn($form);

        $this->eventService->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Event $passedEvent) use ($user) {
                // Assert that the object passed to save is an instance of Event.
                $this->assertInstanceOf(Event::class, $passedEvent);
                // Removed: $this->assertSame($event, $passedEvent); // This assertion was the cause of the failure.
                // Assert that the author was set correctly on this object by the controller.
                $this->assertSame($user, $passedEvent->getAuthor());
                return true;
            }));

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.created_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('event_index');

        $this->controller->expects($this->never())->method('render'); // Should redirect, not render

        $request = Request::create('/event/create', 'POST', ['title' => 'New Event']);
        $response = $this->controller->create($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/event/index', $response->getTargetUrl());
    }

    public function testCreateActionPostRequestInvalidForm(): void
    {
        // FIX: Explicitly configure createForm for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn(new Event()); // Default Event object
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $this->isInstanceOf(Event::class))
            ->willReturn($form);

        $this->eventService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $request = Request::create('/event/create', 'POST', ['title' => '']); // Invalid title
        $response = $this->controller->create($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    // --- Edit Action Tests ---

    public function testEditActionGetRequestRendersForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        // FIX: Explicitly configure createForm for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event); // Ensure getData returns the initial data
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $event, [
                'method' => 'PUT',
                'action' => '/mocked/url/event/edit?id=' . $eventId,
            ])
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/edit.html.twig', [
                'form' => $this->mockFormView,
                'event' => $event,
            ])
            ->willReturn(new Response());

        $request = Request::create('/event/1/edit', 'GET');
        $response = $this->controller->edit($request, $event);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditActionPutRequestValidForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        // FIX: Create and configure the form mock directly in the test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true); // Ensure isSubmitted is true
        $form->method('isValid')->willReturn(true);     // Ensure isValid is true
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event); // Form returns the data it was given (and populated)

        // FIX: Configure createForm to return this specific form mock
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $event, [
                'method' => 'PUT',
                'action' => '/mocked/url/event/edit?id=' . $eventId,
            ])
            ->willReturn($form);

        $this->eventService->expects($this->once())
            ->method('save')
            ->with($event); // Expect the original event instance

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.edited_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('event_index');

        $this->controller->expects($this->never())->method('render'); // Should redirect, not render

        $request = Request::create('/event/1/edit', 'PUT', ['title' => 'Updated Event']);
        $response = $this->controller->edit($request, $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/event/index', $response->getTargetUrl());
    }

    public function testEditActionPutRequestInvalidForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        // FIX: Explicitly configure createForm for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event); // Ensure getData returns the initial data
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $event, [
                'method' => 'PUT',
                'action' => '/mocked/url/event/edit?id=' . $eventId,
            ])
            ->willReturn($form);

        $this->eventService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/edit.html.twig', [
                'form' => $this->mockFormView,
                'event' => $event,
            ])
            ->willReturn(new Response());

        $request = Request::create('/event/1/edit', 'PUT', ['title' => '']); // Invalid title
        $response = $this->controller->edit($request, $event);

        $this->assertInstanceOf(Response::class, $response);
    }

    // --- Delete Action Tests ---

    public function testDeleteActionGetRequestRendersForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        // FIX: Explicitly configure createForm for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event); // Ensure getData returns the initial data
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(FormType::class, $event, [
                'method' => 'DELETE',
                'action' => '/mocked/url/event/delete?id=' . $eventId,
            ])
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/delete.html.twig', [
                'form' => $this->mockFormView,
                'event' => $event,
            ])
            ->willReturn(new Response());

        $request = Request::create('/event/1/delete', 'GET');
        $response = $this->controller->delete($request, $event);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDeleteActionDeleteRequestValidForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        // FIX: Create and configure the form mock directly in the test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true); // Ensure isSubmitted is true
        $form->method('isValid')->willReturn(true);     // Ensure isValid is true
        $form->method('createView')->willReturn($this->mockFormView); // For fallback render
        $form->method('getData')->willReturn($event); // Form returns the data it was given (and populated)

        // FIX: Configure createForm to return this specific form mock
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(FormType::class, $event, [
                'method' => 'DELETE',
                'action' => '/mocked/url/event/delete?id=' . $eventId,
            ])
            ->willReturn($form);

        $this->eventService->expects($this->once())
            ->method('delete')
            ->with($event);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.deleted_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('event_index');

        $this->controller->expects($this->never())->method('render'); // Should redirect, not render

        $request = Request::create('/event/1/delete', 'DELETE');
        $response = $this->controller->delete($request, $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/event/index', $response->getTargetUrl());
    }

    public function testDeleteActionDeleteRequestInvalidForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        // FIX: Explicitly configure createForm for this test
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event); // Ensure getData returns the initial data
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(FormType::class, $event, [
                'method' => 'DELETE',
                'action' => '/mocked/url/event/delete?id=' . $eventId,
            ])
            ->willReturn($form);

        $this->eventService->expects($this->never())->method('delete');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/delete.html.twig', [
                'form' => $this->mockFormView,
                'event' => $event,
            ])
            ->willReturn(new Response());

        $request = Request::create('/event/1/delete', 'DELETE');
        $response = $this->controller->delete($request, $event);

        $this->assertInstanceOf(Response::class, $response);
    }
}
