<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;



class UserChecker implements UserCheckerInterface
{
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }


    /**
     * @param UserInterface $user
     * @return void
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
     * @param UserInterface $user
     * @return void
     */
    public function checkPostAuth(UserInterface $user): void
    {
    }
}
