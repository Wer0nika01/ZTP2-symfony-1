<?php

/**
 * Tag service interface.
 */

namespace App\Service;

use App\Entity\Tag;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 *  Interface TagServiceInterface.
 */
interface TagServiceInterface
{
    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface;

    /**
     * Save tag
     *
     * @param Tag $tag
     *
     * @return void
     */
    public function save(Tag $tag): void;

    /**
     * Delete tag
     *
     * @param Tag $tag
     *
     * @return void
     */
    public function delete(Tag $tag): void;

    /**
     * Find by Name.
     *
     * @param string $name Tag name
     *
     * @return Tag|null Tag entity
     */
    public function findOneByName(string $name): ?Tag;

    /**
     * Find by Id
     *
     * @param string $slug
     *
     * @return Tag|null
     */
    public function findOneById(int $id): ?Tag;
}
