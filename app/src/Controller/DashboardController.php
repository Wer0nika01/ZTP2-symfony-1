<?php

/**
 * Dashboard controller.
 */

namespace App\Controller;

use App\Repository\EventRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class Dashboard controller.
 */
class DashboardController extends AbstractController
{
    /**
     * Constructor.
     *
     * @param EventRepository $eventRepository
     */
    public function __construct(private readonly EventRepository $eventRepository)
    {
    }

    /**
     * Dashboard index action.
     *
     * @return Response HTTP Response
     */
    #[Route('/dashboard', name: 'dashboard_index', methods: 'GET')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeEvents = $this->eventRepository->findActiveEvents($user);
        $upcomingEvents = $this->eventRepository->findUpcomingEvents($user);

        return $this->render('dashboard/index.html.twig', [
            'activeEvents' => $activeEvents,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
