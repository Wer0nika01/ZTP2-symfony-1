<?php

namespace App\Service;

use App\Entity\User;

interface UserServiceInterface
{
    public function getAllUsers(): array;
    public function updateUser(User $user): void;
    public function deleteUser(User $user): void;
}
