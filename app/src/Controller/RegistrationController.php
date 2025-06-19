<?php

/** * Registration controller. */

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\RegistrationType;
use App\Service\EventServiceInterface;
use App\Service\RegistrationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/** * Class Registration controller. */
class RegistrationController extends AbstractController
{
    /** * Constructor.
     *
     * @param TranslatorInterface   $translator */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /** * Register.
     *
     * @param Request                      $request
     * @param RegistrationServiceInterface $registrationService
     * @param AuthenticationUtils          $authenticationUtils
     *
     * @return Response */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, RegistrationServiceInterface $registrationService, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('dashboard_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registrationService->register($user, $form->get('password')->getData());

            $this->addFlash('success', $this -> translator->trans('message.registration_successful'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'last_username' => $lastUsername,
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }
}
