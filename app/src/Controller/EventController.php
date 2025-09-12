<?php

/**
 * Event Controller.
 * */

namespace App\Controller;

use App\Dto\EventListFiltersDto;
use App\Entity\Event;
use App\Entity\User;
use App\Form\Type\EventType;
use App\Security\Voter\EventVoter;
use App\Service\EventServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Form\Type\EventListFilterType;

/**
 * Class Event controller.
 */
class EventController extends AbstractController
{
    /**
     * Constructor.
     *
     * @param EventServiceInterface $eventService Event service
     * @param TranslatorInterface   $translator   Translator
     */
    public function __construct(private readonly EventServiceInterface $eventService, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Index.
     *
     * @param Request $request HTTP Request
     * @param int     $page    Page number
     *
     * @return Response HTTP response
     */
    #[Route('/event', name: 'event_index', methods: 'GET')]
    public function index(Request $request, #[MapQueryParameter] int $page = 1): Response
    {
        $filtersDto = new EventListFiltersDto(new ArrayCollection(), null, null);

        $form = $this->createForm(EventListFilterType::class, $filtersDto, ['method' => 'GET']);
        $form->handleRequest($request);

        /** @var User $user */
        $user = $this->getUser();

        $pagination = $this->eventService->getPaginatedList(
            $page,
            $user,
            $filtersDto
        );

        return $this->render('event/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form->createView(),
        ]);
    }

    /**
     * View action.
     *
     * @param Event $event Event entity
     *
     * @return Response HTTP response
     */
    #[Route('/event/{id}', name: 'event_view', requirements: ['id' => '[1-9]\d*'], methods: 'GET')]
    #[IsGranted(EventVoter::VIEW, subject: 'event')]
    public function view(Event $event): Response
    {
        return $this->render(
            'event/view.html.twig',
            ['event' => $event]
        );
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route('/event/create', name: 'event_create', methods: 'GET|POST')]
    public function create(Request $request): Response
    {

        /** @var User $user */
        $user = $this->getUser();
        $event = new Event();
        $event->setAuthor($this->getUser());
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventService->save($event);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('event_index');
        }

        return $this->render(
            'event/create.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param Event   $event   Category entity
     *
     * @return Response HTTP response
     */
    #[Route('/event/{id}/edit', name: 'event_edit', requirements: ['id' => '[1-9]\d*'], methods: 'GET|PUT')]
    #[IsGranted(EventVoter::EDIT, subject: 'event')]
    public function edit(Request $request, Event $event): Response
    {
        $form = $this->createForm(EventType::class, $event, [
            'method' => 'PUT',
            'action' => $this->generateUrl('event_edit', ['id' => $event->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventService->save($event);

            $this->addFlash(
                'success',
                $this->translator->trans('message.edited_successfully')
            );

            return $this->redirectToRoute('event_index');
        }

        return $this->render(
            'event/edit.html.twig',
            [
                'form' => $form->createView(),
                'event' => $event,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP Request
     * @param Event   $event   Event entity
     *
     * @return Response HTTP response
     */
    #[Route('/event/{id}/delete', name: 'event_delete', requirements: ['id' => '[1-9]\d*'], methods: 'GET|DELETE')]
    #[IsGranted(EventVoter::DELETE, subject: 'event')]
    public function delete(Request $request, Event $event): Response
    {

        $form = $this->createForm(FormType::class, $event, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('event_delete', ['id' => $event->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventService->delete($event);

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('event_index');
        }

        return $this->render(
            'event/delete.html.twig',
            [
                'form' => $form->createView(),
                'event' => $event,
            ]
        );
    }
}
