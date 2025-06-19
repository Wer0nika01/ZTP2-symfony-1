<?php

/**
 * User Checker.
 */

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class User checker.
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * Constructor
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Check Preauth.
     *
     * @param UserInterface $user
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getIsBlocked()) {
            throw new CustomUserMessageAccountStatusException($this->translator->trans('security.account_blocked_message'));
        }
    }

    /**
     * Check PostAuth.
     *
     * @param UserInterface $user
     */
    public function checkPostAuth(UserInterface $user): void
    {
    }
}
