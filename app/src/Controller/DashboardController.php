<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(private readonly EventRepository $eventRepository)
    {
    }

    /**
     * Dashboard index action.
     *
     * @return Response HTTP Response
     */
    #[\Symfony\Component\Routing\Attribute\Route('/dashboard', name: 'dashboard_index', methods: 'GET')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeEvents = $this->eventRepository->findActiveEvents($user, 5);
        $upcomingEvents = $this->eventRepository->findUpcomingEvents($user, 5);

        return $this->render('dashboard/index.html.twig', [
            'activeEvents' => $activeEvents,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
