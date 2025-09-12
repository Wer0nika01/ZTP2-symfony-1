<?php

/**
 * Registration Service Interface.
 */

namespace App\Service;

use App\Entity\User;

/**
 * Interface of Registration Service.
 */
interface RegistrationServiceInterface
{
    /**
     * Register.
     *
     * @param User   $user          User entity
     * @param string $plainPassword Plain password
     */
    public function register(User $user, string $plainPassword): void;
}
