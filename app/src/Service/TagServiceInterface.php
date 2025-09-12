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
     */
    public function getPaginatedList(int $page): PaginationInterface;

    /**
     * Save tags.
     *
     * @param Tag $tag Tag entity
     */
    public function save(Tag $tag): void;

    /**
     * Delete tags.
     *
     * @param Tag $tag Tag entity
     */
    public function delete(Tag $tag): void;

    /**
     * Find by Name.
     *
     * @param string $name Name entity
     */
    public function findOneByName(string $name): ?Tag;

    /**
     * Find by ID.
     *
     * @param int $id Tag entity
     */
    public function findOneById(int $id): ?Tag;
}
