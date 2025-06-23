<?php

/**
 * Event controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\EventController;
use App\Dto\EventListFiltersDto;
use App\Entity\Event;
use App\Entity\User;
use App\Form\Type\EventListFilterType;
use App\Form\Type\EventType;
use App\Service\EventServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Knp\Component\Pager\Pagination\PaginationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class Event controller Test.
 */
class EventControllerTest extends TestCase
{
    private MockObject|EventServiceInterface $eventService;
    private MockObject|EventController $controller;
    private MockObject|FormView $mockFormView;
    private MockObject|User $mockUser;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventService = $this->createMock(EventServiceInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->mockFormView = $this->createMock(FormView::class);
        $this->mockUser = $this->createMock(User::class);

        $this->controller = $this->getMockBuilder(EventController::class)
            ->setConstructorArgs([$this->eventService, $translator])
            ->setMethods(['createForm', 'render', 'redirectToRoute', 'addFlash', 'generateUrl', 'getUser'])
            ->getMock();

        $this->controller->method('render')->willReturn(new Response());

        $this->controller->method('redirectToRoute')->willReturnCallback(function ($route, $params = [], $status = 302) {
            $url = '/mocked/redirect/'.str_replace('_', '/', $route);
            if (!empty($params)) {
                $url .= '?'.http_build_query($params);
            }

            return new RedirectResponse($url, $status);
        });

        $this->controller->method('addFlash');

        $this->controller->method('generateUrl')->willReturnCallback(function ($route, $params = []) {
            $url = '/mocked/url/'.str_replace('_', '/', $route);
            if (!empty($params)) {
                $url .= '?'.http_build_query($params);
            }

            return $url;
        });

        $this->controller->method('getUser')->willReturn($this->mockUser);
        $translator->method('trans')->willReturnArgument(0);
    }

    /**
     * Test index action.
     */
    public function testIndexAction(): void
    {
        $page = 1;
        $pagination = $this->createMock(PaginationInterface::class);

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

        $request = Request::create('/event');
        $this->controller->index($request, $page);
    }

    /**
     * Test index action with filters applied.
     */
    public function testIndexActionWithFiltersApplied(): void
    {
        $page = 1;
        $pagination = $this->createMock(PaginationInterface::class);
        $filtersDto = new EventListFiltersDto(new ArrayCollection(), null, null);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($filtersDto); // Form returns the DTO
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventListFilterType::class, $this->isInstanceOf(EventListFiltersDto::class), ['method' => 'GET'])
            ->willReturn($form);

        $this->eventService->expects($this->once())
            ->method('getPaginatedList')
            ->with($page, $this->mockUser, $filtersDto)
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/index.html.twig', [
                'pagination' => $pagination,
                'form' => $this->mockFormView,
            ])
            ->willReturn(new Response());

        $request = Request::create('/event', 'GET', ['title' => 'Test Event', 'status' => 1]);
        $this->controller->index($request, $page);
    }

    /**
     * Test view action.
     */
    public function testViewAction(): void
    {
        $event = $this->createMock(Event::class);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/view.html.twig', ['event' => $event])
            ->willReturn(new Response());

        $this->controller->view($event);
    }

    /**
     * Test create action get request renders form.
     */
    public function testCreateActionGetRequestRendersForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn(new Event());
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $this->isInstanceOf(Event::class))
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('event/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $request = Request::create('/event/create');
        $this->controller->create($request);
    }

    /**
     * Test create action post request valid form.
     */
    public function testCreateActionPostRequestValidForm(): void
    {
        $user = $this->mockUser;

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $form->method('createView')->willReturn($this->mockFormView);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $this->isInstanceOf(Event::class))
            ->willReturn($form);

        $this->eventService->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Event $passedEvent) use ($user) {
                $this->assertSame($user, $passedEvent->getAuthor());

                return true;
            }));

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.created_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('event_index');

        $this->controller->expects($this->never())->method('render');

        $request = Request::create('/event/create', 'POST', ['title' => 'New Event']);
        $response = $this->controller->create($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/event/index', $response->getTargetUrl());
    }

    /**
     * Test create action post request invalid form.
     */
    public function testCreateActionPostRequestInvalidForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn(new Event());
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

        $request = Request::create('/event/create', 'POST', ['title' => '']);
        $this->controller->create($request);
    }

    /**
     * Test edit action get request renders form.
     */
    public function testEditActionGetRequestRendersForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $event, [
                'method' => 'PUT',
                'action' => '/mocked/url/event/edit?id='.$eventId,
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
        $this->controller->edit($request, $event);
    }

    /**
     * Test edit action put request valid form.
     */
    public function testEditActionPutRequestValidForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $event, [
                'method' => 'PUT',
                'action' => '/mocked/url/event/edit?id='.$eventId,
            ])
            ->willReturn($form);

        $this->eventService->expects($this->once())
            ->method('save')
            ->with($event);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.edited_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('event_index');

        $this->controller->expects($this->never())->method('render');

        $request = Request::create('/event/1/edit', 'PUT', ['title' => 'Updated Event']);
        $response = $this->controller->edit($request, $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/event/index', $response->getTargetUrl());
    }

    /**
     * Test edit action put request invalid form.
     */
    public function testEditActionPutRequestInvalidForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(EventType::class, $event, [
                'method' => 'PUT',
                'action' => '/mocked/url/event/edit?id='.$eventId,
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

        $request = Request::create('/event/1/edit', 'PUT', ['title' => '']);
        $this->controller->edit($request, $event);
    }

    /**
     * Test  delete action get request renders form.
     */
    public function testDeleteActionGetRequestRendersForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(FormType::class, $event, [
                'method' => 'DELETE',
                'action' => '/mocked/url/event/delete?id='.$eventId,
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
        $this->controller->delete($request, $event);
    }

    /**
     * test delete action delete request valid form.
     */
    public function testDeleteActionDeleteRequestValidForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(FormType::class, $event, [
                'method' => 'DELETE',
                'action' => '/mocked/url/event/delete?id='.$eventId,
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

        $this->controller->expects($this->never())->method('render');

        $request = Request::create('/event/1/delete', 'DELETE');
        $response = $this->controller->delete($request, $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/event/index', $response->getTargetUrl());
    }

    /**
     * Test delete action delete request invalid form.
     */
    public function testDeleteActionDeleteRequestInvalidForm(): void
    {
        $event = $this->createMock(Event::class);
        $eventId = 1;
        $event->method('getId')->willReturn($eventId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $form->method('getData')->willReturn($event);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(FormType::class, $event, [
                'method' => 'DELETE',
                'action' => '/mocked/url/event/delete?id='.$eventId,
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
        $this->controller->delete($request, $event);
    }
}
