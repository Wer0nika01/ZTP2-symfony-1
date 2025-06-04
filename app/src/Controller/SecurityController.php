<?php

/**
 * Security controller.
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * Class SecurityController.
 */
class SecurityController extends AbstractController
{
    /**
     * Login action.
     *
     * @param AuthenticationUtils $authenticationUtils Authentication utilities
     * @param $passwordHasher
     * @param $request
     * @param $entityManager
     * @return Response HTTP response
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, UserPasswordHasherInterface $passwordHasher, Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('task_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('message.edited_successfully'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * Logout action.
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
