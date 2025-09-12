<?php

/**
 * User Controller.
 */

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Type\UserType;
use App\Service\UserServiceInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundle;

/**
 * Class User controller.
 */
class UserController extends AbstractController
{
    /**
     * Construct.
     */
    public function __construct(private readonly UserServiceInterface $userService, TranslatorInterface $translator, private readonly SecurityBundle $security)
    {
    }

    /**
     * Index.
     */
    #[\Symfony\Component\Routing\Attribute\Route('/admin/user/', name: 'admin_user_index', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $this->userService->getPaginatedList($page);

        return $this->render('admin/user/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * Show.
     */
    #[\Symfony\Component\Routing\Attribute\Route('/admin/user/{id}', name: 'admin_user_view')]
    #[IsGranted('ROLE_ADMIN')]
    public function show(User $user): Response
    {
        return $this->render('admin/user/view.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Edit.
     */
    #[\Symfony\Component\Routing\Attribute\Route('/admin/user/{id}/edit', name: 'admin_user_edit', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'POST'])]
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
                $this->userService->save($user);
                $this->addFlash('success', $translator->trans('flash.user_saved'));

                return $this->redirectToRoute('admin_user_index');
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * Delete.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[\Symfony\Component\Routing\Attribute\Route('/admin/user/{id}/delete', name: 'admin_user_delete', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, User $user): Response
    {
        $form = $this->createForm(FormType::class, $user, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('admin_user_delete', ['id' => $user->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $currentUser = $this->security->getUser();
                $isSelfDeletion = ($currentUser instanceof User && $currentUser->getId() === $user->getId());

                $this->userService->delete($user);

                $this->addFlash(
                    'success',
                    'flash.user_deleted'
                );

                if ($isSelfDeletion) {
                    $this->container->get('security.token_storage')->setToken(null);
                    $request->getSession()->invalidate();

                    $this->userService->delete($user);

                    return $this->redirectToRoute('app_logout');
                }

                return $this->redirectToRoute('admin_user_index');
            } catch (NoResultException|NonUniqueResultException $e) {
                $this->addFlash('danger', 'message.error_counting_admins');
                error_log($e->getMessage());

                return $this->redirectToRoute('admin_user_index');
            } catch (\RuntimeException $e) {
                $this->addFlash(
                    'danger',
                    $e->getMessage()
                );
                error_log($e->getMessage());

                return $this->redirectToRoute('admin_user_index');
            }
        }

        return $this->render('admin/user/delete.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * Toggle block.
     */
    #[\Symfony\Component\Routing\Attribute\Route('/admin/user/{id}/toggle-block', name: 'admin_user_toggle_block', requirements: ['id' => '[1-9]\d*'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleBlock(User $user, TranslatorInterface $translator): Response
    {
        $this->userService->toggleBlock($user);

        $this->addFlash(
            'success',
            $user->getIsBlocked()
                ? $translator->trans('flash.user_blocked')
                : $translator->trans('flash.user_unblocked')
        );

        return $this->redirectToRoute('admin_user_index', ['id' => $user->getId()]);
    }
}
