<?php

namespace App\Service;

use App\Entity\User;

interface RegistrationServiceInterface
{
    public function register(User $user, string $plainPassword): void;
}
