<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(EventRepository $eventRepository): Response
    {
        $user = $this->getUser();

        $upcomingEvents = $eventRepository->findUpcomingEventsForUser($user);

        return $this->render('dashboard/index.html.twig', [
            'events' => $upcomingEvents,
        ]);
    }
}
