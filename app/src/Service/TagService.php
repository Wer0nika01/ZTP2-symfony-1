<?php

/**
 * Tag service
 */
namespace App\Service;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Class TagService
 */
class TagService implements TagServiceInterface
{
    /**
     * Items per page.
     *
     * Use constants to define configuration options that rarely change instead
     * of specifying them in app/config/config.yml.
     * See https://symfony.com/doc/current/best_practices.html#configuration
     *
     * @constant int
     */
    private const PAGINATOR_ITEMS_PER_PAGE = 10;
    #[ORM\Column(type: 'string')]
    private readonly SluggerInterface $slugger;

    /**
     * Constructor.
     *
     * @param TagRepository $tagRepository Tag repository
     * @param PaginatorInterface $paginator Paginator
     */
    public function __construct(private readonly TagRepository $tagRepository, private readonly PaginatorInterface $paginator, SluggerInterface $slugger,) {
        $this->slugger = $slugger;
    }

    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->tagRepository->queryAll(),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => ['tag.id', 'tag.createdAt', 'tag.updatedAt', 'tag.title'],
                'defaultSortFieldName' => 'tag.id',
                'defaultSortDirection' => 'asc',
            ]
        );
    }

    /**
     * Save entity
     *
     * @param Tag $tag
     * @return void
     */
    public function save(Tag $tag): void
    {
        $this->tagRepository->save($tag);
    }

    /**
     * Delete entity
     *
     * @param Tag $tag
     * @return void
     */
    public function delete(Tag $tag): void
    {
        $this->tagRepository->delete($tag);
    }
}
