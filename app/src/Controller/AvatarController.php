<?php

/**
 * Avatar controller.
 */

namespace App\Controller;

use App\Entity\Avatar;
use App\Entity\User;
use App\Form\Type\AvatarType;
use App\Service\AvatarServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AvatarController.
 */
class AvatarController extends AbstractController
{
    /**
     * Constructor.
     *
     * @param AvatarServiceInterface $avatarService Avatar service
     * @param TranslatorInterface    $translator    Translator
     */
    public function __construct(private readonly AvatarServiceInterface $avatarService, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route(
        '/avatar/create',
        name: 'avatar_create',
        methods: 'GET|POST'
    )]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getAvatar()) {
            return $this->redirectToRoute(
                'avatar_edit',
                ['id' => $user->getAvatar()->getId()]
            );
        }

        $avatar = new Avatar();
        $form = $this->createForm(
            AvatarType::class,
            $avatar,
            ['action' => $this->generateUrl('avatar_create')]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            $this->avatarService->create(
                $file,
                $avatar,
                $user
            );

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('dashboard_index');
        }

        return $this->render(
            'avatar/create.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param Avatar  $avatar  Avatar entity
     *
     * @return Response HTTP response
     */
    #[Route(
        '/avatar/{id}/edit',
        name: 'avatar_edit',
        requirements: ['id' => '[1-9]\d*'],
        methods: 'GET|PUT'
    )]
    public function edit(Request $request, Avatar $avatar): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->getAvatar()) {
            return $this->redirectToRoute('avatar_create');
        }

        $form = $this->createForm(
            AvatarType::class,
            $avatar,
            [
                'method' => 'PUT',
                'action' => $this->generateUrl('avatar_edit', ['id' => $avatar->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            $this->avatarService->update(
                $file,
                $avatar,
                $user
            );

            $this->addFlash(
                'success',
                $this->translator->trans('message.edited_successfully')
            );

            return $this->redirectToRoute('dashboard_index');
        }

        return $this->render(
            'avatar/edit.html.twig',
            [
                'form' => $form->createView(),
                'avatar' => $avatar,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP Request
     * @param Avatar  $avatar  Avatar entity
     *
     * @return Response HTTP response
     */
    #[IsGranted('DELETE', subject: 'avatar')]
    #[Route('/avatar/{id}/delete', name: 'avatar_delete', requirements: ['id' => '\d+'], methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Avatar $avatar): Response
    {
        $form = $this->createForm(FormType::class, null, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('avatar_delete', ['id' => $avatar->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->avatarService->delete($avatar);

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('avatar/delete.html.twig', [
            'form' => $form->createView(),
            'avatar' => $avatar,
        ]);
    }
}
