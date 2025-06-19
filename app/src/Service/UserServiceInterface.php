<?php

/**
 * User Service Interface.
 */
namespace App\Service;

use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use RuntimeException;

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
     * @param User $user
     *
     * @throws RuntimeException If attempting to delete the last administrator
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function delete(User $user): void;

    /**
     * Saves a user entity.
     *
     * @param User $user
     *
     * @throws RuntimeException If attempting to remove ROLE_ADMIN from the last administrator
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function save(User $user): void;

    /**
     * Checks if an email is unique for a user (excluding the user themselves during edit).
     *
     * @param string   $email
     * @param int|null $excludeUserId
     *
     * @return bool True if email is unique, false otherwise
     */
    public function isEmailUnique(string $email, ?int $excludeUserId = null): bool;

    /**
     * Get paginated list of users.
     *
     * @param int $page
     *
     * @return PaginationInterface Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface;

    /**
     * Find by ID.
     *
     * @param int $id
     *
     * @return User|null
     */
    public function findOneById(int $id): ?User;

    /**
     * Toggle to block users.
     *
     * @param User $user
     */
    public function toggleBlock(User $user): void;
}
