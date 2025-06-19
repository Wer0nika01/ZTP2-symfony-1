<?php

/** * Contact controller. */

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Form\Type\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ContactService;
use App\Dto\ContactListFiltersDto;
use App\Form\Type\ContactListFilterType;
use Symfony\Contracts\Translation\TranslatorInterface;

/** * Class ContactController. */
#[IsGranted('ROLE_USER')]
class ContactController extends AbstractController
{
    /** * Constructor.
     *
     * @param ContactService $contactService
     * @param TranslatorInterface $translator
     */
    public function __construct(private readonly ContactService $contactService, private readonly TranslatorInterface $translator)
    {
    }

    /** * Index action.
     *
     * @param Request $request HTTP Request
     *
     * @return Response HTTP Response */
    #[Route('/contact', name: 'contact_index', methods: 'GET')]
    public function index(Request $request): Response
    {
        $filtersDto = new ContactListFiltersDto();

        $form = $this->createForm(ContactListFilterType::class, $filtersDto, ['method' => 'GET']);
        $form->handleRequest($request);

        /** @var User $user */
        $user = $this->getUser();

        $pagination = $this->contactService->getPaginatedList(
            $request->query->getInt('page', 1),
            $user,
            $filtersDto
        );

        return $this->render('contact/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form->createView(),
        ]);
    }

    /** * Show action.
     *
     * @param Contact $contact Contact entity
     *
     * @return Response HTTP Response */
    #[Route('/contact/{id}', name: 'contact_view', requirements: ['id' => '[1-9]\d*'], methods: 'GET')]
    #[IsGranted('CONTACT_VIEW', subject: 'contact')]
    public function show(Contact $contact): Response
    {
        return $this->render('contact/view.html.twig', [
            'contact' => $contact,
        ]);
    }

    /** * Create action.
     *
     * @param Request $request HTTP Request
     *
     * @return Response HTTP Response
     */
    #[Route('/contact/create', name: 'contact_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contact->setAuthor($this->getUser());

            $this->contactService->save($contact);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('contact_index');
        }

        return $this->render('contact/create.html.twig', [
            'contact' => $contact,
            'form' => $form->createView(),
        ]);
    }

    /** * Edit action.
     *
     * @param Request $request HTTP Request
     * @param Contact $contact Contact entity
     *
     * @return Response HTTP Response */
    #[Route('/contact/{id}/edit', name: 'contact_edit', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'POST'])]
    #[IsGranted('CONTACT_EDIT', subject: 'contact')]
    public function edit(Request $request, Contact $contact): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contactService->save($contact);

            $this->addFlash(
                'success',
                $this->translator->trans('message.edited_successfully')
            );

            return $this->redirectToRoute('contact_index');
        }

        return $this->render('contact/edit.html.twig', [
            'contact' => $contact,
            'form' => $form->createView(),
        ]);
    }

    /** * Delete action.
     *
     * @param Request $request HTTP Request
     * @param Contact $contact Contact entity
     *
     * @return Response HTTP Response */
    #[Route('/contact/{id}/delete', name: 'contact_delete', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'POST'])]
    #[IsGranted('CONTACT_DELETE', subject: 'contact')]
    public function delete(Request $request, Contact $contact): Response
    {
        $form = $this->createFormBuilder($contact)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contactService->remove($contact);

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('contact_index');
        }

        return $this->render('contact/delete.html.twig', [
            'contact' => $contact,
            'form' => $form->createView(),
        ]);
    }
}
