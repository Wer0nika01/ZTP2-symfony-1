<?php

namespace App\Service;

use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

interface UserServiceInterface
{
    public function getAllUsers(): array;
    public function updateUser(User $user): void;
    public function delete(User $user): void;
    public function save(User $user): void;
    public function isEmailUnique(string $email, ?int $excludeUserId = null): bool;
    public function getPaginatedList(int $page): PaginationInterface;

}
