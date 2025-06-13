<?php

/**
 * Profile Controller
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Type\ChangePasswordType;
use App\Form\Type\ProfileEditType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Entity\User;

/**
 * Class ProfileController
 */
class ProfileController extends AbstractController
{
    /**
     * Profile dashboard.
     *
     * @return Response
     */
    #[\Symfony\Component\Routing\Attribute\Route('/profile', name: 'app_profile', methods: 'GET')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Edit user profile data.
     *
     * @param Request                $request       HTTP Request
     * @param EntityManagerInterface $entityManager Entity Manager
     *
     * @return Response
     */
    #[\Symfony\Component\Routing\Attribute\Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'message.profile_updated_successfully');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * Change password.
     *
     * @param Request                     $request        HTTP Request
     * @param UserPasswordHasherInterface $passwordHasher Password Hasher
     * @param EntityManagerInterface      $entityManager  Entity Manager
     *
     * @return Response
     */
    #[\Symfony\Component\Routing\Attribute\Route('/profile/change-password', name: 'app_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->flush();
            $this->addFlash('success', 'message.password_changed_successfully');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
