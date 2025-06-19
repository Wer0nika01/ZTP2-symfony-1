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
     * @param User   $user
     * @param string $plainPassword
     */
    public function register(User $user, string $plainPassword): void;
}
