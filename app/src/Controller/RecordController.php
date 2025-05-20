<?php
/**
 * Record controller.
 */

namespace App\Controller;

use App\Repository\RecordRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class RecordController.
 */
#[Route('/record')]
class RecordController extends AbstractController
{
    /**
     * Index action.
     *
     * @param RecordRepository $repository Record repository
     *
     * @return Response HTTP response
     */
    #[Route(
        name: 'record_index',
        methods: 'GET'
    )]
    public function index(RecordRepository $repository): Response
    {
        $records = $repository->findAll();

        return $this->render(
            'record/index.html.twig',
            ['records' => $records]
        );
    }
    /**
     * View action.
     *
     * @param RecordRepository $repository Record repository
     * @param int              $id         Record identifier
     *
     * @return Response HTTP response
     */
    #[Route(
        '/{id}',
        name: 'record_view',
        requirements: ['id' => '[1-9]\d*'],
        methods: 'GET'
    )]
    public function view(RecordRepository $repository, int $id): Response
    {
        $record = $repository->findOneById($id);

        if (null === $record) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'record/view.html.twig',
            ['record' => $record,
            'id' => $id]
        );
    }
}
