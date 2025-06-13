<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Type\UserType;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class UserController extends AbstractController
{
    public $translator;
    public function __construct(private readonly UserServiceInterface $userService, TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    #[\Symfony\Component\Routing\Attribute\Route('/admin/user/', name: 'admin_user_index', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $this->userService->getPaginatedList($page);

        return $this->render('admin/user/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
    #[\Symfony\Component\Routing\Attribute\Route('/admin/user/{id}', name: 'admin_user_view')]
    #[IsGranted('ROLE_ADMIN')]
    public function show(User $user): Response
    {
        return $this->render('admin/user/view.html.twig', [
            'user' => $user,
        ]);
    }
    #[\Symfony\Component\Routing\Attribute\Route(
        '/admin/user/{id}/edit',
        name: 'admin_user_edit',
        requirements: ['id' => '[1-9]\d*'],
        methods: ['GET', 'POST']
    )]

    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, User $user, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            if (!$this->userService->isEmailUnique($email, $user->getId())) {
                $form->get('email')->addError(new FormError($translator->trans('error.email_exists')));
            } else {
                $this->userService->updateUser($user);
                $this->addFlash('success', $this->translator->trans('flash.user_saved'));

                return $this->redirectToRoute('admin_user_index');
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
    #[\Symfony\Component\Routing\Attribute\Route(
        '/admin/user/{id}/delete',
        name: 'admin_user_delete',
        requirements: ['id' => '[1-9]\d*'],
        methods: ['GET', 'DELETE']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, User $user): Response
    {
        $form = $this->createForm(FormType::class, $user, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('admin_user_delete', ['id' => $user->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->delete($user);

            $this->addFlash(
                'success',
                $this->translator->trans('flash.user_deleted')
            );

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/delete.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
