<?php

/**
 * User Service Interface.
 */

namespace App\Service;

use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface for User Service.
 */
interface UserServiceInterface
{
    /**
     * Retrieves all user entities.
     *
     * @return User[] Array of all users
     */
    public function getAllUsers(): array;

    /**
     * Deletes a user entity.
     *
     * @param User $user User entity
     */
    public function delete(User $user): void;

    /**
     * Saves a user entity.
     *
     * @param User $user User entity
     */
    public function save(User $user): void;

    /**
     * Checks if an email is unique for a user (excluding the user themselves during edit).
     *
     * @param string   $email         String email
     * @param int|null $excludeUserId Int excluded user id
     *
     * @return bool True if email is unique, false otherwise
     */
    public function isEmailUnique(string $email, ?int $excludeUserId = null): bool;

    /**
     * Get paginated list of users.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface Pagination
     */
    public function getPaginatedList(int $page): PaginationInterface;

    /**
     * Find by ID.
     *
     * @param int $id Int id
     *
     * @return User|null User entity
     */
    public function findOneById(int $id): ?User;

    /**
     * Toggle to block users.
     *
     * @param User $user User entity
     */
    public function toggleBlock(User $user): void;
}
