<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\RegistrationType;
use App\Service\RegistrationService;
use App\Service\RegistrationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Form\FormError;

class RegistrationController extends AbstractController
{
    #[\Symfony\Component\Routing\Attribute\Route('/register', name: 'app_register')]
    public function register(
        Request                      $request,
        RegistrationServiceInterface $registrationService,
        AuthenticationUtils          $authenticationUtils,
        TranslatorInterface          $translator,
    ): Response {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('dashboard_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $registrationService->register($user, $form->get('password')->getData());

                $this->addFlash('success', 'message.registration_successful');

                return $this->redirectToRoute('app_login');
            } catch (UniqueConstraintViolationException) {
                $form->get('email')->addError(new FormError($translator->trans('message.email_already_used')));
            }
        }

        return $this->render('security/register.html.twig', [
            'last_username' => $lastUsername,
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }
}
